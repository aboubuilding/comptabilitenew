<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\Evenement;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class EvenementRepository extends BaseRepository
{
    public function __construct(Evenement $model)
    {
        parent::__construct($model);
    }

    /**
     * Recherche pour le panier : uniquement les événements à capacité
     * illimitée (cahier des charges — "un événement sans capacité
     * limitée"), les autres passent par l'écran de pré-inscription dédié.
     */
    public function rechercherSansLimite(string $terme, int $limite = 10): Collection
    {
        return $this->activeQuery()
            ->capaciteIllimitee()
            ->where('nom', 'like', "%{$terme}%")
            ->limit($limite)
            ->get();
    }
}