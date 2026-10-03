<?php

namespace App\Domain\Rh\Repositories;

use App\Domain\Rh\Models\Asurance;
use App\Repositories\BaseRepository;

/**
 * Repository du catalogue des assurances.
 * Étend BaseRepository (etat, annee_id auto, activeQuery, paginate...).
 */
class AsuranceRepository extends BaseRepository
{
    public function __construct(Asurance $model)
    {
        parent::__construct($model);
    }

    /**
     * Liste paginée des assurances avec le nombre d'assurés rattachés.
     */
    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $query = $this->activeQuery()
            ->withCount(['assures' => function ($q) {
                $q->where('etat', 1);
            }]);

        if (!empty($filtres['recherche'])) {
            $recherche = $filtres['recherche'];
            $query->where(function ($q) use ($recherche) {
                $q->where('nom_assureur', 'like', "%{$recherche}%")
                  ->orWhere('police_assurance', 'like', "%{$recherche}%");
            });
        }

        return $query->orderBy('nom_assureur')->paginate($perPage);
    }

    /**
     * Liste complète pour export Excel.
     */
    public function listePourExport(array $filtres = [])
    {
        $query = $this->activeQuery()
            ->withCount(['assures' => function ($q) {
                $q->where('etat', 1);
            }]);

        if (!empty($filtres['recherche'])) {
            $recherche = $filtres['recherche'];
            $query->where(function ($q) use ($recherche) {
                $q->where('nom_assureur', 'like', "%{$recherche}%")
                  ->orWhere('police_assurance', 'like', "%{$recherche}%");
            });
        }

        return $query->orderBy('nom_assureur')->get();
    }

    /**
     * Vérifie qu'une assurance n'a aucun assuré actif avant désactivation.
     */
    public function aDesAssuresActifs(int $asuranceId): bool
    {
        return $this->activeQuery()
            ->where('id', $asuranceId)
            ->whereHas('assures', fn ($q) => $q->where('etat', 1))
            ->exists();
    }
}