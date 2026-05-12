<?php

namespace App\Repositories\Eloquent;

use App\Models\MouvementStock;

class MouvementStockRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MouvementStock());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
