<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\PaiementData;
use App\Domain\Finances\DTO\PanierLigneData;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Models\Mouvement;
use App\Domain\Finances\Models\Paiement;
use App\Domain\Finances\Services\EncaissementService;
use App\Domain\Finances\Services\PaiementService;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\MouvementType;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Repositories\BaseRepository;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.3 Encaissements
 * et règles transversales « Cycle de vie d'un paiement » et « Journal de caisse ».
 */
class EncaissementsTest extends DomainTestCase
{
    private EncaissementService $service;
    private User $caissier;
    private Caisse $caisse;
    private Paiement $paiement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EncaissementService::class);
        $this->caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $this->caisse = $this->creerCaisseOuverte($this->caissier, 10000);

        $this->paiement = app(PaiementService::class)->enregistrer(new PaiementData(
            $this->creerInscription()->id, 'KOFFI Yao', null, ModePaiement::ESPECES->value,
            [
                new PanierLigneData(TypeDetail::FRAIS_SCOLAIRE, 'Scolarité tranche 1', 225000),
                new PanierLigneData(TypeDetail::CANTINE, 'Cantine septembre', 45000),
            ],
            $this->creerUtilisateur(RoleUtilisateur::Comptable)->id
        ));
    }

    private function cloturerLaCaisse(): void
    {
        Caisse::whereKey($this->caisse->id)->update(['statut' => CaisseStatut::CLOTUREE->value, 'date_cloture' => now()]);
    }

    public function test_encaisser_une_ligne_constate_l_entree_d_argent_en_caisse(): void
    {
        $detail = $this->service->encaisser($this->paiement->details[0]->id, $this->caisse->id, $this->caissier->id);

        $this->assertSame(PaiementStatut::ENCAISSE, $detail->statut_paiement);
        $this->assertSame($this->caissier->id, (int) $detail->caissier_id);
        $this->assertSame($this->caisse->id, (int) $detail->caisse_id);
        $this->assertNotNull($detail->date_encaissement);
    }

    public function test_chaque_encaissement_alimente_le_journal_de_caisse(): void
    {
        $this->service->encaisser($this->paiement->details[0]->id, $this->caisse->id, $this->caissier->id);

        $mouvement = Mouvement::where('caisse_id', $this->caisse->id)->sole();
        $this->assertSame(MouvementType::ENCAISSEMENT, $mouvement->type_mouvement);
        $this->assertEqualsWithDelta(225000, (float) $mouvement->montant, 0.01);
        $this->assertSame($this->paiement->id, (int) $mouvement->paiement_id);
    }

    public function test_un_paiement_partiellement_encaisse_reste_en_attente(): void
    {
        $this->service->encaisser($this->paiement->details[0]->id, $this->caisse->id, $this->caissier->id);

        $this->assertSame(PaiementStatut::EN_ATTENTE, $this->paiement->fresh()->statut_paiement);
    }

    public function test_un_paiement_devient_encaisse_quand_toutes_ses_lignes_le_sont(): void
    {
        $encaissees = $this->service->encaisserPaiement($this->paiement->id, $this->caisse->id, $this->caissier->id);

        $this->assertCount(2, $encaissees);
        $this->assertSame(PaiementStatut::ENCAISSE, $this->paiement->fresh()->statut_paiement);
        $this->assertSame(2, Mouvement::where('caisse_id', $this->caisse->id)->count());
    }

    public function test_une_ligne_deja_encaissee_ne_l_est_pas_deux_fois(): void
    {
        $detailId = $this->paiement->details[0]->id;
        $this->service->encaisser($detailId, $this->caisse->id, $this->caissier->id);

        $this->expectException(RuntimeException::class);

        $this->service->encaisser($detailId, $this->caisse->id, $this->caissier->id);
    }

    public function test_seul_le_caissier_responsable_encaisse_dans_sa_caisse(): void
    {
        $autreCaissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);

        $this->expectException(RuntimeException::class);

        $this->service->encaisser($this->paiement->details[0]->id, $this->caisse->id, $autreCaissier->id);
    }

    public function test_on_n_encaisse_pas_dans_une_caisse_cloturee(): void
    {
        $this->cloturerLaCaisse();

        $this->expectException(RuntimeException::class);

        $this->service->encaisser($this->paiement->details[0]->id, $this->caisse->id, $this->caissier->id);
    }

    public function test_annuler_un_encaissement_avant_cloture_remet_la_ligne_en_attente(): void
    {
        $detailId = $this->paiement->details[0]->id;
        $this->service->encaisser($detailId, $this->caisse->id, $this->caissier->id);

        $detail = $this->service->annuler($detailId, 'Billet refusé', $this->caissier->id);

        $this->assertSame(PaiementStatut::EN_ATTENTE, $detail->statut_paiement);
        $this->assertNull($detail->date_encaissement);
        $this->assertNull($detail->caisse_id);
        $this->assertSame(PaiementStatut::EN_ATTENTE, $this->paiement->fresh()->statut_paiement);
    }

    public function test_annuler_un_encaissement_retire_son_mouvement_du_journal(): void
    {
        $detailId = $this->paiement->details[0]->id;
        $this->service->encaisser($detailId, $this->caisse->id, $this->caissier->id);

        $this->service->annuler($detailId, 'Billet refusé', $this->caissier->id);

        $this->assertSame(0, Mouvement::where('caisse_id', $this->caisse->id)->where('etat', BaseRepository::ACTIF)->count());
        // Archivé, jamais supprimé
        $this->assertSame(1, Mouvement::where('caisse_id', $this->caisse->id)->count());
    }

    public function test_annuler_une_ligne_ne_touche_pas_au_mouvement_des_autres_lignes_du_meme_paiement(): void
    {
        $this->service->encaisserPaiement($this->paiement->id, $this->caisse->id, $this->caissier->id);

        $this->service->annuler($this->paiement->details[1]->id, 'Erreur', $this->caissier->id);

        $restants = Mouvement::where('caisse_id', $this->caisse->id)->where('etat', BaseRepository::ACTIF)->get();
        $this->assertCount(1, $restants);
        $this->assertEqualsWithDelta(225000, (float) $restants->first()->montant, 0.01);
    }

    public function test_apres_cloture_de_la_caisse_un_encaissement_ne_s_annule_plus(): void
    {
        $detailId = $this->paiement->details[0]->id;
        $this->service->encaisser($detailId, $this->caisse->id, $this->caissier->id);
        $this->cloturerLaCaisse();

        $this->expectException(RuntimeException::class);

        $this->service->annuler($detailId, 'Trop tard', $this->caissier->id);
    }

    /* ===================== Écran Encaissements ===================== */

    private function encaisserParEcran(float $montantRecu, ?User $caissier = null)
    {
        $this->actingAs($caissier ?? $this->caissier);

        return $this->postJson("/finances/encaissements/paiement/{$this->paiement->id}/encaisser", ['montant_recu' => $montantRecu]);
    }

    public function test_le_caissier_encaisse_un_paiement_en_saisissant_le_montant_reellement_recu(): void
    {
        $this->encaisserParEcran(270000)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Encaissement effectué.')
            ->assertJsonPath('data.recu_url', route('finances.encaissements.paiement.recu', $this->paiement->id));

        $paiement = $this->paiement->fresh('details');
        $this->assertSame(PaiementStatut::ENCAISSE, $paiement->statut_paiement);
        $this->assertEqualsWithDelta(270000, $paiement->montant_recu, 0.01);
        foreach ($paiement->details as $detail) {
            $this->assertSame($this->caisse->id, (int) $detail->caisse_id);   // sa caisse ouverte
            $this->assertSame($this->caissier->id, (int) $detail->caissier_id);
        }
        $this->assertSame(2, Mouvement::where('caisse_id', $this->caisse->id)->where('etat', 1)->count());
    }

    public function test_un_montant_recu_superieur_donne_la_monnaie_a_rendre(): void
    {
        $this->encaisserParEcran(300000)->assertOk()->assertJsonPath('data.monnaie', 30000);

        // La caisse n'enregistre que ce qui est dû : la monnaie est rendue
        $this->assertEqualsWithDelta(270000, (float) Mouvement::where('caisse_id', $this->caisse->id)->sum('montant'), 0.01);
        $this->assertEqualsWithDelta(300000, $this->paiement->fresh()->montant_recu, 0.01);
    }

    public function test_un_montant_recu_insuffisant_est_refuse(): void
    {
        $this->encaisserParEcran(200000)->assertStatus(422)->assertJsonPath('success', false);

        $this->assertSame(PaiementStatut::EN_ATTENTE, $this->paiement->fresh()->statut_paiement);
        $this->assertSame(0, Mouvement::where('caisse_id', $this->caisse->id)->count());
    }

    public function test_un_cheque_s_encaisse_pour_son_montant_exact(): void
    {
        Paiement::whereKey($this->paiement->id)->update(['mode_paiement' => ModePaiement::CHEQUE->value]);

        $this->encaisserParEcran(300000)->assertStatus(422);
        $this->encaisserParEcran(270000)->assertOk();
    }

    public function test_sans_caisse_ouverte_on_n_encaisse_pas(): void
    {
        $sansCaisse = $this->creerUtilisateur(RoleUtilisateur::Caissier);

        $this->encaisserParEcran(270000, $sansCaisse)
            ->assertStatus(422)
            ->assertJsonPath('message', "Vous n'avez pas de caisse ouverte : ouvrez votre caisse (menu Caisses) avant d'encaisser.");

        $this->assertSame(PaiementStatut::EN_ATTENTE, $this->paiement->fresh()->statut_paiement);
    }

    public function test_un_utilisateur_ne_peut_pas_avoir_deux_caisses_ouvertes(): void
    {
        $this->expectException(RuntimeException::class);

        app(\App\Domain\Finances\Services\CaisseService::class)->ouvrir(new \App\Domain\Finances\DTO\OuvertureCaisseData(
            'Deuxième caisse', $this->caissier->id, 0, $this->caissier->id
        ));
    }

    public function test_l_ecran_presente_les_trois_onglets(): void
    {
        $this->actingAs($this->caissier);

        $page = $this->get('/finances/encaissements')->assertOk()
            ->assertSee('Tous les encaissements')
            ->assertSee('Paiements en attente')
            ->assertSee('Mes encaissements du jour')
            ->assertSee('id="btn-nouvel-encaissement"', false)
            ->assertSee($this->paiement->reference)          // en attente
            ->assertDontSee('Ouvrir une caisse');

        // Le paiement en attente propose « Encaisser » ; seul l'Administrateur peut l'annuler
        $page->assertSee('btn-encaisser-action', false)->assertDontSee('id="annulerPaiementModal"', false);

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));
        $this->get('/finances/encaissements?onglet=attente')->assertOk()->assertSee('id="annulerPaiementModal"', false)->assertSee('data-reference="' . $this->paiement->reference . '"', false);
    }

    public function test_les_encaissements_affichent_la_caisse_et_le_caissier(): void
    {
        $this->encaisserParEcran(270000)->assertOk();
        $this->caissier->update(['nom' => 'AMEGAN', 'prenom' => 'Kodjo']);

        $page = $this->get('/finances/encaissements?onglet=caisse')->assertOk()
            ->assertSee('Caisse principale')
            ->assertSee('AMEGAN Kodjo')
            ->assertSee('270 000')
            ->assertSee(route('finances.encaissements.paiement.recu', $this->paiement->id), false);

        // Il n'est plus en attente
        $this->assertSame(0, app(\App\Domain\Finances\Repositories\PaiementRepository::class)
            ->paiementsEnAttenteQuery(['annee_id' => $this->annee->id])->count());

        // Mes encaissements du jour : seulement les miens
        $autre = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $this->assertCount(1, app(\App\Domain\Finances\Repositories\PaiementRepository::class)->encaissementsDuJour($this->caissier->id));
        $this->assertCount(0, app(\App\Domain\Finances\Repositories\PaiementRepository::class)->encaissementsDuJour($autre->id));
    }

    public function test_la_recherche_d_un_paiement_en_attente_se_fait_par_le_nom_de_l_eleve(): void
    {
        $eleve = $this->paiement->fresh('inscription.eleve')->inscription->eleve;
        $this->actingAs($this->caissier);

        $this->getJson('/finances/encaissements/paiements-en-attente?q=' . urlencode($eleve->prenom))
            ->assertOk()
            ->assertJsonPath('results.0.id', $this->paiement->id);
        $this->getJson('/finances/encaissements/paiements-en-attente?q=zzzz')->assertOk()->assertJsonCount(0, 'results');

        $this->get("/finances/encaissements/paiement/{$this->paiement->id}/resume")
            ->assertOk()
            ->assertSee('Scolarité tranche 1')
            ->assertSee('Cantine septembre')
            ->assertSee('270 000');
    }

    public function test_le_recu_d_un_paiement_encaisse_s_imprime_a_tout_moment(): void
    {
        $this->actingAs($this->caissier);
        $this->get("/finances/encaissements/paiement/{$this->paiement->id}/recu")->assertNotFound();

        $this->encaisserParEcran(270000)->assertOk();
        $this->get("/finances/encaissements/paiement/{$this->paiement->id}/recu")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_le_caissier_annule_son_encaissement_tant_que_sa_caisse_est_ouverte(): void
    {
        $this->encaisserParEcran(270000)->assertOk();

        $this->postJson("/finances/encaissements/paiement/{$this->paiement->id}/annuler-encaissement", ['motif' => 'Erreur de saisie'])
            ->assertOk();

        $paiement = $this->paiement->fresh('details');
        $this->assertSame(PaiementStatut::EN_ATTENTE, $paiement->statut_paiement);
        $this->assertNull($paiement->montant_recu);
        $this->assertSame(0, Mouvement::where('caisse_id', $this->caisse->id)->where('etat', 1)->count());
    }

    public function test_seul_l_administrateur_annule_un_paiement_en_attente(): void
    {
        $this->actingAs($this->caissier);
        $this->postJson("/finances/encaissements/paiement/{$this->paiement->id}/annuler", ['motif' => 'Doublon de saisie'])
            ->assertForbidden();

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));
        $this->postJson("/finances/encaissements/paiement/{$this->paiement->id}/annuler", ['motif' => 'Doublon de saisie'])
            ->assertOk();

        $this->assertSame(PaiementStatut::ANNULE, $this->paiement->fresh()->statut_paiement);
    }

    public function test_un_paiement_en_partie_encaisse_ne_s_annule_pas(): void
    {
        $this->service->encaisser($this->paiement->details[0]->id, $this->caisse->id, $this->caissier->id);

        $this->expectException(RuntimeException::class);

        app(PaiementService::class)->annuler($this->paiement->id, 'Doublon', $this->caissier->id);
    }

    public function test_les_encaissements_suivent_l_annee_scolaire_choisie(): void
    {
        // Une année précédente, clôturée, avec un paiement encaissé et un paiement en attente
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $inscription = $this->creerInscription(null, ['annee_id' => $precedente->id]);
        $ancienneCaisse = $this->creerCaisseOuverte($this->caissier, 0, ['libelle' => 'Caisse 2025-2026', 'annee_id' => $precedente->id,
            'statut' => CaisseStatut::CLOTUREE->value, 'date_cloture' => '2026-05-10']);
        $ancien = function (string $reference, PaiementStatut $statut) use ($inscription, $precedente, $ancienneCaisse) {
            $id = \Illuminate\Support\Facades\DB::table('paiements')->insertGetId([
                'reference' => $reference, 'montant' => 5000, 'payeur' => 'ANCIEN Payeur', 'date_paiement' => '2026-05-10',
                'statut_paiement' => $statut->value, 'mode_paiement' => ModePaiement::ESPECES->value,
                'inscription_id' => $inscription->id, 'annee_id' => $precedente->id, 'etat' => 1,
            ]);
            \Illuminate\Support\Facades\DB::table('details')->insert([
                'paiement_id' => $id, 'inscription_id' => $inscription->id, 'annee_id' => $precedente->id,
                'libelle' => 'Scolarité', 'montant' => 5000, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
                'statut_paiement' => $statut->value, 'date_paiement' => '2026-05-10',
                'date_encaissement' => $statut === PaiementStatut::ENCAISSE ? '2026-05-10' : null,
                'caisse_id' => $statut === PaiementStatut::ENCAISSE ? $ancienneCaisse->id : null,
                'caissier_id' => $statut === PaiementStatut::ENCAISSE ? $this->caissier->id : null, 'etat' => 1,
            ]);

            return $id;
        };
        $ancien('PAI-ANCIEN-ENC', PaiementStatut::ENCAISSE);
        $enAttenteAncien = $ancien('PAI-ANCIEN-ATT', PaiementStatut::EN_ATTENTE);
        $this->encaisserParEcran(270000)->assertOk();

        // Sur l'année en cours : seulement ses encaissements
        $this->get('/finances/encaissements?annee_id=' . $this->annee->id)
            ->assertOk()
            ->assertSee('Année scolaire : ' . $this->annee->libelle)
            ->assertSee($this->paiement->reference)
            ->assertDontSee('PAI-ANCIEN-ENC')
            ->assertDontSee('PAI-ANCIEN-ATT');
        $this->getJson('/finances/encaissements/paiements-en-attente?q=ANCIEN')->assertOk()->assertJsonCount(0, 'results');

        // Un paiement d'une autre année ne s'encaisse pas depuis l'année en cours
        $this->postJson("/finances/encaissements/paiement/{$enAttenteAncien}/encaisser", ['montant_recu' => 5000])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        // L'année précédente choisie en haut de page : « Tous » et « En attente » passent sur ses paiements
        // (« Mes encaissements du jour » reste l'argent reçu aujourd'hui, toutes années confondues)
        $page = $this->get('/finances/encaissements?annee_id=' . $precedente->id)
            ->assertOk()
            ->assertSee('Année scolaire : 2025-2026')
            ->assertSee('PAI-ANCIEN-ENC')
            ->assertSee('PAI-ANCIEN-ATT');
        $this->assertFalse(collect($page->viewData('encaissements')->items())->contains('paiement_id', $this->paiement->id));
        $this->assertTrue($page->viewData('duJour')->contains('paiement_id', $this->paiement->id));
        $this->getJson('/finances/encaissements/paiements-en-attente?q=ANCIEN')
            ->assertOk()
            ->assertJsonPath('results.0.id', $enAttenteAncien);
    }

    public function test_les_paiements_en_attente_couvrent_toute_la_base_et_se_filtrent_par_annee(): void
    {
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $inscription = $this->creerInscription(null, ['annee_id' => $precedente->id]);
        $ancien = \Illuminate\Support\Facades\DB::table('paiements')->insertGetId([
            'reference' => 'PAI-ANCIEN-ATT', 'montant' => 5000, 'payeur' => 'ANCIEN Payeur', 'date_paiement' => '2026-05-10',
            'statut_paiement' => PaiementStatut::EN_ATTENTE->value, 'mode_paiement' => ModePaiement::ESPECES->value,
            'inscription_id' => $inscription->id, 'annee_id' => $precedente->id, 'etat' => 1,
        ]);
        \Illuminate\Support\Facades\DB::table('details')->insert([
            'paiement_id' => $ancien, 'inscription_id' => $inscription->id, 'annee_id' => $precedente->id,
            'libelle' => 'Scolarité', 'montant' => 5000, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
            'statut_paiement' => PaiementStatut::EN_ATTENTE->value, 'date_paiement' => '2026-05-10', 'etat' => 1,
        ]);
        $this->actingAs($this->caissier);

        // Par défaut : l'année choisie dans l'en-tête sert de filtre
        $page = $this->get('/finances/encaissements?onglet=attente')->assertOk()
            ->assertSee($this->paiement->reference)
            ->assertDontSee('PAI-ANCIEN-ATT')
            ->assertSee('Toutes les années (2)')
            ->assertSee('2025-2026 (1)');
        $this->assertSame($this->annee->id, $page->viewData('anneeAttente'));
        $this->assertSame(1, $page->viewData('compteurAttente')['nb']);

        // Toutes les années : liste complète, rangée par année (la plus récente d'abord)
        $page = $this->get('/finances/encaissements?onglet=attente&annee_attente=toutes')->assertOk()
            ->assertSee('Année scolaire 2025-2026')
            ->assertSeeInOrder([$this->paiement->reference, 'PAI-ANCIEN-ATT']);
        $this->assertNull($page->viewData('anneeAttente'));
        $this->assertSame(2, $page->viewData('compteurAttente')['nb']);
        $this->assertEqualsWithDelta(275000, $page->viewData('compteurAttente')['montant'], 0.01);

        // Une autre année que celle de l'en-tête
        $page = $this->get('/finances/encaissements?onglet=attente&annee_attente=' . $precedente->id)->assertOk()
            ->assertSee('PAI-ANCIEN-ATT')
            ->assertDontSee($this->paiement->reference);
        $this->assertSame(1, $page->viewData('compteurAttente')['nb']);

        // « Tous les encaissements » reste sur l'année choisie, et l'encaissement aussi
        $this->assertSame($this->annee->id, $page->viewData('filtres')['annee_id']);
        $this->postJson("/finances/encaissements/paiement/{$ancien}/encaisser", ['montant_recu' => 5000])->assertStatus(422);
    }

    public function test_les_paiements_en_attente_vont_du_plus_recent_au_plus_ancien(): void
    {
        $ancien = $this->paiement->id;
        Paiement::whereKey($ancien)->update(['date_paiement' => now()->subDays(5)->toDateString()]);
        $recent = app(PaiementService::class)->enregistrer(new PaiementData(
            $this->creerInscription()->id, 'MENSAH Afi', null, ModePaiement::ESPECES->value,
            [new PanierLigneData(TypeDetail::FRAIS_SCOLAIRE, 'Scolarité', 10000)],
            $this->creerUtilisateur(RoleUtilisateur::Comptable)->id
        ));

        $ordre = app(\App\Domain\Finances\Repositories\PaiementRepository::class)
            ->paiementsEnAttenteQuery(['annee_id' => $this->annee->id])->pluck('id')->all();

        $this->assertSame([$recent->id, $ancien], $ordre);
    }

    public function test_le_nouvel_encaissement_est_un_ecran_dedie(): void
    {
        $this->actingAs($this->caissier);

        $this->get('/finances/encaissements?onglet=caisse')
            ->assertOk()
            ->assertSee('href="' . route('finances.encaissements.nouveau') . '"', false);

        $this->get('/finances/encaissements/nouveau')
            ->assertOk()
            ->assertSee('Nouvel encaissement')
            ->assertSee('id="enc-paiement"', false)
            ->assertSee('Caisse « Caisse principale » ouverte');

        // Sans caisse ouverte : l'écran le signale ; d'autres profils n'y ont pas accès
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Caissier));
        $this->get('/finances/encaissements/nouveau')->assertOk()->assertSee('Aucune caisse ouverte');
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $this->get('/finances/encaissements/nouveau')->assertForbidden();
    }

    public function test_le_total_de_mes_encaissements_du_jour_ne_depend_pas_de_l_annee_choisie(): void
    {
        $this->encaisserParEcran(300000)->assertOk();   // 270 000 encaissés (30 000 rendus)
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);

        foreach ([$this->annee->id, $precedente->id] as $anneeChoisie) {
            $page = $this->get('/finances/encaissements?onglet=caisse&annee_id=' . $anneeChoisie)->assertOk();

            // Le total du jour est l'argent entré dans la caisse aujourd'hui
            $this->assertEqualsWithDelta(270000, $page->viewData('totalJour'), 0.01);
            $this->assertEqualsWithDelta(
                (float) Mouvement::where('caisse_id', $this->caisse->id)->where('etat', 1)->whereDate('date_mouvement', today())->sum('montant'),
                $page->viewData('totalJour'),
                0.01
            );
            $page->assertSee('270 000 F')->assertSee('<span id="nb-jour">1</span> encaissement(s)', false);
        }
    }

    public function test_quatre_onglets_mes_encaissements_et_caisse_ouverte_avec_son_journal(): void
    {
        // Un encaissement par moi, un autre par un collègue (dans sa propre caisse)
        $this->encaisserParEcran(270000)->assertOk();
        $collegue = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'COLLEGUE', 'prenom' => 'Yao']);
        $caisseCollegue = $this->creerCaisseOuverte($collegue, 0, ['libelle' => 'Caisse annexe']);
        $autre = app(PaiementService::class)->enregistrer(new PaiementData(
            $this->creerInscription()->id, 'MENSAH Afi', null, ModePaiement::ESPECES->value,
            [new PanierLigneData(TypeDetail::FRAIS_SCOLAIRE, 'Scolarité', 10000)],
            $this->creerUtilisateur(RoleUtilisateur::Comptable)->id
        ));
        $this->service->encaisser($autre->details()->first()->id, $caisseCollegue->id, $collegue->id);

        // Caissier avec une caisse ouverte : quatre onglets, la caisse ouverte par défaut
        $this->actingAs($this->caissier);
        $page = $this->get('/finances/encaissements')->assertOk()
            ->assertSee('id="onglet-tous"', false)->assertSee('id="onglet-attente"', false)
            ->assertSee('id="onglet-mes"', false)->assertSee('id="onglet-caisse"', false)
            ->assertSee('id="btn-journal-caisse"', false)
            ->assertSee('href="' . route('finances.caisses.show', $this->caisse->id) . '"', false);
        $this->assertSame('caisse', $page->viewData('onglet'));
        $this->assertSame(1, $page->viewData('totalCaisse')['nb']);
        $this->assertSame(1, $page->viewData('totalMes')['nb'], 'Mes encaissements : seulement les miens.');
        $this->assertEqualsWithDelta(270000, $page->viewData('totalMes')['montant'], 0.01);
        $this->assertSame(2, $page->viewData('encaissements')->total(), 'Tous les encaissements de l\'année.');
        // L'ancien onglet « jour » mène à la caisse ouverte
        $this->assertSame('caisse', $this->get('/finances/encaissements?onglet=jour')->viewData('onglet'));

        // Filtre par caissier
        $page = $this->get('/finances/encaissements?onglet=tous&caissier_id=' . $collegue->id)->assertOk()
            ->assertSee('id="filtre-caissier"', false)->assertSee('COLLEGUE Yao');
        $this->assertSame(1, $page->viewData('encaissements')->total());
        $this->assertSame($autre->reference, $page->viewData('encaissements')->first()->reference);

        // Caisse clôturée : plus d'onglet « Caisse ouverte » ; « Mes encaissements » reste
        $this->cloturerLaCaisse();
        $page = $this->get('/finances/encaissements')->assertOk()
            ->assertDontSee('id="onglet-caisse"', false)->assertSee('id="onglet-mes"', false);
        $this->assertSame('mes', $page->viewData('onglet'));
        $this->assertSame('mes', $this->get('/finances/encaissements?onglet=caisse')->viewData('onglet'));

        // Un caissier sans caisse ouverte ni encaissement : « Mes encaissements » (vide), pas de « Caisse ouverte »
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Caissier));
        $page = $this->get('/finances/encaissements')->assertOk()
            ->assertSee('id="onglet-mes"', false)->assertDontSee('id="onglet-caisse"', false)->assertDontSee('id="btn-journal-caisse"', false);
        $this->assertSame(0, $page->viewData('totalMes')['nb']);
    }

    public function test_le_recu_est_imprime_en_double_exemplaire(): void
    {
        $this->encaisserParEcran(270000)->assertOk();
        $paiement = app(\App\Domain\Finances\Repositories\PaiementRepository::class)->findPourEncaissement($this->paiement->id);
        $detail = app(\App\Domain\Finances\Repositories\PaiementRepository::class)->findDetailAvecRelations($paiement->details->first()->id);

        $html = view('domain.finances.encaissements.recu', [
            'detail' => $detail, 'paiement' => $paiement, 'eleve' => $paiement->inscription?->eleve,
            'caisse' => $detail->caisse, 'genereLe' => now(), 'generePar' => $this->caissier,
        ])->render();

        // Original et duplicata sur la même feuille, chacun sur une demi-page, séparés par un pointillé de découpe
        $this->assertSame(2, substr_count($html, 'exemplaire-mention'));
        $this->assertStringContainsString('Original — exemplaire du payeur', $html);
        $this->assertStringContainsString('Duplicata — exemplaire de l&#039;école', $html);
        $this->assertSame(2, substr_count($html, 'REÇU DE PAIEMENT'));
        $this->assertSame(2, substr_count($html, 'class="exemplaire demi"'));
        $this->assertSame(1, substr_count($html, 'class="decoupe"'));
        $this->assertStringNotContainsString('exemplaire seul', $html);

        $this->get(route('finances.encaissements.paiement.recu', $this->paiement->id))->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_l_ecran_des_encaissements_precise_le_niveau_et_la_classe_de_l_eleve(): void
    {
        $structure = $this->creerStructure('CE2');
        \Illuminate\Support\Facades\DB::table('inscriptions')->where('id', $this->paiement->inscription_id)
            ->update(['niveau_id' => $structure['niveau_id'], 'classe_id' => $structure['classe_id']]);
        $inscription = $this->paiement->fresh('inscription.niveau', 'inscription.classe')->inscription;
        $niveau = $inscription->niveau->libelle;
        $classe = $inscription->classe?->libelle;
        $this->actingAs($this->caissier);

        // Paiement en attente : niveau et classe
        $this->get('/finances/encaissements?onglet=attente')->assertOk()
            ->assertSee(' · ' . $niveau)->assertSee(' · ' . $classe);
        $texte = $this->getJson('/finances/encaissements/paiements-en-attente?q=' . urlencode($inscription->eleve->nom))
            ->assertOk()->json('results.0.text');
        $this->assertStringContainsString("({$niveau} · {$classe})", $texte);
        $this->get(route('finances.encaissements.paiement.resume', $this->paiement->id))->assertOk()
            ->assertSee('Niveau ' . $niveau)->assertSee('Classe ' . $classe);

        // Une fois encaissé : la ligne de la caisse aussi
        $this->encaisserParEcran(270000)->assertOk();
        $ligne = $this->get('/finances/encaissements?onglet=caisse')->assertOk()->viewData('encaissementsCaisse')->first();
        $this->assertSame($niveau, $ligne->niveau);
        $this->assertSame($classe, $ligne->classe);

        // Sans classe : le niveau seul
        \Illuminate\Support\Facades\DB::table('inscriptions')->where('id', $inscription->id)->update(['classe_id' => null]);
        $ligne = $this->get('/finances/encaissements?onglet=caisse')->assertOk()->viewData('encaissementsCaisse')->first();
        $this->assertSame($niveau, $ligne->niveau);
        $this->assertNull($ligne->classe);
    }
}
