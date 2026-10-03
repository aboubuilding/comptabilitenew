<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\PlanEcheancierData;
use App\Domain\Finances\Models\PlanEcheancier;
use App\Domain\Finances\Models\PlanEcheancierLigne;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanEcheancierService
{
    public function __construct(
        private PlanEcheancierRepository $plans,
    ) {}

    public function creer(PlanEcheancierData $data): PlanEcheancier
    {
        $this->validerLignes($data->lignes);

        return DB::transaction(function () use ($data) {
            $plan = $this->plans->create([
                'nom'         => $data->nom,
                'description' => $data->description,
            ]);

            $this->enregistrerLignes($plan->id, $data->lignes);

            return $plan->fresh('lignes');
        });
    }

    public function modifier(int $id, PlanEcheancierData $data): PlanEcheancier
    {
        $this->validerLignes($data->lignes);

        return DB::transaction(function () use ($id, $data) {
            $plan = $this->plans->findOrFail($id);

            $this->plans->update($plan->id, [
                'nom'         => $data->nom,
                'description' => $data->description,
            ]);

            // Remplacement complet des lignes (simple et sûr)
            PlanEcheancierLigne::query()
                ->where('plan_echeancier_id', $plan->id)
                ->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);

            $this->enregistrerLignes($plan->id, $data->lignes);

            return $plan->fresh('lignes');
        });
    }

    public function supprimer(int $id): void
    {
        $plan = $this->plans->findOrFail($id);

        if ($plan->fraisEcoles()->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce plan : des frais y sont rattachés."
            );
        }

        $this->plans->delete($id);
    }

    /* ================================================================
       Internals
       ================================================================ */

    private function validerLignes(array $lignes): void
    {
        if (empty($lignes)) {
            throw new RuntimeException("Un plan d'échéancier doit comporter au moins une ligne.");
        }

        // Règle : soit toutes les lignes en pourcentage, soit toutes en montant
        $modes = array_unique(array_map(function ($l) {
            return ! empty($l['pourcentage']) && (float) $l['pourcentage'] > 0
                ? 'pourcentage' : 'montant';
        }, $lignes));

        if (count($modes) > 1) {
            throw new RuntimeException(
                "Mélange interdit : toutes les lignes doivent être soit en %, soit en montant."
            );
        }

        if (in_array('pourcentage', $modes, true)) {
            $total = array_sum(array_map(fn ($l) => (float) ($l['pourcentage'] ?? 0), $lignes));
            if (abs($total - 100) > 0.01) {
                throw new RuntimeException(
                    "La somme des pourcentages doit être égale à 100 % (actuellement {$total} %)."
                );
            }
        }
    }

    private function enregistrerLignes(int $planId, array $lignes): void
    {
        foreach ($lignes as $i => $ligne) {
            PlanEcheancierLigne::create([
                'plan_echeancier_id' => $planId,
                'ordre'              => $ligne['ordre'] ?? ($i + 1),
                'libelle'            => $ligne['libelle'],
                'jour_echeance'      => $ligne['jour_echeance'] ?? null,
                'date_echeance'      => $ligne['date_echeance'] ?? null,
                'montant'            => ! empty($ligne['montant']) ? (float) $ligne['montant'] : null,
                'pourcentage'        => ! empty($ligne['pourcentage']) ? (float) $ligne['pourcentage'] : null,
                'etat'               => 1,
            ]);
        }
    }
}