<?php

namespace App\Repositories\Eloquent;

use App\Models\Produit;

class ProduitRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Produit());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
