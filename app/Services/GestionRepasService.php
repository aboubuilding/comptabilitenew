<?php
namespace App\Services;

use App\Models\Menu;
use App\Models\Produit;
use App\Models\PreparationRepas;
use Illuminate\Support\Facades\DB;

class GestionRepasService
{
    protected function getCurrentAnneeId()
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des menus avec coût moyen par part
     */
    public function listMenus(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $query = Menu::with('produits')
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
    public function createMenu(array $data, array $produits): Menu
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) throw new \Exception('Année non définie');

        return DB::transaction(function () use ($data, $produits, $anneeId) {
            // Calculer le coût total en fonction des produits et de leurs prix actuels
            $total = 0;
            foreach ($produits as &$item) {
                $produit = Produit::find($item['produit_id']);
                if (!$produit) throw new \Exception("Produit introuvable");
                $coutUnitaire = $produit->prix_unitaire;
                $coutTotal = $coutUnitaire * $item['quantite'];
                $item['cout_unitaire'] = $coutUnitaire;
                $item['cout_total'] = $coutTotal;
                $total += $coutTotal;
            }

            $data['annee_id'] = $anneeId;
            $data['cout_total_prevu'] = $total;
            $data['etat'] = 1;

            $menu = Menu::create($data);
            $menu->produits()->attach(collect($produits)->mapWithKeys(function ($item) {
                return [$item['produit_id'] => [
                    'quantite' => $item['quantite'],
                    'cout_unitaire' => $item['cout_unitaire'],
                    'cout_total' => $item['cout_total']
                ]];
            })->toArray());

            return $menu;
        });
    }

    /**
     * Mettre à jour un menu
     */
    public function updateMenu(int $id, array $data, array $produits = null): Menu
    {
        $menu = Menu::findOrFail($id);
        return DB::transaction(function () use ($menu, $data, $produits) {
            if ($produits !== null) {
                $total = 0;
                $syncData = [];
                foreach ($produits as $item) {
                    $produit = Produit::find($item['produit_id']);
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
            $menu->update($data);
            return $menu;
        });
    }

    /**
     * Enregistrer une préparation réelle (production du repas)
     */
    public function enregistrerPreparation(int $menuId, int $nombreParts, ?float $coutReel = null, string $observations = null): PreparationRepas
    {
        $menu = Menu::findOrFail($menuId);
        $preparation = PreparationRepas::create([
            'menu_id' => $menuId,
            'date_preparation' => now(),
            'nombre_parts' => $nombreParts,
            'cout_reel' => $coutReel,
            'observations' => $observations,
            'responsable_id' => auth()->id()
        ]);

        // Mettre à jour le menu avec les quantités réelles
        $menu->quantite_reellement = $nombreParts;
        if ($coutReel) {
            $menu->cout_total_reel = $coutReel;
        }
        $menu->save();

        return $preparation;
    }

    /**
     * Coût moyen par repas sur une période
     */
    public function getCoutMoyenRepas($dateDebut = null, $dateFin = null): array
    {
        $query = Menu::whereNotNull('cout_total_reel');
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
     * Coût par inscrit à la cantine (en utilisant les repas consommés par jour)
     * Nécessite une table de consommation, ici option simple.
     */
    public function getCoutParInscrit($dateDebut = null, $dateFin = null): array
    {
        // Hypothèse : on récupère le nombre d'inscrits actifs pour la période
        $inscritsTotal = \App\Models\InscriptionCantine::where('statut', 1)->count();
        if ($inscritsTotal == 0) return ['cout_par_inscrit' => 0];

        $stats = $this->getCoutMoyenRepas($dateDebut, $dateFin);
        $coutParInscrit = $stats['cout_total_periode'] / $inscritsTotal;

        return [
            'nombre_inscrits_periode' => $inscritsTotal,
            'cout_total_periode' => $stats['cout_total_periode'],
            'cout_par_inscrit' => $coutParInscrit,
        ];
    }
}