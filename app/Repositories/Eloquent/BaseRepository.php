<?php

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class BaseRepository
{
    /**
     * Le modèle Eloquent associé à ce repository
     */
    protected Model $model;

    /** États métier pour le champ 'etat' */
    public const ACTIF = 1;
    public const SUPPRIME = 2;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Requête brute (sans filtre d'état)
     */
    protected function query(): Builder
    {
        return $this->model->query();
    }

    /**
     * Requête filtrée : exclut les records marqués SUPPRIME
     */
    protected function activeQuery(): Builder
    {
        return $this->query()->where('etat', self::ACTIF);
    }

    public function find(int $id): ?Model
    {
        return $this->activeQuery()->find($id);
    }

    /**
     * ⚠️ Lève ModelNotFoundException si le record est supprimé ou inexistant
     */
    public function findOrFail(int $id): Model
    {
        return $this->activeQuery()->findOrFail($id);
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->activeQuery()->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->activeQuery()->paginate($perPage, $columns);
    }

    public function count(): int
    {
        return $this->activeQuery()->count();
    }

    /**
     * Création avec état par défaut ACTIF
     */
    public function create(array $data): Model
    {
        $data['etat'] = $data['etat'] ?? self::ACTIF;
        return $this->model->create($data);
    }

    /**
     * Mise à jour classique (ne touche pas à 'etat' sauf si explicitement fourni)
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * 🗑️ Suppression logique : change etat = SUPPRIME au lieu de DELETE SQL
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->update(['etat' => self::SUPPRIME]);
    }

    /**
     * ♻️ Restauration d'un record "supprimé"
     */
    public function restore(int $id): bool
    {
        $model = $this->query()
            ->where('etat', self::SUPPRIME)
            ->findOrFail($id);
            
        return $model->update(['etat' => self::ACTIF]);
    }

    /**
     * 🔓 Accéder aux records supprimés (pour admin, audit, rapports)
     */
    public function withSupprime(): Builder
    {
        return $this->query()->whereIn('etat', [self::ACTIF, self::SUPPRIME]);
    }

    /**
     * 🔍 Rechercher uniquement dans les records supprimés
     */
    public function onlySupprime(): Builder
    {
        return $this->query()->where('etat', self::SUPPRIME);
    }

    /**
     * 💀 Suppression physique (déconseillée en comptabilité/audit)
     * À utiliser uniquement pour conformité RGPD ou purge légale
     */
    public function forceDelete(int $id): bool
    {
        $model = $this->query()->findOrFail($id);
        return $model->forceDelete() ?? $model->delete();
    }
}