<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\ClotureCaisseData;
use App\Domain\Finances\DTO\OuvertureCaisseData;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Services\CaisseService;
use App\Domain\Finances\Types\StatutEcartCaisse;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.4 Caisses : « Direction (validation des écarts) »
 * et règle transversale « Gestion des caisses et des écarts » : tout écart
 * est motivé et soumis à validation de la Direction.
 */
class EcartsCaisseTest extends DomainTestCase
{
    private CaisseService $service;
    private User $caissier;
    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CaisseService::class);
        $this->caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $this->directeur = $this->creerUtilisateur(RoleUtilisateur::Directeur);
    }

    /** Caisse ouverte à 50 000 puis clôturée avec le solde compté indiqué. */
    private function cloturer(float $soldeCompte, ?string $motif = 'Erreur de rendu de monnaie', ?User $caissier = null): Caisse
    {
        $caissier ??= $this->caissier;
        $caisse = $this->service->ouvrir(new OuvertureCaisseData('Caisse du jour', $caissier->id, 50000, $caissier->id));

        return $this->service->cloturer(new ClotureCaisseData($caisse->id, $soldeCompte, $motif, $caissier->id));
    }

    public function test_une_cloture_sans_ecart_ne_demande_aucune_validation(): void
    {
        $caisse = $this->cloturer(50000, null);

        $this->assertSame(StatutEcartCaisse::AUCUN, $caisse->statut_ecart);
        $this->assertFalse($caisse->ecartEnInstance());
    }

    public function test_un_ecart_constate_a_la_cloture_est_soumis_a_la_direction(): void
    {
        $caisse = $this->cloturer(48000);

        $this->assertSame(StatutEcartCaisse::EN_ATTENTE, $caisse->statut_ecart);
        $this->assertTrue($caisse->ecartEnInstance());
        $this->assertNull($caisse->valide_par);
    }

    public function test_la_direction_valide_un_ecart(): void
    {
        $caisse = $this->cloturer(48000);

        $decidee = $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::VALIDE, $this->directeur->id);

        $this->assertSame(StatutEcartCaisse::VALIDE, $decidee->statut_ecart);
        $this->assertSame($this->directeur->id, (int) $decidee->valide_par);
        $this->assertNotNull($decidee->date_validation_ecart);
    }

    public function test_statuer_sur_un_ecart_ne_modifie_pas_le_journal_fige(): void
    {
        $caisse = $this->cloturer(48000);

        $decidee = $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::VALIDE, $this->directeur->id);

        $this->assertEqualsWithDelta(50000, (float) $decidee->solde_final, 0.01);
        $this->assertEqualsWithDelta(48000, (float) $decidee->solde_compte, 0.01);
        $this->assertEqualsWithDelta(-2000, (float) $decidee->ecart, 0.01);
        $this->assertFalse($decidee->isOuverte());
    }

    public function test_refuser_un_ecart_exige_un_commentaire(): void
    {
        $caisse = $this->cloturer(48000);

        try {
            $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::REFUSE, $this->directeur->id, '  ');
            $this->fail('Un refus sans commentaire aurait dû être rejeté.');
        } catch (RuntimeException) {
            $this->assertSame(StatutEcartCaisse::EN_ATTENTE, $caisse->fresh()->statut_ecart);
        }
    }

    public function test_un_ecart_refuse_conserve_le_commentaire_de_la_direction(): void
    {
        $caisse = $this->cloturer(48000);

        $decidee = $this->service->statuerSurEcart(
            $caisse->id, StatutEcartCaisse::REFUSE, $this->directeur->id, 'Justification insuffisante, à rembourser.'
        );

        $this->assertSame(StatutEcartCaisse::REFUSE, $decidee->statut_ecart);
        $this->assertSame('Justification insuffisante, à rembourser.', $decidee->commentaire_validation_ecart);
    }

    public function test_un_ecart_en_verification_reste_a_trancher(): void
    {
        $caisse = $this->cloturer(48000);

        $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::ENQUETE, $this->directeur->id, 'Recompter avec le comptable.');
        $this->assertTrue($caisse->fresh()->ecartEnInstance());

        $finale = $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::VALIDE, $this->directeur->id);
        $this->assertSame(StatutEcartCaisse::VALIDE, $finale->statut_ecart);
    }

    public function test_une_decision_rendue_est_definitive(): void
    {
        $caisse = $this->cloturer(48000);
        $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::VALIDE, $this->directeur->id);

        $this->expectException(RuntimeException::class);

        $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::REFUSE, $this->directeur->id, 'Changement d\'avis');
    }

    public function test_on_ne_statue_pas_sur_une_caisse_sans_ecart(): void
    {
        $caisse = $this->cloturer(50000, null);

        $this->expectException(RuntimeException::class);

        $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::VALIDE, $this->directeur->id);
    }

    public function test_un_caissier_ne_valide_pas_l_ecart_de_sa_propre_caisse(): void
    {
        // Un directeur qui tient aussi une caisse (cumul de profils) ne se valide pas lui-même
        $caisse = $this->cloturer(48000, 'Erreur', $this->directeur);

        $this->expectException(RuntimeException::class);

        $this->service->statuerSurEcart($caisse->id, StatutEcartCaisse::VALIDE, $this->directeur->id);
    }

    public function test_l_ecran_de_la_direction_liste_les_ecarts_a_traiter(): void
    {
        $enAttente = $this->cloturer(48000);
        $this->cloturer(50000, null, $this->creerUtilisateur(RoleUtilisateur::Caissier));
        $traite = $this->cloturer(51000, 'Excédent inexpliqué', $this->creerUtilisateur(RoleUtilisateur::Caissier));
        $this->service->statuerSurEcart($traite->id, StatutEcartCaisse::VALIDE, $this->directeur->id);

        $depot = app(CaisseRepository::class);

        $this->assertSame([$enAttente->id], $depot->ecartsPagines()->pluck('id')->all());
        $this->assertSame(2, $depot->ecartsPagines(['statut_ecart' => 'tous'])->total());
        $this->assertSame(
            ['en_attente' => 1, 'enquete' => 0, 'valide' => 1, 'refuse' => 0],
            $depot->compterEcartsParStatut($this->annee->id)
        );
    }

    /* ---------- Accès : seule la Direction (et l'Administrateur) statue ---------- */

    public function test_la_direction_ouvre_l_ecran_des_ecarts_et_y_rend_sa_decision(): void
    {
        $caisse = $this->cloturer(48000);

        $this->actingAs($this->directeur)
            ->get('/finances/caisses/ecarts')
            ->assertOk()
            ->assertSee('Caisse du jour')
            ->assertSee('Erreur de rendu de monnaie');

        $this->actingAs($this->directeur)
            ->post("/finances/caisses/{$caisse->id}/ecart", ['decision' => 'refuse', 'commentaire' => 'À rembourser par le caissier.'])
            ->assertRedirect(route('finances.caisses.ecarts.index'));

        $this->assertSame(StatutEcartCaisse::REFUSE, $caisse->fresh()->statut_ecart);
    }

    public function test_un_refus_sans_commentaire_est_rejete_par_le_formulaire(): void
    {
        $caisse = $this->cloturer(48000);

        $this->actingAs($this->directeur)
            ->post("/finances/caisses/{$caisse->id}/ecart", ['decision' => 'refuse'])
            ->assertSessionHasErrors('commentaire');

        $this->assertSame(StatutEcartCaisse::EN_ATTENTE, $caisse->fresh()->statut_ecart);
    }

    public function test_ni_le_caissier_ni_le_comptable_ne_valident_un_ecart(): void
    {
        $caisse = $this->cloturer(48000);

        foreach ([$this->caissier, $this->creerUtilisateur(RoleUtilisateur::Comptable)] as $user) {
            $this->actingAs($user)->get('/finances/caisses/ecarts')->assertForbidden();
            $this->actingAs($user)
                ->post("/finances/caisses/{$caisse->id}/ecart", ['decision' => 'valide'])
                ->assertForbidden();
        }

        $this->assertSame(StatutEcartCaisse::EN_ATTENTE, $caisse->fresh()->statut_ecart);
    }
}
