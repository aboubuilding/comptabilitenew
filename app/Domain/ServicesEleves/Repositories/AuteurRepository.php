<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Auteur;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class AuteurRepository extends BaseRepository
{
    public function __construct(Auteur $model)
    {
        parent::__construct($model);
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('nom_prenom')
            ->get(['id', 'nom_prenom']);
    }
}
