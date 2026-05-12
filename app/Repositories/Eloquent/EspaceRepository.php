<?php

namespace App\Repositories\Eloquent;

use App\Models\Espace;

class EspaceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Espace());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
