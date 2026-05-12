<?php

namespace App\Repositories\Eloquent;

use App\Models\InscriptionCantine;

class InscriptionCantineRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new InscriptionCantine());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
