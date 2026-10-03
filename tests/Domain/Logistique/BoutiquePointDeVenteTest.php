<?php

namespace Tests\Domain\Logistique;

use App\Domain\Logistique\DTO\ClientData;
use App\Domain\Logistique\DTO\VenteData;
use App\Domain\Logistique\DTO\VenteLigneData;
use App\Domain\Logistique\Models\Vente;
use App\Domain\Logistique\Services\ClientService;
use App\Domain\Logistique\Services\StockService;
use App\Domain\Logistique\Services\VenteService;
use App\Domain\Logistique\Types\TypeMagasin;
use App\Domain\Logistique\Types\TypeVente;
use App\Domain\Logistique\Types\VenteStatutPaiement;
use RuntimeException;

/**
 * Cahier des charges — Menu 3, § 3.4 Boutique / Point de vente
 * et règle transversale « Paiement multi-natures (panier) » pour le stock.
 */
class BoutiquePointDeVenteTest extends LogistiqueTestCase
{
    private VenteService $ventes;
    private int $produit;
    private int $boutique;
    private int $vendeur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ventes = app(VenteService::class);
        $this->produit = $this->creerProduit('T-shirt M', 6500);
        $this->boutique = $this->creerMagasin('Boutique', TypeMagasin::BOUTIQUE);
        $this->vendeur = $this->creerUtilisateur()->id;
        app(StockService::class)->entrerEnStock($this->produit, $this->boutique, 10, 4000, 'Stock initial');
    }

    private function vendre(float $quantite = 2, float $remise = 0, ?int $clientId = null, ?int $inscriptionId = null, bool $payee = true): Vente
    {
        return $this->ventes->enregistrer(new VenteData(
            '2026-09-20', $this->boutique, TypeVente::COMPTOIR->value, $clientId, $inscriptionId,
            [new VenteLigneData($this->produit, $quantite, 6500, $remise)],
            $this->vendeur, $payee
        ));
    }

    public function test_une_vente_decremente_le_stock_de_la_boutique(): void
    {
        $vente = $this->vendre(2);

        $this->assertNotEmpty($vente->reference);
        $this->assertCount(1, $vente->details);
        $this->assertEqualsWithDelta(13000, (float) $vente->total_ttc, 0.01);
        $this->assertEqualsWithDelta(8, $this->quantiteEnStock($this->produit, $this->boutique), 0.001);
    }

    public function test_une_remise_reduit_le_total_de_la_vente(): void
    {
        $vente = $this->vendre(2, 10);

        $this->assertEqualsWithDelta(11700, (float) $vente->total_ttc, 0.01);
    }

    public function test_on_ne_vend_pas_plus_que_le_stock_disponible(): void
    {
        try {
            $this->vendre(11);
            $this->fail('Une vente au-delà du stock aurait dû être refusée.');
        } catch (RuntimeException) {
            $this->assertSame(0, Vente::count());
            $this->assertEqualsWithDelta(10, $this->quantiteEnStock($this->produit, $this->boutique), 0.001);
        }
    }

    public function test_une_vente_sans_ligne_est_refusee(): void
    {
        $this->expectException(RuntimeException::class);

        $this->ventes->enregistrer(new VenteData(
            '2026-09-20', $this->boutique, TypeVente::COMPTOIR->value, null, null, [], $this->vendeur
        ));
    }

    public function test_une_vente_peut_etre_associee_a_l_inscription_d_un_eleve(): void
    {
        $inscription = $this->creerInscription();

        $vente = $this->vendre(1, 0, null, $inscription->id);

        $this->assertSame($inscription->id, (int) $vente->inscription_id);
        $this->assertSame($inscription->id, (int) $vente->details->first()->inscription_id);
    }

    public function test_une_vente_peut_etre_associee_a_un_client_externe(): void
    {
        $client = app(ClientService::class)->creer(
            new ClientData('ONG Espoir', 'institution', '90222222', null, null, null, null)
        );

        $vente = $this->vendre(1, 0, $client->id);

        $this->assertSame($client->id, (int) $vente->client_id);
    }

    public function test_annuler_une_vente_remet_les_produits_en_stock(): void
    {
        $vente = $this->vendre(3);

        $annulee = $this->ventes->annuler($vente->id, 'Erreur de caisse', $this->vendeur);

        $this->assertSame(VenteStatutPaiement::ANNULE, $annulee->statut_paiement);
        $this->assertEqualsWithDelta(10, $this->quantiteEnStock($this->produit, $this->boutique), 0.001);
    }

    public function test_une_vente_n_est_annulee_qu_une_fois(): void
    {
        $vente = $this->vendre(3);
        $this->ventes->annuler($vente->id, 'Erreur de caisse', $this->vendeur);

        try {
            $this->ventes->annuler($vente->id, 'Encore', $this->vendeur);
            $this->fail('Une seconde annulation aurait dû être refusée.');
        } catch (RuntimeException) {
            $this->assertEqualsWithDelta(10, $this->quantiteEnStock($this->produit, $this->boutique), 0.001);
        }
    }

    public function test_une_vente_a_credit_peut_etre_reglee_plus_tard(): void
    {
        $vente = $this->vendre(1, 0, null, null, false);
        $this->assertSame(VenteStatutPaiement::IMPAYE, $vente->statut_paiement);

        $reglee = $this->ventes->marquerPayee($vente->id);

        $this->assertSame(VenteStatutPaiement::PAYE, $reglee->statut_paiement);
    }
}
