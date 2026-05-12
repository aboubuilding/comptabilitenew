<?php

namespace App\Repositories\Eloquent;

use App\Models\AssignationEleveBus;

class AssignationEleveBusRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AssignationEleveBus());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
