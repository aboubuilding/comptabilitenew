<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\FraisEcole;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FraisEcoleRepository extends BaseRepository
{
    public function __construct(FraisEcole $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $anneeId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->with(['niveau', 'planEcheancier'])
            ->when($filters['niveau_id'] ?? null, fn ($q, $v) => $q->where('niveau_id', $v))
            ->when($filters['type_forfait'] ?? null, fn ($q, $v) => $q->where('type_forfait', $v))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('libelle', 'like', "%{$s}%"))
            ->orderBy('libelle')
            ->paginate($perPage);
    }
}