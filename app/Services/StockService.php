<?php
namespace App\Services;

use App\Models\Produit;
use App\Models\StockMouvement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockService
{
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des produits avec stock actuel, seuil, indicateur de rupture/stock bas
     */
    public function listStock(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = Produit::where('etat', 1)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId)) // si annee_id dans produits
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $q->where('libelle', 'like', $search);
            })
            ->when(isset($filters['type_produit']) && $filters['type_produit'] !== '', fn($q) => $q->where('type_produit', $filters['type_produit']))
            ->when(isset($filters['stock_bas']) && $filters['stock_bas'] == 1, fn($q) => $q->whereRaw('quantite_stock <= seuil_alerte'));

        $perPage = $filters['per_page'] ?? 15;
        $produits = $query->orderBy('libelle')->paginate($perPage);

        $data = $produits->map(fn($p) => [
            'id'               => $p->id,
            'libelle'          => $p->libelle,
            'prix_unitaire'    => $p->prix_unitaire,
            'unite_stock'      => $p->unite_stock,
            'quantite_stock'   => $p->quantite_stock,
            'seuil_alerte'     => $p->seuil_alerte,
            'stock_bas'        => $p->isStockBas(),
            'rupture'          => $p->isRupture(),
            'type_produit'     => $p->type_produit,
            'etat'             => $p->etat,
        ]);

        $aggregates = [
            'total_produits'   => $produits->total(),
            'produits_stock_bas' => (clone $query)->whereRaw('quantite_stock <= seuil_alerte')->count(),
            'produits_rupture'    => (clone $query)->where('quantite_stock', '<=', 0)->count(),
        ];

        return [
            'data'       => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $produits->currentPage(),
                'last_page'    => $produits->lastPage(),
                'per_page'     => $produits->perPage(),
                'total'        => $produits->total(),
            ]
        ];
    }

    /**
     * Détail d’un produit avec historique des mouvements
     */
    public function getStockDetail(int $produitId): array
    {
        $produit = Produit::findOrFail($produitId);
        $mouvements = StockMouvement::with('utilisateur')
            ->where('produit_id', $produitId)
            ->orderBy('date_mouvement', 'desc')
            ->get()
            ->map(fn($m) => [
                'id'            => $m->id,
                'type'          => $m->type,
                'quantite'      => $m->quantite,
                'motif'         => $m->motif,
                'reference_id'  => $m->reference_id,
                'utilisateur'   => $m->utilisateur?->name,
                'date_mouvement'=> $m->date_mouvement->format('d/m/Y H:i'),
            ]);

        return [
            'produit'    => [
                'id'             => $produit->id,
                'libelle'        => $produit->libelle,
                'prix_unitaire'  => $produit->prix_unitaire,
                'unite_stock'    => $produit->unite_stock,
                'quantite_stock' => $produit->quantite_stock,
                'seuil_alerte'   => $produit->seuil_alerte,
                'stock_min'      => $produit->stock_min,
                'stock_max'      => $produit->stock_max,
            ],
            'mouvements' => $mouvements,
        ];
    }

    /**
     * Statistiques globales (pour dashboard)
     */
    public function getStats(): array
    {
        $anneeId = $this->getCurrentAnneeId();
        // Valeur totale du stock (quantité * prix_unitaire)
        $valeurStock = Produit::where('etat', 1)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->get()
            ->sum(fn($p) => $p->quantite_stock * $p->prix_unitaire);

        // Entrées sur les 30 derniers jours
        $entrees30j = StockMouvement::where('type', 'entree')
            ->where('date_mouvement', '>=', now()->subDays(30))
            ->sum('quantite');
        $sorties30j = StockMouvement::where('type', 'sortie')
            ->where('date_mouvement', '>=', now()->subDays(30))
            ->sum('quantite');

        $produitsBas = Produit::where('etat', 1)
            ->whereRaw('quantite_stock <= seuil_alerte')
            ->count();

        return [
            'valeur_stock'       => $valeurStock,
            'entrees_30j'        => $entrees30j,
            'sorties_30j'        => $sorties30j,
            'produits_stock_bas' => $produitsBas,
        ];
    }

    /**
     * Mouvements de stock pour une période donnée
     */
    public function getMouvementsPeriode(?string $dateDebut, ?string $dateFin, array $filters = []): array
    {
        $query = StockMouvement::with('produit', 'utilisateur')
            ->when($dateDebut, fn($q) => $q->whereDate('date_mouvement', '>=', $dateDebut))
            ->when($dateFin, fn($q) => $q->whereDate('date_mouvement', '<=', $dateFin))
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['produit_id']), fn($q) => $q->where('produit_id', $filters['produit_id']));

        $perPage = $filters['per_page'] ?? 20;
        $mouvements = $query->orderBy('date_mouvement', 'desc')->paginate($perPage);

        $data = $mouvements->map(fn($m) => [
            'id'            => $m->id,
            'produit'       => $m->produit?->libelle,
            'type'          => $m->type,
            'quantite'      => $m->quantite,
            'motif'         => $m->motif,
            'utilisateur'   => $m->utilisateur?->name,
            'date_mouvement'=> $m->date_mouvement->format('d/m/Y H:i'),
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $mouvements->currentPage(),
                'last_page'    => $mouvements->lastPage(),
                'per_page'     => $mouvements->perPage(),
                'total'        => $mouvements->total(),
            ]
        ];
    }
}