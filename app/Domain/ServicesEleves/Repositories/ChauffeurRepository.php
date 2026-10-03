<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Chauffeur;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ChauffeurRepository extends BaseRepository
{
    public function __construct(Chauffeur $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('nom', 'like', "%{$v}%")
                        ->orWhere('prenom', 'like', "%{$v}%")
                        ->orWhere('permis_conduire', 'like', "%{$v}%")
                        ->orWhere('telephone', 'like', "%{$v}%");
                });
            })
            ->orderBy('nom')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->where('statut', 1)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom', 'permis_conduire']);
    }

    public function findAvecAffectations(int $id): Chauffeur
    {
        return $this->activeQuery()
            ->with([
                'affectations.voiture',
                'zones',
            ])
            ->findOrFail($id);
    }

    public function permisExpirant(int $jours = 30): Collection
    {
        return $this->activeQuery()
            ->whereNotNull('date_validite_permis')
            ->whereDate('date_validite_permis', '<=', now()->addDays($jours))
            ->whereDate('date_validite_permis', '>=', now())
            ->orderBy('date_validite_permis')
            ->get();
    }
}