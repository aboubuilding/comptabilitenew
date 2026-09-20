<?php

namespace App\Repositories;

use App\Support\AnneeScolaireContext;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class BaseRepository
{
    /**
     * Le modèle Eloquent associé à ce repository.
     */
    protected Model $model;

    /**
     * Valeurs du champ 'etat', présent sur (quasiment) toutes les tables du
     * schéma : $table->integer('etat')->default(1). C'est un indicateur
     * BINAIRE actif/inactif — pas un statut à 3 états. Un "delete" logique
     * via ce repository ne supprime donc jamais la ligne : il la passe à
     * INACTIF (archivage logique, cf. cahier des charges §6 "Statuts et
     * états génériques").
     */
    public const ACTIF = 1;
    public const INACTIF = 0;

    /**
     * Injecte automatiquement annee_id à la création si la colonne existe
     * et est fillable sur le modèle. La valeur vient du contexte d'année
     * scolaire active (voir injectAnneeId ci-dessous) — jamais lue en
     * session directement ici.
     */
    protected bool $autoInjectAnneeId = true;

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
        return $this->model->newQuery();
    }

    /**
     * Requête de base : uniquement les enregistrements actifs.
     */
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

    /**
     * Pagination "page N" classique — usage par défaut pour les écrans
     * standards (référentiels, listes de taille modérée).
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->activeQuery()->paginate($perPage, $columns);
    }

    /**
     * Pagination par curseur (keyset) — à utiliser dans les repositories
     * des tables volumineuses (paiements, mouvements, details,
     * stock_mouvements) : contrairement à paginate(), le coût ne croît
     * pas avec le numéro de page (pas de OFFSET). Nécessite un tri
     * stable, $orderBy doit être une colonne indexée.
     */
    public function cursorPaginate(int $perPage = 15, string $orderBy = 'id', array $columns = ['*']): CursorPaginator
    {
        return $this->activeQuery()
            ->orderBy($orderBy)
            ->cursorPaginate($perPage, $columns);
    }

    public function count(): int
    {
        return $this->activeQuery()->count();
    }

    /**
     * Création avec état ACTIF par défaut + injection de l'année scolaire
     * active si la colonne existe. Rappel : 'etat' et 'annee_id' doivent
     * être déclarés $fillable sur chaque modèle de domaine — sinon
     * l'assignation de masse les ignore silencieusement.
     */
    public function create(array $data): Model
    {
        $data['etat'] ??= self::ACTIF;

        if ($this->autoInjectAnneeId) {
            $data = $this->injectAnneeId($data);
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        return $this->findOrFail($id)->update($data);
    }

    /**
     * Suppression LOGIQUE (etat -> INACTIF). Jamais de suppression
     * physique ici — voir forceDelete() ci-dessous, volontairement séparée
     * et explicite pour ne jamais être appelée par erreur.
     */
    public function delete(int $id): bool
    {
        return $this->findOrFail($id)->update(['etat' => self::INACTIF]);
    }

    public function restore(int $id): bool
    {
        $model = $this->query()->where('etat', self::INACTIF)->findOrFail($id);

        return $model->update(['etat' => self::ACTIF]);
    }

    /**
     * Actifs + inactifs — pour un écran avec filtre "afficher aussi les
     * inactifs".
     */
    public function withInactifs(): Builder
    {
        return $this->query()->whereIn('etat', [self::ACTIF, self::INACTIF]);
    }

    public function onlyInactifs(): Builder
    {
        return $this->query()->where('etat', self::INACTIF);
    }

    /**
     * Suppression PHYSIQUE, explicitement distincte de delete(). Gère les
     * deux cas réels du projet : un modèle avec le trait SoftDeletes
     * (aujourd'hui, seul `User` via `deleted_at`) -> forceDelete() natif ;
     * tout autre modèle (pas de SoftDeletes, cas de ~90% des tables du
     * schéma) -> delete() EST déjà une suppression physique, donc on
     * l'utilise directement plutôt que d'appeler une méthode qui n'existe
     * pas sur ce modèle.
     */
    public function forceDelete(int $id): bool
    {
        $model = $this->query()->findOrFail($id);

        return method_exists($model, 'forceDelete')
            ? (bool) $model->forceDelete()
            : (bool) $model->delete();
    }

    /**
     * Bascule actif <-> inactif. Cast explicite en int : la colonne SQL
     * est un integer, mais selon le driver et les $casts du modèle,
     * l'attribut peut revenir en string — la comparaison stricte doit en
     * être protégée.
     */
    public function toggleActive(Model $model): Model
    {
        $model->etat = ((int) $model->etat === self::ACTIF) ? self::INACTIF : self::ACTIF;
        $model->save();

        return $model;
    }

    /**
     * Injecte annee_id depuis le contexte d'année scolaire active
     * (App\Support\AnneeScolaireContext), résolu par le middleware
     * ResolveAnneeScolaire en amont de la requête. Plus aucun accès
     * direct à session() ici : la source de vérité de "l'année active"
     * reste unique, et remplaçable/testable sans toucher ce fichier.
     */
    protected function injectAnneeId(array $data): array
    {
        if (isset($data['annee_id'])) {
            return $data;
        }

        if (!in_array('annee_id', $this->model->getFillable(), true)) {
            return $data;
        }

        $anneeId = app(AnneeScolaireContext::class)->id();

        if ($anneeId !== null) {
            $data['annee_id'] = $anneeId;
        }

        return $data;
    }
}