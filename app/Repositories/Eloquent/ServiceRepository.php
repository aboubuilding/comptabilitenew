<?php

namespace App\Repositories\Eloquent;

use App\Models\Service;

class ServiceRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Service());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
