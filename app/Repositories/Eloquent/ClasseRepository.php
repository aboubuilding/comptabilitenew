<?php

namespace App\Repositories\Eloquent;

use App\Models\Classe;
use App\Repositories\Interfaces\ClasseRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

class ClasseRepository extends BaseRepository implements ClasseRepositoryInterface
{
    public function __construct(Classe $model)
    {
        parent::__construct($model);
    }

    // ✅ Aucune méthode redéfinie. Tout est géré par BaseRepository.
}