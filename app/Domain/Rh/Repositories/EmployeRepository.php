<?php

namespace App\Domain\Rh\Repositories;

use App\Domain\Rh\Models\Employe;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EmployeRepository extends BaseRepository
{
    public function __construct(Employe $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('profession:id,libelle,salaire_base')
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['profession_id'] ?? null, fn ($q, $v) => $q->where('profession_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('etat', (int) $filters['statut'])
            )
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('nom_prenom', 'like', "%{$v}%")
                        ->orWhere('numero_cnss', 'like', "%{$v}%")
                        ->orWhere('telephone', 'like', "%{$v}%")
                        ->orWhere('email', 'like', "%{$v}%");
                });
            })
            ->orderBy('nom_prenom')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('nom_prenom')
            ->get(['id', 'nom_prenom', 'profession_id', 'salaire']);
    }

    public function findAvecRelations(int $id): Employe
    {
        return $this->activeQuery()
            ->with('profession:id,libelle,salaire_base')
            ->findOrFail($id);
    }

    /** Statistiques pour les cartes. */
    public function compterStatistiques(): array
    {
        $base = $this->activeQuery();

        return [
            'total'        => (clone $base)->count(),
            'actifs'       => (clone $base)->where('etat', 1)->count(),
            'inactifs'     => (clone $base)->where('etat', \App\Repositories\BaseRepository::INACTIF)->count(),
            'assures_cnss' => (clone $base)->where('is_cnss', 1)->count(),
            'assures_amu'  => (clone $base)->where('is_amu', 1)->count(),
            'masse_salariale' => round((float) (clone $base)->where('etat', 1)->sum('salaire'), 2),
        ];
    }

    /** Répartition par profession. */
    public function parProfession(): array
    {
        return $this->activeQuery()
            ->selectRaw('profession_id, COUNT(*) as nb, SUM(salaire) as masse')
            ->where('etat', 1)
            ->groupBy('profession_id')
            ->with('profession:id,libelle')
            ->get()
            ->map(fn ($row) => [
                'profession' => $row->profession?->libelle ?? 'Sans profession',
                'nb'         => $row->nb,
                'masse'      => round((float) $row->masse, 2),
            ])
            ->toArray();
    }
}