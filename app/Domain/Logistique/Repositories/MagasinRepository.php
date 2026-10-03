<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Magasin;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MagasinRepository extends BaseRepository
{
    public function __construct(Magasin $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->withCount('stocksActuels')
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('type')
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'type']);
    }

    public function findAvecStocks(int $id): Magasin
    {
        return $this->activeQuery()
            ->with(['stocksActuels.produit:id,libelle,unite_stock'])
            ->findOrFail($id);
    }
}