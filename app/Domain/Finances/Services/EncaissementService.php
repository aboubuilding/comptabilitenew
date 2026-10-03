<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\Detail;
use App\Domain\Finances\Models\Mouvement;
use App\Domain\Finances\Models\Paiement;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\PaiementRepository;
use App\Domain\Finances\Types\MouvementType;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Logistique\Models\Stock;
use App\Domain\Logistique\Services\StockService;
use App\Domain\Logistique\Types\TypeMagasin;
use App\Domain\Logistique\Types\TypeMouvementStock;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EncaissementService
{
    public function __construct(
        private PaiementRepository $paiements,
        private CaisseRepository   $caisses,
        private StockService       $stocks,
    ) {}

    /** Origine des mouvements de stock nés d'un encaissement (colonne stocks.source_type). */
    private const SOURCE_STOCK = 'detail_paiement';

    /**
     * Encaisse un détail : constate l'entrée effective en caisse, crée le
     * mouvement associé, et passe le paiement parent à ENCAISSE si tous ses
     * détails le sont.
     */
    public function encaisser(int $detailId, int $caisseId, int $caissierId): Detail
    {
        return DB::transaction(function () use ($detailId, $caisseId, $caissierId) {
            $detail = $this->paiements->findDetailAvecRelations($detailId);
            $caisse = $this->caisses->findOrFail($caisseId);

            // 1) Vérifications
            if ($detail->statut_paiement === PaiementStatut::ENCAISSE) {
                throw new RuntimeException("Ce détail est déjà encaissé.");
            }
            if ($detail->statut_paiement === PaiementStatut::ANNULE) {
                throw new RuntimeException("Ce détail a été annulé — encaissement impossible.");
            }
            if (! $caisse->isOuverte()) {
                throw new RuntimeException(
                    "La caisse « {$caisse->libelle} » est clôturée : impossible d'y encaisser."
                );
            }
            // Un caissier n'encaisse que dans SA caisse (responsable_id)
            if ((int) $caisse->responsable_id !== $caissierId) {
                throw new RuntimeException(
                    "Vous n'êtes pas le caissier responsable de cette caisse."
                );
            }

            // 2) Mise à jour du détail
            $detail->update([
                'statut_paiement'   => PaiementStatut::ENCAISSE->value,
                'caissier_id'       => $caissierId,
                'date_encaissement' => now()->toDateString(),
                'caisse_id'         => $caisse->id,
            ]);

            // 3) Mouvement de caisse (encaissement)
            Mouvement::create([
                'libelle'          => "Encaissement — {$detail->libelle}",
                'beneficiaire'     => $detail->paiement?->payeur,
                'motif'            => "Réf. paiement : " . ($detail->paiement?->reference ?? 'N/A'),
                'date_mouvement'   => now()->toDateString(),
                'montant'          => (float) $detail->montant,
                'type_mouvement'   => MouvementType::ENCAISSEMENT->value,
                'caisse_id'        => $caisse->id,
                'utilisateur_id'   => $caissierId,
                'paiement_id'      => $detail->paiement_id,
                'annee_id'         => $detail->annee_id,
                'statut_mouvement' => 1,
                'etat'             => 1,
            ]);

            // 4) Produit vendu par le panier : le stock sort au moment où la vente
            //    est effective, par le même service que le point de vente.
            if ($detail->produit_id) {
                $this->sortirProduitDuStock($detail, $caissierId);
            }

            // 5) Si tous les détails du paiement sont encaissés → paiement ENCAISSE
            $this->majStatutPaiementParent($detail->paiement_id);

            return $detail->refresh();
        });
    }

    /**
     * Sortie de stock d'un produit vendu par le panier de l'écran Paiements
     * (cahier des charges § 6, « Paiement multi-natures »). Le produit sort du
     * magasin qui en a assez, la boutique en priorité.
     */
    private function sortirProduitDuStock(Detail $detail, int $caissierId): void
    {
        $quantite = (float) ($detail->quantite ?: 1);

        $magasinId = DB::table('stock_actuel as s')
            ->join('magasins as m', 'm.id', '=', 's.magasin_id')
            ->where('s.produit_id', $detail->produit_id)
            ->where('m.etat', 1)
            ->where('s.quantite', '>=', $quantite)
            ->orderByRaw('CASE WHEN m.type = ? THEN 0 ELSE 1 END', [TypeMagasin::BOUTIQUE->value])
            ->orderByDesc('s.quantite')
            ->value('s.magasin_id');

        if (! $magasinId) {
            throw new RuntimeException(
                "Stock insuffisant pour « {$detail->libelle} » : aucun magasin ne dispose de {$quantite} unité(s)."
            );
        }

        $reference = $detail->paiement?->reference ?? "détail #{$detail->id}";

        $this->stocks->enregistrer((int) $detail->produit_id, (int) $magasinId, $quantite, TypeMouvementStock::SORTIE, [
            'libelle'        => "Vente — paiement {$reference}",
            'motif'          => 'Produit vendu par le panier de paiement',
            'utilisateur_id' => $caissierId,
            'source_type'    => self::SOURCE_STOCK,
            'source_id'      => $detail->id,
        ]);
    }

    /** Annulation d'un encaissement : le produit revient dans le magasin d'où il était sorti. */
    private function remettreProduitEnStock(Detail $detail, int $userId): void
    {
        $sortie = Stock::query()
            ->where('source_type', self::SOURCE_STOCK)
            ->where('source_id', $detail->id)
            ->where('type_mouvement', TypeMouvementStock::SORTIE->value)
            ->latest('id')
            ->first();

        if (! $sortie) {
            return;
        }

        $this->stocks->enregistrer((int) $sortie->produit_id, (int) $sortie->magasin_id, (float) $sortie->quantite, TypeMouvementStock::ENTREE, [
            'prix_unitaire'  => (float) $sortie->prix_unitaire,
            'libelle'        => "Annulation d'encaissement — {$detail->libelle}",
            'motif'          => 'Retour en stock après annulation',
            'utilisateur_id' => $userId,
            'source_type'    => self::SOURCE_STOCK,
            'source_id'      => $detail->id,
        ]);
    }

    /**
     * Encaisse tous les détails en attente d'un même paiement d'un coup.
     * Utile depuis la liste quand un paiement a plusieurs natures.
     */
    public function encaisserPaiement(int $paiementId, int $caisseId, int $caissierId): array
    {
        return DB::transaction(function () use ($paiementId, $caisseId, $caissierId) {
            $paiement = $this->paiements->findAvecDetails($paiementId);

            if ($paiement->statut_paiement === PaiementStatut::ANNULE) {
                throw new RuntimeException("Ce paiement a été annulé.");
            }

            $detailsEnAttente = $paiement->details
                ->filter(fn (Detail $d) => $d->statut_paiement === PaiementStatut::EN_ATTENTE);

            if ($detailsEnAttente->isEmpty()) {
                throw new RuntimeException("Aucun détail en attente sur ce paiement.");
            }

            $encaissees = [];
            foreach ($detailsEnAttente as $detail) {
                $encaissees[] = $this->encaisser($detail->id, $caisseId, $caissierId);
            }

            return $encaissees;
        });
    }

    /**
     * Encaissement d'un paiement par le caissier connecté, dans SA caisse
     * ouverte : il saisit le montant réellement remis.
     *  - inférieur au montant à encaisser : refusé (le paiement doit être corrigé) ;
     *  - supérieur : la différence est la monnaie rendue (espèces seulement —
     *    un chèque s'encaisse pour son montant exact).
     *
     * @return array{details: Detail[], montant: float, montant_recu: float, monnaie: float}
     */
    public function encaisserParCaissier(int $paiementId, float $montantRecu, int $caissierId): array
    {
        $caisse = $this->caisses->findOuverteParCaissier($caissierId);
        if (! $caisse) {
            throw new RuntimeException(
                "Vous n'avez pas de caisse ouverte : ouvrez votre caisse (menu Caisses) avant d'encaisser."
            );
        }

        return DB::transaction(function () use ($paiementId, $montantRecu, $caissierId, $caisse) {
            $paiement = $this->paiements->findAvecDetails($paiementId);

            if ($paiement->statut_paiement === PaiementStatut::ANNULE) {
                throw new RuntimeException("Ce paiement a été annulé.");
            }

            $aEncaisser = round((float) $paiement->details
                ->filter(fn (Detail $d) => (int) $d->etat === 1 && $d->statut_paiement === PaiementStatut::EN_ATTENTE)
                ->sum('montant'), 2);

            if ($aEncaisser <= 0) {
                throw new RuntimeException("Ce paiement n'a plus rien à encaisser.");
            }

            $montantRecu = round($montantRecu, 2);
            $fmt = fn (float $v) => number_format($v, 0, ',', ' ');

            if ($montantRecu < $aEncaisser) {
                throw new RuntimeException(
                    "Montant insuffisant : {$fmt($montantRecu)} reçu pour {$fmt($aEncaisser)} à encaisser. " .
                    "Faites corriger le paiement par le comptable."
                );
            }
            if ($montantRecu > $aEncaisser && $paiement->mode_paiement === \App\Domain\Finances\Types\ModePaiement::CHEQUE) {
                throw new RuntimeException("Un chèque s'encaisse pour son montant exact ({$fmt($aEncaisser)}).");
            }

            $details = $this->encaisserPaiement($paiement->id, $caisse->id, $caissierId);
            $paiement->update(['montant_recu' => $montantRecu]);

            return [
                'details'      => $details,
                'montant'      => $aEncaisser,
                'montant_recu' => $montantRecu,
                'monnaie'      => round($montantRecu - $aEncaisser, 2),
            ];
        });
    }

    /**
     * Annule l'encaissement d'un paiement fait dans la caisse (ouverte) du
     * caissier : toutes ses lignes encaissées repassent en attente.
     *
     * @return Detail[]
     */
    public function annulerEncaissementPaiement(int $paiementId, string $motif, int $userId): array
    {
        return DB::transaction(function () use ($paiementId, $motif, $userId) {
            $paiement = $this->paiements->findAvecDetails($paiementId);

            $encaisses = $paiement->details
                ->filter(fn (Detail $d) => (int) $d->etat === 1 && $d->statut_paiement === PaiementStatut::ENCAISSE);

            if ($encaisses->isEmpty()) {
                throw new RuntimeException("Ce paiement n'a aucune ligne encaissée.");
            }

            $annules = [];
            foreach ($encaisses as $detail) {
                $annules[] = $this->annuler($detail->id, $motif, $userId);
            }

            $paiement->update(['montant_recu' => null]);

            return $annules;
        });
    }

    /**
     * Annule un encaissement : uniquement si la caisse est encore ouverte.
     * On ne supprime pas le mouvement : on le désactive (etat = 0) pour
     * préserver la traçabilité.
     */
    public function annuler(int $detailId, string $motif, int $userId): Detail
    {
        return DB::transaction(function () use ($detailId, $motif, $userId) {
            $detail = $this->paiements->findDetailAvecRelations($detailId);

            if ($detail->statut_paiement !== PaiementStatut::ENCAISSE) {
                throw new RuntimeException("Seul un détail encaissé peut être annulé.");
            }

            $caisse = $detail->caisse_id ? $this->caisses->find($detail->caisse_id) : null;
            if ($caisse && ! $caisse->isOuverte()) {
                throw new RuntimeException(
                    "Impossible d'annuler : la caisse « {$caisse->libelle} » est clôturée. " .
                    "Le journal est figé."
                );
            }

            // La caisse est relevée avant d'être effacée du détail : elle sert à retrouver le mouvement
            $caisseId = $detail->caisse_id;

            // 1) Repasse le détail en attente
            $detail->update([
                'statut_paiement'   => PaiementStatut::EN_ATTENTE->value,
                'caissier_id'       => null,
                'date_encaissement' => null,
                'caisse_id'         => null,
            ]);

            // 2) Désactive le mouvement de CE détail uniquement : un même paiement
            //    peut avoir plusieurs lignes encaissées dans la même caisse.
            $mouvement = Mouvement::query()
                ->where('paiement_id', $detail->paiement_id)
                ->where('caisse_id', $caisseId)
                ->where('type_mouvement', MouvementType::ENCAISSEMENT->value)
                ->where('libelle', "Encaissement — {$detail->libelle}")
                ->where('montant', (float) $detail->montant)
                ->where('etat', 1)
                ->first();

            $mouvement?->update([
                'etat'  => \App\Repositories\BaseRepository::INACTIF,
                'motif' => trim(($mouvement->motif ?? '') . " | Annulé : {$motif}"),
            ]);

            // 3) Le produit vendu revient en stock
            if ($detail->produit_id) {
                $this->remettreProduitEnStock($detail, $userId);
            }

            // 4) Repasse le paiement parent en attente
            $this->majStatutPaiementParent($detail->paiement_id);

            return $detail->refresh();
        });
    }

    /**
     * Recalcule le statut du paiement parent à partir de ses détails.
     * Règle : si TOUS les détails sont ENCAISSE → paiement ENCAISSE.
     *         sinon → paiement reste EN_ATTENTE.
     *         (ANNULE sur le paiement ne se déduit pas — c'est une décision manuelle.)
     */
    private function majStatutPaiementParent(?int $paiementId): void
    {
        if (! $paiementId) return;

        $paiement = Paiement::with('details')->find($paiementId);
        if (! $paiement) return;

        $details = $paiement->details->where('etat', 1);

        if ($details->isEmpty()) return;

        $tousEncaisses = $details->every(
            fn (Detail $d) => $d->statut_paiement === PaiementStatut::ENCAISSE
        );

        $nouveauStatut = $tousEncaisses
            ? PaiementStatut::ENCAISSE
            : PaiementStatut::EN_ATTENTE;

        if ($paiement->statut_paiement !== $nouveauStatut) {
            $paiement->update(['statut_paiement' => $nouveauStatut->value]);
        }
    }
}