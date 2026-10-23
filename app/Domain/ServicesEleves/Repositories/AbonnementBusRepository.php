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
                'zone:id,libelle,tarif_base',
                'voiture:id,plaque,marque,modele',
                'ligne:id,libelle,chauffeur_id',
                'ligne.chauffeur:id,nom,prenom',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['zone_id'] ?? null, fn ($q, $v) => $q->where('zone_id', $v))
            ->when($filters['voiture_id'] ?? null, fn ($q, $v) => $q->where('voiture_id', $v))
            ->when(($filters['statut'] ?? '') !== '', fn ($q) => $q->where('statut', (int) $filters['statut']))
            ->when(($filters['gps'] ?? '') === 'avec', fn ($q) => $q->whereNotNull('latitude'))
            ->when(($filters['gps'] ?? '') === 'sans', fn ($q) => $q->whereNull('latitude'))
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

    public function actifsParZone(?int $anneeId = null): array
    {
        return $this->activeQuery()
            ->where('statut', 1)
            ->when($anneeId, fn ($q) => $q->where('annee_id', $anneeId))
            ->with('zone:id,libelle')
            ->get()
            ->groupBy(fn ($a) => $a->zone?->libelle ?? 'Sans zone')
            ->map(fn ($group) => $group->count())
            ->all();
    }

    public function compterActifs(?int $anneeId = null): array
    {
        $actifs = $this->activeQuery()->where('statut', 1)->when($anneeId, fn ($q) => $q->where('annee_id', $anneeId));

        return [
            'total_actifs'   => (clone $actifs)->count(),
            'revenus_mensuels' => round((float) (clone $actifs)->sum('montant_mensuel'), 2),
            'revenus_annuels'  => round((float) (clone $actifs)->sum('montant_total_du'), 2),
            'sans_zone'        => (clone $actifs)->whereNull('zone_id')->count(),
            'sans_gps'         => (clone $actifs)->whereNull('latitude')->count(),
        ];
    }
}