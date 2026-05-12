<?php

namespace App\Repositories\Eloquent;

use App\Models\PreparationRepas;

class PreparationRepasRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new PreparationRepas());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
