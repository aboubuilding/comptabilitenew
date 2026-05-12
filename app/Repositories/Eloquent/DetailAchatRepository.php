<?php

namespace App\Repositories\Eloquent;

use App\Models\DetailAchat;

class DetailAchatRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new DetailAchat());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
