<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Produit;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class ProduitRepository extends BaseRepository
{
    public function __construct(Produit $model)
    {
        parent::__construct($model);
    }

    /**
     * Recherche pour le panier (écran Paiements) : uniquement les
     * produits ayant du stock disponible.
     */
    public function rechercherEnStock(string $terme, int $limite = 10): Collection
    {
        return $this->activeQuery()
            ->enStock()
            ->where('libelle', 'like', "%{$terme}%")
            ->limit($limite)
            ->get();
    }
}