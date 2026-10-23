<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Zone;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ZoneRepository extends BaseRepository
{
    public function __construct(Zone $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['ligne:id,libelle,chauffeur_id,voiture_id', 'ligne.chauffeur:id,nom,prenom', 'ligne.voiture:id,plaque'])
            ->withCount(['abonnements' => fn ($q) => $q->where('etat', 1)->where('statut', 1)])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['ligne_id'] ?? null, fn ($q, $v) => $q->where('ligne_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Zones proposées à la souscription, avec leur tarif indicatif et leur ligne. */
    public function listePourSelect(?int $anneeId = null): Collection
    {
        return $this->activeQuery()
            ->with('ligne:id,libelle')
            ->when($anneeId, fn ($q) => $q->where('annee_id', $anneeId))
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'tarif_base', 'ligne_id']);
    }
}
