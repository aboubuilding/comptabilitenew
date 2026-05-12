<?php

namespace App\Repositories\Eloquent;

use App\Models\StockActuel;

class StockActuelRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new StockActuel());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
