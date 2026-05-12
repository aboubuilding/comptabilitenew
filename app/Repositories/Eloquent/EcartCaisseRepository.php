<?php

namespace App\Repositories\Eloquent;

use App\Models\EcartCaisse;

class EcartCaisseRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new EcartCaisse());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
