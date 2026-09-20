<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Cycle;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CycleRepository extends BaseRepository
{
    public function __construct(Cycle $model)
    {
        parent::__construct($model);
    }

    /**
     * Vérification simple (un seul booléen) : reste ici, ne justifie pas
     * un Service dédié — même règle que pour Niveau/Classe.
     */
    public function hasNiveaux(int $cycleId): bool
    {
        return $this->model->find($cycleId)?->niveaux()->where('etat', 1)->exists() ?? false;
    }

    /**
     * Ne montre que les cycles actifs (activeQuery()) : "supprimer" fait
     * disparaître un cycle de la liste, même si techniquement ce n'est
     * qu'un changement d'etat (BaseRepository::delete()), jamais une
     * suppression physique.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('libelle', 'like', "%{$s}%"))
            ->orderBy('libelle')
            ->paginate($perPage);
    }
}