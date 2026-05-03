<?php

namespace App\Repositories\Eloquent;

use App\Models\Periode;
use App\Repositories\Interfaces\PeriodeRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

class PeriodeRepository extends BaseRepository implements PeriodeRepositoryInterface
{
    public function __construct(Periode $model)
    {
        parent::__construct($model);
    }

    // ✅ Aucune méthode redéfinie. Tout est géré par BaseRepository.
}