<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Voiture;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VoitureRepository extends BaseRepository
{
    public function __construct(Voiture $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['zones:id,libelle,voiture_id'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('plaque', 'like', "%{$v}%")
                        ->orWhere('marque', 'like', "%{$v}%")
                        ->orWhere('modele', 'like', "%{$v}%");
                });
            })
            ->orderBy('plaque')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('plaque')
            ->get(['id', 'plaque', 'marque', 'modele']);
    }

    public function findAvecDetails(int $id): Voiture
    {
        return $this->activeQuery()
            ->with([
                'zones',
                'entretiens' => fn ($q) => $q->orderByDesc('date_entretien')->limit(10),
                'carburants' => fn ($q) => $q->orderByDesc('date_plein')->limit(10),
                'assurances' => fn ($q) => $q->orderByDesc('date_fin'),
                'affectations' => fn ($q) => $q->orderByDesc('date_debut'),
                'affectations.chauffeur',
            ])
            ->findOrFail($id);
    }

    public function compterParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return [
            'total'       => $this->activeQuery()->count(),
            'disponible'  => (int) ($rows[\App\Domain\ServicesEleves\Types\StatutVehicule::DISPONIBLE->value]      ?? 0),
            'maintenance' => (int) ($rows[\App\Domain\ServicesEleves\Types\StatutVehicule::EN_MAINTENANCE->value] ?? 0),
            'sorti'       => (int) ($rows[\App\Domain\ServicesEleves\Types\StatutVehicule::SORTI->value]         ?? 0),
            'reforme'     => (int) ($rows[\App\Domain\ServicesEleves\Types\StatutVehicule::REFORME->value]       ?? 0),
        ];
    }
}