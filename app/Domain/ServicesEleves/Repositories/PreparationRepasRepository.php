<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\PreparationRepas;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PreparationRepasRepository extends BaseRepository
{
    public function __construct(PreparationRepas $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['menu:id,libelle,type_repas', 'magasin:id,libelle', 'responsable:id,name'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['menu_id'] ?? null, fn ($q, $v) => $q->where('menu_id', $v))
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_preparation', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_preparation', '<=', $v))
            ->orderByDesc('date_preparation')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(?int $anneeId = null, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $q = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->whereDate('date_preparation', '>=', $v))
            ->when($dateFin,   fn ($q, $v) => $q->whereDate('date_preparation', '<=', $v));

        return [
            'nb'         => (clone $q)->count(),
            'parts'      => (int) (clone $q)->sum('nombre_parts'),
            'cout_total' => round((float) (clone $q)->sum('cout_reel'), 2),
            'cout_moyen_par_part' => (function () use ($q) {
                $parts = (clone $q)->sum('nombre_parts');
                $cout  = (clone $q)->sum('cout_reel');
                return $parts > 0 ? round((float) $cout / $parts, 2) : 0;
            })(),
        ];
    }
}