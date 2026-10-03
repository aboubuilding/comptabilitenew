<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\InscriptionCantine;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InscriptionCantineRepository extends BaseRepository
{
    public function __construct(InscriptionCantine $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', (int) $filters['statut'])
            )
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

    /** Compteurs et revenus. */
    public function compterActifs(): array
    {
        $actifs = $this->activeQuery()->where('statut', 1);

        return [
            'total_actifs'    => (clone $actifs)->count(),
            'revenus_mensuels'=> round((float) (clone $actifs)->sum('montant_mensuel'), 2),
            'revenus_annuels' => round((float) (clone $actifs)->sum('montant_total_du'), 2),
        ];
    }
}