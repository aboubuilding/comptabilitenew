<?php

namespace App\Repositories\Eloquent;

use App\Models\ParentEleve;

class ParentEleveRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ParentEleve());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
