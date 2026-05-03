<?php

namespace App\Repositories\Eloquent;

use App\Models\Annee;
use App\Repositories\Interfaces\AnneeRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

class AnneeRepository extends BaseRepository implements AnneeRepositoryInterface
{
    public function __construct(Annee $model)
    {
        parent::__construct($model);
    }

    // ✅ Aucune méthode redéfinie. Tout est géré par BaseRepository.
}