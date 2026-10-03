<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Tranche;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TrancheRepository extends BaseRepository
{
    public function __construct(Tranche $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('fraisEcole:id,libelle,montant')
            ->when($filters['frais_ecole_id'] ?? null, fn ($q, $v) => $q->where('frais_ecole_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('frais_ecole_id')
            ->orderBy('date_butoire')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function pourFrais(int $fraisEcoleId)
    {
        return $this->activeQuery()
            ->where('frais_ecole_id', $fraisEcoleId)
            ->orderBy('date_butoire')
            ->get();
    }

    /** Somme des taux pour un frais donné (doit faire 100 %). */
    public function totalTauxPourFrais(int $fraisEcoleId, ?int $ignoreId = null): float
    {
        return round(
            (float) $this->activeQuery()
                ->where('frais_ecole_id', $fraisEcoleId)
                ->when($ignoreId, fn ($q, $v) => $q->where('id', '!=', $v))
                ->sum('taux'),
            2
        );
    }
}