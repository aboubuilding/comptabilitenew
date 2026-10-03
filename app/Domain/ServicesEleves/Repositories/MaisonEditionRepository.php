<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\MaisonEdition;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class MaisonEditionRepository extends BaseRepository
{
    public function __construct(MaisonEdition $model)
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
