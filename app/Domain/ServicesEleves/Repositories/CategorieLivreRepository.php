<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\CategorieLivre;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class CategorieLivreRepository extends BaseRepository
{
    public function __construct(CategorieLivre $model)
    {
        parent::__construct($model);
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('libelle')
            ->get(['id', 'libelle']);
    }
}
