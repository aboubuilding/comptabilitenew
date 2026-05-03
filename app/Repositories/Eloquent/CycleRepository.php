<?php

namespace App\Repositories\Eloquent;

use App\Models\Cycle;
use App\Repositories\Interfaces\CycleRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

class CycleRepository extends BaseRepository implements CycleRepositoryInterface
{
    public function __construct(Cycle $model)
    {
        parent::__construct($model);
    }

    // ✅ Aucune méthode redéfinie : tout est hérité de BaseRepository
    // find(), create(), update(), delete(), activeQuery(), etc.
}