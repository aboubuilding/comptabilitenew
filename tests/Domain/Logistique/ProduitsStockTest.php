<?php

namespace Tests\Domain\Logistique;

use App\Domain\Logistique\Models\Stock;
use App\Domain\Logistique\Models\StockActuel;
use App\Domain\Logistique\Services\BonService;
use App\Domain\Logistique\Services\StockService;
use App\Domain\Logistique\Types\TypeBon;
use App\Domain\Logistique\Types\TypeMagasin;
use App\Domain\Logistique\Types\TypeMouvementStock;
use RuntimeException;

/**
 * Cahier des charges — Menu 3, § 3.3 Produits & Stock.
 */
class ProduitsStockTest extends LogistiqueTestCase
{
    private StockService $stock;
    private int $produit;
    private int $entrepot;
    private int $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
        $this->produit = $this->creerProduit();
        $this->entrepot = $this->creerMagasin();
        $this->utilisateur = $this->creerUtilisateur()->id;
    }

    public function test_une_entree_augmente_le_stock_et_sa_valeur(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 100, 4000, 'Livraison');

        $actuel = StockActuel::where('produit_id', $this->produit)->where('magasin_id', $this->entrepot)->sole();
        $this->assertEqualsWithDelta(100, (float) $actuel->quantite, 0.001);
        $this->assertEqualsWithDelta(400000, (float) $actuel->valeur_stock, 0.01);
    }

    public function test_une_sortie_diminue_le_stock(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 100, 4000, 'Livraison');

        $this->stock->sortirDuStock($this->produit, $this->entrepot, 30, 'Dotation');

        $this->assertEqualsWithDelta(70, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
    }

    public function test_on_ne_sort_pas_plus_que_le_stock_disponible(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 10, 4000, 'Livraison');

        try {
            $this->stock->sortirDuStock($this->produit, $this->entrepot, 11, 'Dotation');
            $this->fail('Une sortie supérieure au stock aurait dû être refusée.');
        } catch (RuntimeException) {
            $this->assertEqualsWithDelta(10, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
        }
    }

    public function test_un_mouvement_a_une_quantite_strictement_positive(): void
    {
        $this->expectException(RuntimeException::class);

        $this->stock->entrerEnStock($this->produit, $this->entrepot, 0, 4000, 'Livraison');
    }

    public function test_chaque_mouvement_est_conserve_dans_l_historique(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 100, 4000, 'Livraison', $this->utilisateur);
        $this->stock->sortirDuStock($this->produit, $this->entrepot, 30, 'Dotation', $this->utilisateur);

        $historique = Stock::where('produit_id', $this->produit)->orderBy('id')->get();
        $this->assertCount(2, $historique);
        $this->assertSame(TypeMouvementStock::ENTREE, $historique[0]->type_mouvement);
        $this->assertSame(TypeMouvementStock::SORTIE, $historique[1]->type_mouvement);
        $this->assertSame($this->annee->id, (int) $historique[0]->annee_id);
    }

    public function test_un_ajustement_d_inventaire_a_la_hausse_augmente_le_stock(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 10, 4000, 'Livraison');

        $this->stock->ajuster($this->produit, $this->entrepot, 15, 'Inventaire', $this->utilisateur);

        $this->assertEqualsWithDelta(15, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
    }

    public function test_un_ajustement_d_inventaire_a_la_baisse_diminue_le_stock(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 10, 4000, 'Livraison');

        $this->stock->ajuster($this->produit, $this->entrepot, 7, 'Casse', $this->utilisateur);

        $this->assertEqualsWithDelta(7, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
    }

    public function test_un_transfert_deplace_le_stock_d_un_magasin_a_l_autre(): void
    {
        $boutique = $this->creerMagasin('Boutique', TypeMagasin::BOUTIQUE);
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 100, 4000, 'Livraison');

        $this->stock->transferer($this->produit, $this->entrepot, $boutique, 40);

        $this->assertEqualsWithDelta(60, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
        $this->assertEqualsWithDelta(40, $this->quantiteEnStock($this->produit, $boutique), 0.001);
    }

    public function test_un_transfert_exige_deux_magasins_differents(): void
    {
        $this->expectException(RuntimeException::class);

        $this->stock->transferer($this->produit, $this->entrepot, $this->entrepot, 1);
    }

    public function test_un_bon_d_entree_alimente_le_stock(): void
    {
        $bon = app(BonService::class)->creer([
            'type'       => TypeBon::ENTREE->value,
            'magasin_id' => $this->entrepot,
            'lignes'     => [['produit_id' => $this->produit, 'quantite' => 50, 'prix_unitaire' => 4000]],
        ], $this->utilisateur);

        $this->assertNotEmpty($bon->reference);
        $this->assertCount(1, $bon->details);
        $this->assertEqualsWithDelta(50, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
    }

    public function test_un_bon_de_sortie_decremente_le_stock(): void
    {
        $this->stock->entrerEnStock($this->produit, $this->entrepot, 50, 4000, 'Livraison');

        app(BonService::class)->creer([
            'type'       => TypeBon::SORTIE->value,
            'magasin_id' => $this->entrepot,
            'motif'      => 'Dotation cantine',
            'lignes'     => [['produit_id' => $this->produit, 'quantite' => 20]],
        ], $this->utilisateur);

        $this->assertEqualsWithDelta(30, $this->quantiteEnStock($this->produit, $this->entrepot), 0.001);
    }

    public function test_un_bon_de_transfert_exige_un_magasin_de_destination(): void
    {
        $this->expectException(RuntimeException::class);

        app(BonService::class)->creer([
            'type'       => TypeBon::TRANSFERT->value,
            'magasin_id' => $this->entrepot,
            'lignes'     => [['produit_id' => $this->produit, 'quantite' => 5]],
        ], $this->utilisateur);
    }

    public function test_un_bon_sans_ligne_est_refuse(): void
    {
        $this->expectException(RuntimeException::class);

        app(BonService::class)->creer([
            'type' => TypeBon::ENTREE->value, 'magasin_id' => $this->entrepot, 'lignes' => [],
        ], $this->utilisateur);
    }
}
