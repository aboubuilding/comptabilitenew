<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Nationalite;
use App\Repositories\BaseRepository;

class NationaliteRepository extends BaseRepository
{
    public function __construct(Nationalite $model)
    {
        parent::__construct($model);
    }
}