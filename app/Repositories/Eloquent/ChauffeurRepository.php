<?php

namespace App\Repositories\Eloquent;

use App\Models\Chauffeur;

class ChauffeurRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Chauffeur());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
