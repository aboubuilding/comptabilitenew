<?php

namespace App\Repositories\Eloquent;

use App\Models\Voiture;

class VoitureRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Voiture());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
