<?php

namespace App\Repositories\Eloquent;

use App\Models\Vente;

class VenteRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Vente());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
