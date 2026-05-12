<?php

namespace App\Repositories\Eloquent;

use App\Models\Eleve;

class EleveRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Eleve());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
