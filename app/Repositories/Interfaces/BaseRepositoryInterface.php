<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Interface BaseRepositoryInterface
 * 
 * Contrat de base pour les repositories utilisant la suppression logique (etat).
 * Les méthodes internes (query, activeQuery) ne font pas partie du contrat public.
 */
interface BaseRepositoryInterface
{
    /**
     * Retourne l'instance du modèle Eloquent associé
     */
    public function getModel(): Model;

    /**
     * Trouve un enregistrement par son ID (exclut les suppressions logiques)
     */
    public function find(int $id): ?Model;

    /**
     * Trouve un enregistrement par son ID ou lève une exception
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findOrFail(int $id): Model;

    /**
     * Récupère tous les enregistrements actifs
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Pagination des enregistrements actifs
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    /**
     * Compte le nombre d'enregistrements actifs
     */
    public function count(): int;

    /**
     * Crée un nouvel enregistrement (etat = ACTIF par défaut)
     */
    public function create(array $data): Model;

    /**
     * Met à jour un enregistrement actif
     */
    public function update(int $id, array $data): bool;

    /**
     * Suppression logique : passe etat = SUPPRIME au lieu de DELETE SQL
     */
    public function delete(int $id): bool;

    /**
     * Restaure un enregistrement marqué comme supprimé
     */
    public function restore(int $id): bool;

    /**
     * Builder incluant actifs + supprimés (audit, admin, exports)
     */
    public function withSupprime(): Builder;

    /**
     * Builder filtré uniquement sur les supprimés
     */
    public function onlySupprime(): Builder;

    /**
     * Suppression physique définitive (RGPD / purge légale)
     */
    public function forceDelete(int $id): bool;
}