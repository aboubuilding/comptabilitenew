<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\StockActuel;
use App\Repositories\BaseRepository;

class StockActuelRepository extends BaseRepository
{
    public function __construct(StockActuel $model)
    {
        parent::__construct($model);
    }

    public function pourProduitEtMagasin(int $produitId, int $magasinId): ?StockActuel
    {
        return $this->activeQuery()
            ->where('produit_id', $produitId)
            ->where('magasin_id', $magasinId)
            ->first();
    }
}