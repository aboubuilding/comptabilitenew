<?php

namespace App\Repositories\Eloquent;

use App\Models\VenteDetail;

class VenteDetailRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new VenteDetail());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
