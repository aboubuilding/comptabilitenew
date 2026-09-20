<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\PlanEcheancierLigne;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PlanEcheancierLigneRepository extends BaseRepository
{
    public function __construct(PlanEcheancierLigne $model)
    {
        parent::__construct($model);
    }

    public function forPlan(int $planId): Collection
    {
        return $this->activeQuery()->where('plan_echeancier_id', $planId)->orderBy('ordre')->get();
    }
}