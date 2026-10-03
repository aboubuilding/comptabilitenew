<?php

namespace Tests\Domain\Logistique;

use App\Domain\Logistique\DTO\AchatData;
use App\Domain\Logistique\DTO\AchatLigneData;
use App\Domain\Logistique\DTO\FournisseurData;
use App\Domain\Logistique\Models\Achat;
use App\Domain\Logistique\Models\Fournisseur;
use App\Domain\Logistique\Services\AchatService;
use App\Domain\Logistique\Services\FournisseurService;
use App\Domain\Logistique\Types\AchatStatutLivraison;
use App\Domain\Logistique\Types\AchatStatutPaiement;
use App\Domain\Logistique\Types\AchatType;
use App\Repositories\BaseRepository;
use RuntimeException;

/**
 * Cahier des charges — Menu 3, § 3.1 Fournisseurs et § 3.2 Achats.
 */
class FournisseursAchatsTest extends LogistiqueTestCase
{
    private AchatService $achats;
    private int $fournisseur;
    private int $produit;
    private int $utilisateur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->achats = app(AchatService::class);
        $this->fournisseur = $this->creerFournisseur();
        $this->produit = $this->creerProduit('Rame de papier', 3500);
        $this->utilisateur = $this->creerUtilisateur()->id;
    }

    private function fournisseurData(string $raisonSociale): FournisseurData
    {
        return new FournisseurData($raisonSociale, 'M. Agbo', '90111111', null, 'Lomé', null, null);
    }

    private function achat(array $lignes = null): Achat
    {
        return $this->achats->creer(new AchatData(
            '2026-09-20', 'Econome', 'BC-001', null, $this->fournisseur, AchatType::STOCK->value,
            $lignes ?? [new AchatLigneData($this->produit, 20, 3000)]
        ));
    }

    /* ---------- 3.1 Fournisseurs ---------- */

    public function test_deux_fournisseurs_n_ont_pas_la_meme_raison_sociale(): void
    {
        $service = app(FournisseurService::class);
        $service->creer($this->fournisseurData('Librairie Bon Pasteur'));

        $this->expectException(RuntimeException::class);

        $service->creer($this->fournisseurData('Librairie Bon Pasteur'));
    }

    public function test_un_fournisseur_ayant_des_achats_ne_peut_pas_etre_supprime(): void
    {
        $this->achat();

        $this->expectException(RuntimeException::class);

        app(FournisseurService::class)->supprimer($this->fournisseur);
    }

    public function test_supprimer_un_fournisseur_sans_achat_l_archive(): void
    {
        $fournisseur = app(FournisseurService::class)->creer($this->fournisseurData('Quincaillerie Moderne'));

        app(FournisseurService::class)->supprimer($fournisseur->id);

        $this->assertSame(BaseRepository::INACTIF, (int) Fournisseur::find($fournisseur->id)->etat);
    }

    /* ---------- 3.2 Achats ---------- */

    public function test_les_statuts_d_achat_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, AchatStatutPaiement::NON_PAYE->value);
        $this->assertSame(2, AchatStatutPaiement::PAYE->value);
        $this->assertSame(1, AchatStatutLivraison::EN_ATTENTE->value);
        $this->assertSame(2, AchatStatutLivraison::LIVRE->value);
    }

    public function test_un_achat_est_cree_non_paye_et_en_attente_de_livraison(): void
    {
        $achat = $this->achat([
            new AchatLigneData($this->produit, 20, 3000),
            new AchatLigneData($this->creerProduit('Stylo', 150), 100, 100),
        ]);

        $this->assertSame(AchatStatutPaiement::NON_PAYE, $achat->statut_paiement);
        $this->assertSame(AchatStatutLivraison::EN_ATTENTE, $achat->statut_livraison);
        $this->assertCount(2, $achat->details);
        $this->assertEqualsWithDelta(70000, (float) $achat->montant_total, 0.01);
        $this->assertNotEmpty($achat->reference);
    }

    public function test_un_achat_sans_ligne_est_refuse(): void
    {
        $this->expectException(RuntimeException::class);

        $this->achat([]);
    }

    public function test_receptionner_un_achat_met_a_jour_le_stock(): void
    {
        $entrepot = $this->creerMagasin();
        $achat = $this->achat();

        $recu = $this->achats->receptionner($achat->id, $this->utilisateur);

        $this->assertTrue($recu->isLivre());
        $this->assertEqualsWithDelta(20, $this->quantiteEnStock($this->produit, $entrepot), 0.001);
    }

    public function test_la_reception_peut_viser_un_magasin_precis(): void
    {
        $this->creerMagasin();
        $annexe = $this->creerMagasin('Entrepôt annexe');
        $achat = $this->achat();

        $this->achats->receptionner($achat->id, $this->utilisateur, $annexe);

        $this->assertEqualsWithDelta(20, $this->quantiteEnStock($this->produit, $annexe), 0.001);
    }

    public function test_sans_magasin_la_reception_est_refusee_et_l_achat_reste_en_attente(): void
    {
        $achat = $this->achat();

        try {
            $this->achats->receptionner($achat->id, $this->utilisateur);
            $this->fail('Sans magasin pour recevoir la marchandise, la réception aurait dû être refusée.');
        } catch (RuntimeException) {
            $this->assertSame(AchatStatutLivraison::EN_ATTENTE, Achat::find($achat->id)->statut_livraison);
        }
    }

    public function test_un_achat_n_est_receptionne_qu_une_fois(): void
    {
        $this->creerMagasin();
        $achat = $this->achat();
        $this->achats->receptionner($achat->id, $this->utilisateur);

        $this->expectException(RuntimeException::class);

        $this->achats->receptionner($achat->id, $this->utilisateur);
    }

    public function test_un_achat_livre_ne_se_modifie_plus(): void
    {
        $this->creerMagasin();
        $achat = $this->achat();
        $this->achats->receptionner($achat->id, $this->utilisateur);

        $this->expectException(RuntimeException::class);

        $this->achats->modifier($achat->id, new AchatData(
            '2026-09-21', null, null, null, $this->fournisseur, AchatType::STOCK->value,
            [new AchatLigneData($this->produit, 5, 3000)]
        ));
    }

    public function test_un_achat_annule_ne_peut_plus_etre_receptionne(): void
    {
        $this->creerMagasin();
        $achat = $this->achat();
        $this->achats->annuler($achat->id, 'Commande en double');

        $this->expectException(RuntimeException::class);

        $this->achats->receptionner($achat->id, $this->utilisateur);
    }

    public function test_le_statut_de_paiement_d_un_achat_est_suivi(): void
    {
        $achat = $this->achats->enregistrerPaiement($this->achat()->id, (string) AchatStatutPaiement::PAYE->value);

        $this->assertSame(AchatStatutPaiement::PAYE, $achat->statut_paiement);
    }
}
