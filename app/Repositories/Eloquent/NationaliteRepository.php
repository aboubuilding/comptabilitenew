<?php

namespace App\Repositories\Eloquent;

use App\Models\Nationalite;

class NationaliteRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Nationalite());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
