<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Ligne;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LigneRepository extends BaseRepository
{
    public function __construct(Ligne $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['chauffeur:id,nom,prenom,telephone', 'voiture:id,plaque,marque,modele', 'zones:id,libelle,ligne_id'])
            ->withCount(['abonnements' => fn ($q) => $q->where('etat', 1)->where('statut', 1)])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(?int $anneeId = null): Collection
    {
        return $this->activeQuery()
            ->with(['chauffeur:id,nom,prenom', 'voiture:id,plaque'])
            ->when($anneeId, fn ($q) => $q->where('annee_id', $anneeId))
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'chauffeur_id', 'voiture_id']);
    }
}
