<?php

namespace App\Repositories\Eloquent;

use App\Models\Cheque;

class ChequeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Cheque());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
