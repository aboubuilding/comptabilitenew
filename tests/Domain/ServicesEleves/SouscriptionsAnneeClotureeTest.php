<?php

namespace Tests\Domain\ServicesEleves;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Services\PanierRechercheService;
use App\Domain\Finances\Services\SouscriptionService;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Types\StatutAnnee;
use App\Domain\ServicesEleves\DTO\AbonnementBusData;
use App\Domain\ServicesEleves\DTO\InscriptionActiviteData;
use App\Domain\ServicesEleves\DTO\InscriptionCantineData;
use App\Domain\ServicesEleves\Services\AbonnementBusService;
use App\Domain\ServicesEleves\Services\ActiviteService;
use App\Domain\ServicesEleves\Services\CantineService;
use App\Domain\ServicesEleves\Services\EvenementService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Une année scolaire clôturée ne reçoit plus de souscription : cantine,
 * transport, activités extrascolaires, événements (demande de l'utilisateur
 * du 2026-10-14). Une année ouverte les accepte toujours.
 */
class SouscriptionsAnneeClotureeTest extends DomainTestCase
{
    private function cloturer(): void
    {
        DB::table('annees')->where('id', $this->annee->id)->update(['statut_annee' => StatutAnnee::Cloture->value]);
    }

    /** @return array<string, callable> une souscription de chaque service pour l'inscription donnée */
    private function souscriptions(): array
    {
        $inscription = $this->creerInscription();
        $activite = DB::table('activites')->insertGetId(['libelle' => 'FOOTBALL', 'montant' => 30000, 'annee_id' => $this->annee->id, 'etat' => 1]);
        $evenement = DB::table('evenements')->insertGetId([
            'nom' => 'Voyage', 'type' => 'voyage', 'date_evenement' => now()->addMonth()->toDateString(), 'participation' => 25000,
            'capacite' => 10, 'capacite_limitee' => 1, 'date_limite_confirmation' => now()->addWeek()->toDateString(),
            'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $fraisCantine = $this->creerFrais(108000, TypeDetail::CANTINE);
        $agent = $this->creerUtilisateur(RoleUtilisateur::Administrateur)->id;

        return [
            'cantine'   => fn () => app(CantineService::class)->souscrire(new InscriptionCantineData($inscription->id, '2026-10-01', 15000, 9)),
            'transport' => fn () => app(AbonnementBusService::class)->souscrire(new AbonnementBusData($inscription->id, '2026-10-01', 20000, 9, null, 'aller_retour')),
            'activite'  => fn () => app(ActiviteService::class)->souscrire(new InscriptionActiviteData($inscription->eleve_id, $activite, null, '2026-10-01')),
            'evenement' => fn () => app(EvenementService::class)->preInscrire($evenement, $inscription->id, $agent),
            'ecran'     => fn () => app(SouscriptionService::class)->creer(new SouscriptionData($fraisCantine->id, $inscription->id, 0, '2026-10-01', null)),
        ];
    }

    public function test_une_annee_cloturee_refuse_toute_souscription(): void
    {
        $souscriptions = $this->souscriptions();
        $this->cloturer();

        foreach ($souscriptions as $nom => $souscrire) {
            try {
                $souscrire();
                $this->fail("La souscription « {$nom} » ne doit pas être acceptée sur une année clôturée.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('est clôturée', $e->getMessage(), $nom);
            }
        }

        $this->assertSame(0, DB::table('inscriptions_cantine')->count());
        $this->assertSame(0, DB::table('abonnements_bus')->count());
        $this->assertSame(0, DB::table('inscriptions_activites')->count());
        $this->assertSame(0, DB::table('participation_evenements')->count());
        $this->assertSame(0, DB::table('souscriptions')->count());
    }

    public function test_une_annee_ouverte_accepte_les_souscriptions(): void
    {
        foreach ($this->souscriptions() as $nom => $souscrire) {
            $this->assertNotNull($souscrire(), $nom);
        }
    }

    public function test_l_ecran_de_paiement_ne_propose_plus_de_souscrire_sur_une_annee_cloturee(): void
    {
        $inscription = $this->creerInscription();
        DB::table('evenements')->insert([
            'nom' => 'Kermesse', 'type' => 'autre', 'date_evenement' => now()->addMonth()->toDateString(), 'participation' => 2000,
            'capacite_limitee' => 0, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));

        $this->get("/finances/paiements/{$inscription->eleve_id}")->assertOk()
            ->assertSee('id="barre-souscrire"', false)->assertDontSee('id="annee-cloturee-souscription"', false);
        $this->assertNotEmpty(app(PanierRechercheService::class)->rechercher('Kermesse', 'evenement', 20, $inscription->id)['lignes']);

        $this->cloturer();

        $this->get("/finances/paiements/{$inscription->eleve_id}")->assertOk()
            ->assertDontSee('id="barre-souscrire"', false)->assertSee('id="annee-cloturee-souscription"', false);
        // Plus de participation à un événement par le panier non plus
        $this->assertSame([], app(PanierRechercheService::class)->rechercher('Kermesse', 'evenement', 20, $inscription->id)['lignes']);

        // Le refus remonte aussi par l'écran (réponse AJAX en erreur)
        $this->postJson('/services-eleves/cantine/inscriptions', [
            'inscription_id' => $inscription->id, 'date_debut' => '2026-10-01', 'montant_mensuel' => 15000, 'nombre_mois' => 9,
        ])->assertStatus(422)->assertJsonPath('success', false);
    }
}
