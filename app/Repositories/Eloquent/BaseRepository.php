<?php

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
     * (Peut être surchargée dans un repository enfant : protected bool $autoInjectAnneId = false;)
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

    protected function activeQuery(): Builder
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
        
        // 🔹 Injection intelligente de l'année scolaire
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

    // ─────────────────────────────────────────────────────────────
    // 🔧 Méthodes d'injection automatique
    // ─────────────────────────────────────────────────────────────

    /**
     * Injecte annee_id depuis la session si :
     * 1. Le champ n'est pas déjà fourni dans $data
     * 2. Le modèle accepte le champ (présent dans $fillable)
     * 3. L'injection est activée
     */
    protected function injectSessionAnneId(array $data): array
    {
        // Ne pas écraser une valeur explicite
        if (isset($data['annee_id'])) {
            return $data;
        }

        // Vérification légère : le modèle doit autoriser le mass-assignment
        if (!in_array('annee_id', $this->model->getFillable(), true)) {
            return $data;
        }

        // Récupération sécurisée depuis la session (adaptée à votre structure)
        $anneeId = session('LoginUser.annee_id') ?? session('annee_id');

        if ($anneeId !== null) {
            $data['annee_id'] = (int) $anneeId;
        }

        return $data;
    }

    /**
     * Permet de récupérer l'année active sans dépendre du modèle
     */
    protected function getCurrentAnneId(): ?int
    {
        $id = session('LoginUser.annee_id') ?? session('annee_id');
        return $id ? (int) $id : null;
    }
}