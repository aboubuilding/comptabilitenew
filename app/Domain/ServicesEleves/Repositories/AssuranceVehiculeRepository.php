<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\AssuranceVehicule;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AssuranceVehiculeRepository extends BaseRepository
{
    public function __construct(AssuranceVehicule $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('voiture:id,plaque,marque,modele')
            ->when($filters['voiture_id'] ?? null, fn ($q, $v) => $q->where('voiture_id', $v))
            ->when($filters['statut'] ?? null, function ($q, $v) {
                if ($v === 'active') {
                    $q->whereDate('date_fin', '>=', now());
                } elseif ($v === 'expiree') {
                    $q->whereDate('date_fin', '<', now());
                } elseif ($v === 'bientot') {
                    $q->whereDate('date_fin', '>=', now())
                      ->whereDate('date_fin', '<=', now()->addDays(30));
                }
            })
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('compagnie_assurance', 'like', "%{$v}%")
                        ->orWhere('numero_contrat', 'like', "%{$v}%")
                        ->orWhere('type_assurance', 'like', "%{$v}%")
                        ->orWhereHas('voiture', fn ($c) => $c->where('plaque', 'like', "%{$v}%"));
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_debut', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_fin', '<=', $v))
            ->orderBy('date_fin')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        $q = $this->activeQuery();

        return [
            'total'        => (clone $q)->count(),
            'active'       => (clone $q)->whereDate('date_fin', '>=', now())->count(),
            'expirees'     => (clone $q)->whereDate('date_fin', '<', now())->count(),
            'bientot'      => (clone $q)->whereDate('date_fin', '>=', now())
                                        ->whereDate('date_fin', '<=', now()->addDays(30))
                                        ->count(),
            'prime_totale' => round((float) (clone $q)->whereDate('date_fin', '>=', now())->sum('prime'), 2),
        ];
    }

    /** Assurances expirant dans les 30 jours. */
    public function expirantBientot(int $jours = 30): Collection
    {
        return $this->activeQuery()
            ->with('voiture:id,plaque')
            ->whereDate('date_fin', '>=', now())
            ->whereDate('date_fin', '<=', now()->addDays($jours))
            ->orderBy('date_fin')
            ->get();
    }
}