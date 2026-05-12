<?php

namespace App\Repositories\Eloquent;

use App\Models\MenuProduit;

class MenuProduitRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MenuProduit());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
