<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Paiement;
use App\Repositories\BaseRepository;

class PaiementRepository extends BaseRepository
{
    public function __construct(Paiement $model)
    {
        parent::__construct($model);
    }
}