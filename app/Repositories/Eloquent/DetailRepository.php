<?php

namespace App\Repositories\Eloquent;

use App\Models\Detail;

class DetailRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Detail());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
