<?php

namespace App\Repositories\Eloquent;

use App\Models\Prevision;

class PrevisionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Prevision());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
