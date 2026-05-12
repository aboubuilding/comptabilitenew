<?php
namespace App\Services;

use App\Repositories\Interfaces\MenuRepositoryInterface;
use App\Repositories\Interfaces\ProduitRepositoryInterface;
use App\Repositories\Interfaces\PreparationRepasRepositoryInterface;
use App\Repositories\Interfaces\InscriptionCantineRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class GestionRepasService extends BaseService
{
    protected ProduitRepositoryInterface $produitRepo;
    protected PreparationRepasRepositoryInterface $preparationRepo;
    protected InscriptionCantineRepositoryInterface $cantineRepo;

    // Propriétés pour BaseService
    protected string $entityName = 'Menu';
    protected array $defaultSelectFields = ['id', 'libelle', 'date_service', 'type_repas', 'quantite_prevue', 'cout_total_prevu', 'etat'];

    public function __construct(
        MenuRepositoryInterface $menuRepo,
        ProduitRepositoryInterface $produitRepo,
        PreparationRepasRepositoryInterface $preparationRepo,
        InscriptionCantineRepositoryInterface $cantineRepo
    ) {
        parent::__construct($menuRepo);
        $this->produitRepo = $produitRepo;
        $this->preparationRepo = $preparationRepo;
        $this->cantineRepo = $cantineRepo;
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des menus avec coût moyen par part
     */
    public function listMenus(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $query = $this->repo->getModel()->newQuery()
            ->with('produits')
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!empty($filters['type_repas']), fn($q) => $q->where('type_repas', $filters['type_repas']))
            ->when(!empty($filters['date_service']), fn($q) => $q->whereDate('date_service', $filters['date_service']))
            ->when(!empty($filters['search']), fn($q) => $q->where('libelle', 'like', '%' . $filters['search'] . '%'));

        $perPage = $filters['per_page'] ?? 15;
        $menus = $query->orderBy('date_service', 'desc')->paginate($perPage);

        $data = $menus->map(fn($menu) => [
            'id'                => $menu->id,
            'libelle'           => $menu->libelle,
            'date_service'      => $menu->date_service,
            'type_repas'        => $menu->type_repas,
            'quantite_prevue'   => $menu->quantite_prevue,
            'quantite_reel'     => $menu->quantite_reellement,
            'cout_total_prevu'  => $menu->cout_total_prevu,
            'cout_total_reel'   => $menu->cout_total_reel,
            'cout_par_part'     => $menu->cout_par_part,
            'produits'          => $menu->produits->map(fn($p) => [
                'libelle' => $p->libelle,
                'quantite' => $p->pivot->quantite,
                'cout' => $p->pivot->cout_total
            ])
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $menus->currentPage(),
                'last_page'    => $menus->lastPage(),
                'per_page'     => $menus->perPage(),
                'total'        => $menus->total(),
            ]
        ];
    }

    /**
     * Créer un menu avec ses produits
     */
    public function createMenu(array $data, array $produits)
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) throw new \Exception('Année non définie');

        return DB::transaction(function () use ($data, $produits, $anneeId) {
            $total = 0;
            $syncData = [];
            foreach ($produits as $item) {
                $produit = $this->produitRepo->find($item['produit_id']);
                if (!$produit) throw new \Exception("Produit introuvable");
                $coutUnitaire = $produit->prix_unitaire;
                $coutTotal = $coutUnitaire * $item['quantite'];
                $syncData[$item['produit_id']] = [
                    'quantite' => $item['quantite'],
                    'cout_unitaire' => $coutUnitaire,
                    'cout_total' => $coutTotal
                ];
                $total += $coutTotal;
            }

            $data['annee_id'] = $anneeId;
            $data['cout_total_prevu'] = $total;
            $data['etat'] = 1;

            $menu = $this->repo->create($data);
            $menu->produits()->attach($syncData);
            return $menu;
        });
    }

    /**
     * Mettre à jour un menu
     */
    public function updateMenu(int $id, array $data, array $produits = null)
    {
        $menu = $this->repo->findOrFail($id);
        return DB::transaction(function () use ($menu, $data, $produits) {
            if ($produits !== null) {
                $total = 0;
                $syncData = [];
                foreach ($produits as $item) {
                    $produit = $this->produitRepo->find($item['produit_id']);
                    if (!$produit) throw new \Exception("Produit introuvable");
                    $coutUnitaire = $produit->prix_unitaire;
                    $coutTotal = $coutUnitaire * $item['quantite'];
                    $syncData[$item['produit_id']] = [
                        'quantite' => $item['quantite'],
                        'cout_unitaire' => $coutUnitaire,
                        'cout_total' => $coutTotal
                    ];
                    $total += $coutTotal;
                }
                $menu->produits()->sync($syncData);
                $data['cout_total_prevu'] = $total;
            }
            $this->repo->update($menu->id, $data);
            return $this->repo->find($menu->id);
        });
    }

    /**
     * Enregistrer une préparation réelle (production du repas)
     */
    public function enregistrerPreparation(int $menuId, int $nombreParts, ?float $coutReel = null, string $observations = null)
    {
        $menu = $this->repo->findOrFail($menuId);
        $preparation = $this->preparationRepo->create([
            'menu_id' => $menuId,
            'date_preparation' => now(),
            'nombre_parts' => $nombreParts,
            'cout_reel' => $coutReel,
            'observations' => $observations,
            'responsable_id' => Auth::id()
        ]);

        $updateData = ['quantite_reellement' => $nombreParts];
        if ($coutReel !== null) {
            $updateData['cout_total_reel'] = $coutReel;
        }
        $this->repo->update($menuId, $updateData);

        return $preparation;
    }

    /**
     * Coût moyen par repas sur une période
     */
    public function getCoutMoyenRepas($dateDebut = null, $dateFin = null): array
    {
        $query = $this->repo->getModel()->newQuery()
            ->whereNotNull('cout_total_reel');
        if ($dateDebut) $query->whereDate('date_service', '>=', $dateDebut);
        if ($dateFin) $query->whereDate('date_service', '<=', $dateFin);

        $menus = $query->get();
        $totalCout = $menus->sum('cout_total_reel');
        $totalParts = $menus->sum('quantite_reellement');
        $coutMoyenParPart = $totalParts > 0 ? $totalCout / $totalParts : 0;

        return [
            'cout_total_periode' => $totalCout,
            'nombre_parts_totales' => $totalParts,
            'cout_moyen_par_part' => $coutMoyenParPart
        ];
    }

    /**
     * Coût par inscrit à la cantine
     */
    public function getCoutParInscrit($dateDebut = null, $dateFin = null): array
    {
        $inscritsTotal = $this->cantineRepo->activeQuery()
            ->where('statut', 1)
            ->count();
        if ($inscritsTotal == 0) return ['cout_par_inscrit' => 0];

        $stats = $this->getCoutMoyenRepas($dateDebut, $dateFin);
        $coutParInscrit = $inscritsTotal > 0 ? $stats['cout_total_periode'] / $inscritsTotal : 0;

        return [
            'nombre_inscrits_periode' => $inscritsTotal,
            'cout_total_periode'      => $stats['cout_total_periode'],
            'cout_par_inscrit'        => $coutParInscrit,
        ];
    }
}
