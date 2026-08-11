<?php
// app/Repositories/Eloquent/BaseRepository.php

namespace App\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    /**
     * Le modèle Eloquent associé à ce repository
     */
    protected Model $model;

    /** États métier pour le champ 'etat' */
    public const ACTIF = 1;
    public const SUPPRIME = 2;

    /**
     * Active/désactive l'injection automatique de annee_id
     */
    protected bool $autoInjectAnneId = true;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    protected function query(): Builder
    {
        return $this->model->query();
    }

    public function activeQuery(): Builder
    {
        return $this->query()->where('etat', self::ACTIF);
    }

    public function find(int $id): ?Model
    {
        return $this->activeQuery()->find($id);
    }

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
     * Création avec état par défaut ACTIF + injection automatique de annee_id
     */
    public function create(array $data): Model
    {
        $data['etat'] = $data['etat'] ?? self::ACTIF;

        if ($this->autoInjectAnneId) {
            $data = $this->injectSessionAnneId($data);
        }

        return $this->model->create($data);
    }

    /**
     * Mise à jour classique
     */
    public function update(int $id, array $data): bool
    {
        $model = $this->findOrFail($id);
        return $model->update($data);
    }

    /**
     * Suppression logique
     */
    public function delete(int $id): bool
    {
        $model = $this->findOrFail($id);
        return $model->update(['etat' => self::SUPPRIME]);
    }

    /**
     * Restauration
     */
    public function restore(int $id): bool
    {
        $model = $this->query()
            ->where('etat', self::SUPPRIME)
            ->findOrFail($id);

        return $model->update(['etat' => self::ACTIF]);
    }

    public function withSupprime(): Builder
    {
        return $this->query()->whereIn('etat', [self::ACTIF, self::SUPPRIME]);
    }

    public function onlySupprime(): Builder
    {
        return $this->query()->where('etat', self::SUPPRIME);
    }

    public function forceDelete(int $id): bool
    {
        $model = $this->query()->findOrFail($id);
        return $model->forceDelete() ?? $model->delete();
    }

    /**
     * Injecte annee_id depuis la session
     */
    protected function injectSessionAnneId(array $data): array
    {
        if (isset($data['annee_id'])) {
            return $data;
        }

        if (!in_array('annee_id', $this->model->getFillable(), true)) {
            return $data;
        }

        $anneeId = session('LoginUser.annee_id') ?? session('annee_id');

        if ($anneeId !== null) {
            $data['annee_id'] = (int) $anneeId;
        }

        return $data;
    }

    /**
     * Récupérer l'année active depuis la session
     */
    protected function getCurrentAnneId(): ?int
    {
        $id = session('LoginUser.annee_id') ?? session('annee_id');
        return $id ? (int) $id : null;
    }

    /**
     * Toggle active status - CORRIGÉ : Utilise Model au lieu du type concret
     */
    public function toggleActive(Model $model): Model
    {
        $model->etat = $model->etat === self::ACTIF ? self::SUPPRIME : self::ACTIF;
        $model->save();
        return $model;
    }
}
