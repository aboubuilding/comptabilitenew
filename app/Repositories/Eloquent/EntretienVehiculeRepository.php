<?php

namespace App\Repositories\Eloquent;

use App\Models\EntretienVehicule;

class EntretienVehiculeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EntretienVehicule());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
