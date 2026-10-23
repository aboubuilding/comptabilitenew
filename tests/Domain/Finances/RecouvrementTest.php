<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\Models\DossierRecouvrement;
use App\Domain\Finances\Models\SituationRecouvrement;
use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Services\RecouvrementService;
use App\Domain\Finances\Services\RelanceService;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeRelance;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.8 Recouvrement
 * et règles transversales « Souscription indépendante du paiement » et
 * « Détection et suivi du recouvrement ».
 */
class RecouvrementTest extends DomainTestCase
{
    private Inscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        // Inscrit depuis 60 jours
        $this->inscription = $this->creerInscription(null, ['date_inscription' => now()->subDays(60)->toDateString()]);
    }

    /** La scolarité est due dès l'inscription, qui porte son montant et sa remise. */
    private function devoirScolarite(float $montant = 469000, int $remise = 0): void
    {
        DB::table('inscriptions')->where('id', $this->inscription->id)
            ->update(['frais_scolarite' => $montant, 'taux_remise' => $remise]);
    }

    private function encaisser(float $montant, TypeDetail $type = TypeDetail::FRAIS_SCOLAIRE, bool $encaisse = true): void
    {
        DB::table('details')->insert([
            'inscription_id'    => $this->inscription->id,
            'annee_id'          => $this->annee->id,
            'libelle'           => $type->label(),
            'montant'           => $montant,
            'type_paiement'     => $type->value,
            'statut_paiement'   => ($encaisse ? PaiementStatut::ENCAISSE : PaiementStatut::EN_ATTENTE)->value,
            'date_encaissement' => $encaisse ? now()->toDateString() : null,
            'etat'              => 1,
        ]);
    }

    private function dossier(): DossierRecouvrement
    {
        return DossierRecouvrement::create([
            'inscription_id'     => $this->inscription->id,
            'nature'             => NatureCreance::SCOLARITE->value,
            'montant_initial_du' => 200000,
            'montant_recouvre'   => 0,
            'statut'             => DossierStatut::EN_COURS->value,
            'date_ouverture'     => now()->toDateString(),
            'annee_id'           => $this->annee->id,
            'etat'               => 1,
        ]);
    }

    public function test_le_du_apparait_des_l_inscription_sans_aucun_paiement(): void
    {
        $this->devoirScolarite(469000);

        $engagements = app(EngagementService::class);

        $this->assertEqualsWithDelta(469000, $engagements->montantDu($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
        $this->assertEqualsWithDelta(0, $engagements->montantPaye($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
    }

    public function test_la_remise_accordee_reduit_le_montant_du(): void
    {
        $this->devoirScolarite(400000, 25);

        $du = app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::SCOLARITE->value);

        $this->assertEqualsWithDelta(300000, $du, 0.01);
    }

    public function test_seuls_les_encaissements_effectifs_reduisent_le_reste_a_payer(): void
    {
        $this->devoirScolarite(469000);
        $this->encaisser(225000);
        $this->encaisser(100000, encaisse: false); // saisi par le comptable, pas encore en caisse

        $engagements = app(EngagementService::class);

        $this->assertEqualsWithDelta(225000, $engagements->montantPaye($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
        $this->assertEqualsWithDelta(244000, $engagements->resteDu($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
    }

    public function test_un_encaissement_de_cantine_ne_solde_pas_l_ecolage(): void
    {
        $this->devoirScolarite(469000);
        $this->encaisser(45000, TypeDetail::CANTINE);

        $paye = app(EngagementService::class)->montantPaye($this->inscription->id, NatureCreance::SCOLARITE->value);

        $this->assertEqualsWithDelta(0, $paye, 0.01);
    }

    public function test_le_recalcul_photographie_la_situation_par_nature(): void
    {
        $this->devoirScolarite(469000);
        $this->encaisser(225000);

        $situation = app(RecouvrementService::class)->recalculerUne($this->inscription->id, NatureCreance::SCOLARITE->value);

        $this->assertNotNull($situation);
        $this->assertEqualsWithDelta(469000, (float) $situation->montant_du, 0.01);
        $this->assertEqualsWithDelta(225000, (float) $situation->montant_paye, 0.01);
        $this->assertEqualsWithDelta(244000, (float) $situation->retard, 0.01);
        $this->assertSame(60, (int) $situation->jours_retard);
    }

    public function test_un_eleve_sans_engagement_n_a_pas_de_situation(): void
    {
        $situation = app(RecouvrementService::class)->recalculerUne($this->inscription->id, NatureCreance::CANTINE->value);

        $this->assertNull($situation);
    }

    /**
     * Plan d'échéancier de la scolarité du niveau de l'élève : T1 échue il y a
     * 40 jours (200 000), T2 à échoir dans 60 jours (269 000).
     */
    private function planEcheancier(): void
    {
        ['niveau_id' => $niveau] = $this->creerStructure('CP');
        DB::table('inscriptions')->where('id', $this->inscription->id)->update(['niveau_id' => $niveau, 'frais_scolarite' => 469000]);
        app(\App\Domain\Finances\Services\EcheancierService::class)->definirPourFrais(
            $this->creerFrais(469000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CP', 'niveau_id' => $niveau]),
            [
                ['libelle' => 'T1', 'date_echeance' => now()->subDays(40)->toDateString(), 'montant' => 200000],
                ['libelle' => 'T2', 'date_echeance' => now()->addDays(60)->toDateString(), 'montant' => 269000],
            ]
        );
    }

    private function payerScolarite(float $montant): void
    {
        DB::table('details')->insert([
            'inscription_id' => $this->inscription->id, 'annee_id' => $this->annee->id, 'libelle' => 'Scolarité', 'montant' => $montant,
            'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value, 'statut_paiement' => PaiementStatut::ENCAISSE->value,
            'date_paiement' => now()->subDays(1)->toDateString(), 'date_encaissement' => now()->subDays(1)->toDateString(), 'etat' => 1,
        ]);
    }

    public function test_le_recalcul_ouvre_un_dossier_pour_chaque_echeance_echue_impayee(): void
    {
        $this->planEcheancier();

        $stats = app(RecouvrementService::class)->recalculerTout();

        $this->assertSame(1, $stats['echus']);
        $this->assertSame(1, $stats['dossiers_ouverts']);
        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertSame(DossierStatut::EN_COURS, $dossier->statut);
        $this->assertSame(NatureCreance::SCOLARITE->value, $dossier->getRawOriginal('nature'));
        // Seule l'échéance échue (T1) compte, pas la scolarité entière
        $this->assertEqualsWithDelta(200000, (float) $dossier->montant_initial_du, 0.01);
        $this->assertEqualsWithDelta(200000, $dossier->montant_restant, 0.01);
        $situation = SituationRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertSame(40, (int) $situation->jours_retard, 'Ancienneté comptée depuis la date butoir de T1.');
    }

    public function test_recalculer_ne_cree_pas_de_doublon_et_suit_les_paiements(): void
    {
        $this->planEcheancier();
        $service = app(RecouvrementService::class);

        $service->recalculerTout();
        $this->payerScolarite(150000);
        $stats = $service->recalculerTout();

        $this->assertSame(0, $stats['dossiers_ouverts']);
        $this->assertSame(1, $stats['dossiers_maj']);
        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertEqualsWithDelta(150000, (float) $dossier->montant_recouvre, 0.01);
        $this->assertEqualsWithDelta(50000, $dossier->montant_restant, 0.01);
        $this->assertCount(1, SituationRecouvrement::where('inscription_id', $this->inscription->id)->get(), 'Une photo par jour.');
    }

    public function test_aucun_dossier_avant_la_date_butoir_ni_sans_plan_d_echeancier(): void
    {
        // Sans plan d'échéancier : rien d'échu, même avec une scolarité due
        $this->devoirScolarite(469000);
        $this->assertSame(0, app(RecouvrementService::class)->recalculerTout()['dossiers_ouverts']);

        // Avec le plan, mais avant la date butoir de T1
        $this->planEcheancier();
        $stats = app(RecouvrementService::class)->recalculerTout(null, now()->subDays(41));

        $this->assertSame(0, $stats['dossiers_ouverts']);
        $this->assertSame(0, DossierRecouvrement::count());
    }

    public function test_un_eleve_revenu_a_jour_voit_son_dossier_solde(): void
    {
        $this->planEcheancier();
        $service = app(RecouvrementService::class);
        $service->recalculerTout();

        $this->payerScolarite(200000);
        $stats = $service->recalculerTout();

        $this->assertSame(0, $stats['echus']);
        $this->assertSame(1, $stats['dossiers_soldes']);
        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertSame(DossierStatut::SOLDE, $dossier->statut);
        $this->assertNotNull($dossier->date_cloture);
    }

    public function test_l_ecran_suit_l_annee_de_l_en_tete_et_le_recalcul_est_planifie_a_2_h(): void
    {
        $this->planEcheancier();
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));

        $this->postJson('/finances/recouvrement/recalculer')->assertOk()
            ->assertJsonPath('data.echus', 1)->assertJsonPath('data.dossiers_ouverts', 1);
        $page = $this->get('/finances/recouvrement')->assertOk()
            ->assertDontSee('<select name="annee_id" class="form-select filtre-input">', false)->assertSee('id="annee-recouvrement"', false);
        $this->assertSame(1, $page->viewData('dossiers')->total());

        // Une autre année choisie en haut : ses propres dossiers (aucun ici)
        $autre = $this->creerAnnee(['libelle' => '2025-2026']);
        $this->assertSame(0, $this->get('/finances/recouvrement?annee_id=' . $autre->id)->viewData('dossiers')->total());

        // Planification : chaque jour à 2 h
        $evenement = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'recouvrement:recalculer'));
        $this->assertNotNull($evenement, 'La commande recouvrement:recalculer est planifiée.');
        $this->assertSame('0 2 * * *', $evenement->expression);
        $this->artisan('recouvrement:recalculer', ['--annee' => [$this->annee->id]])->assertSuccessful();
    }

    public function test_chaque_relance_est_historisee_avec_son_canal_et_son_resultat(): void
    {
        $dossier = $this->dossier();

        $relance = app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::APPEL->value, now()->toDateString(), 'Parent injoignable',
            ResultatRelance::SANS_REPONSE->value, now()->addWeek()->toDateString(), $this->creerUtilisateur()->id
        );

        $this->assertSame($dossier->id, (int) $relance->dossier_recouvrement_id);
        $this->assertEqualsWithDelta(200000, (float) $relance->montant_du_moment, 0.01);
        $this->assertSame(1, $dossier->relances()->count());
        $this->assertSame(DossierStatut::EN_COURS, $dossier->fresh()->statut);
    }

    public function test_une_promesse_de_paiement_place_le_dossier_sous_echeancier(): void
    {
        $dossier = $this->dossier();

        app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::APPEL->value, now()->toDateString(), null,
            ResultatRelance::PROMESSE_PAIEMENT->value, null, null
        );

        $this->assertSame(DossierStatut::ECHEANCIER, $dossier->fresh()->statut);
    }

    public function test_un_paiement_total_solde_le_dossier(): void
    {
        $dossier = $this->dossier();

        app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::EMAIL->value, now()->toDateString(), null,
            ResultatRelance::PAIEMENT_TOTAL->value, null, null
        );

        $dossier->refresh();
        $this->assertSame(DossierStatut::SOLDE, $dossier->statut);
        $this->assertTrue($dossier->isClos());
        $this->assertNotNull($dossier->date_cloture);
        $this->assertEqualsWithDelta(0, $dossier->montant_restant, 0.01);
    }

    public function test_un_dossier_clos_ne_recoit_plus_de_relance(): void
    {
        $dossier = $this->dossier();
        app(RelanceService::class)->changerStatut($dossier->id, DossierStatut::IRRECOUVRABLE->value, 'Famille partie');

        $this->expectException(RuntimeException::class);

        app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::SMS->value, now()->toDateString(), null, null, null, null
        );
    }

    /* ===================== Échéances toutes natures, dossiers, relances, planification ===================== */

    /** Abonnement cantine de 9 mois à 15 000 F, commencé il y a 3 mois et demi. */
    private function abonnementCantine(): void
    {
        DB::table('inscriptions_cantine')->insert([
            'inscription_id' => $this->inscription->id, 'date_debut' => now()->subMonthsNoOverflow(3)->subDays(15)->toDateString(),
            'montant_mensuel' => 15000, 'nombre_mois' => 9, 'montant_total_du' => 135000, 'statut' => 1,
            'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
    }

    /** Un parent principal pour l'élève (même espace famille). */
    private function parent(?string $email = 'parent@exemple.tg', string $telephone = '90112233'): void
    {
        $espace = 1000 + $this->inscription->id;
        DB::table('inscriptions')->where('id', $this->inscription->id)->update(['espace_id' => $espace]);
        DB::table('parent_eleves')->insert(['nom_parent' => 'KOFFI', 'prenom_parent' => 'Paul', 'telephone' => $telephone,
            'email' => $email, 'espace_id' => $espace, 'is_principal' => 1, 'role' => 1, 'etat' => 1]);
    }

    public function test_les_mois_de_cantine_sont_des_echeances_couvertes_dans_l_ordre(): void
    {
        $this->abonnementCantine();
        $this->encaisser(20000, TypeDetail::CANTINE);   // 1er mois couvert, 2e mois à moitié... (5 000 / 15 000)

        $echeances = app(\App\Domain\Finances\Services\EcheancesEleveService::class)->echeances($this->annee->id, ['cantine']);
        $this->assertCount(9, $echeances);
        $this->assertEqualsWithDelta(135000, $echeances->sum('montant'), 0.01);
        $parDate = $echeances->sortBy(fn ($e) => $e['date']->timestamp)->values();
        $this->assertEqualsWithDelta(0, $parDate[0]['reste'], 0.01);
        $this->assertEqualsWithDelta(10000, $parDate[1]['reste'], 0.01);
        $this->assertEqualsWithDelta(15000, $parDate[2]['reste'], 0.01);

        // Au recalcul : 4 mois échus, 60 000 dus, 20 000 payés → 40 000 réclamés, sur la nature cantine
        app(RecouvrementService::class)->recalculerTout();
        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertEqualsWithDelta(40000, $dossier->montant_restant, 0.01);
        $this->assertSame(['cantine'], $dossier->echeances->map(fn ($e) => $e->nature->value)->unique()->values()->all());
    }

    public function test_un_seul_dossier_par_eleve_toutes_natures_et_filtre_par_nature(): void
    {
        $this->planEcheancier();
        $this->abonnementCantine();

        app(RecouvrementService::class)->recalculerTout();
        app(RecouvrementService::class)->recalculerTout();

        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertEqualsCanonicalizing(['scolarite', 'cantine'], $dossier->echeances->map(fn ($e) => $e->nature->value)->unique()->values()->all());
        $this->assertEqualsWithDelta(200000 + 60000, $dossier->montant_restant, 0.01);

        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));
        $page = $this->get('/finances/recouvrement')->assertOk()->assertSee('id="onglet-clotures"', false)->assertSee('id="onglet-relances"', false);
        $this->assertSame(5, preg_match_all('/<th[\s>]/', $this->sectionTableau($page->getContent(), 'table-dossiers')));
        $page->assertSee('CP')->assertDontSee('dossier-id', false);
        $this->assertSame(1, $this->get('/finances/recouvrement?nature=cantine')->viewData('dossiers')->total());
        $this->assertSame(0, $this->get('/finances/recouvrement?nature=bus')->viewData('dossiers')->total());
    }

    /** Le contenu HTML d'un tableau (pour compter ses colonnes). */
    private function sectionTableau(string $html, string $id): string
    {
        $debut = strpos($html, 'id="' . $id . '"');

        return substr($html, $debut, strpos($html, '</thead>', $debut) - $debut);
    }

    public function test_un_dossier_est_cloture_quand_le_parent_a_paye_avec_le_paiement_de_cloture(): void
    {
        $this->planEcheancier();
        $service = app(RecouvrementService::class);
        $service->recalculerTout();

        $paiementId = DB::table('paiements')->insertGetId(['reference' => 'PAI-SOLDE-T1', 'montant' => 200000, 'payeur' => 'KOFFI Paul',
            'date_paiement' => now()->toDateString(), 'inscription_id' => $this->inscription->id, 'annee_id' => $this->annee->id, 'etat' => 1]);
        DB::table('details')->insert(['paiement_id' => $paiementId, 'inscription_id' => $this->inscription->id, 'annee_id' => $this->annee->id,
            'libelle' => 'Scolarité', 'montant' => 200000, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
            'statut_paiement' => PaiementStatut::ENCAISSE->value, 'date_paiement' => now()->toDateString(), 'date_encaissement' => now()->toDateString(), 'etat' => 1]);
        $service->recalculerTout();

        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertSame(DossierStatut::SOLDE, $dossier->statut);
        $this->assertSame($paiementId, (int) $dossier->paiement_cloture_id);

        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));
        $page = $this->get('/finances/recouvrement?onglet=clotures')->assertOk()->assertSee('PAI-SOLDE-T1');
        $this->assertSame(1, $page->viewData('dossiers')->total());
        $this->get(route('finances.recouvrement.show', $dossier->id))->assertOk()->assertSee('id="paiement-cloture"', false)->assertSee('PAI-SOLDE-T1');
    }

    public function test_relance_groupee_ou_individuelle_par_message_et_historique(): void
    {
        $this->planEcheancier();
        $this->parent();
        $autre = $this->creerInscription(null, ['niveau_id' => $this->inscription->fresh()->niveau_id, 'frais_scolarite' => 469000]);   // sans parent
        app(RecouvrementService::class)->recalculerTout();
        $dossiers = DossierRecouvrement::pluck('id')->all();
        $this->assertCount(2, $dossiers);

        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));
        $this->postJson('/finances/recouvrement/relances', ['canal' => 'email', 'message' => 'Bonjour, {eleve} ({classe}) doit {montant} F : {echeances}.', 'dossiers' => $dossiers])
            ->assertOk()->assertJsonPath('data.bilan.relances', 2)->assertJsonPath('data.bilan.sans_destinataire', 1);

        $relance = \App\Domain\Finances\Models\Relance::where('inscription_id', $this->inscription->id)->sole();
        $this->assertSame('parent@exemple.tg', $relance->destinataire);
        $this->assertSame('a_envoyer', $relance->statut_envoi);
        $this->assertSame(TypeRelance::EMAIL, $relance->type_relance);
        $this->assertStringContainsString('doit 200 000 F', $relance->contenu);
        $this->assertStringContainsString('T1', $relance->contenu);
        $this->assertNotNull($relance->dossier_recouvrement_id);
        $this->assertSame('sans_destinataire', \App\Domain\Finances\Models\Relance::where('inscription_id', $autre->id)->value('statut_envoi'));

        // Relance individuelle par SMS
        $dossierEleve = DossierRecouvrement::where('inscription_id', $this->inscription->id)->value('id');
        $this->postJson('/finances/recouvrement/relances', ['canal' => 'sms', 'message' => '{eleve} : {montant} F', 'dossiers' => [$dossierEleve]])->assertOk();
        $this->assertSame('90112233', \App\Domain\Finances\Models\Relance::where('type_relance', 'sms')->value('destinataire'));

        // Historique des relances
        $page = $this->get('/finances/recouvrement?onglet=relances')->assertOk()->assertSee('parent@exemple.tg')->assertSee('Sans destinataire');
        $this->assertSame(3, $page->viewData('historique')->total());
        $this->assertSame(1, $this->get('/finances/recouvrement?onglet=relances&canal=sms')->viewData('historique')->total());

        // Message et canal obligatoires
        $this->postJson('/finances/recouvrement/relances', ['dossiers' => $dossiers])->assertStatus(422)->assertJsonValidationErrors(['canal', 'message']);
    }

    public function test_la_planification_simule_qui_ne_sera_pas_a_jour_par_type_et_niveau(): void
    {
        $this->planEcheancier();          // T1 échue, T2 dans 60 jours (niveau CP)
        $this->parent();
        $niveauCp = (int) $this->inscription->fresh()->niveau_id;
        ['niveau_id' => $niveauCe1] = $this->creerStructure('CE1');
        $ce1 = $this->creerInscription(null, ['niveau_id' => $niveauCe1]);
        DB::table('inscriptions_cantine')->insert(['inscription_id' => $ce1->id, 'date_debut' => now()->subDays(10)->toDateString(),
            'montant_mensuel' => 15000, 'nombre_mois' => 9, 'montant_total_du' => 135000, 'statut' => 1, 'annee_id' => $this->annee->id, 'etat' => 1]);
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));
        $jour = now()->addDays(70)->toDateString();

        // Plus de calendrier jour par jour ; un filtre « Niveau »
        $page = $this->get("/finances/recouvrement/planification?jour={$jour}")->assertOk()
            ->assertDontSee('id="table-calendrier"', false)->assertSee('name="niveau_id"', false)->assertSee('id="relanceCibleeModal"', false);
        $this->assertSame(2, $page->viewData('eleves')->total());
        $this->assertSame(1, $page->viewData('parNature')['scolarite']['eleves']);
        $this->assertSame(1, $page->viewData('parNature')['cantine']['eleves']);
        $this->assertCount(2, $page->viewData('choix'), 'Les élèves proposés au Select2.');

        // Un type de paiement, un niveau, une échéance
        $this->assertSame(1, $this->get("/finances/recouvrement/planification?jour={$jour}&nature=cantine")->viewData('eleves')->total());
        $this->assertSame(0, $this->get("/finances/recouvrement/planification?jour={$jour}&nature=cantine&niveau_id={$niveauCp}")->viewData('eleves')->total());
        $page = $this->get("/finances/recouvrement/planification?jour={$jour}&nature=scolarite&niveau_id={$niveauCp}&libelle=T2")->assertOk();
        $this->assertSame(1, $page->viewData('eleves')->total());
        $this->assertSame(['T2'], array_map(fn ($e) => preg_replace('/^.* — /u', '', $e['libelle']), $page->viewData('eleves')->first()['echeances']));

        // Relance de tous les élèves d'un type de paiement (toutes pages, filtres repris par le serveur)
        $this->postJson('/finances/recouvrement/relances', ['canal' => 'sms', 'message' => '{eleve} : {montant} F pour {echeances}',
            'tous' => 1, 'jour' => $jour, 'nature' => 'cantine'])->assertOk()->assertJsonPath('data.bilan.relances', 1);
        $this->assertSame($ce1->id, (int) \App\Domain\Finances\Models\Relance::sole()->inscription_id);

        // Relance de tous les élèves d'un niveau pour un type de paiement
        $this->postJson('/finances/recouvrement/relances', ['canal' => 'sms', 'message' => '{eleve} : {montant} F pour {echeances}',
            'tous' => 1, 'jour' => $jour, 'nature' => 'scolarite', 'niveau_id' => $niveauCp, 'libelle' => 'T2'])->assertOk()->assertJsonPath('data.bilan.relances', 1);
        $relance = \App\Domain\Finances\Models\Relance::where('inscription_id', $this->inscription->id)->sole();
        $this->assertStringContainsString('269 000 F', $relance->contenu);

        // Des élèves choisis (Select2) : seulement eux
        $this->postJson('/finances/recouvrement/relances', ['canal' => 'email', 'message' => 'Bonjour {eleve}',
            'inscriptions' => [$this->inscription->id], 'jour' => $jour])->assertOk()->assertJsonPath('data.bilan.relances', 1);
        $this->assertSame(3, \App\Domain\Finances\Models\Relance::count());
    }

    public function test_sans_dossier_pour_l_annee_l_ecran_le_dit(): void
    {
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));

        $this->get('/finances/recouvrement')->assertOk()->assertSee('id="pas-de-dossiers"', false)
            ->assertSee("Pas de dossiers pour l'année " . $this->annee->libelle, false);
        $this->postJson('/finances/recouvrement/recalculer')->assertOk()->assertJsonPath('data.echus', 0);
    }

    /** Deux relances « à envoyer » (email et SMS) et une sans destinataire. */
    private function relancesAEnvoyer(): array
    {
        $this->planEcheancier();
        $this->parent();
        app(RecouvrementService::class)->recalculerTout();
        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->value('id');
        $service = app(RelanceService::class);
        $service->relancerParMessage([$this->inscription->id => ['montant' => 200000, 'echeances' => 'T1']], 'email', 'Bonjour {eleve}', null);
        $service->relancerParMessage([$this->inscription->id => ['montant' => 200000, 'echeances' => 'T1']], 'sms', '{eleve} : {montant} F', null);
        unset($dossier);

        return \App\Domain\Finances\Models\Relance::orderBy('id')->pluck('id')->all();
    }

    public function test_l_envoi_reel_des_relances_est_desactive_en_developpement(): void
    {
        config(['finances.relances_envoi_actif' => false]);
        \Illuminate\Support\Facades\Mail::fake();
        $ids = $this->relancesAEnvoyer();
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));

        $this->get('/finances/recouvrement?onglet=relances')->assertOk()
            ->assertSee('id="btn-envoyer-relances"', false)->assertSee('id="envoi-desactive"', false)
            ->assertSee('class="form-check-input me-2 selection-relance"', false);

        $this->postJson('/finances/recouvrement/relances/envoyer', ['relances' => $ids])
            ->assertStatus(422)->assertJsonPath('success', false)
            ->assertJsonPath('message', fn ($m) => str_contains($m, "n'est pas encore activé"));

        // Rien n'est parti : messages et statuts conservés
        \Illuminate\Support\Facades\Mail::assertNothingSent();
        $this->assertSame(['a_envoyer', 'a_envoyer'], \App\Domain\Finances\Models\Relance::orderBy('id')->pluck('statut_envoi')->all());
    }

    public function test_une_fois_active_l_envoi_part_par_email_et_le_sms_attend_sa_passerelle(): void
    {
        config(['finances.relances_envoi_actif' => true]);
        \Illuminate\Support\Facades\Mail::fake();
        [$email, $sms] = $this->relancesAEnvoyer();
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Administrateur));

        $this->get('/finances/recouvrement?onglet=relances')->assertOk()->assertDontSee('id="envoi-desactive"', false);
        $this->postJson('/finances/recouvrement/relances/envoyer', ['relances' => [$email, $sms]])->assertOk()
            ->assertJsonPath('data.bilan.envoyes', 1)->assertJsonPath('data.bilan.echecs', 1);

        $this->assertSame('envoye', \App\Domain\Finances\Models\Relance::find($email)->statut_envoi);
        $this->assertSame('echec', \App\Domain\Finances\Models\Relance::find($sms)->statut_envoi);
        // Déjà envoyée : ignorée au second passage
        $this->postJson('/finances/recouvrement/relances/envoyer', ['relances' => [$email]])->assertOk()->assertJsonPath('data.bilan.ignores', 1);
        $this->postJson('/finances/recouvrement/relances/envoyer', [])->assertStatus(422)->assertJsonValidationErrors(['relances']);
    }
}
