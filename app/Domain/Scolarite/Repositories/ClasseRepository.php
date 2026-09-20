<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Classe;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ClasseRepository extends BaseRepository
{
    public function __construct(Classe $model)
    {
        parent::__construct($model);
    }

    public function byNiveau(int $niveauId): Collection
    {
        return $this->activeQuery()->where('niveau_id', $niveauId)->orderBy('libelle')->get();
    }

    public function byAnnee(int $anneeId): Collection
    {
        return $this->activeQuery()->where('annee_id', $anneeId)->orderBy('libelle')->get();
    }
}