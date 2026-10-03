<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Niveau;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class NiveauRepository extends BaseRepository
{
    public function __construct(Niveau $model)
    {
        parent::__construct($model);
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('numero_ordre')
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'cycle_id']);
    }

    public function byCycle(int $cycleId): Collection
    {
        return $this->activeQuery()
            ->where('cycle_id', $cycleId)
            ->orderBy('numero_ordre')
            ->get();
    }

    /**
     * Pagination + filtres GET (cycle_id, search) — pas de DataTables ici,
     * conformément à la décision actée par défaut sur les 13 modules.
     */
    public function paginateWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('cycle')
            ->when($filters['cycle_id'] ?? null, fn ($q, $id) => $q->where('cycle_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('libelle', 'like', "%{$s}%"))
            ->orderBy('numero_ordre')
            ->paginate($perPage);
    }

    /**
     * A enrichir une fois le modèle Classe construit : un Niveau ne
     * devrait pas être supprimable s'il a des classes actives rattachées.
     * Retourne true partout en attendant, pour ne pas bloquer un module
     * qui n'existe pas encore.
     */
    public function canDelete(int $niveauId): bool
    {
        return ! \App\Domain\Scolarite\Models\Classe::query()
            ->where('niveau_id', $niveauId)
            ->where('etat', 1)
            ->exists();
    }
}