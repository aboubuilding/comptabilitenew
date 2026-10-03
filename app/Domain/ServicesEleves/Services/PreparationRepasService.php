<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\Logistique\Services\StockService;
use App\Domain\ServicesEleves\DTO\PreparationRepasData;
use App\Domain\ServicesEleves\Models\PreparationRepas;
use App\Domain\ServicesEleves\Repositories\MenuRepository;
use App\Domain\ServicesEleves\Repositories\PreparationRepasRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PreparationRepasService
{
    public function __construct(
        private PreparationRepasRepository $preparations,
        private MenuRepository             $menus,
        private StockService               $stocks,
    ) {}

    /**
     * Enregistre une préparation.
     *
     * Coût réel :
     *   - si fourni explicitement, on l'utilise
     *   - sinon on calcule depuis les produits du menu et le nombre de parts
     *
     * Décrément du stock :
     *   - proportionnellement au nombre de parts par rapport à la quantité prévue
     *   - via StockService (module 3.3)
     */
    public function enregistrer(PreparationRepasData $data, int $userId): PreparationRepas
    {
        $menu = $this->menus->findAvecProduits($data->menuId);

        if ($menu->menuProduits->isEmpty()) {
            throw new RuntimeException("Ce menu n'a aucun produit composé.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();

        return DB::transaction(function () use ($data, $menu, $userId, $anneeId) {
            // 1) Calcul du coût réel
            $coutReel = $data->coutReel;
            if ($coutReel === null) {
                // Coût réel par part = coût prévu / parts prévues
                $partsPrevues = max(1, (int) $menu->quantite_prevue);
                $coutParPart  = (float) $menu->cout_total_prevu / $partsPrevues;
                $coutReel     = round($coutParPart * $data->nombreParts, 2);
            }

            // 2) Créer la préparation
            $preparation = $this->preparations->create([
                'menu_id'          => $menu->id,
                'magasin_id'       => $data->magasinId,
                'date_preparation' => $data->datePreparation,
                'nombre_parts'     => $data->nombreParts,
                'cout_reel'        => $coutReel,
                'observations'     => $data->observations,
                'responsable_id'   => $userId,
                'annee_id'         => $anneeId,
                'stock_decremente' => false,
            ]);

            // 3) Décrémenter le stock
            $this->decrementerStock($menu, $data, $userId);

            $this->preparations->update($preparation->id, ['stock_decremente' => true]);

            // 4) Le menu cumule ce qui a réellement été servi (parts et coût réel),
            //    à comparer à son prévisionnel.
            DB::table('menus')->where('id', $menu->id)->update([
                'quantite_reellement' => DB::raw('COALESCE(quantite_reellement, 0) + ' . (int) $data->nombreParts),
                'cout_total_reel'     => DB::raw('COALESCE(cout_total_reel, 0) + ' . (float) $coutReel),
                'updated_at'          => now(),
            ]);

            return $preparation->refresh();
        });
    }

    /* ================================================================
       Internals
       ================================================================ */

    private function decrementerStock($menu, PreparationRepasData $data, int $userId): void
    {
        // Ratio : combien de parts réellement / parts prévues
        $ratio = $menu->quantite_prevue > 0
            ? $data->nombreParts / $menu->quantite_prevue
            : 1;

        foreach ($menu->menuProduits as $mp) {
            $quantiteUtilisee = round($mp->quantite * $ratio, 3);
            if ($quantiteUtilisee <= 0) continue;

            $this->stocks->sortirDuStock(
                produitId:     (int) $mp->produit_id,
                magasinId:     $data->magasinId,
                quantite:      $quantiteUtilisee,
                libelle:       "Préparation repas — {$menu->libelle}",
                utilisateurId: $userId,
                motif:         "Cantine — {$data->nombreParts} part(s)",
            );
        }
    }
}