<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Prevision;
use App\Domain\Finances\Types\TypePrevision;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PrevisionRepository extends BaseRepository
{
    public function __construct(Prevision $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['centre:id,code,libelle', 'auteur:id,name'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['type'] ?? null,     fn ($q, $v) => $q->where('type', $v))
            ->when($filters['centre_analytique_id'] ?? null, fn ($q, $v) => $q->where('centre_analytique_id', $v))
            ->when($filters['search'] ?? null,   fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Agrégat par centre analytique sur une période — utilisé par le
     * rapport analytique (module 2.7).
     *
     * @return Collection<int, object{centre_id:int, total_prevu:float}>
     */
    public function agregerParCentre(
        ?int $anneeId = null,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): Collection {
        return $this->activeQuery()
            ->whereNotNull('centre_analytique_id')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->whereDate('date_debut', '>=', $v))
            ->when($dateFin,   fn ($q, $v) => $q->whereDate('date_fin', '<=', $v))
            ->groupBy('centre_analytique_id')
            ->selectRaw(
                "centre_analytique_id as centre_id, SUM(CASE WHEN type = ? THEN montant_previsionnel ELSE -montant_previsionnel END) as total_prevu",
                [\App\Domain\Finances\Types\TypePrevision::RECETTE->value]
            )
            ->get()
            ->keyBy('centre_id');
    }
}