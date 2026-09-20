<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Detail;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DetailRepository extends BaseRepository
{
    public function __construct(Detail $model)
    {
        parent::__construct($model);
    }

    /**
     * Historique des lignes de paiement d'un élève (toutes natures
     * confondues) — utilisé par la zone "historique" de l'écran Paiements.
     */
    public function paginateForEleve(int $eleveId, int $anneeId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->whereHas('paiement.inscription', fn ($q) => $q->where('eleve_id', $eleveId))
            ->with(['paiement.utilisateur', 'produit', 'service', 'activite', 'evenement'])
            ->latest('date_paiement')
            ->paginate($perPage);
    }
}