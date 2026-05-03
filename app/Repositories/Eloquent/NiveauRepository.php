<?php

namespace App\Repositories\Eloquent;

use App\Models\Niveau;
use App\Repositories\Interfaces\NiveauRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;

class NiveauRepository extends BaseRepository implements NiveauRepositoryInterface
{
    public function __construct(Niveau $model)
    {
        parent::__construct($model);
    }

    // ✅ Aucune méthode redéfinie. Tout est géré par BaseRepository.
}
