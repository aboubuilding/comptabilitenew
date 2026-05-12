<?php
namespace App\Services;

use App\Repositories\Interfaces\ProduitRepositoryInterface;
use Illuminate\Support\Collection;

class ProduitService extends BaseService
{
    protected string $entityName = 'Produit';
    protected array $defaultSelectFields = [
        'id', 'code', 'libelle', 'categorie', 'type_produit', 'unite_base', 'unite_achat', 'conversion_achat',
        'unite_vente', 'conversion_vente', 'prix_achat', 'prix_vente', 'quantite_stock', 'seuil_alerte',
        'stock_min', 'stock_max', 'etat'
    ];

    public function __construct(ProduitRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste paginée des produits avec gestion des unités
     */
    public function listProduits(array $filters = []): array
    {
        $query = $this->repo->activeQuery();

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        }
        if (!empty($filters['categorie'])) {
            $query->where('categorie', $filters['categorie']);
        }
        if (isset($filters['type_produit']) && $filters['type_produit'] !== '') {
            $query->where('type_produit', $filters['type_produit']);
        }
        if (isset($filters['stock_bas']) && $filters['stock_bas'] == 1) {
            $query->whereRaw('quantite_stock <= seuil_alerte');
        }

        $perPage = $filters['per_page'] ?? 15;
        $produits = $query->orderBy('libelle')->paginate($perPage);

        $data = $produits->map(function ($p) {
            return [
                'id'               => $p->id,
                'code'             => $p->code,
                'libelle'          => $p->libelle,
                'categorie'        => $p->categorie,
                'prix_vente'       => $p->prix_vente,
                'prix_achat'       => $p->prix_achat,
                'unite_base'       => $p->unite_base,
                'unite_vente'      => $p->unite_vente ?? $p->unite_base,
                'conversion_vente' => $p->conversion_vente,
                'unite_achat'      => $p->unite_achat,
                'conversion_achat' => $p->conversion_achat,
                'quantite_stock'   => $p->quantite_stock,
                'stock_vente'      => $p->getStockVente(),
                'stock_achat'      => $p->getStockAchat(),
                'seuil_alerte'     => $p->seuil_alerte,
                'stock_bas'        => $p->isStockBas(),
                'rupture'          => $p->isRupture(),
                'type_produit'     => $p->type_produit,
            ];
        });

        $aggregates = [
            'total_produits'   => $produits->total(),
            'valeur_stock'     => $produits->sum(fn($p) => $p->quantite_stock * $p->prix_vente),
            'produits_stock_bas' => (clone $query)->whereRaw('quantite_stock <= seuil_alerte')->count(),
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
     * Détail d'un produit (complet)
     */
    public function getProduit(int $id): array
    {
        $produit = $this->repo->findOrFail($id);
        return [
            'id'               => $produit->id,
            'code'             => $produit->code,
            'libelle'          => $produit->libelle,
            'categorie'        => $produit->categorie,
            'description'      => $produit->description,
            'photo'            => $produit->photo,
            'type_produit'     => $produit->type_produit,
            'unite_base'       => $produit->unite_base,
            'unite_achat'      => $produit->unite_achat,
            'conversion_achat' => $produit->conversion_achat,
            'unite_vente'      => $produit->unite_vente ?? $produit->unite_base,
            'conversion_vente' => $produit->conversion_vente,
            'prix_achat'       => $produit->prix_achat,
            'prix_vente'       => $produit->prix_vente,
            'quantite_stock'   => $produit->quantite_stock,
            'stock_vente'      => $produit->getStockVente(),
            'stock_achat'      => $produit->getStockAchat(),
            'seuil_alerte'     => $produit->seuil_alerte,
            'stock_min'        => $produit->stock_min,
            'stock_max'        => $produit->stock_max,
            'etat'             => $produit->etat,
        ];
    }

    /**
     * Créer un produit
     */
    public function createProduit(array $data): \App\Models\Produit
    {
        $data['conversion_achat'] = $data['conversion_achat'] ?? 1;
        $data['conversion_vente'] = $data['conversion_vente'] ?? 1;
        if (empty($data['unite_vente'])) {
            $data['unite_vente'] = $data['unite_base'];
        }
        $data['etat'] = $data['etat'] ?? 1;
        $data['quantite_stock'] = $data['quantite_stock'] ?? 0;

        return $this->repo->create($data);
    }

    /**
     * Mettre à jour un produit
     */
    public function updateProduit(int $id, array $data): \App\Models\Produit
    {
        $this->repo->update($id, $data);
        return $this->repo->find($id);
    }

    /**
     * Supprimer (désactiver) un produit
     */
    public function deleteProduit(int $id): void
    {
        $this->repo->update($id, ['etat' => 0]);
    }

    /**
     * Liste pour selects (dropdown)
     */
    public function getForSelect(): Collection
    {
        return $this->repo->activeQuery()
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'prix_vente', 'unite_vente', 'quantite_stock'])
            ->map(fn($p) => [
                'id'    => $p->id,
                'libelle' => $p->libelle . ' (' . number_format($p->prix_vente, 0) . ' FCFA) - Stock: ' . $p->getStockVente() . ' ' . ($p->unite_vente ?? $p->unite_base),
                'prix_vente' => $p->prix_vente,
                'stock' => $p->getStockVente(),
                'unite' => $p->unite_vente ?? $p->unite_base,
            ]);
    }
}
