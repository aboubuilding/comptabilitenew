<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\ImputationData;
use App\Domain\Finances\Repositories\ImputationAnalytiqueRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VentilationAnalytiqueService
{
    public function __construct(
        private ImputationAnalytiqueRepository $imputations,
    ) {}

    /**
     * Ventile une source (Dépense, Mouvement, Detail, Vente...) sur un
     * ou plusieurs centres.
     *
     * Règles :
     *  - 1 à N imputations
     *  - si mode % : somme des quotes-parts = 100 %
     *  - si mode montant : somme des montants = montantSource (si fourni)
     *  - pas de mélange entre montant et pourcentage sur une même source
     *
     * @param  ImputationData[] $imputations
     */
    public function ventiler(
        Model $source,
        array $imputations,
        string $sens,
        ?float $montantSource = null,
        ?int $userId = null
    ): void {
        if (empty($imputations)) {
            throw new RuntimeException("Au moins une imputation est requise.");
        }

        $modes = array_unique(array_map(
            fn (ImputationData $i) => $i->montant !== null ? 'montant' : 'pourcentage',
            $imputations
        ));

        if (count($modes) > 1) {
            throw new RuntimeException(
                "Mélange interdit : toutes les imputations doivent être soit en montant, soit en pourcentage."
            );
        }

        $mode = $modes[0];

        if ($mode === 'pourcentage') {
            $total = array_sum(array_map(fn (ImputationData $i) => (float) $i->pourcentage, $imputations));
            if (abs($total - 100) > 0.01) {
                throw new RuntimeException(
                    "La somme des quotes-parts doit être égale à 100 % (actuellement {$total} %)."
                );
            }
        } else {
            $total = array_sum(array_map(fn (ImputationData $i) => (float) $i->montant, $imputations));
            if ($montantSource !== null && abs($total - $montantSource) > 0.01) {
                throw new RuntimeException(
                    "La somme des montants imputés ({$total}) doit être égale au montant source ({$montantSource})."
                );
            }
        }

        DB::transaction(function () use ($source, $imputations, $sens, $montantSource, $userId, $mode) {
            // 1) Désactive toutes les imputations existantes pour cette source.
            //    Simple et sûr — plus lisible qu'un diff ligne à ligne.
            $this->imputations->activeQuery()
                ->where('imputable_type', $source::class)
                ->where('imputable_id', $source->getKey())
                ->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);

            // 2) Réinsère les nouvelles imputations.
            foreach ($imputations as $imp) {
                $montantCalcule = null;
                $pourcentageCalcule = null;

                if ($mode === 'montant') {
                    $montantCalcule = round((float) $imp->montant, 2);
                } else {
                    $pourcentageCalcule = round((float) $imp->pourcentage, 2);
                    if ($montantSource !== null) {
                        $montantCalcule = round($montantSource * ($pourcentageCalcule / 100), 2);
                    }
                }

                $this->imputations->create([
                    'imputable_type'       => $source::class,
                    'imputable_id'         => $source->getKey(),
                    'centre_analytique_id' => $imp->centreId,
                    'montant'              => $montantCalcule,
                    'pourcentage'          => $pourcentageCalcule,
                    'sens'                 => $sens,
                    'cree_par'             => $userId,
                ]);
            }
        });
    }

    /** Supprime toutes les imputations d'une source (annulation ventilation). */
    public function depouiller(Model $source): void
    {
        $this->imputations->activeQuery()
            ->where('imputable_type', $source::class)
            ->where('imputable_id', $source->getKey())
            ->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);
    }

    /**
     * Applique les règles de ventilation automatique pour une source
     * nouvellement créée.
     *
     * @return int  Nombre d'imputations créées.
     */
    public function appliquerReglesAuto(Model $source, string $nature, string $sens, float $montantSource, ?int $userId = null): int
    {
        $regles = \App\Domain\Finances\Models\RegleVentilation::query()
            ->where('nature', $nature)
            ->where('sens', $sens)
            ->where('etat', 1)
            ->orderBy('priorite')
            ->get();

        if ($regles->isEmpty()) {
            return 0;
        }

        $imputations = $regles->map(fn ($r) => new ImputationData(
            centreId:    $r->centre_analytique_id,
            montant:     null,
            pourcentage: (float) $r->pourcentage,
            sens:        $sens,
        ))->all();

        // Contrôle : la somme des % doit faire 100 — sinon on refuse.
        $total = array_sum(array_map(fn ($i) => $i->pourcentage, $imputations));
        if (abs($total - 100) > 0.01) {
            throw new RuntimeException(
                "Les règles de ventilation pour la nature « {$nature} » totalisent {$total} % — " .
                "elles doivent faire 100 %. Vérifiez la configuration."
            );
        }

        $this->ventiler($source, $imputations, $sens, $montantSource, $userId);

        return count($imputations);
    }
}