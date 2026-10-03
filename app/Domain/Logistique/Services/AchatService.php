<?php

namespace App\Domain\Logistique\Services;

use App\Domain\Logistique\DTO\AchatData;
use App\Domain\Logistique\Models\Achat;
use App\Domain\Logistique\Models\DetailAchat;
use App\Domain\Logistique\Repositories\AchatRepository;
use App\Domain\Logistique\Types\AchatStatutLivraison;
use App\Domain\Logistique\Types\AchatStatutPaiement;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AchatService
{
    public function __construct(
        private AchatRepository $achats,
    ) {}

    /**
     * Crée un achat (bon de commande) avec ses lignes.
     * Statut initial : en attente de livraison + non payé.
     */
    public function creer(AchatData $data): Achat
    {
        if (empty($data->lignes)) {
            throw new RuntimeException("Un achat doit comporter au moins une ligne.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();
        if (! $anneeId) {
            throw new RuntimeException("Aucune année scolaire active.");
        }

        return DB::transaction(function () use ($data, $anneeId) {
            // 1) En-tête
            $achat = $this->achats->create([
                'reference'        => $this->achats->genererReference(),
                'date_achat'       => $data->dateAchat,
                'nom_acheteur'     => $data->nomAcheteur,
                'bon_commande'     => $data->bonCommande,
                'commentaire'      => $data->commentaire,
                'fournisseur_id'   => $data->fournisseurId,
                'type_achat'       => $data->typeAchat,
                'statut_paiement'  => AchatStatutPaiement::NON_PAYE->value,
                'statut_livraison' => AchatStatutLivraison::EN_ATTENTE->value,
                'montant_total'    => $data->montantTotal(),
                'annee_id'         => $anneeId,
            ]);

            // 2) Lignes
            foreach ($data->lignes as $ligne) {
                DetailAchat::create([
                    'achat_id'      => $achat->id,
                    'produit_id'    => $ligne->produitId,
                    'quantite'      => $ligne->quantite,
                    'prix_unitaire' => $ligne->prixUnitaire,
                    'montant_achat' => $ligne->montant(),
                    'annee_id'      => $anneeId,
                ]);
            }

            return $achat->fresh('details');
        });
    }

    /**
     * Modifie un achat EN ATTENTE de livraison uniquement.
     * Remplace complètement les lignes (simple et sûr).
     */
    public function modifier(int $id, AchatData $data): Achat
    {
        return DB::transaction(function () use ($id, $data) {
            $achat = $this->achats->findOrFail($id);

            if (! ($achat->statut_livraison === AchatStatutLivraison::EN_ATTENTE)) {
                throw new RuntimeException(
                    "Seul un achat en attente de livraison peut être modifié."
                );
            }

            $this->achats->update($achat->id, [
                'date_achat'     => $data->dateAchat,
                'nom_acheteur'   => $data->nomAcheteur,
                'bon_commande'   => $data->bonCommande,
                'commentaire'    => $data->commentaire,
                'fournisseur_id' => $data->fournisseurId,
                'type_achat'     => $data->typeAchat,
                'montant_total'  => $data->montantTotal(),
            ]);

            // Remplace les lignes
            $achat->details()->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);

            foreach ($data->lignes as $ligne) {
                DetailAchat::create([
                    'achat_id'      => $achat->id,
                    'produit_id'    => $ligne->produitId,
                    'quantite'      => $ligne->quantite,
                    'prix_unitaire' => $ligne->prixUnitaire,
                    'montant_achat' => $ligne->montant(),
                    'annee_id'      => $achat->annee_id,
                ]);
            }

            return $achat->fresh('details');
        });
    }

    /**
     * Réceptionne un achat : passe le statut en LIVRÉ et alimente le stock.
     * Si le module Stock n'est pas encore livré, l'alimentation est ignorée.
     *
     * $magasinId : magasin qui reçoit la marchandise. À défaut, l'entrepôt
     * principal (premier entrepôt actif).
     */
    public function receptionner(int $id, int $userId, ?int $magasinId = null): Achat
    {
        return DB::transaction(function () use ($id, $userId, $magasinId) {
            $achat = $this->achats->findAvecDetails($id);

            if ($achat->isLivre()) {
                throw new RuntimeException("Cet achat est déjà réceptionné.");
            }
            if ($achat->isAnnule()) {
                throw new RuntimeException("Impossible de réceptionner un achat annulé.");
            }

            // 1) Marque comme livré
            $this->achats->update($achat->id, [
                'statut_livraison' => AchatStatutLivraison::LIVRE->value,
            ]);

            // 2) Alimente le stock (si le module 3.3 est disponible)
            $this->alimenterStock($achat, $userId, $magasinId);

            return $achat->refresh();
        });
    }

    /** Annule un achat (avant ou après livraison — avec précaution). */
    public function annuler(int $id, string $motif): Achat
    {
        return DB::transaction(function () use ($id, $motif) {
            $achat = $this->achats->findOrFail($id);

            if ($achat->isAnnule()) {
                throw new RuntimeException("Cet achat est déjà annulé.");
            }

            // Un achat livré ne peut être annulé s'il a déjà entré en stock
            // (on empêche la désynchronisation). On tolère si le module
            // Stock n'existe pas encore.
            if ($achat->isLivre() && $this->moduleStockDisponible()) {
                throw new RuntimeException(
                    "Un achat déjà livré ne peut pas être annulé. Passez une écriture de correction."
                );
            }

            $this->achats->update($achat->id, [
                'statut_livraison' => AchatStatutLivraison::ANNULE->value,
                'commentaire'      => trim(($achat->commentaire ?? '') . " | Annulé : {$motif}"),
            ]);

            return $achat->refresh();
        });
    }

    /** Enregistre un paiement (total ou partiel) sur un achat. */
    public function enregistrerPaiement(int $id, string $statut): Achat
    {
        $achat = $this->achats->findOrFail($id);

        $this->achats->update($achat->id, [
            'statut_paiement' => $statut,
        ]);

        return $achat->refresh();
    }

    /* ================================================================
       Intégration avec le module Stock (tolérante)
       ================================================================ */

    private function alimenterStock(Achat $achat, int $userId, ?int $magasinId = null): void
    {
        if (! $this->moduleStockDisponible()) {
            Log::info('AchatService : module Stock non disponible, alimentation ignorée', [
                'achat_id' => $achat->id,
            ]);
            return;
        }

        $magasinId ??= $this->magasinDeReception();

        // Une erreur ici annule la réception (transaction) : un achat ne doit
        // jamais être marqué livré sans que son stock soit entré.
        $stockService = app(\App\Domain\Logistique\Services\StockService::class);

        foreach ($achat->details as $ligne) {
            if (! $ligne->produit_id) continue;

            $stockService->entrerEnStock(
                produitId:     (int) $ligne->produit_id,
                magasinId:     $magasinId,
                quantite:      (float) $ligne->quantite,
                prixUnitaire:  (float) $ligne->prix_unitaire,
                sourceType:    $achat::class,
                sourceId:      $achat->id,
                libelle:       "Réception achat {$achat->reference}",
                utilisateurId: $userId,
            );
        }
    }

    /** Entrepôt principal : premier entrepôt actif, sinon premier magasin actif. */
    private function magasinDeReception(): int
    {
        $magasinId = DB::table('magasins')
            ->where('etat', 1)
            ->orderByRaw('CASE WHEN type = ? THEN 0 ELSE 1 END', [\App\Domain\Logistique\Types\TypeMagasin::ENTREPOT->value])
            ->orderBy('id')
            ->value('id');

        if (! $magasinId) {
            throw new RuntimeException(
                "Aucun magasin actif : créez un entrepôt avant de réceptionner un achat."
            );
        }

        return (int) $magasinId;
    }

    private function moduleStockDisponible(): bool
    {
        return \Schema::hasTable('stocks') || \Schema::hasTable('stock_mouvements');
    }
}