<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\ClotureCaisseData;
use App\Domain\Finances\DTO\MouvementCaisseData;
use App\Domain\Finances\DTO\OuvertureCaisseData;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Models\Mouvement;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\MouvementRepository;
use App\Domain\Finances\Types\CaisseStatut;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CaisseService
{
    public function __construct(
        private CaisseRepository    $caisses,
        private MouvementRepository $mouvements,
    ) {}

    /**
     * Ouvre une nouvelle session (démarre un journal).
     * Règle : un caissier ne peut avoir qu'une caisse ouverte à la fois.
     * annee_id et etat sont injectés par BaseRepository::create() via
     * AnneeScolaireContext (middleware ResolveAnneeScolaire).
     */
    public function ouvrir(OuvertureCaisseData $data): Caisse
    {
        if ($this->caisses->findOuverteParCaissier($data->caissierId)) {
            throw new RuntimeException(
                "Ce caissier a déjà une caisse ouverte. Clôturez-la avant d'en ouvrir une nouvelle."
            );
        }

        return DB::transaction(fn () => $this->caisses->create([
            'libelle'        => $data->libelle,
            'solde_initial'  => $data->soldeInitial,
            'date_ouverture' => now(),
            'statut'         => CaisseStatut::OUVERTE->value,
            'responsable_id' => $data->caissierId,
            'utilisateur_id' => $data->auteurId,
        ]));
    }

    /**
     * Clôture : fige le journal, calcule le solde théorique et l'écart.
     * Un motif est obligatoire dès qu'un écart non nul est constaté.
     */
    public function cloturer(ClotureCaisseData $data): Caisse
    {
        return DB::transaction(function () use ($data) {
            $caisse = $this->caisses->findOrFail($data->caisseId);

            if (! $caisse->isOuverte()) {
                throw new RuntimeException("Cette caisse est déjà clôturée.");
            }

            $mouvements     = $this->mouvements->journalDeCaisse($caisse->id);
            $soldeTheorique = $this->calculerSoldeTheorique((float) $caisse->solde_initial, $mouvements);
            $ecart          = round($data->soldeCompte - $soldeTheorique, 2);

            if (abs($ecart) > 0.01 && blank($data->motifEcart)) {
                throw new RuntimeException(
                    "Un écart de {$ecart} a été constaté : le motif est obligatoire."
                );
            }

            $this->caisses->update($caisse->id, [
                'solde_final'  => $soldeTheorique,
                'solde_compte' => $data->soldeCompte,
                'ecart'        => $ecart,
                'motif_ecart'  => abs($ecart) > 0.01 ? $data->motifEcart : null,
                'date_cloture' => now(),
                'statut'       => CaisseStatut::CLOTUREE->value,
            ]);

            return $caisse->refresh();
        });
    }

    /** Enregistre un mouvement manuel (hors encaissement / dépense). */
    public function enregistrerMouvement(MouvementCaisseData $data): Mouvement
    {
        return DB::transaction(function () use ($data) {
            $caisse = $this->caisses->findOrFail($data->caisseId);

            if (! $caisse->isOuverte()) {
                throw new RuntimeException(
                    "Impossible d'ajouter un mouvement : la caisse est clôturée."
                );
            }

            if ($data->montant <= 0) {
                throw new RuntimeException("Le montant du mouvement doit être strictement positif.");
            }

            // Cohérence avec l'année active : le BaseRepository injecte
            // annee_id depuis le contexte — on refuse d'alimenter une
            // caisse d'une autre année scolaire.
            $anneeActive = app(AnneeScolaireContext::class)->id();
            if ($anneeActive !== null && (int) $caisse->annee_id !== $anneeActive) {
                throw new RuntimeException(
                    "Cette caisse appartient à une autre année scolaire : aucun mouvement ne peut y être ajouté."
                );
            }

            return $this->mouvements->create([
                'libelle'          => $data->libelle,
                'beneficiaire'     => $data->beneficiaire,
                'motif'            => $data->motif,
                'date_mouvement'   => $data->dateMouvement,
                'montant'          => $data->montant,
                'type_mouvement'   => $data->type->value,
                'caisse_id'        => $caisse->id,
                'utilisateur_id'   => $data->utilisateurId,
                'statut_mouvement' => 1,
            ]);
        });
    }

    /* =========================================================
       Helpers de calcul — une seule source de vérité pour la
       formule du solde, partagée entre Controller / Service /
       éventuels Jobs ou Observers.
       ========================================================= */

    public function calculerSoldeTheorique(float $soldeInitial, $mouvements): float
    {
        $entrees = $mouvements
            ->filter(fn (Mouvement $m) => $m->type_mouvement?->isEntree())
            ->sum('montant');

        $sorties = $mouvements
            ->filter(fn (Mouvement $m) => $m->type_mouvement && ! $m->type_mouvement->isEntree())
            ->sum('montant');

        return round($soldeInitial + $entrees - $sorties, 2);
    }

    public function totalEntrees($mouvements): float
    {
        return round(
            $mouvements
                ->filter(fn (Mouvement $m) => $m->type_mouvement?->isEntree())
                ->sum('montant'),
            2
        );
    }

    public function totalSorties($mouvements): float
    {
        return round(
            $mouvements
                ->filter(fn (Mouvement $m) => $m->type_mouvement && ! $m->type_mouvement->isEntree())
                ->sum('montant'),
            2
        );
    }

    /**
     * Prépare les données de la vue show en un seul passage.
     *
     * @return array{
     *     caisse: Caisse,
     *     mouvements: \Illuminate\Support\Collection,
     *     soldeTheorique: float,
     *     totalEntrees: float,
     *     totalSorties: float
     * }
     */
    public function preparerJournal(int $caisseId): array
    {
        $caisse     = $this->caisses->findAvecJournal($caisseId);
        $mouvements = $caisse->mouvements;

        return [
            'caisse'         => $caisse,
            'mouvements'     => $mouvements,
            'soldeTheorique' => $this->calculerSoldeTheorique((float) $caisse->solde_initial, $mouvements),
            'totalEntrees'   => $this->totalEntrees($mouvements),
            'totalSorties'   => $this->totalSorties($mouvements),
        ];
    }
}