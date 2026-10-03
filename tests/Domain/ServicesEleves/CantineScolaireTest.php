<?php

namespace Tests\Domain\ServicesEleves;

use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Logistique\Models\StockActuel;
use App\Domain\Logistique\Services\StockService;
use App\Domain\ServicesEleves\DTO\InscriptionCantineData;
use App\Domain\ServicesEleves\DTO\MenuData;
use App\Domain\ServicesEleves\DTO\MenuProduitData;
use App\Domain\ServicesEleves\DTO\PreparationRepasData;
use App\Domain\ServicesEleves\Models\Menu;
use App\Domain\ServicesEleves\Services\CantineService;
use App\Domain\ServicesEleves\Services\MenuService;
use App\Domain\ServicesEleves\Services\PreparationRepasService;
use App\Domain\ServicesEleves\Types\TypeRepas;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 4, § 4.2 Cantine scolaire
 * et règle transversale « Souscription indépendante du paiement ».
 */
class CantineScolaireTest extends DomainTestCase
{
    private CantineService $cantine;
    private int $riz;
    private int $magasin;
    private int $cuisinier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cantine = app(CantineService::class);
        $this->riz = DB::table('produits')->insertGetId(['code' => 'RIZ', 'libelle' => 'Riz (kg)', 'prix_unitaire' => 600, 'etat' => 1]);
        $this->magasin = DB::table('magasins')->insertGetId(['libelle' => 'Économat', 'type' => 1, 'etat' => 1]);
        $this->cuisinier = $this->creerUtilisateur()->id;
        app(StockService::class)->entrerEnStock($this->riz, $this->magasin, 100, 600, 'Stock initial');
    }

    private function menu(int $partsPrevues = 100, float $kgDeRiz = 20): Menu
    {
        return app(MenuService::class)->creer(new MenuData(
            'Riz sauce arachide', null, '2026-09-21', TypeRepas::DEJEUNER->value, $partsPrevues, $this->magasin,
            [new MenuProduitData($this->riz, $kgDeRiz, 600)]
        ));
    }

    private function stockDeRiz(): float
    {
        return (float) StockActuel::where('produit_id', $this->riz)->where('magasin_id', $this->magasin)->value('quantite');
    }

    public function test_souscrire_a_la_cantine_fait_apparaitre_le_montant_du_sans_paiement(): void
    {
        $inscription = $this->creerInscription();

        $souscription = $this->cantine->souscrire(new InscriptionCantineData($inscription->id, '2026-09-15', 12000, 9));

        $this->assertTrue($souscription->isActif());
        $this->assertEqualsWithDelta(108000, (float) $souscription->montant_total_du, 0.01);
        $this->assertSame($this->annee->id, (int) $souscription->annee_id);
        $this->assertEqualsWithDelta(
            108000,
            app(EngagementService::class)->montantDu($inscription->id, NatureCreance::CANTINE->value),
            0.01
        );
        $this->assertSame(0, DB::table('paiements')->count());
    }

    public function test_un_eleve_n_a_qu_une_inscription_cantine_active(): void
    {
        $inscription = $this->creerInscription();
        $this->cantine->souscrire(new InscriptionCantineData($inscription->id, '2026-09-15', 12000, 9));

        $this->expectException(RuntimeException::class);

        $this->cantine->souscrire(new InscriptionCantineData($inscription->id, '2026-09-15', 12000, 9));
    }

    public function test_abandonner_la_cantine_est_date_motive_et_eteint_l_engagement(): void
    {
        $inscription = $this->creerInscription();
        $souscription = $this->cantine->souscrire(new InscriptionCantineData($inscription->id, '2026-09-15', 12000, 9));

        $abandon = $this->cantine->abandonner($souscription->id, 'Régime particulier', $this->cuisinier);

        $this->assertFalse($abandon->isActif());
        $this->assertSame('Régime particulier', $abandon->motif_abandon);
        $this->assertSame($this->cuisinier, (int) $abandon->abandonne_par);
        $this->assertEqualsWithDelta(0, app(EngagementService::class)->montantDu($inscription->id, NatureCreance::CANTINE->value), 0.01);
    }

    public function test_une_inscription_deja_abandonnee_ne_s_abandonne_pas_deux_fois(): void
    {
        $souscription = $this->cantine->souscrire(new InscriptionCantineData($this->creerInscription()->id, '2026-09-15', 12000, 9));
        $this->cantine->abandonner($souscription->id, 'Départ', $this->cuisinier);

        $this->expectException(RuntimeException::class);

        $this->cantine->abandonner($souscription->id, 'Départ', $this->cuisinier);
    }

    public function test_un_menu_est_compose_de_produits_et_son_cout_prevu_est_calcule(): void
    {
        $menu = $this->menu(100, 20);

        $this->assertCount(1, $menu->menuProduits);
        $this->assertEqualsWithDelta(12000, (float) $menu->cout_total_prevu, 0.01);
    }

    public function test_un_menu_sans_produit_est_refuse(): void
    {
        $this->expectException(RuntimeException::class);

        app(MenuService::class)->creer(new MenuData(
            'Menu vide', null, '2026-09-21', TypeRepas::DEJEUNER->value, 100, $this->magasin, []
        ));
    }

    public function test_une_preparation_de_repas_sort_les_produits_du_stock_au_prorata_des_parts(): void
    {
        $menu = $this->menu(100, 20);

        $preparation = app(PreparationRepasService::class)->enregistrer(
            new PreparationRepasData($menu->id, $this->magasin, '2026-09-21', 50, null, null), $this->cuisinier
        );

        // 50 parts sur 100 prévues : la moitié des 20 kg
        $this->assertEqualsWithDelta(90, $this->stockDeRiz(), 0.001);
        $this->assertTrue((bool) $preparation->stock_decremente);
        $this->assertEqualsWithDelta(6000, (float) $preparation->cout_reel, 0.01);
    }

    public function test_le_cout_reel_saisi_prime_sur_le_cout_calcule(): void
    {
        $preparation = app(PreparationRepasService::class)->enregistrer(
            new PreparationRepasData($this->menu()->id, $this->magasin, '2026-09-21', 100, 15500, 'Hausse du riz'), $this->cuisinier
        );

        $this->assertEqualsWithDelta(15500, (float) $preparation->cout_reel, 0.01);
    }

    public function test_une_preparation_est_refusee_si_le_stock_ne_suffit_pas(): void
    {
        $menu = $this->menu(100, 150); // 150 kg nécessaires, 100 en stock

        try {
            app(PreparationRepasService::class)->enregistrer(
                new PreparationRepasData($menu->id, $this->magasin, '2026-09-21', 100, null, null), $this->cuisinier
            );
            $this->fail('La préparation aurait dû être refusée faute de stock.');
        } catch (RuntimeException) {
            $this->assertEqualsWithDelta(100, $this->stockDeRiz(), 0.001);
            $this->assertSame(0, DB::table('preparations_repas')->count());
        }
    }

    public function test_un_menu_deja_prepare_ne_peut_pas_etre_supprime(): void
    {
        $menu = $this->menu();
        app(PreparationRepasService::class)->enregistrer(
            new PreparationRepasData($menu->id, $this->magasin, '2026-09-21', 10, null, null), $this->cuisinier
        );

        $this->expectException(RuntimeException::class);

        app(MenuService::class)->supprimer($menu->id);
    }
}
