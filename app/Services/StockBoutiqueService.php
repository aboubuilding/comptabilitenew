<?php
namespace App\Services;

use App\Repositories\Interfaces\StockActuelRepositoryInterface;
use App\Repositories\Interfaces\ProduitRepositoryInterface;
use App\Repositories\Interfaces\MagasinRepositoryInterface;
use App\Repositories\Interfaces\MouvementStockRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StockBoutiqueService extends BaseService
{
    protected string $entityName = 'Stock';
    protected ProduitRepositoryInterface $produitRepo;
    protected MagasinRepositoryInterface $magasinRepo;
    protected MouvementStockRepositoryInterface $mouvementRepo;

    public function __construct(
        StockActuelRepositoryInterface $stockRepo,
        ProduitRepositoryInterface $produitRepo,
        MagasinRepositoryInterface $magasinRepo,
        MouvementStockRepositoryInterface $mouvementRepo
    ) {
        parent::__construct($stockRepo);
        $this->produitRepo = $produitRepo;
        $this->magasinRepo = $magasinRepo;
        $this->mouvementRepo = $mouvementRepo;
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste du stock actuel pour toutes les boutiques (ou une boutique spécifique)
     * Avec filtres : magasin_id, produit_id, stock_bas, rupture, recherche
     */
    public function listStock(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();

        $query = $this->repo->activeQuery()
            ->with(['produit', 'magasin'])
            ->whereHas('magasin', function ($q) {
                $q->where('type', 2);
            })
            ->where('annee_id', $anneeId);

        if (!empty($filters['magasin_id'])) {
            $query->where('magasin_id', $filters['magasin_id']);
        }
        if (!empty($filters['produit_id'])) {
            $query->where('produit_id', $filters['produit_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('produit', fn($q) => $q->where('libelle', 'like', $search));
        }
        if (isset($filters['stock_bas']) && $filters['stock_bas'] == 1) {
            $query->whereColumn('quantite', '<=', 'seuil_alerte');
        }
        if (isset($filters['rupture']) && $filters['rupture'] == 1) {
            $query->where('quantite', '<=', 0);
        }

        $perPage = $filters['per_page'] ?? 15;
        $stocks = $query->orderBy('magasin_id')->orderBy('produit_id')->paginate($perPage);

        $data = $stocks->map(function ($stock) {
            return [
                'id'             => $stock->id,
                'produit'        => $stock->produit->libelle,
                'produit_id'     => $stock->produit_id,
                'magasin'        => $stock->magasin->libelle,
                'magasin_id'     => $stock->magasin_id,
                'quantite'       => $stock->quantite,
                'seuil_alerte'   => $stock->seuil_alerte,
                'stock_bas'      => $stock->quantite <= $stock->seuil_alerte,
                'rupture'        => $stock->quantite <= 0,
                'valeur_stock'   => $stock->quantite * ($stock->produit->prix_unitaire ?? 0),
                'unite'          => $stock->produit->unite_base,
            ];
        });

        $aggregates = [
            'total_valeur'          => $stocks->sum(fn($s) => $s->quantite * ($s->produit->prix_unitaire ?? 0)),
            'total_produits_en_stock' => $stocks->filter(fn($s) => $s->quantite > 0)->count(),
            'produits_stock_bas'    => $stocks->filter(fn($s) => $s->quantite <= $s->seuil_alerte)->count(),
        ];

        return [
            'data'       => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $stocks->currentPage(),
                'last_page'    => $stocks->lastPage(),
                'per_page'     => $stocks->perPage(),
                'total'        => $stocks->total(),
            ],
        ];
    }

    /**
     * Détail du stock pour un produit dans une boutique
     * + historique des mouvements
     */
    public function getStockDetail(int $produitId, int $magasinId): array
    {
        $produit = $this->produitRepo->findOrFail($produitId);
        $magasin = $this->magasinRepo->findOrFail($magasinId);

        $stock = $this->repo->activeQuery()
            ->where('produit_id', $produitId)
            ->where('magasin_id', $magasinId)
            ->first();

        $mouvements = $this->mouvementRepo->activeQuery()
            ->with('utilisateur')
            ->where('produit_id', $produitId)
            ->where('magasin_id', $magasinId)
            ->orderBy('date_mouvement', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'id'            => $m->id,
                'type'          => $m->type,
                'type_label'    => $this->getTypeLabel($m->type),
                'quantite'      => $m->quantite,
                'motif'         => $m->motif,
                'reference_id'  => $m->reference_id,
                'utilisateur'   => $m->utilisateur?->name,
                'date_mouvement'=> $m->date_mouvement->format('d/m/Y H:i'),
            ]);

        return [
            'produit' => [
                'id'            => $produit->id,
                'libelle'       => $produit->libelle,
                'prix_unitaire' => $produit->prix_unitaire,
                'unite_stock'   => $produit->unite_base,
            ],
            'magasin' => [
                'id'      => $magasin->id,
                'libelle' => $magasin->libelle,
            ],
            'stock' => [
                'quantite'     => $stock?->quantite ?? 0,
                'seuil_alerte' => $stock?->seuil_alerte,
                'valeur'       => ($stock?->quantite ?? 0) * ($produit->prix_unitaire ?? 0),
                'seuil_depasse'=> ($stock?->quantite ?? 0) <= ($stock?->seuil_alerte ?? 0),
            ],
            'mouvements' => $mouvements,
        ];
    }

    /**
     * Ajustement manuel du stock (inventaire)
     * Calcule l'écart entre la quantité théorique (stock actuel) et la quantité réelle constatée.
     * Crée un mouvement de type 'ajustement' et met à jour stock_actuel.
     */
    public function ajusterInventaire(int $produitId, int $magasinId, float $quantiteReelle, string $motif): void
    {
        $stockModel = $this->repo->getModel();
        $stock = $stockModel::firstOrNew([
            'produit_id' => $produitId,
            'magasin_id' => $magasinId,
        ]);
        $ancienne = $stock->quantite ?? 0;
        $difference = $quantiteReelle - $ancienne;

        if (abs($difference) < 0.0001) {
            return;
        }

        DB::transaction(function () use ($produitId, $magasinId, $quantiteReelle, $motif, $difference, $stockModel) {
            // Mettre à jour stock_actuel
            $stockModel::updateOrCreate(
                ['produit_id' => $produitId, 'magasin_id' => $magasinId],
                ['quantite' => $quantiteReelle]
            );

            // Enregistrer le mouvement d'ajustement
            $this->mouvementRepo->create([
                'produit_id'     => $produitId,
                'magasin_id'     => $magasinId,
                'type'           => 'ajustement',
                'quantite'       => abs($difference),
                'motif'          => $motif . " (écart: " . ($difference > 0 ? '+' : '') . $difference . ")",
                'reference_id'   => null,
                'utilisateur_id' => auth()->id(),
                'date_mouvement' => now(),
            ]);
        });
    }

    /**
     * Alerte stock bas pour toutes les boutiques
     */
    public function alertesStockBas(): array
    {
        $alertes = $this->repo->activeQuery()
            ->with(['produit', 'magasin'])
            ->whereHas('magasin', fn($q) => $q->where('type', 2))
            ->whereColumn('quantite', '<=', 'seuil_alerte')
            ->where('seuil_alerte', '>', 0)
            ->get()
            ->map(fn($s) => [
                'produit'      => $s->produit->libelle,
                'magasin'      => $s->magasin->libelle,
                'stock_actuel' => $s->quantite,
                'seuil'        => $s->seuil_alerte,
                'unite'        => $s->produit->unite_base,
            ]);

        return [
            'total_alertes' => $alertes->count(),
            'alertes'       => $alertes,
        ];
    }

    /**
     * Rapport d'inventaire complet pour une boutique
     */
    public function rapportInventaire(int $magasinId): array
    {
        $magasin = $this->magasinRepo->findOrFail($magasinId);
        $stocks = $this->repo->activeQuery()
            ->with('produit')
            ->where('magasin_id', $magasinId)
            ->where('quantite', '>', 0)
            ->get();

        $totalValeur = $stocks->sum(fn($s) => $s->quantite * ($s->produit->prix_unitaire ?? 0));
        $coutRemplacement = $stocks->sum(fn($s) => $s->quantite * ($s->produit->prix_achat ?? 0));

        return [
            'magasin'           => $magasin->libelle,
            'date_rapport'      => now()->format('Y-m-d H:i'),
            'nombre_references' => $stocks->count(),
            'valeur_totale'     => $totalValeur,
            'cout_remplacement' => $coutRemplacement,
            'details'           => $stocks->map(fn($s) => [
                'produit'        => $s->produit->libelle,
                'quantite'       => $s->quantite,
                'prix_unitaire'  => $s->produit->prix_unitaire,
                'valeur'         => $s->quantite * ($s->produit->prix_unitaire ?? 0),
                'seuil'          => $s->seuil_alerte,
                'unite'          => $s->produit->unite_base,
            ]),
        ];
    }

    /**
     * Mouvements de stock sur une période, filtrés par boutique et/ou produit
     */
    public function mouvementsPeriode(array $filters = []): array
    {
        $query = $this->mouvementRepo->activeQuery()
            ->with(['produit', 'magasin', 'utilisateur'])
            ->whereHas('magasin', fn($q) => $q->where('type', 2));

        if (!empty($filters['magasin_id'])) {
            $query->where('magasin_id', $filters['magasin_id']);
        }
        if (!empty($filters['produit_id'])) {
            $query->where('produit_id', $filters['produit_id']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_mouvement', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_mouvement', '<=', $filters['date_fin']);
        }

        $perPage = $filters['per_page'] ?? 20;
        $mouvements = $query->orderBy('date_mouvement', 'desc')->paginate($perPage);

        $data = $mouvements->map(fn($m) => [
            'id'         => $m->id,
            'produit'    => $m->produit?->libelle,
            'magasin'    => $m->magasin?->libelle,
            'type'       => $m->type,
            'quantite'   => $m->quantite,
            'motif'      => $m->motif,
            'utilisateur'=> $m->utilisateur?->name,
            'date'       => $m->date_mouvement->format('d/m/Y H:i'),
        ]);

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $mouvements->currentPage(),
                'last_page'    => $mouvements->lastPage(),
                'per_page'     => $mouvements->perPage(),
                'total'        => $mouvements->total(),
            ],
        ];
    }

    private function getTypeLabel(string $type): string
    {
        return match($type) {
            'entree'     => 'Entrée',
            'sortie'     => 'Sortie',
            'transfert'  => 'Transfert',
            'ajustement' => 'Ajustement inventaire',
            default      => $type,
        };
    }
}
