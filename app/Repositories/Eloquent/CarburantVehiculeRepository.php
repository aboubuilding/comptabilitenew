<?php

namespace App\Repositories\Eloquent;

use App\Models\CarburantVehicule;

class CarburantVehiculeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CarburantVehicule());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
