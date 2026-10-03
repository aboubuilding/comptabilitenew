<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Produit;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProduitRepository extends BaseRepository
{
    public function __construct(Produit $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['stocksActuels'])
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('libelle', 'like', "%{$v}%");
                });
            })
            ->when($filters['type_produit'] ?? null, fn ($q, $v) => $q->where('type_produit', $v))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'unite_stock', 'prix_unitaire']);
    }

    public function findAvecStocks(int $id): Produit
    {
        return $this->activeQuery()
            ->with([
                'stocksActuels.magasin:id,libelle,type',
            ])
            ->findOrFail($id);
    }

    /**
     * Produits en alerte de stock : quantité totale (tous magasins) <=
     * seuil d'alerte du stock_actuel (un seuil par magasin).
     */
    public function enAlerte(): Collection
    {
        return $this->activeQuery()
            ->with(['stocksActuels.magasin:id,libelle'])
            ->whereHas('stocksActuels', function ($q) {
                $q->whereColumn('stock_actuel.quantite', '<=', 'stock_actuel.seuil_alerte')
                  ->whereNotNull('stock_actuel.seuil_alerte');
            })
            ->orderBy('libelle')
            ->get();
    }

    public function compterStatistiques(): array
    {
        $total = $this->activeQuery()->count();

        $enAlerte = $this->activeQuery()
            ->whereHas('stocksActuels', function ($q) {
                $q->whereColumn('stock_actuel.quantite', '<=', 'stock_actuel.seuil_alerte')
                  ->whereNotNull('stock_actuel.seuil_alerte');
            })
            ->count();

        $enRupture = $this->activeQuery()
            ->whereDoesntHave('stocksActuels', fn ($q) => $q->where('quantite', '>', 0))
            ->count();

        $valeurTotale = round((float) DB::table('stock_actuel')
            ->where('etat', 1)
            ->sum('valeur_stock'), 2);

        return [
            'total'         => $total,
            'en_alerte'     => $enAlerte,
            'en_rupture'    => $enRupture,
            'valeur_totale' => $valeurTotale,
        ];
    }
}