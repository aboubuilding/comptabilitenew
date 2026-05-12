<?php

namespace App\Repositories\Eloquent;

use App\Models\Paiement;

class PaiementRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Paiement());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
