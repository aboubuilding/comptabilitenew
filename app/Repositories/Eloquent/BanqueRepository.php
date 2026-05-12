<?php

namespace App\Repositories\Eloquent;

use App\Models\Banque;

class BanqueRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Banque());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
