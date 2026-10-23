<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Types\RoleUtilisateur as Role;
use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Services\PanierRechercheService;
use App\Domain\Finances\Services\SouscriptionService;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.2 Paiements : l'écran de saisie.
 *
 * Recherche de l'élève, situation frais par frais (dû, payé, en attente,
 * reste), paiements passés, puis saisie d'un nouveau paiement — y compris
 * partiel — sur ses engagements et sur les articles du catalogue.
 */
class SaisiePaiementTest extends DomainTestCase
{
    private EngagementService $engagements;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engagements = app(EngagementService::class);
    }

    /* ===================== Jeux de données ===================== */

    /** Inscription de la base historique : le dû est porté par l'inscription elle-même. */
    private function inscriptionHistorique(array $frais = []): Inscription
    {
        return $this->creerInscription(null, $frais + [
            'frais_scolarite'   => 469000,
            'frais_inscription' => 50000,
            'frais_assurance'   => 10000,
            'taux_remise'       => 0,
        ]);
    }

    private function detail(Inscription $inscription, float $montant, TypeDetail $type, bool $encaisse = true, array $attributs = []): int
    {
        return DB::table('details')->insertGetId($attributs + [
            'inscription_id'    => $inscription->id,
            'annee_id'          => $inscription->annee_id,
            'libelle'           => $type->label(),
            'montant'           => $montant,
            'type_paiement'     => $type->value,
            'statut_paiement'   => ($encaisse ? PaiementStatut::ENCAISSE : PaiementStatut::EN_ATTENTE)->value,
            'date_paiement'     => now()->toDateString(),
            'date_encaissement' => $encaisse ? now()->toDateString() : null,
            'etat'              => 1,
        ]);
    }

    /** @return array<string, array> lignes indexées par nature de frais (code TypeDetail) */
    private function lignesParType(Inscription $inscription): array
    {
        return collect($this->engagements->lignes($inscription->id))->keyBy('type')->all();
    }

    /* ===================== Situation frais par frais ===================== */

    public function test_les_frais_portes_par_l_inscription_sont_des_engagements(): void
    {
        $inscription = $this->inscriptionHistorique(['frais_cantine' => 235000, 'frais_bus' => 90000]);

        $lignes = $this->lignesParType($inscription);

        $this->assertEqualsWithDelta(50000, $lignes[TypeDetail::FRAIS_INSCRIPTION->value]['du'], 0.01);
        $this->assertEqualsWithDelta(469000, $lignes[TypeDetail::FRAIS_SCOLAIRE->value]['du'], 0.01);
        $this->assertEqualsWithDelta(10000, $lignes[TypeDetail::FRAIS_ASSURANCE->value]['du'], 0.01);
        $this->assertEqualsWithDelta(235000, $lignes[TypeDetail::CANTINE->value]['du'], 0.01);
        $this->assertEqualsWithDelta(90000, $lignes[TypeDetail::BUS->value]['du'], 0.01);
        $this->assertSame('inscription:' . TypeDetail::FRAIS_SCOLAIRE->value, $lignes[TypeDetail::FRAIS_SCOLAIRE->value]['cle']);
    }

    public function test_la_remise_de_l_inscription_s_applique_a_la_scolarite_seulement(): void
    {
        $inscription = $this->inscriptionHistorique(['taux_remise' => 5]);

        $lignes = $this->lignesParType($inscription);

        $this->assertEqualsWithDelta(445550, $lignes[TypeDetail::FRAIS_SCOLAIRE->value]['du'], 0.01);
        $this->assertEqualsWithDelta(50000, $lignes[TypeDetail::FRAIS_INSCRIPTION->value]['du'], 0.01);
    }

    public function test_la_scolarite_ne_vient_que_de_l_inscription_jamais_d_une_souscription(): void
    {
        $inscription = $this->inscriptionHistorique(['taux_remise' => 5]);
        // Une ancienne souscription de scolarité restée en base est ignorée
        DB::table('souscriptions')->insert([
            'inscription_id' => $inscription->id, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
            'montant_annuel_prevu' => 999000, 'taux_remise' => 0, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);

        try {
            app(SouscriptionService::class)->creer(new SouscriptionData(
                $this->creerFrais(469000)->id, $inscription->id, 10, now()->toDateString(), null
            ));
            $this->fail('La scolarité ne doit pas pouvoir être souscrite.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString("portées par l'inscription", $e->getMessage());
        }

        $scolarite = array_values(array_filter(
            $this->engagements->lignes($inscription->id),
            fn (array $l) => $l['type'] === TypeDetail::FRAIS_SCOLAIRE->value
        ));

        // Une seule ligne : le montant de l'inscription, remise de 5 % déduite
        $this->assertCount(1, $scolarite);
        $this->assertStringStartsWith('inscription:', $scolarite[0]['cle']);
        $this->assertEqualsWithDelta(445550, $scolarite[0]['du'], 0.01);
    }

    public function test_chaque_frais_distingue_le_paye_l_attente_d_encaissement_et_le_reste(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->detail($inscription, 200000, TypeDetail::FRAIS_SCOLAIRE);                 // encaissé
        $this->detail($inscription, 100000, TypeDetail::FRAIS_SCOLAIRE, encaisse: false); // saisi, pas encore encaissé
        $this->detail($inscription, 10000, TypeDetail::FRAIS_ASSURANCE);

        $lignes = $this->lignesParType($inscription);
        $scolarite = $lignes[TypeDetail::FRAIS_SCOLAIRE->value];

        $this->assertEqualsWithDelta(200000, $scolarite['paye'], 0.01);
        $this->assertEqualsWithDelta(100000, $scolarite['en_attente'], 0.01);
        $this->assertEqualsWithDelta(269000, $scolarite['reste'], 0.01);   // 469 000 − 200 000
        $this->assertEqualsWithDelta(169000, $scolarite['payable'], 0.01); // reste − ce qui attend la caisse

        $this->assertEqualsWithDelta(0, $lignes[TypeDetail::FRAIS_ASSURANCE->value]['reste'], 0.01);
        $this->assertEqualsWithDelta(50000, $lignes[TypeDetail::FRAIS_INSCRIPTION->value]['reste'], 0.01);
    }

    public function test_un_paiement_annule_ou_supprime_ne_compte_pas(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->detail($inscription, 100000, TypeDetail::FRAIS_SCOLAIRE, true, ['statut_paiement' => PaiementStatut::ANNULE->value]);
        $this->detail($inscription, 100000, TypeDetail::FRAIS_SCOLAIRE, true, ['etat' => 2]);

        $scolarite = $this->lignesParType($inscription)[TypeDetail::FRAIS_SCOLAIRE->value];

        $this->assertEqualsWithDelta(0, $scolarite['paye'], 0.01);
        $this->assertEqualsWithDelta(469000, $scolarite['payable'], 0.01);
    }

    public function test_l_achat_d_un_article_ne_solde_aucun_engagement(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->detail($inscription, 13000, TypeDetail::PRODUIT);

        $lignes = collect($this->engagements->lignes($inscription->id));

        $this->assertEqualsWithDelta(0, $lignes->sum('paye'), 0.01);
        $this->assertNull($lignes->firstWhere('type', TypeDetail::PRODUIT->value));
    }

    public function test_le_recouvrement_voit_le_meme_du_que_l_ecran_de_saisie(): void
    {
        $inscription = $this->inscriptionHistorique(['frais_cantine' => 235000, 'taux_remise' => 5]);

        $lignes = collect($this->engagements->lignes($inscription->id));
        $dus = $this->engagements->duParNature($inscription->id);

        // Écolage = scolarité remisée + inscription + assurance
        $this->assertEqualsWithDelta(445550 + 50000 + 10000, $dus[NatureCreance::SCOLARITE->value], 0.01);
        $this->assertEqualsWithDelta(235000, $dus[NatureCreance::CANTINE->value], 0.01);
        $this->assertEqualsWithDelta(
            $lignes->where('nature', NatureCreance::SCOLARITE->value)->sum('du'),
            $this->engagements->montantDu($inscription->id, NatureCreance::SCOLARITE->value),
            0.01
        );
    }

    /* ===================== Plafonds de saisie ===================== */

    public function test_un_montant_superieur_au_reste_a_payer_est_refuse(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->detail($inscription, 400000, TypeDetail::FRAIS_SCOLAIRE);
        $cle = 'inscription:' . TypeDetail::FRAIS_SCOLAIRE->value;

        $this->engagements->verifierPlafonds($inscription->id, [$cle => 69000]); // exactement le reste : accepté

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('dépasse ce qui reste à payer');
        $this->engagements->verifierPlafonds($inscription->id, [$cle => 69001]);
    }

    public function test_ce_qui_attend_deja_l_encaissement_ne_peut_pas_etre_saisi_une_seconde_fois(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->detail($inscription, 469000, TypeDetail::FRAIS_SCOLAIRE, encaisse: false);

        $this->expectException(RuntimeException::class);
        $this->engagements->verifierPlafonds($inscription->id, ['inscription:' . TypeDetail::FRAIS_SCOLAIRE->value => 1000]);
    }

    /* ===================== Catalogue ===================== */

    public function test_le_catalogue_propose_les_articles_vendus_comme_frais_mais_pas_les_frais_a_souscrire(): void
    {
        $structure = $this->creerStructure('CP');
        $inscription = $this->creerInscription(null, ['niveau_id' => $structure['niveau_id']]);
        $autreNiveau = $this->creerStructure('6e');

        $this->creerFrais(6500, TypeDetail::PRODUIT, ['libelle' => 'T-SHIRT']);
        $this->creerFrais(25000, TypeDetail::LIVRE, ['libelle' => 'LIVRES CP', 'niveau_id' => $structure['niveau_id']]);
        $this->creerFrais(30000, TypeDetail::LIVRE, ['libelle' => 'LIVRES 6e', 'niveau_id' => $autreNiveau['niveau_id']]);
        $this->creerFrais(469000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CP']);
        $this->creerFrais(235000, TypeDetail::CANTINE, ['libelle' => 'Déjeuner']);

        $catalogue = array_column(
            app(PanierRechercheService::class)->rechercher('', null, 20, $inscription->id)['lignes'],
            'libelle'
        );

        $this->assertEqualsCanonicalizing(['T-SHIRT', 'LIVRES CP'], $catalogue);
    }

    /* ===================== Écran (HTTP) ===================== */

    public function test_la_recherche_ne_propose_que_les_eleves_inscrits_sur_l_annee_de_travail(): void
    {
        $present = $this->creerEleve(['nom' => 'DUPONT', 'prenom' => 'Awa', 'matricule' => 'M-PRESENT']);
        $ancien  = $this->creerEleve(['nom' => 'DUPONT', 'prenom' => 'Ancien', 'matricule' => 'M-ANCIEN']);
        $this->creerInscription($present);
        $this->creerInscription($ancien, ['annee_id' => $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => 3])->id]);

        $this->actingAs($this->creerUtilisateur(Role::Comptable));
        $resultats = $this->getJson('/finances/paiements/eleves?q=dupont')->assertOk()->json('results');

        $this->assertCount(1, $resultats);
        $this->assertSame($present->id, $resultats[0]['id']);
        $this->assertStringContainsString('M-PRESENT', $resultats[0]['text']);

        // Moins de deux caractères : aucune recherche
        $this->getJson('/finances/paiements/eleves?q=d')->assertOk()->assertExactJson(['results' => []]);
    }

    public function test_l_ecran_affiche_la_situation_et_les_paiements_passes_de_l_eleve(): void
    {
        $eleve = $this->creerEleve(['nom' => 'KOFFI', 'prenom' => 'Ama', 'matricule' => 'M-0001']);
        $inscription = $this->creerInscription($eleve, ['frais_scolarite' => 469000, 'frais_cantine' => 235000]);
        $paiementId = DB::table('paiements')->insertGetId([
            'reference' => 'PAI-TEST-0001', 'montant' => 200000, 'payeur' => 'KOFFI Yao', 'date_paiement' => now()->toDateString(),
            'statut_paiement' => PaiementStatut::ENCAISSE->value, 'mode_paiement' => ModePaiement::ESPECES->value,
            'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $this->detail($inscription, 200000, TypeDetail::FRAIS_SCOLAIRE, true, ['paiement_id' => $paiementId, 'libelle' => 'Scolarité tranche 1']);

        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->get("/finances/paiements/{$eleve->id}")
            ->assertOk()
            ->assertSee('KOFFI Ama')
            ->assertSee('Situation de l', false)
            ->assertSee('PAI-TEST-0001')
            ->assertSee('Scolarité tranche 1')
            ->assertSee('269 000')    // reste de scolarité : 469 000 − 200 000
            ->assertSee('235 000');   // cantine entièrement due
    }

    public function test_un_eleve_sans_inscription_sur_l_annee_renvoie_a_la_recherche(): void
    {
        $eleve = $this->creerEleve();
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->get("/finances/paiements/{$eleve->id}")
            ->assertRedirect('/finances/paiements/nouveau')
            ->assertSessionHas('error');
    }

    public function test_un_paiement_partiel_est_enregistre_et_reduit_le_montant_saisissable(): void
    {
        $inscription = $this->inscriptionHistorique();
        $cle = 'inscription:' . TypeDetail::FRAIS_SCOLAIRE->value;
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/finances/paiements', [
            'inscription_id' => $inscription->id,
            'payeur'         => 'KOFFI Yao',
            'mode_paiement'  => ModePaiement::ESPECES->value,
            'lignes'         => [[
                'engagement' => $cle, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
                'libelle' => 'Frais de scolarité', 'montant' => 150000,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('details', [
            'inscription_id' => $inscription->id, 'montant' => 150000,
            'statut_paiement' => PaiementStatut::EN_ATTENTE->value,
        ]);

        $scolarite = $this->lignesParType($inscription)[TypeDetail::FRAIS_SCOLAIRE->value];
        $this->assertEqualsWithDelta(150000, $scolarite['en_attente'], 0.01);
        $this->assertEqualsWithDelta(319000, $scolarite['payable'], 0.01);
    }

    public function test_l_ecran_refuse_un_paiement_qui_depasse_le_reste_du(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/finances/paiements', [
            'inscription_id' => $inscription->id,
            'payeur'         => 'KOFFI Yao',
            'mode_paiement'  => ModePaiement::ESPECES->value,
            'lignes'         => [[
                'engagement' => 'inscription:' . TypeDetail::FRAIS_ASSURANCE->value,
                'type_paiement' => TypeDetail::FRAIS_ASSURANCE->value,
                'libelle' => "Frais d'assurance", 'montant' => 15000,
            ]],
        ])->assertStatus(422)->assertJsonPath('success', false);

        $this->assertDatabaseMissing('details', ['inscription_id' => $inscription->id]);
    }

    public function test_seuls_les_profils_qui_saisissent_des_paiements_accedent_a_l_ecran(): void
    {
        $eleve = $this->creerEleve();
        $this->creerInscription($eleve);

        $this->actingAs($this->creerUtilisateur(Role::Caissier));
        $this->get('/finances/paiements/nouveau')->assertForbidden();
        $this->getJson('/finances/paiements/eleves?q=ele')->assertForbidden();
        $this->get("/finances/paiements/{$eleve->id}")->assertForbidden();
    }

    /* ===================== Année scolaire sélectionnée ===================== */

    public function test_les_paiements_deja_enregistres_sont_ceux_de_l_annee_selectionnee(): void
    {
        $eleve = $this->creerEleve(['nom' => 'KOFFI', 'prenom' => 'Ama']);
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $ancienne = $this->creerInscription($eleve, ['annee_id' => $precedente->id, 'frais_scolarite' => 400000]);
        $courante = $this->creerInscription($eleve, ['frais_scolarite' => 469000]);

        $this->detail($ancienne, 400000, TypeDetail::FRAIS_SCOLAIRE, true, ['libelle' => 'Scolarité année passée']);
        $this->detail($courante, 100000, TypeDetail::FRAIS_SCOLAIRE, true, ['libelle' => 'Scolarité année en cours']);

        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->get("/finances/paiements/{$eleve->id}")
            ->assertOk()
            ->assertSee('Scolarité année en cours')
            ->assertDontSee('Scolarité année passée')
            ->assertSee('369 000');   // situation de l'année : 469 000 − 100 000
    }

    public function test_la_liste_des_paiements_est_celle_de_l_annee_selectionnee(): void
    {
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $this->paiement($this->inscriptionHistorique(), 'PAI-COURANT-01', 50000);
        $this->paiement($this->creerInscription(null, ['annee_id' => $precedente->id]), 'PAI-ANCIEN-01', 60000, ['annee_id' => $precedente->id]);

        $this->actingAs($this->saisisseur());

        $this->get('/finances/paiements')->assertOk()->assertSee('PAI-COURANT-01')->assertDontSee('PAI-ANCIEN-01');
    }

    /* ===================== Modes de paiement et chèque ===================== */

    /** Comptable qui a saisi les paiements créés par les tests (onglet « Mes paiements »). */
    private ?\App\Domain\Administration\Models\User $saisisseur = null;

    private function saisisseur(): \App\Domain\Administration\Models\User
    {
        return $this->saisisseur ??= $this->creerUtilisateur(Role::Comptable);
    }

    private function paiement(Inscription $inscription, string $reference, float $montant, array $attributs = []): int
    {
        return DB::table('paiements')->insertGetId($attributs + [
            'utilisateur_id' => $this->saisisseur()->id,
            'reference' => $reference, 'montant' => $montant, 'payeur' => 'KOFFI Yao', 'date_paiement' => now()->toDateString(),
            'statut_paiement' => PaiementStatut::ENCAISSE->value, 'mode_paiement' => ModePaiement::ESPECES->value,
            'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
    }

    /** Requête d'enregistrement d'un règlement d'assurance (10 000) pour le mode donné. */
    private function saisie(Inscription $inscription, ModePaiement $mode, array $complement = []): array
    {
        return $complement + [
            'inscription_id' => $inscription->id,
            'payeur'         => 'KOFFI Yao',
            'mode_paiement'  => $mode->value,
            'lignes'         => [[
                'engagement' => 'inscription:' . TypeDetail::FRAIS_ASSURANCE->value,
                'type_paiement' => TypeDetail::FRAIS_ASSURANCE->value,
                'libelle' => "Frais d'assurance", 'montant' => 10000,
            ]],
        ];
    }

    public function test_seuls_les_especes_et_le_cheque_sont_proposes_a_la_saisie(): void
    {
        $this->assertSame(
            [ModePaiement::ESPECES->value, ModePaiement::CHEQUE->value],
            array_keys(ModePaiement::disponibles())
        );

        $eleve = $this->creerEleve();
        $inscription = $this->creerInscription($eleve, ['frais_assurance' => 10000]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->get("/finances/paiements/{$eleve->id}")
            ->assertOk()
            ->assertSee(ModePaiement::ESPECES->label())
            ->assertSee(ModePaiement::CHEQUE->label())
            ->assertDontSee(ModePaiement::MOBILE_MONEY->label())
            ->assertDontSee(ModePaiement::VIREMENT->label());

        $this->postJson('/finances/paiements', $this->saisie($inscription, ModePaiement::MOBILE_MONEY))
            ->assertStatus(422);
        $this->assertDatabaseMissing('paiements', ['inscription_id' => $inscription->id]);
    }

    public function test_un_paiement_par_cheque_exige_le_numero_et_la_banque(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/finances/paiements', $this->saisie($inscription, ModePaiement::CHEQUE))
            ->assertStatus(422);
        $this->postJson('/finances/paiements', $this->saisie($inscription, ModePaiement::CHEQUE, ['cheque' => ['numero' => 'CHQ-0001']]))
            ->assertStatus(422);

        $this->assertDatabaseMissing('paiements', ['inscription_id' => $inscription->id]);
        $this->assertDatabaseCount('cheques', 0);
    }

    public function test_un_paiement_par_cheque_enregistre_le_cheque_au_portefeuille(): void
    {
        $inscription = $this->inscriptionHistorique();
        $banqueId = DB::table('banques')->insertGetId(['nom' => 'Ecobank', 'etat' => 1]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/finances/paiements', $this->saisie($inscription, ModePaiement::CHEQUE, [
            'cheque' => ['numero' => 'CHQ-0001', 'banque_id' => $banqueId, 'emetteur' => '', 'date_emission' => '2026-10-01'],
        ]))->assertOk()->assertJsonPath('success', true);

        $paiement = DB::table('paiements')->where('inscription_id', $inscription->id)->first();
        $cheque = DB::table('cheques')->where('numero', 'CHQ-0001')->first();

        $this->assertNotNull($cheque);
        $this->assertSame($cheque->id, $paiement->cheque_id);
        $this->assertSame($paiement->id, $cheque->paiement_id);
        $this->assertEqualsWithDelta(10000, $cheque->montant, 0.01);     // montant total du paiement
        $this->assertSame($banqueId, $cheque->banque_id);
        $this->assertSame('KOFFI Yao', $cheque->emetteur);                // à défaut d'émetteur : le payeur
        $this->assertSame(\App\Domain\Finances\Types\ChequeStatut::EMIS->value, (int) $cheque->statut);
        $this->assertSame($this->annee->id, $cheque->annee_id);

        // La liste rappelle le numéro du chèque et sa banque
        $this->get('/finances/paiements')->assertOk()->assertSee('CHQ-0001')->assertSee('Ecobank');
    }

    public function test_un_paiement_en_especes_ne_cree_aucun_cheque(): void
    {
        $inscription = $this->inscriptionHistorique();
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/finances/paiements', $this->saisie($inscription, ModePaiement::ESPECES))->assertOk();

        $this->assertDatabaseHas('paiements', ['inscription_id' => $inscription->id, 'cheque_id' => null]);
        $this->assertDatabaseCount('cheques', 0);
    }

    /* ===================== Liste des paiements ===================== */

    public function test_la_liste_des_paiements_tient_en_cinq_colonnes(): void
    {
        $eleve = $this->creerEleve(['nom' => 'KOFFI', 'prenom' => 'Ama', 'matricule' => 'M-0001']);
        $this->paiement($this->creerInscription($eleve), 'PAI-TEST-0001', 50000);
        $this->actingAs($this->saisisseur());

        $page = $this->get('/finances/paiements')->assertOk()
            ->assertSee('PAI-TEST-0001')
            ->assertSee('KOFFI Ama')      // l'élève réglé est rappelé à côté du payeur
            ->assertSee('M-0001');

        $this->assertSame(5, preg_match_all("/<th[\s>]/", $page->getContent()));
    }

    public function test_les_totaux_encaisses_couvrent_l_annee_le_mois_la_semaine_et_le_jour(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-14 10:00:00');   // un mercredi

        $inscription = $this->inscriptionHistorique();
        $le = fn (string $date) => ['date_paiement' => $date, 'date_encaissement' => $date];

        $this->detail($inscription, 1000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-14'));   // aujourd'hui
        $this->detail($inscription, 2000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-12'));   // lundi de la semaine
        $this->detail($inscription, 4000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-02'));   // même mois
        $this->detail($inscription, 8000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-09-20'));   // même année scolaire
        // Encaissé sans date d'encaissement (base historique) : la date de saisie fait foi
        $this->detail($inscription, 16000, TypeDetail::FRAIS_SCOLAIRE, true, ['date_paiement' => '2026-10-14', 'date_encaissement' => null]);

        // Ne comptent pas : en attente d'encaissement, annulé, supprimé, autre année scolaire
        $this->detail($inscription, 500000, TypeDetail::FRAIS_SCOLAIRE, false, $le('2026-10-14'));
        $this->detail($inscription, 500000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-14') + ['statut_paiement' => PaiementStatut::ANNULE->value]);
        $this->detail($inscription, 500000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-14') + ['etat' => 2]);
        $autre = $this->creerAnnee(['libelle' => '2025-2026']);
        $this->detail($inscription, 500000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-14') + ['annee_id' => $autre->id]);

        $totaux = app(\App\Domain\Finances\Repositories\PaiementRepository::class)->totauxEncaisses($this->annee->id);

        $this->assertEqualsWithDelta(17000, $totaux['jour'], 0.01);
        $this->assertEqualsWithDelta(19000, $totaux['semaine'], 0.01);
        $this->assertEqualsWithDelta(23000, $totaux['mois'], 0.01);
        $this->assertEqualsWithDelta(31000, $totaux['annee'], 0.01);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_l_onglet_des_totaux_par_type_separe_encaisse_et_en_attente_sur_chaque_periode(): void
    {
        \Illuminate\Support\Carbon::setTestNow('2026-10-14 10:00:00');   // un mercredi

        $inscription = $this->inscriptionHistorique();
        $le = fn (string $date) => ['date_paiement' => $date, 'date_encaissement' => $date];

        $this->detail($inscription, 1000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-14'));   // aujourd'hui
        $this->detail($inscription, 2000, TypeDetail::FRAIS_SCOLAIRE, true, $le('2026-10-12'));   // cette semaine
        $this->detail($inscription, 4000, TypeDetail::CANTINE, true, $le('2026-10-02'));          // ce mois
        $this->detail($inscription, 8000, TypeDetail::BUS, true, $le('2026-09-20'));              // cette année
        $this->detail($inscription, 300, TypeDetail::CANTINE, false, ['date_paiement' => '2026-10-14']);   // en attente, saisi aujourd'hui
        $this->detail($inscription, 700, TypeDetail::FRAIS_SCOLAIRE, false, ['date_paiement' => '2026-09-01']);
        // Ne comptent pas : inactif, annulé
        $this->detail($inscription, 900000, TypeDetail::CANTINE, true, $le('2026-10-14') + ['etat' => 2]);
        $this->detail($inscription, 900000, TypeDetail::CANTINE, true, $le('2026-10-14') + ['statut_paiement' => PaiementStatut::ANNULE->value]);

        $parType = app(\App\Domain\Finances\Repositories\PaiementRepository::class)->totauxParType($this->annee->id);
        $encaisse = $parType['encaisse']->keyBy('type');
        $attente = $parType['attente']->keyBy('type');

        $scolarite = $encaisse[TypeDetail::FRAIS_SCOLAIRE->value];
        $this->assertSame([3000.0, 3000.0, 3000.0, 1000.0], [$scolarite->annee, $scolarite->mois, $scolarite->semaine, $scolarite->jour]);
        $cantine = $encaisse[TypeDetail::CANTINE->value];
        $this->assertSame([4000.0, 4000.0, 0.0, 0.0], [$cantine->annee, $cantine->mois, $cantine->semaine, $cantine->jour]);
        $this->assertSame([8000.0, 0.0], [$encaisse[TypeDetail::BUS->value]->annee, $encaisse[TypeDetail::BUS->value]->mois]);
        $this->assertSame([300.0, 300.0], [$attente[TypeDetail::CANTINE->value]->annee, $attente[TypeDetail::CANTINE->value]->jour]);
        $this->assertSame([700.0, 0.0], [$attente[TypeDetail::FRAIS_SCOLAIRE->value]->annee, $attente[TypeDetail::FRAIS_SCOLAIRE->value]->mois]);

        // L'onglet : Administrateur et Direction ; tables de cinq colonnes
        $this->actingAs($this->creerUtilisateur(Role::Directeur));
        $page = $this->get('/finances/paiements?onglet=totaux')->assertOk()
            ->assertSee('Totaux par type de paiement')->assertSee('Paiements encaissés')->assertSee("Paiements en attente d'encaissement")
            ->assertSee('Cantine')->assertSee('Transport');
        $entete = explode('</thead>', explode('id="table-types-encaisse"', $page->getContent())[1])[0];
        $this->assertSame(5, preg_match_all('/<th[ >]/', $entete));

        $this->actingAs($this->creerUtilisateur(Role::Comptable));
        $this->get('/finances/paiements?onglet=totaux')->assertOk()
            ->assertDontSee('id="onglet-totaux"', false)->assertDontSee('id="totaux-par-type"', false);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_l_onglet_des_details_liste_chaque_ligne_avec_son_paiement_filtrable_et_pagine(): void
    {
        $inscription = $this->inscriptionHistorique();
        $paiement = DB::table('paiements')->insertGetId([
            'reference' => 'PAI-DETAIL-01', 'montant' => 158000, 'payeur' => 'MAMAN Test', 'date_paiement' => '2026-10-01',
            'statut_paiement' => PaiementStatut::ENCAISSE->value, 'mode_paiement' => \App\Domain\Finances\Types\ModePaiement::ESPECES->value,
            'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $this->detail($inscription, 150000, TypeDetail::FRAIS_SCOLAIRE, true, ['paiement_id' => $paiement, 'libelle' => 'Scolarité tranche 1']);
        $this->detail($inscription, 8000, TypeDetail::CANTINE, false, ['paiement_id' => $paiement, 'libelle' => 'Cantine octobre']);
        $this->detail($inscription, 999000, TypeDetail::CANTINE, true, ['paiement_id' => $paiement, 'libelle' => 'Ligne inactive', 'etat' => 2]);
        $autre = $this->creerAnnee(['libelle' => '2025-2026']);
        $this->detail($inscription, 777000, TypeDetail::BUS, true, ['libelle' => 'Bus autre année', 'annee_id' => $autre->id]);
        foreach (range(1, 25) as $n) {
            $this->detail($inscription, 1000, TypeDetail::PRODUIT, true, ['libelle' => "Article {$n}"]);
        }

        // Réservé à l'Administrateur : ni la Direction ni le Comptable ne le voient
        foreach ([Role::Directeur, Role::Comptable] as $role) {
            $this->actingAs($this->creerUtilisateur($role));
            $refus = $this->get('/finances/paiements?onglet=details')->assertOk()
                ->assertDontSee('id="onglet-details"', false)->assertDontSee('id="table-details"', false);
            $this->assertContains($refus->viewData('onglet'), ['mes', 'tous']);
        }

        $this->actingAs($this->creerUtilisateur(Role::Administrateur));
        $page = $this->get('/finances/paiements?onglet=details')->assertOk()
            ->assertSee('id="onglet-details"', false);
        $this->assertSame(27, $page->viewData('details')->total());          // lignes actives de l'année
        $this->assertSame(20, $page->viewData('details')->count());          // paginé
        $entete = explode('</thead>', explode('id="table-details"', $page->getContent())[1])[0];
        $this->assertSame(5, preg_match_all('/<th[ >]/', $entete));

        // Filtre par type : on y voit le paiement qui porte la ligne, et le lien vers son détail
        $page = $this->get('/finances/paiements?onglet=details&type_paiement=' . TypeDetail::CANTINE->value)->assertOk()
            ->assertSee('Cantine octobre')->assertSee('PAI-DETAIL-01')->assertSee('MAMAN Test')
            ->assertSee(route('finances.paiements.detail', $paiement), false)
            ->assertDontSee('Scolarité tranche 1')->assertDontSee('Ligne inactive');
        $this->assertSame(['lignes' => 1, 'montant' => 8000.0], $page->viewData('totauxDetails'));

        // Filtre par statut de la ligne, recherche, seconde page
        $this->assertSame(1, $this->get('/finances/paiements?onglet=details&statut_ligne=' . PaiementStatut::EN_ATTENTE->value)->viewData('details')->total());
        $this->assertSame(2, $this->get('/finances/paiements?onglet=details&search=PAI-DETAIL')->viewData('details')->total());
        $this->assertSame(7, $this->get('/finances/paiements?onglet=details&page=2')->viewData('details')->count());
    }

    /** @dataProvider profilsEtTotaux */
    public function test_les_totaux_encaisses_sont_reserves_a_l_administrateur_et_a_la_direction(Role $role, bool $visibles): void
    {
        $this->detail($this->inscriptionHistorique(), 123000, TypeDetail::FRAIS_SCOLAIRE);
        $this->actingAs($this->creerUtilisateur($role));

        $page = $this->get('/finances/paiements')->assertOk();

        if ($visibles) {
            $page->assertSee('Total des paiements encaissés')->assertSee('123 000');
        } else {
            $page->assertDontSee('Total des paiements encaissés')->assertDontSee('123 000');
        }
    }

    public static function profilsEtTotaux(): array
    {
        return [
            'Administrateur'         => [Role::Administrateur, true],
            'Directeur'              => [Role::Directeur, true],
            'Comptable'              => [Role::Comptable, false],
            'Administrateur adjoint' => [Role::AdministrateurAdjoint, false],
        ];
    }

    public function test_le_comptable_ne_voit_que_ses_paiements_et_la_direction_voit_tous_les_paiements_avec_un_filtre(): void
    {
        $moi = $this->saisisseur();
        $collegue = $this->creerUtilisateur(Role::Comptable, ['nom' => 'COLLEGUE', 'prenom' => 'Ama']);
        $inscription = $this->inscriptionHistorique();
        $mien = $this->paiement($inscription, 'PAI-MIEN-01', 50000);
        $autre = $this->paiement($inscription, 'PAI-COLLEGUE-01', 60000, ['utilisateur_id' => $collegue->id]);

        // Comptable : un seul onglet, « Mes paiements », avec ses seules saisies
        $this->actingAs($moi);
        $page = $this->get('/finances/paiements')->assertOk()
            ->assertSee('id="onglet-mes"', false)->assertDontSee('id="onglet-tous"', false)
            ->assertSee('PAI-MIEN-01')->assertDontSee('PAI-COLLEGUE-01');
        $this->assertSame('mes', $page->viewData('onglet'));
        $this->assertSame(1, $page->viewData('compteurs')['encaisse']);
        // Demander « Tous » ne change rien ; ni le détail ni l'export d'un paiement d'un collègue
        $this->get('/finances/paiements?onglet=tous')->assertOk()->assertDontSee('PAI-COLLEGUE-01');
        $this->get('/finances/paiements?onglet=tous&saisi_par=' . $collegue->id)->assertOk()->assertDontSee('PAI-COLLEGUE-01');
        $this->get(route('finances.paiements.detail', $autre))->assertForbidden();
        $this->get(route('finances.paiements.detail', $mien))->assertOk();

        // Direction : « Tous les paiements », filtrable par auteur de la saisie
        $this->actingAs($this->creerUtilisateur(Role::Directeur));
        $page = $this->get('/finances/paiements')->assertOk()
            ->assertSee('id="onglet-tous"', false)->assertDontSee('id="onglet-mes"', false)
            ->assertSee('PAI-MIEN-01')->assertSee('PAI-COLLEGUE-01')
            ->assertSee('id="filtre-saisi-par"', false)->assertSee('COLLEGUE Ama');
        $this->assertSame('tous', $page->viewData('onglet'));
        $this->get('/finances/paiements?onglet=tous&saisi_par=' . $collegue->id)->assertOk()
            ->assertSee('PAI-COLLEGUE-01')->assertDontSee('PAI-MIEN-01');

        // Administrateur : les deux onglets
        $this->actingAs($this->creerUtilisateur(Role::Administrateur));
        $this->get('/finances/paiements')->assertOk()->assertSee('id="onglet-mes"', false)->assertSee('id="onglet-tous"', false);
    }

    /* ===================== Détail d'un paiement ===================== */

    public function test_le_detail_resume_ce_qui_a_ete_fait_sur_le_paiement(): void
    {
        $eleve = $this->creerEleve(['nom' => 'KOFFI', 'prenom' => 'Ama', 'matricule' => 'M-0001']);
        $inscription = $this->creerInscription($eleve, ['frais_scolarite' => 469000, 'frais_assurance' => 10000]);
        $caissier = $this->creerUtilisateur(Role::Caissier, ['nom' => 'AMEGAN', 'prenom' => 'Kodjo']);
        $banqueId = DB::table('banques')->insertGetId(['nom' => 'Orabank', 'etat' => 1]);

        $paiementId = $this->paiement($inscription, 'PAI-DETAIL-01', 210000, [
            'statut_paiement' => PaiementStatut::EN_ATTENTE->value, 'mode_paiement' => ModePaiement::CHEQUE->value,
            'payeur' => 'KOFFI Yao', 'telephone_payeur' => '90000001',
        ]);
        $chequeId = DB::table('cheques')->insertGetId([
            'numero' => 'CHQ-4521', 'emetteur' => 'KOFFI Yao', 'montant' => 210000, 'banque_id' => $banqueId,
            'statut' => \App\Domain\Finances\Types\ChequeStatut::EMIS->value, 'paiement_id' => $paiementId,
            'annee_id' => $this->annee->id, 'date_emission' => '2026-10-01', 'etat' => 1,
        ]);
        DB::table('paiements')->where('id', $paiementId)->update(['cheque_id' => $chequeId]);
        $this->detail($inscription, 200000, TypeDetail::FRAIS_SCOLAIRE, true, [
            'paiement_id' => $paiementId, 'libelle' => 'Scolarité 1re tranche', 'caissier_id' => $caissier->id,
        ]);
        $this->detail($inscription, 10000, TypeDetail::FRAIS_ASSURANCE, false, ['paiement_id' => $paiementId, 'libelle' => 'Assurance annuelle']);

        $this->actingAs($this->saisisseur());

        $this->get('/finances/paiements')->assertOk()->assertSee(route('finances.paiements.detail', $paiementId), false);

        $this->get("/finances/paiements/{$paiementId}/detail")
            ->assertOk()
            ->assertSee('PAI-DETAIL-01')
            ->assertSee('KOFFI Ama')
            ->assertSee('M-0001')
            ->assertSee('KOFFI Yao')
            ->assertSee('90000001')
            ->assertSee('CHQ-4521')
            ->assertSee('Orabank')
            ->assertSee('Scolarité 1re tranche')
            ->assertSee('Assurance annuelle')
            ->assertSee('AMEGAN Kodjo')          // le caissier qui a encaissé
            ->assertSee('200 000')               // encaissé
            ->assertSee('10 000');               // en attente
    }

    public function test_le_detail_d_un_paiement_suit_les_droits_de_consultation(): void
    {
        $paiementId = $this->paiement($this->inscriptionHistorique(), 'PAI-DETAIL-02', 50000);

        $this->actingAs($this->creerUtilisateur(Role::Directeur));
        $this->get("/finances/paiements/{$paiementId}/detail")->assertOk()->assertSee('PAI-DETAIL-02');

        $this->actingAs($this->creerUtilisateur(Role::Caissier));
        $this->get("/finances/paiements/{$paiementId}/detail")->assertForbidden();

        $this->actingAs($this->creerUtilisateur(Role::Comptable));
        $this->get('/finances/paiements/999999/detail')->assertNotFound();
    }

    /* ===================== Souscrire depuis l'écran de paiement ===================== */

    public function test_l_ecran_de_saisie_propose_de_souscrire_cantine_transport_activite_et_evenement(): void
    {
        $eleve = $this->creerEleve();
        $this->creerInscription($eleve, ['frais_scolarite' => 300000]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $page = $this->get("/finances/paiements/{$eleve->id}")->assertOk();

        foreach (['cantine', 'transport', 'activite', 'evenement'] as $fenetre) {
            $page->assertSee('id="modal-' . $fenetre . '"', false);
        }
        // La ligne « Cantine — aucun engagement souscrit » ouvre la fenêtre au lieu de quitter l'écran
        $page->assertSee('data-bs-target="#modal-cantine"', false);
    }

    /** Engagement de l'élève pour une nature, après souscription. */
    private function engagement(Inscription $inscription, string $prefixe): ?array
    {
        return collect($this->engagements->lignes($inscription->id))
            ->first(fn (array $l) => str_starts_with($l['cle'], $prefixe . ':'));
    }

    public function test_souscrire_la_cantine_depuis_l_ecran_cree_aussitot_l_engagement_a_regler(): void
    {
        $eleve = $this->creerEleve();
        $inscription = $this->creerInscription($eleve, ['frais_scolarite' => 300000]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/services-eleves/cantine/inscriptions', [
            'inscription_id' => $inscription->id, 'date_debut' => now()->toDateString(),
            'montant_mensuel' => 15000, 'nombre_mois' => 10,
        ])->assertOk()->assertJsonPath('success', true);

        $cantine = $this->engagement($inscription, 'cantine');
        $this->assertNotNull($cantine);
        $this->assertEqualsWithDelta(150000, $cantine['du'], 0.01);
        $this->assertEqualsWithDelta(150000, $cantine['payable'], 0.01);

        // L'écran rechargé propose la cantine au règlement, sans renvoi vers la souscription
        $this->get("/finances/paiements/{$eleve->id}")
            ->assertOk()
            ->assertSee('data-cle="' . $cantine['cle'] . '"', false)
            ->assertSee('150 000');
    }

    public function test_souscrire_le_transport_depuis_l_ecran_cree_aussitot_l_engagement_a_regler(): void
    {
        $inscription = $this->creerInscription(null, ['frais_scolarite' => 300000]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/services-eleves/transport/abonnements', [
            'inscription_id' => $inscription->id, 'date_debut' => now()->toDateString(),
            'montant_mensuel' => 12000, 'nombre_mois' => 10, 'sens' => array_key_last(\App\Domain\ServicesEleves\Types\SensTrajet::options()),
        ])->assertOk();

        $this->assertEqualsWithDelta(120000, $this->engagement($inscription, 'bus')['du'], 0.01);
    }

    public function test_inscrire_a_une_activite_depuis_l_ecran_cree_aussitot_l_engagement_a_regler(): void
    {
        $eleve = $this->creerEleve();
        $inscription = $this->creerInscription($eleve, ['frais_scolarite' => 300000]);
        $activiteId = DB::table('activites')->insertGetId([
            'libelle' => 'Judo', 'montant' => 30000, 'type' => 'sportive', 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->get("/finances/paiements/{$eleve->id}")->assertOk()->assertSee('Judo');

        $this->postJson('/services-eleves/activites-evenements/activites/souscrire', [
            'eleve_id' => $eleve->id, 'activite_id' => $activiteId, 'date_inscription' => now()->toDateString(),
        ])->assertOk();

        $activite = $this->engagement($inscription, 'activite');
        $this->assertEqualsWithDelta(30000, $activite['du'], 0.01);
        $this->assertSame($activiteId, $activite['activite_id']);

        // Une activité déjà souscrite n'est plus proposée dans la fenêtre
        $this->assertFalse(collect(app(\App\Http\Controllers\Domain\Finances\PaiementController::class)
            ->show($eleve->id)->getData()['souscrire']['activites'])->contains('id', $activiteId));
    }

    public function test_pre_inscrire_a_un_evenement_depuis_l_ecran_cree_aussitot_l_engagement_a_regler(): void
    {
        $eleve = $this->creerEleve();
        $inscription = $this->creerInscription($eleve, ['frais_scolarite' => 300000]);
        $evenementId = DB::table('evenements')->insertGetId([
            'nom' => 'Sortie au zoo', 'type' => 'sortie_pedagogique', 'date_evenement' => now()->addMonth()->toDateString(),
            'participation' => 8000, 'capacite' => 30, 'capacite_limitee' => 1,
            'date_limite_confirmation' => now()->addDays(20)->toDateString(), 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $this->actingAs($this->creerUtilisateur(Role::Comptable));

        $this->postJson('/services-eleves/activites-evenements/evenements/pre-inscrire', [
            'evenement_id' => $evenementId, 'inscription_id' => $inscription->id,
        ])->assertOk();

        $evenement = $this->engagement($inscription, 'evenement');
        $this->assertEqualsWithDelta(8000, $evenement['du'], 0.01);
        $this->assertSame($evenementId, $evenement['evenement_id']);
    }

    public function test_la_direction_consulte_les_paiements_sans_pouvoir_en_saisir(): void
    {
        $eleve = $this->creerEleve();
        $this->creerInscription($eleve);
        $this->actingAs($this->creerUtilisateur(Role::Directeur));

        $this->get('/finances/paiements')->assertOk()->assertDontSee('Nouveau paiement');
        $this->get('/finances/paiements/nouveau')->assertForbidden();
        $this->get("/finances/paiements/{$eleve->id}")->assertForbidden();
    }
}
