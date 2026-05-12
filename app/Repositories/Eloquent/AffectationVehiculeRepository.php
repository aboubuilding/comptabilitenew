<?php

namespace App\Repositories\Eloquent;

use App\Models\AffectationVehicule;

class AffectationVehiculeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AffectationVehicule());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
