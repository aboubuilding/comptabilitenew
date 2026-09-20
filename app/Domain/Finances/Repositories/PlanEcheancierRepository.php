<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\PlanEcheancier;
use App\Repositories\BaseRepository;

class PlanEcheancierRepository extends BaseRepository
{
    public function __construct(PlanEcheancier $model)
    {
        parent::__construct($model);
    }
}