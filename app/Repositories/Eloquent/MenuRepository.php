<?php

namespace App\Repositories\Eloquent;

use App\Models\Menu;

class MenuRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Menu());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
