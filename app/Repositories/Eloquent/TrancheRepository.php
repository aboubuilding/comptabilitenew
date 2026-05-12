<?php

namespace App\Repositories\Eloquent;

use App\Models\Tranche;

class TrancheRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Tranche());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
