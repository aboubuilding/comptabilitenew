<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Zone;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ZoneRepository extends BaseRepository
{
    public function __construct(Zone $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['chauffeur:id,nom,prenom', 'voiture:id,plaque,marque,modele'])
            ->withCount('abonnements')
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('libelle')
            ->get(['id', 'libelle']);
    }
}