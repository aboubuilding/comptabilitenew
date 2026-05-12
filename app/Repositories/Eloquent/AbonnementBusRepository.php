<?php

namespace App\Repositories\Eloquent;

use App\Models\AbonnementBus;

class AbonnementBusRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AbonnementBus());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
