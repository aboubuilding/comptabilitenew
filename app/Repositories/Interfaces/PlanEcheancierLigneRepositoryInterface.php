<?php

namespace App\Repositories\Interfaces;

use App\Models\PlanEcheancierLigne;
use Illuminate\Database\Eloquent\Collection;

interface PlanEcheancierLigneRepositoryInterface
{
    public function getByPlanEcheancier(int $planEcheancierId): Collection;
    public function createForPlan(int $planEcheancierId, array $data): PlanEcheancierLigne;
    public function updateForPlan(int $planEcheancierId, array $data): bool;
    public function deleteByPlan(int $planEcheancierId): bool;
    public function getTotalMontant(int $planEcheancierId): float;

}
