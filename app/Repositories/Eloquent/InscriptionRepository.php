<?php

namespace App\Repositories\Eloquent;

use App\Models\Inscription;

class InscriptionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Inscription());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
