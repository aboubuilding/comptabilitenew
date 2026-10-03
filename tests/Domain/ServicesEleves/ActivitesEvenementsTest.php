<?php

namespace Tests\Domain\ServicesEleves;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\PaiementData;
use App\Domain\Finances\DTO\PanierLigneData;
use App\Domain\Finances\Services\EncaissementService;
use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Services\PaiementService;
use App\Domain\Finances\Services\PanierRechercheService;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\ServicesEleves\DTO\ActiviteData;
use App\Domain\ServicesEleves\DTO\EvenementData;
use App\Domain\ServicesEleves\DTO\InscriptionActiviteData;
use App\Domain\ServicesEleves\Models\Evenement;
use App\Domain\ServicesEleves\Models\ParticipationEvenement;
use App\Domain\ServicesEleves\Services\ActiviteService;
use App\Domain\ServicesEleves\Services\EvenementService;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 4, § 4.4 Activités & Événements
 * et règles transversales « Souscription indépendante du paiement » et
 * « Trois régimes d'engagement pour le paiement ».
 */
class ActivitesEvenementsTest extends DomainTestCase
{
    private ActiviteService $activites;
    private EvenementService $evenements;
    private Inscription $inscription;
    private int $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->activites = app(ActiviteService::class);
        $this->evenements = app(EvenementService::class);
        $this->inscription = $this->creerInscription();
        $this->agent = $this->creerUtilisateur()->id;
    }

    private function evenement(bool $capaciteLimitee = true, ?int $capacite = 2, ?string $dateLimite = null, string $nom = 'Voyage à Kpalimé'): Evenement
    {
        return $this->evenements->creer(new EvenementData(
            $nom, 'voyage', now()->addMonth()->toDateString(), 25000, $capacite, $capaciteLimitee,
            $dateLimite ?? now()->addWeeks(2)->toDateString(), null
        ));
    }

    /** Saisit puis encaisse le paiement de la participation à un événement. */
    private function payerEtEncaisser(Evenement $evenement, int $inscriptionId): void
    {
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $caisse = $this->creerCaisseOuverte($caissier);

        $paiement = app(PaiementService::class)->enregistrer(new PaiementData(
            $inscriptionId, 'Parent', null, ModePaiement::ESPECES->value,
            [new PanierLigneData(TypeDetail::EVENEMENT, $evenement->nom, 25000, evenementId: $evenement->id)],
            $this->agent
        ));

        app(EncaissementService::class)->encaisserPaiement($paiement->id, $caisse->id, $caissier->id);
    }

    /* ---------- Activités ---------- */

    public function test_souscrire_a_une_activite_fait_apparaitre_le_montant_du_sans_paiement(): void
    {
        $activite = $this->activites->creer(new ActiviteData('Football', null, 30000, 'sportive', 'Coach Ali', null, null));

        $souscription = $this->activites->souscrire(
            new InscriptionActiviteData($this->inscription->eleve_id, $activite->id, null, '2026-09-20')
        );

        $this->assertSame(StatutInscriptionActivite::EN_ATTENTE, $souscription->statut);
        $this->assertEqualsWithDelta(30000, (float) $souscription->montant_du, 0.01);
        $this->assertEqualsWithDelta(
            30000,
            app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::ACTIVITE->value),
            0.01
        );
        $this->assertSame(0, DB::table('paiements')->count());
    }

    public function test_un_eleve_ne_s_inscrit_pas_deux_fois_a_la_meme_activite(): void
    {
        $activite = $this->activites->creer(new ActiviteData('Football', null, 30000, 'sportive', null, null, null));
        $data = new InscriptionActiviteData($this->inscription->eleve_id, $activite->id, null, '2026-09-20');
        $this->activites->souscrire($data);

        $this->expectException(RuntimeException::class);

        $this->activites->souscrire($data);
    }

    public function test_annuler_une_inscription_a_une_activite_eteint_l_engagement(): void
    {
        $activite = $this->activites->creer(new ActiviteData('Football', null, 30000, 'sportive', null, null, null));
        $souscription = $this->activites->souscrire(
            new InscriptionActiviteData($this->inscription->eleve_id, $activite->id, null, '2026-09-20')
        );

        $this->activites->annulerInscription($souscription->id);

        $this->assertSame(StatutInscriptionActivite::ANNULEE, $souscription->fresh()->statut);
        $this->assertEqualsWithDelta(0, app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::ACTIVITE->value), 0.01);
    }

    public function test_les_compteurs_d_inscriptions_ignorent_le_montant_des_inscriptions_annulees(): void
    {
        $football = $this->activites->creer(new ActiviteData('Football', null, 30000, 'sportive', null, null, null));
        $danse = $this->activites->creer(new ActiviteData('Danse', null, 20000, 'artistique', null, null, null));
        $this->activites->souscrire(new InscriptionActiviteData($this->inscription->eleve_id, $football->id, null, '2026-09-20'));
        $annulee = $this->activites->souscrire(new InscriptionActiviteData($this->inscription->eleve_id, $danse->id, null, '2026-09-20'));
        $this->activites->annulerInscription($annulee->id);

        $stats = app(\App\Domain\ServicesEleves\Repositories\InscriptionActiviteRepository::class)
            ->compterStatistiques($this->annee->id);

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['en_attente']);
        $this->assertSame(0, $stats['confirmees']);
        $this->assertEqualsWithDelta(30000, $stats['montant_du'], 0.01);
    }

    public function test_une_activite_avec_des_inscrits_ne_peut_pas_etre_supprimee(): void
    {
        $activite = $this->activites->creer(new ActiviteData('Football', null, 30000, 'sportive', null, null, null));
        $this->activites->souscrire(new InscriptionActiviteData($this->inscription->eleve_id, $activite->id, null, '2026-09-20'));

        $this->expectException(RuntimeException::class);

        $this->activites->supprimer($activite->id);
    }

    /* ---------- Événements ---------- */

    public function test_un_evenement_a_capacite_limitee_doit_preciser_son_nombre_de_places(): void
    {
        $this->expectException(RuntimeException::class);

        $this->evenement(true, null);
    }

    public function test_pre_inscrire_un_eleve_avant_tout_paiement(): void
    {
        $evenement = $this->evenement();

        $participation = $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);

        $this->assertSame(StatutParticipation::PRE_INSCRIT, $participation->statut);
        $this->assertSame($this->annee->id, (int) $participation->fresh()->annee_id);
        $this->assertSame(0, DB::table('paiements')->count());
    }

    public function test_un_evenement_ouvert_a_tous_ne_demande_pas_de_pre_inscription(): void
    {
        $evenement = $this->evenement(false, null);

        $this->expectException(RuntimeException::class);

        $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);
    }

    public function test_un_eleve_n_est_pre_inscrit_qu_une_fois(): void
    {
        $evenement = $this->evenement();
        $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);

        $this->expectException(RuntimeException::class);

        $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);
    }

    public function test_la_pre_inscription_est_fermee_apres_la_date_limite(): void
    {
        $evenement = $this->evenement(true, 2, now()->subDay()->toDateString());

        $this->expectException(RuntimeException::class);

        $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);
    }

    public function test_l_encaissement_du_paiement_confirme_la_pre_inscription(): void
    {
        $evenement = $this->evenement();
        $participation = $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);

        $this->payerEtEncaisser($evenement, $this->inscription->id);

        $this->assertSame(StatutParticipation::CONFIRME, $participation->fresh()->statut);
    }

    public function test_la_simple_saisie_du_paiement_ne_confirme_pas_la_pre_inscription(): void
    {
        $evenement = $this->evenement();
        $participation = $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);

        app(PaiementService::class)->enregistrer(new PaiementData(
            $this->inscription->id, 'Parent', null, ModePaiement::ESPECES->value,
            [new PanierLigneData(TypeDetail::EVENEMENT, $evenement->nom, 25000, evenementId: $evenement->id)],
            $this->agent
        ));

        $this->assertSame(StatutParticipation::PRE_INSCRIT, $participation->fresh()->statut);
    }

    public function test_un_evenement_complet_n_accepte_plus_de_pre_inscription(): void
    {
        $evenement = $this->evenement(true, 1);
        $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);
        $this->payerEtEncaisser($evenement, $this->inscription->id);

        $this->expectException(RuntimeException::class);

        $this->evenements->preInscrire($evenement->id, $this->creerInscription()->id, $this->agent);
    }

    public function test_une_pre_inscription_annulee_libere_l_eleve(): void
    {
        $evenement = $this->evenement();
        $participation = $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);

        $this->evenements->annulerPreInscription($participation->id, $this->agent);
        $nouvelle = $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);

        $this->assertSame(StatutParticipation::ANNULE, $participation->fresh()->statut);
        $this->assertSame(StatutParticipation::PRE_INSCRIT, $nouvelle->statut);
        $this->assertSame(2, ParticipationEvenement::where('inscription_id', $this->inscription->id)->count());
    }

    /* ---------- Les trois régimes de l'écran Paiements ---------- */

    public function test_le_panier_ne_propose_a_la_recherche_que_les_evenements_ouverts_a_tous(): void
    {
        $this->evenement(true, 10, null, 'Voyage à Kpalimé');
        $this->evenement(false, null, null, 'Kermesse');

        $resultat = app(PanierRechercheService::class)->rechercher('', 'evenement');

        $this->assertSame(['Kermesse'], array_column($resultat['lignes'], 'libelle'));
    }

    public function test_un_evenement_a_capacite_limitee_n_apparait_au_paiement_qu_apres_pre_inscription(): void
    {
        $evenement = $this->evenement();
        $panier = app(PanierRechercheService::class);

        $avant = array_column($panier->engagementsDisponibles($this->inscription->id), 'libelle');
        $this->evenements->preInscrire($evenement->id, $this->inscription->id, $this->agent);
        $apres = collect($panier->engagementsDisponibles($this->inscription->id))->firstWhere('evenement_id', $evenement->id);

        $this->assertNotContains('Pré-inscription : Voyage à Kpalimé', $avant);
        $this->assertNotNull($apres);
        $this->assertEqualsWithDelta(25000, $apres['reste_du'], 0.01);
    }

    public function test_un_engagement_absent_renvoie_vers_l_ecran_de_souscription(): void
    {
        $engagements = collect(app(PanierRechercheService::class)->engagementsDisponibles($this->inscription->id));

        $cantine = $engagements->firstWhere('nature', NatureCreance::CANTINE->value);
        $this->assertFalse($cantine['engagement_existe']);
        $this->assertNotEmpty($cantine['lien_souscription']);
    }
}
