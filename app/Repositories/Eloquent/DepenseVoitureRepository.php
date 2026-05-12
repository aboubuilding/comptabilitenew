<?php

namespace App\Repositories\Eloquent;

use App\Models\DepenseVoiture;

class DepenseVoitureRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new DepenseVoiture());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
