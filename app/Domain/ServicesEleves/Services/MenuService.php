<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\MenuData;
use App\Domain\ServicesEleves\DTO\MenuProduitData;
use App\Domain\ServicesEleves\Models\Menu;
use App\Domain\ServicesEleves\Models\MenuProduit;
use App\Domain\ServicesEleves\Repositories\MenuRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MenuService
{
    public function __construct(
        private MenuRepository $menus,
    ) {}

    public function creer(MenuData $data): Menu
    {
        if (empty($data->produits)) {
            throw new RuntimeException("Un menu doit comporter au moins un produit.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();

        return DB::transaction(function () use ($data, $anneeId) {
            $menu = $this->menus->create([
                'libelle'        => $data->libelle,
                'description'    => $data->description,
                'date_service'   => $data->dateService,
                'type_repas'     => $data->typeRepas,
                'quantite_prevue'=> $data->quantitePrevue,
                'magasin_id'     => $data->magasinId,
                'annee_id'       => $anneeId,
            ]);

            $this->enregistrerProduits($menu->id, $data->produits);
            $menu->recalculerCoutPrevu();

            return $menu->fresh('menuProduits');
        });
    }

    public function modifier(int $id, MenuData $data): Menu
    {
        return DB::transaction(function () use ($id, $data) {
            $menu = $this->menus->findOrFail($id);

            $this->menus->update($id, [
                'libelle'        => $data->libelle,
                'description'    => $data->description,
                'date_service'   => $data->dateService,
                'type_repas'     => $data->typeRepas,
                'quantite_prevue'=> $data->quantitePrevue,
                'magasin_id'     => $data->magasinId,
            ]);

            // Remplace les produits du menu
            MenuProduit::where('menu_id', $menu->id)->delete();
            $this->enregistrerProduits($menu->id, $data->produits);
            $menu->recalculerCoutPrevu();

            return $menu->fresh('menuProduits');
        });
    }

    public function supprimer(int $id): void
    {
        $menu = $this->menus->findOrFail($id);

        if ($menu->preparations()->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce menu : des préparations y sont rattachées."
            );
        }

        $this->menus->delete($id);
    }

    /* ================================================================
       Internals
       ================================================================ */

    /**
     * @param MenuProduitData[] $produits
     */
    private function enregistrerProduits(int $menuId, array $produits): void
    {
        foreach ($produits as $p) {
            MenuProduit::create([
                'menu_id'      => $menuId,
                'produit_id'   => $p->produitId,
                'quantite'     => $p->quantite,
                'cout_unitaire'=> $p->coutUnitaire,
                'cout_total'   => $p->coutTotal(),
            ]);
        }
    }
}