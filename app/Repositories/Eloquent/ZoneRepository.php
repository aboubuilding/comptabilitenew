<?php

namespace App\Repositories\Eloquent;

use App\Models\Zone;

class ZoneRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Zone());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
