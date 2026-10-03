<?php

namespace App\Domain\Rh\Repositories;

use App\Domain\Rh\Models\Periode;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PeriodeRepository extends BaseRepository
{
    public function __construct(Periode $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->withCount('paies')
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderByDesc('date_debut')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderByDesc('date_debut')
            ->get(['id', 'libelle', 'date_debut', 'date_fin']);
    }
}