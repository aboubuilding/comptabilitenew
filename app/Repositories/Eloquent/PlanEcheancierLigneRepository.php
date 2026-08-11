<?php

namespace App\Repositories\Eloquent;

use App\Models\PlanEcheancierLigne;
use App\Repositories\Interfaces\PlanEcheancierLigneRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class PlanEcheancierLigneRepository extends BaseRepository implements PlanEcheancierLigneRepositoryInterface
{
    protected bool $autoInjectAnneId = false;

    public function __construct(PlanEcheancierLigne $model)
    {
        parent::__construct($model);
    }

    public function getByPlanEcheancier(int $planEcheancierId): Collection
    {
        return $this->activeQuery()
            ->where('plan_echeancier_id', $planEcheancierId)
            ->orderBy('ordre')
            ->get();
    }

    public function createForPlan(int $planEcheancierId, array $data): PlanEcheancierLigne
    {
        $data['plan_echeancier_id'] = $planEcheancierId;
        return $this->create($data);
    }

    public function updateForPlan(int $planEcheancierId, array $data): bool
    {
        // Supprimer les anciennes lignes
        $this->query()->where('plan_echeancier_id', $planEcheancierId)->delete();

        // Créer les nouvelles lignes
        foreach ($data as $ligne) {
            $ligne['plan_echeancier_id'] = $planEcheancierId;
            $this->create($ligne);
        }

        return true;
    }

    public function deleteByPlan(int $planEcheancierId): bool
    {
        return $this->query()->where('plan_echeancier_id', $planEcheancierId)->delete();
    }

    public function getTotalMontant(int $planEcheancierId): float
    {
        return $this->query()
            ->where('plan_echeancier_id', $planEcheancierId)
            ->sum('montant');
    }
}
