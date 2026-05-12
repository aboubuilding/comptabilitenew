<?php

namespace App\Repositories\Eloquent;

use App\Models\Fournisseur;

class FournisseurRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Fournisseur());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
