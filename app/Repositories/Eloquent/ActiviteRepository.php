<?php

namespace App\Repositories\Eloquent;

use App\Models\Activite;

class ActiviteRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Activite());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
