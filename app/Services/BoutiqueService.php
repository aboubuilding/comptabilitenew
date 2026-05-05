<?php
namespace App\Services;

use App\Models\Magasin;
use App\Models\Vente;
use App\Models\StockActuel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BoutiqueService
{
    // Liste des boutiques (type=2) avec pagination
    public function listBoutiques(array $filters = []): array
    {
        $query = Magasin::where('type', 2)->where('etat', 1);

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('libelle', 'like', $search)
                  ->orWhere('responsable', 'like', $search);
            });
        }
        $perPage = $filters['per_page'] ?? 15;
        $boutiques = $query->orderBy('libelle')->paginate($perPage);

        $data = $boutiques->map(fn($b) => [
            'id'          => $b->id,
            'libelle'     => $b->libelle,
            'responsable' => $b->responsable,
            'adresse'     => $b->adresse,
            'telephone'   => $b->telephone,
            'etat'        => $b->etat,
            'created_at'  => $b->created_at->format('d/m/Y'),
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $boutiques->currentPage(),
                'last_page'    => $boutiques->lastPage(),
                'per_page'     => $boutiques->perPage(),
                'total'        => $boutiques->total(),
            ]
        ];
    }

    // Détail d’une boutique (stock actuel + ventes)
    public function getBoutiqueDetail(int $id, array $filters = []): array
    {
        $boutique = Magasin::where('id', $id)->where('type', 2)->firstOrFail();

        // Stock actuel des produits dans cette boutique
        $stocks = StockActuel::with('produit')
            ->where('magasin_id', $id)
            ->where('quantite', '>', 0)
            ->get()
            ->map(fn($s) => [
                'produit_id'   => $s->produit_id,
                'produit'      => $s->produit->libelle,
                'quantite'     => $s->quantite,
                'unite'        => $s->produit->unite_vente ?? $s->produit->unite_base,
                'seuil_alerte' => $s->seuil_alerte,
            ]);

        // Ventes filtrées par dates
        $query = Vente::with('produit')
            ->where('magasin_id', $id)
            ->where('etat', 1);

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_vente', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_vente', '<=', $filters['date_fin']);
        }
        if (!empty($filters['produit_id'])) {
            $query->where('produit_id', $filters['produit_id']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $ventes = $query->orderBy('date_vente', 'desc')->paginate($perPage);

        $ventesData = $ventes->map(fn($v) => [
            'id'           => $v->id,
            'date_vente'   => $v->date_vente->format('d/m/Y'),
            'produit'      => $v->produit->libelle,
            'quantite'     => $v->quantite,
            'unite'        => $v->unite,
            'prix_unitaire'=> $v->prix_unitaire,
            'montant_total'=> $v->quantite * $v->prix_unitaire,
            'client'       => $v->inscription?->eleve?->nom ?? ($v->paiement?->payeur ?? ''),
        ]);

        // Agrégats des ventes
        $aggs = [
            'total_ventes' => (clone $query)->count(),
            'montant_total'=> (clone $query)->sum(DB::raw('quantite * prix_unitaire')),
            'periodes' => compact('filters.date_debut', 'filters.date_fin'),
        ];

        return [
            'boutique' => [
                'id'          => $boutique->id,
                'libelle'     => $boutique->libelle,
                'responsable' => $boutique->responsable,
                'adresse'     => $boutique->adresse,
                'telephone'   => $boutique->telephone,
                'etat'        => $boutique->etat,
            ],
            'stock_actuel' => $stocks,
            'ventes' => $ventesData,
            'ventes_aggregates' => $aggs,
            'pagination_ventes' => [
                'current_page' => $ventes->currentPage(),
                'last_page'    => $ventes->lastPage(),
                'per_page'     => $ventes->perPage(),
                'total'        => $ventes->total(),
            ]
        ];
    }

    // CRUD classique
    public function createMagasin(array $data): Magasin
    {
        $data['type'] = 2; // forcé boutique
        $data['etat'] = $data['etat'] ?? 1;
        return Magasin::create($data);
    }

    public function updateMagasin(int $id, array $data): Magasin
    {
        $magasin = Magasin::findOrFail($id);
        $magasin->update($data);
        return $magasin;
    }

    public function deleteMagasin(int $id): void
    {
        $magasin = Magasin::findOrFail($id);
        // Vérifier si des ventes existent
        if (Vente::where('magasin_id', $id)->exists()) {
            throw new \Exception("Impossible de supprimer : des ventes sont associées à cette boutique.");
        }
        $magasin->deleted_at = now(); // soft delete
        $magasin->etat = 0;
        $magasin->save();
    }
}