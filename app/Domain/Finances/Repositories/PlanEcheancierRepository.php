<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\PlanEcheancier;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlanEcheancierRepository extends BaseRepository
{
    public function __construct(PlanEcheancier $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->withCount(['lignes', 'fraisEcoles'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nom', 'like', "%{$v}%"))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAvecLignes(int $id): PlanEcheancier
    {
        return $this->activeQuery()
            ->with(['lignes'])
            ->findOrFail($id);
    }
}