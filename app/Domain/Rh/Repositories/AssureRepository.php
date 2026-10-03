<?php

namespace App\Domain\Rh\Repositories;

use App\Domain\Rh\Models\Assure;
use App\Repositories\BaseRepository;

/**
 * Repository des souscriptions d'assurances du personnel.
 * Étend BaseRepository (etat, annee_id auto, activeQuery, paginate...).
 */
class AssureRepository extends BaseRepository
{
    public function __construct(Assure $model)
    {
        parent::__construct($model);
    }

    /**
     * Liste paginée des assurés avec relations et filtres.
     */
    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $query = $this->activeQuery()
            ->with(['employe', 'assurance']);

        if (!empty($filtres['employe_id'])) {
            $query->where('employe_id', $filtres['employe_id']);
        }

        if (!empty($filtres['assurance_id'])) {
            $query->where('assurance_id', $filtres['assurance_id']);
        }

        if (!empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }

        if (!empty($filtres['recherche'])) {
            $recherche = $filtres['recherche'];
            $query->whereHas('employe', function ($q) use ($recherche) {
                $q->where('nom_prenom', 'like', "%{$recherche}%");
            });
        }

        return $query->latest('date_souscription')->paginate($perPage);
    }

    /**
     * Liste complète pour export Excel.
     */
    public function listePourExport(array $filtres = [])
    {
        $query = $this->activeQuery()
            ->with(['employe', 'assurance']);

        if (!empty($filtres['employe_id'])) {
            $query->where('employe_id', $filtres['employe_id']);
        }
        if (!empty($filtres['assurance_id'])) {
            $query->where('assurance_id', $filtres['assurance_id']);
        }
        if (!empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }

        return $query->orderBy('date_souscription', 'desc')->get();
    }

    /**
     * Vérifie si un employé est déjà assuré (actif) pour une assurance donnée.
     */
    public function dejaAssure(int $employeId, int $assuranceId): bool
    {
        return $this->activeQuery()
            ->where('employe_id', $employeId)
            ->where('assurance_id', $assuranceId)
            ->exists();
    }

    /**
     * Total des prélèvements mensuels (assurés actifs).
     */
    public function totalPrelevementsMensuels(): float
    {
        return (float) $this->activeQuery()
            ->where('statut', 'actif')
            ->sum('prelevement_mensuel');
    }
}