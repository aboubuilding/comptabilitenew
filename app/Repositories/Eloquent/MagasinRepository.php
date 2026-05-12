<?php

namespace App\Repositories\Eloquent;

use App\Models\Magasin;

class MagasinRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Magasin());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
