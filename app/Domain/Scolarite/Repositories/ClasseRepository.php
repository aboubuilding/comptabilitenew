<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Classe;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ClasseRepository extends BaseRepository
{
    public function __construct(Classe $model)
    {
        parent::__construct($model);
    }

    public function byNiveau(int $niveauId): Collection
    {
        return $this->activeQuery()->where('niveau_id', $niveauId)->orderBy('libelle')->get();
    }

    public function byAnnee(int $anneeId): Collection
    {
        return $this->activeQuery()->where('annee_id', $anneeId)->orderBy('libelle')->get();
    }

    /** Classes d'une année, avec leur niveau, leur cycle et leur effectif. */
    public function paginateWithFilters(array $filters, int $anneeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->with(['niveau:id,libelle,numero_ordre', 'cycle:id,libelle'])
            ->when($filters['cycle_id'] ?? null, fn ($q, $id) => $q->where('cycle_id', $id))
            ->when($filters['niveau_id'] ?? null, fn ($q, $id) => $q->where('niveau_id', $id))
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where('libelle', 'like', "%{$s}%"))
            ->addSelect([
                'effectif' => DB::table('inscriptions')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('inscriptions.classe_id', 'classes.id')
                    ->where('inscriptions.etat', 1)
                    ->where('inscriptions.statut_abandon', 0),
            ])
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Une classe qui compte des élèves inscrits ne se désactive pas. */
    public function aDesInscrits(int $classeId): bool
    {
        return DB::table('inscriptions')
            ->where('classe_id', $classeId)
            ->where('etat', 1)
            ->where('statut_abandon', 0)
            ->exists();
    }
}
