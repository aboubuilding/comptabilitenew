<?php

namespace App\Repositories\Eloquent;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User());
    }

    // Vous pouvez ajouter ici des méthodes spécifiques de requêtage.
}
