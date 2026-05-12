<?php

namespace App\Repositories\Eloquent;

use App\Models\Achat;

class AchatRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Achat());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
