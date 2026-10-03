<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\AbonnementBus;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AbonnementBusRepository extends BaseRepository
{
    public function __construct(AbonnementBus $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'zone:id,libelle',
                'voiture:id,plaque,marque,modele',
                'ligne:id,libelle',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['zone_id'] ?? null, fn ($q, $v) => $q->where('zone_id', $v))
            ->when($filters['voiture_id'] ?? null, fn ($q, $v) => $q->where('voiture_id', $v))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->whereHas('inscription.eleve', function ($sub) use ($v) {
                    $sub->where('nom', 'like', "%{$v}%")
                        ->orWhere('prenom', 'like', "%{$v}%")
                        ->orWhere('matricule', 'like', "%{$v}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function actifsParZone(): array
    {
        return $this->activeQuery()
            ->where('statut', 1)
            ->with('zone:id,libelle')
            ->get()
            ->groupBy(fn ($a) => $a->zone?->libelle ?? 'Sans zone')
            ->map(fn ($group) => $group->count())
            ->all();
    }

    public function compterActifs(): array
    {
        $actifs = $this->activeQuery()->where('statut', 1);

        return [
            'total_actifs'   => (clone $actifs)->count(),
            'revenus_mensuels' => round((float) (clone $actifs)->sum('montant_mensuel'), 2),
            'revenus_annuels'  => round((float) (clone $actifs)->sum('montant_total_du'), 2),
        ];
    }
}