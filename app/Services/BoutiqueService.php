<?php
namespace App\Services;

use App\Repositories\Eloquent\MagasinRepository;
use App\Repositories\Eloquent\VenteRepository;
use App\Repositories\Eloquent\StockActuelRepository;
use Illuminate\Support\Facades\DB;

class BoutiqueService extends BaseService
{
    protected string $entityName = 'Boutique';
    protected MagasinRepository $magasinRepo;
    protected VenteRepository $venteRepo;
    protected StockActuelRepository $stockRepo;

    public function __construct(
        MagasinRepository $magasinRepo,
        VenteRepository $venteRepo,
        StockActuelRepository $stockRepo
    ) {
        parent::__construct($magasinRepo);
        $this->magasinRepo = $magasinRepo;
        $this->venteRepo = $venteRepo;
        $this->stockRepo = $stockRepo;
    }

    /**
     * Liste des boutiques (type=2) avec pagination et filtres
     */
    public function listBoutiques(array $filters = []): array
    {
        $query = $this->magasinRepo->activeQuery()
            ->where('type', 2);

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

        return $this->formatResponse(true, 'Liste des boutiques', $data, [
            'pagination' => [
                'current_page' => $boutiques->currentPage(),
                'last_page'    => $boutiques->lastPage(),
                'per_page'     => $boutiques->perPage(),
                'total'        => $boutiques->total(),
            ]
        ]);
    }

    /**
     * Détail d’une boutique (stock actuel + ventes)
     */
    public function getBoutiqueDetail(int $id, array $filters = []): array
    {
        // Récupérer la boutique
        $boutique = $this->magasinRepo->activeQuery()
            ->where('type', 2)
            ->find($id);

        if (!$boutique) {
            return $this->formatResponse(false, 'Boutique introuvable');
        }

        // Stock actuel
        $stocks = $this->stockRepo->activeQuery()
            ->with('produit')
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

        // Ventes
        $query = $this->venteRepo->activeQuery()
            ->with('produit')
            ->where('magasin_id', $id);

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

        $aggs = [
            'total_ventes' => (clone $query)->count(),
            'montant_total'=> (clone $query)->sum(DB::raw('quantite * prix_unitaire')),
            'periodes' => [
                'date_debut' => $filters['date_debut'] ?? null,
                'date_fin'   => $filters['date_fin'] ?? null,
            ],
        ];

        $detail = [
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

        return $this->formatResponse(true, 'Détail de la boutique', $detail);
    }

    /**
     * Crée une boutique (type forcé à 2)
     */
    public function store(array $validatedData): array
    {
        $validatedData['type'] = 2;
        $validatedData['etat'] = $validatedData['etat'] ?? 1;
        return parent::store($validatedData);
    }

    /**
     * Met à jour une boutique
     */
    public function update(int $id, array $validatedData): array
    {
        return parent::update($id, $validatedData);
    }

    /**
     * Supprime une boutique (soft delete) avec vérification des ventes associées
     */
    public function destroy(int $id): array
    {
        // Vérifier si des ventes existent
        $hasVentes = $this->venteRepo->activeQuery()
            ->where('magasin_id', $id)
            ->exists();
        if ($hasVentes) {
            return $this->formatResponse(false, 'Impossible de supprimer : des ventes sont associées à cette boutique.');
        }
        return parent::destroy($id);
    }

    /**
     * Récupère les boutiques pour les selects (type=2)
     */
    public function getForSelect(array $filters = [], string $labelField = 'libelle', string $valueField = 'id'): array
    {
        $query = $this->magasinRepo->activeQuery()
            ->where('type', 2)
            ->select($valueField, $labelField);
        if (!empty($filters['search'])) {
            $query->where('libelle', 'like', '%' . $filters['search'] . '%');
        }
        $items = $query->orderBy($labelField)->get()
            ->map(fn($item) => ['value' => $item->$valueField, 'label' => $item->$labelField]);
        return $this->formatResponse(true, '', $items);
    }
}
