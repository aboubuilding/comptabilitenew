<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Evenement;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EvenementRepository extends BaseRepository
{
    public function __construct(Evenement $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nom', 'like', "%{$v}%"))
            ->when($filters['a_venir'] ?? null, fn ($q) => $q->whereDate('date_evenement', '>=', now()))
            ->when($filters['capacite_limitee'] ?? null, fn ($q) => $q->where('capacite_limitee', true))
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_evenement', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_evenement', '<=', $v))
            ->orderByDesc('date_evenement')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Événements à venir (pour le dashboard et la page d'accueil). */
    public function aVenir(int $limit = 10): Collection
    {
        return $this->activeQuery()
            ->whereDate('date_evenement', '>=', now())
            ->orderBy('date_evenement')
            ->limit($limit)
            ->get();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderByDesc('date_evenement')
            ->get(['id', 'nom', 'date_evenement', 'participation', 'capacite_limitee']);
    }

    public function findAvecParticipations(int $id): Evenement
    {
        return $this->activeQuery()
            ->with([
                'participations.inscription.eleve:id,nom,prenom,matricule',
                'participations.inscription.classe:id,libelle',
                'participations.utilisateur:id,name',
            ])
            ->findOrFail($id);
    }

    public function compterStatistiques(): array
    {
        return [
            'total'             => $this->activeQuery()->count(),
            'a_venir'           => $this->activeQuery()->whereDate('date_evenement', '>=', now())->count(),
            'capacite_limitee'  => $this->activeQuery()->where('capacite_limitee', true)->count(),
        ];
    }
}