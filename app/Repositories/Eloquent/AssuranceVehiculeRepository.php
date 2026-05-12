<?php

namespace App\Repositories\Eloquent;

use App\Models\AssuranceVehicule;

class AssuranceVehiculeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AssuranceVehicule());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
