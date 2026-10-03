<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Stock;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockMouvementRepository extends BaseRepository
{
    public function __construct(Stock $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'produit:id,libelle,unite_stock',
                'magasin:id,libelle,type',
                'utilisateur:id,name',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['produit_id'] ?? null, fn ($q, $v) => $q->where('produit_id', $v))
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->when($filters['type_mouvement'] ?? null, fn ($q, $v) => $q->where('type_mouvement', $v))
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_stock', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_stock', '<=', $v))
            ->orderByDesc('date_stock')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}