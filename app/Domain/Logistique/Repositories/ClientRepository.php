<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Client;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClientRepository extends BaseRepository
{
    public function __construct(Client $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('nom_raison_sociale', 'like', "%{$v}%")
                        ->orWhere('telephone', 'like', "%{$v}%")
                        ->orWhere('email', 'like', "%{$v}%");
                });
            })
            ->orderBy('nom_raison_sociale')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('nom_raison_sociale')
            ->get(['id', 'nom_raison_sociale', 'telephone']);
    }

    /** Recherche AJAX pour le point de vente. */
    public function rechercher(string $q, int $limit = 20): Collection
    {
        return $this->activeQuery()
            ->where(function ($sub) use ($q) {
                $sub->where('nom_raison_sociale', 'like', "%{$q}%")
                    ->orWhere('telephone', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get(['id', 'nom_raison_sociale', 'telephone', 'type_client']);
    }
}