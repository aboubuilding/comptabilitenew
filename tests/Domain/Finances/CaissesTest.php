<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\ClotureCaisseData;
use App\Domain\Finances\DTO\MouvementCaisseData;
use App\Domain\Finances\DTO\OuvertureCaisseData;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Services\CaisseService;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\MouvementType;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.4 Caisses
 * et règles transversales « Gestion des caisses et des écarts » et « Journal de caisse ».
 */
class CaissesTest extends DomainTestCase
{
    private CaisseService $service;
    private User $caissier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CaisseService::class);
        $this->caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
    }

    private function ouvrir(float $soldeInitial = 50000): Caisse
    {
        return $this->service->ouvrir(
            new OuvertureCaisseData('Caisse 1', $this->caissier->id, $soldeInitial, $this->caissier->id)
        );
    }

    private function mouvement(Caisse $caisse, MouvementType $type, float $montant): void
    {
        $this->service->enregistrerMouvement(new MouvementCaisseData(
            $caisse->id, $type, 'Mouvement de test', $montant, now()->toDateString(), $this->caissier->id
        ));
    }

    public function test_les_statuts_de_caisse_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, CaisseStatut::OUVERTE->value);
        $this->assertSame(2, CaisseStatut::CLOTUREE->value);
    }

    public function test_ouvrir_une_caisse_demarre_une_session_avec_son_solde_initial(): void
    {
        $caisse = $this->ouvrir(50000)->fresh();

        $this->assertTrue($caisse->isOuverte());
        $this->assertEqualsWithDelta(50000, (float) $caisse->solde_initial, 0.01);
        $this->assertSame($this->caissier->id, (int) $caisse->responsable_id);
        $this->assertSame($this->annee->id, (int) $caisse->annee_id);
        $this->assertNotNull($caisse->date_ouverture);
    }

    public function test_un_caissier_n_a_qu_une_caisse_ouverte_a_la_fois(): void
    {
        $this->ouvrir();

        $this->expectException(RuntimeException::class);

        $this->ouvrir();
    }

    public function test_le_solde_theorique_additionne_les_entrees_et_retranche_les_sorties(): void
    {
        $caisse = $this->ouvrir(50000);
        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 20000);
        $this->mouvement($caisse, MouvementType::SORTIE_MANUELLE, 5000);

        $journal = $this->service->preparerJournal($caisse->id);

        $this->assertEqualsWithDelta(20000, $journal['totalEntrees'], 0.01);
        $this->assertEqualsWithDelta(5000, $journal['totalSorties'], 0.01);
        $this->assertEqualsWithDelta(65000, $journal['soldeTheorique'], 0.01);
        $this->assertCount(2, $journal['mouvements']);
    }

    public function test_cloturer_sans_ecart_fige_le_solde_final(): void
    {
        $caisse = $this->ouvrir(50000);
        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 20000);

        $cloturee = $this->service->cloturer(new ClotureCaisseData($caisse->id, 70000, null, $this->caissier->id));

        $this->assertFalse($cloturee->isOuverte());
        $this->assertEqualsWithDelta(70000, (float) $cloturee->solde_final, 0.01);
        $this->assertEqualsWithDelta(0, (float) $cloturee->ecart, 0.01);
        $this->assertNotNull($cloturee->date_cloture);
    }

    public function test_un_ecart_de_caisse_doit_etre_motive(): void
    {
        $caisse = $this->ouvrir(50000);

        $this->expectException(RuntimeException::class);

        $this->service->cloturer(new ClotureCaisseData($caisse->id, 48000, null, $this->caissier->id));
    }

    public function test_un_ecart_motive_est_enregistre_a_la_cloture(): void
    {
        $caisse = $this->ouvrir(50000);

        $cloturee = $this->service->cloturer(
            new ClotureCaisseData($caisse->id, 48000, 'Erreur de rendu de monnaie', $this->caissier->id)
        );

        $this->assertEqualsWithDelta(-2000, (float) $cloturee->ecart, 0.01);
        $this->assertEqualsWithDelta(48000, (float) $cloturee->solde_compte, 0.01);
        $this->assertSame('Erreur de rendu de monnaie', $cloturee->motif_ecart);
    }

    public function test_une_caisse_deja_cloturee_ne_se_cloture_pas_deux_fois(): void
    {
        $caisse = $this->ouvrir(50000);
        $this->service->cloturer(new ClotureCaisseData($caisse->id, 50000, null, $this->caissier->id));

        $this->expectException(RuntimeException::class);

        $this->service->cloturer(new ClotureCaisseData($caisse->id, 50000, null, $this->caissier->id));
    }

    public function test_le_journal_d_une_caisse_cloturee_est_fige(): void
    {
        $caisse = $this->ouvrir(50000);
        $this->service->cloturer(new ClotureCaisseData($caisse->id, 50000, null, $this->caissier->id));

        $this->expectException(RuntimeException::class);

        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 1000);
    }

    public function test_le_journal_reste_consultable_apres_cloture(): void
    {
        $caisse = $this->ouvrir(50000);
        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 20000);
        $this->service->cloturer(new ClotureCaisseData($caisse->id, 70000, null, $this->caissier->id));

        $journal = $this->service->preparerJournal($caisse->id);

        $this->assertCount(1, $journal['mouvements']);
        $this->assertEqualsWithDelta(70000, $journal['soldeTheorique'], 0.01);
    }

    public function test_un_mouvement_de_caisse_a_un_montant_strictement_positif(): void
    {
        $caisse = $this->ouvrir();

        $this->expectException(RuntimeException::class);

        $this->mouvement($caisse, MouvementType::SORTIE_MANUELLE, 0);
    }

    public function test_une_caisse_d_une_autre_annee_scolaire_ne_recoit_plus_de_mouvement(): void
    {
        $autreAnnee = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => 3]);
        $caisse = $this->creerCaisseOuverte($this->caissier, 0, ['annee_id' => $autreAnnee->id]);

        $this->expectException(RuntimeException::class);

        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 1000);
    }

    /* ===================== Écrans ===================== */

    public function test_la_liste_des_caisses_tient_en_cinq_colonnes(): void
    {
        $this->ouvrir(10000);
        $this->actingAs($this->caissier);

        $page = $this->get('/finances/caisses')->assertOk()->assertSee('Ouverte')->assertSee('10 000,00');

        $this->assertSame(5, preg_match_all('/<th[\s>]/', $page->getContent()));
    }

    public function test_le_journal_presente_mouvements_encaissements_par_type_et_decaissements(): void
    {
        $caisse = $this->ouvrir(10000);

        // Un paiement de scolarité et de cantine encaissé dans la caisse
        $paiement = app(\App\Domain\Finances\Services\PaiementService::class)->enregistrer(new \App\Domain\Finances\DTO\PaiementData(
            $this->creerInscription()->id, 'KOFFI Yao', null, \App\Domain\Finances\Types\ModePaiement::ESPECES->value,
            [
                new \App\Domain\Finances\DTO\PanierLigneData(\App\Domain\Finances\Types\TypeDetail::FRAIS_SCOLAIRE, 'Scolarité tranche 1', 225000),
                new \App\Domain\Finances\DTO\PanierLigneData(\App\Domain\Finances\Types\TypeDetail::CANTINE, 'Cantine septembre', 45000),
            ],
            $this->creerUtilisateur(RoleUtilisateur::Comptable)->id
        ));
        app(\App\Domain\Finances\Services\EncaissementService::class)->encaisserPaiement($paiement->id, $caisse->id, $this->caissier->id);

        // Une entrée manuelle, une sortie manuelle, et une sortie annulée qui ne doit pas compter
        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 5000);
        $this->mouvement($caisse, MouvementType::SORTIE_MANUELLE, 3000);
        $this->mouvement($caisse, MouvementType::SORTIE_MANUELLE, 99999);
        \App\Domain\Finances\Models\Mouvement::where('caisse_id', $caisse->id)->where('montant', 99999)->update(['etat' => 0]);

        $this->actingAs($this->caissier);
        $page = $this->get("/finances/caisses/{$caisse->id}")->assertOk()
            ->assertSee('Tous les mouvements')
            ->assertSee('Tous les encaissements')
            ->assertSee('Décaissements')
            ->assertSee($paiement->reference)
            ->assertDontSee('99 999');

        $donnees = $page->viewData('totauxParType')->keyBy('libelle');
        $this->assertEqualsWithDelta(225000, $donnees[\App\Domain\Finances\Types\TypeDetail::FRAIS_SCOLAIRE->label()]['montant'], 0.01);
        $this->assertEqualsWithDelta(45000, $donnees[\App\Domain\Finances\Types\TypeDetail::CANTINE->label()]['montant'], 0.01);
        $this->assertEqualsWithDelta(270000, $page->viewData('totalEncaisse'), 0.01);
        $this->assertCount(1, $page->viewData('entreesManuelles'));
        $this->assertCount(1, $page->viewData('decaissements'));

        // Le solde théorique ignore la sortie annulée : 10 000 + 270 000 + 5 000 − 3 000
        $this->assertEqualsWithDelta(282000, $page->viewData('journal')['soldeTheorique'], 0.01);
        $page->assertSee('282 000,00');

        // Le journal en PDF reprend les totaux encaissés par type de paiement
        $html = null;
        \Illuminate\Support\Facades\View::composer('domain.finances.caisses.print', function ($vue) use (&$html) {
            $html = $vue;
        });
        $pdf = $this->get("/finances/caisses/{$caisse->id}/export/pdf")->assertOk();
        $this->assertStringContainsString('pdf', $pdf->headers->get('content-type'));

        $contenu = $html->render();
        $this->assertStringContainsString('Encaissements — totaux par type', $contenu);
        $this->assertStringContainsString(\App\Domain\Finances\Types\TypeDetail::FRAIS_SCOLAIRE->label(), $contenu);
        $this->assertStringContainsString('225 000,00', $contenu);
        $this->assertStringContainsString(\App\Domain\Finances\Types\TypeDetail::CANTINE->label(), $contenu);
        $this->assertStringContainsString('45 000,00', $contenu);
        $this->assertStringContainsString('Entrées manuelles', $contenu);
        $this->assertStringContainsString('275 000,00', $contenu);   // total encaissé : 270 000 + 5 000
    }

    /* ===================== Ouverture depuis l'écran ===================== */

    private function ouvrirParEcran(User $auteur, array $donnees)
    {
        $this->actingAs($auteur);

        return $this->postJson('/finances/caisses', $donnees + ['libelle' => 'Caisse du matin', 'solde_initial' => 0]);
    }

    public function test_un_caissier_ouvre_toujours_sa_propre_caisse(): void
    {
        $collegue = $this->creerUtilisateur(RoleUtilisateur::Caissier);

        // Même s'il désigne un collègue, la caisse est la sienne
        $this->ouvrirParEcran($this->caissier, ['responsable_id' => $collegue->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('caisses', ['libelle' => 'Caisse du matin', 'responsable_id' => $this->caissier->id]);
        $this->assertDatabaseMissing('caisses', ['responsable_id' => $collegue->id]);

        // L'écran ne lui propose pas de choisir le responsable
        $this->get('/finances/caisses')->assertOk()->assertSee('id="responsable-moi"', false);
    }

    public function test_l_administrateur_ouvre_une_caisse_pour_lui_meme_ou_la_confie(): void
    {
        $admin = $this->creerUtilisateur(RoleUtilisateur::Administrateur, ['nom' => 'SUPER', 'prenom' => 'Admin']);
        $this->actingAs($admin);

        // Il figure parmi les responsables proposés, sélectionné par défaut, avec les caissiers
        $enseignant = $this->creerUtilisateur(RoleUtilisateur::Enseignant);
        $page = $this->get('/finances/caisses')->assertOk()->assertSee('SUPER Admin (moi)');
        $this->assertTrue($page->viewData('responsables')->contains('id', $this->caissier->id));
        $this->assertFalse($page->viewData('responsables')->contains('id', $enseignant->id));   // n'encaisse pas

        $this->ouvrirParEcran($admin, ['responsable_id' => $admin->id])->assertOk();
        $this->assertDatabaseHas('caisses', ['responsable_id' => $admin->id]);

        $this->ouvrirParEcran($admin, ['responsable_id' => $this->caissier->id])->assertOk();
        $this->assertDatabaseHas('caisses', ['responsable_id' => $this->caissier->id, 'utilisateur_id' => $admin->id]);
    }

    public function test_le_refus_d_ouverture_donne_son_motif(): void
    {
        $this->ouvrir();

        $this->ouvrirParEcran($this->caissier, [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', "Ce caissier a déjà une caisse ouverte. Clôturez-la avant d'en ouvrir une nouvelle.");
    }

    public function test_les_compteurs_de_caisses_de_l_annee_sont_reserves_a_l_administrateur_et_a_la_direction(): void
    {
        // Année en cours : une caisse ouverte, une clôturée ; année précédente : une caisse
        $this->ouvrir();
        $this->creerCaisseOuverte($this->creerUtilisateur(RoleUtilisateur::Caissier), 0, ['statut' => CaisseStatut::CLOTUREE->value, 'date_cloture' => now()]);
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $this->creerCaisseOuverte($this->creerUtilisateur(RoleUtilisateur::Caissier), 0, ['annee_id' => $precedente->id, 'libelle' => 'Caisse ancienne']);

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));
        $page = $this->get('/finances/caisses?annee_id=' . $this->annee->id)->assertOk()->assertDontSee('Caisse ancienne');
        $this->assertSame(['total' => 2, 'ouvertes' => 1, 'fermees' => 1], array_intersect_key($page->viewData('compteurs'), array_flip(['total', 'ouvertes', 'fermees'])));
        $page->assertSee('id="compteur-caisses"', false)->assertSee('1 clôturée(s)');

        // L'année choisie en haut de page change la liste et les compteurs
        $page = $this->get('/finances/caisses?annee_id=' . $precedente->id)->assertOk()->assertSee('Caisse ancienne');
        $this->assertSame(['total' => 1, 'ouvertes' => 1, 'fermees' => 0], array_intersect_key($page->viewData('compteurs'), array_flip(['total', 'ouvertes', 'fermees'])));

        // La Direction aussi
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $this->get('/finances/caisses?annee_id=' . $this->annee->id)->assertOk()->assertSee('id="compteur-caisses"', false);

        // Les autres profils n'ont pas ces chiffres
        $this->actingAs($this->caissier);
        $this->get('/finances/caisses?annee_id=' . $this->annee->id)->assertOk()->assertDontSee('id="compteur-caisses"', false);
    }

    public function test_on_ne_peut_plus_ouvrir_de_caisse_dans_une_annee_cloturee(): void
    {
        $cloturee = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);

        // Année clôturée choisie en haut de page : refus, et l'écran ne propose plus l'ouverture
        $this->actingAs($this->caissier);
        $this->get('/finances/caisses?annee_id=' . $cloturee->id)
            ->assertOk()
            ->assertSee('id="annee-cloturee"', false)
            ->assertDontSee('data-bs-target="#caisseModal"', false);

        $this->postJson('/finances/caisses', ['libelle' => 'Caisse tardive', 'solde_initial' => 0])
            ->assertStatus(422)
            ->assertJsonPath('message', "L'année scolaire 2025-2026 est clôturée : on ne peut plus y ouvrir de caisse.");
        $this->assertDatabaseMissing('caisses', ['libelle' => 'Caisse tardive']);

        // Retour sur l'année ouverte : l'ouverture redevient possible
        $this->get('/finances/caisses?annee_id=' . $this->annee->id)->assertOk()->assertSee('data-bs-target="#caisseModal"', false);
        $this->postJson('/finances/caisses', ['libelle' => 'Caisse du jour', 'solde_initial' => 0])->assertOk();
        $this->assertDatabaseHas('caisses', ['libelle' => 'Caisse du jour', 'annee_id' => $this->annee->id]);
    }

    public function test_mes_caisses_pour_le_caissier_toutes_les_caisses_pour_l_administrateur_et_la_direction(): void
    {
        $this->ouvrir();
        $collegue = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'COLLEGUE', 'prenom' => 'Yao']);
        $this->creerCaisseOuverte($collegue, 0, ['libelle' => 'Caisse du collègue']);

        // Caissier : un seul onglet, ses caisses
        $this->actingAs($this->caissier);
        $page = $this->get('/finances/caisses')->assertOk()
            ->assertSee('id="onglet-mes-caisses"', false)->assertDontSee('id="onglet-toutes-caisses"', false)
            ->assertSee('Caisse 1')->assertDontSee('Caisse du collègue');
        $this->assertSame('mes', $page->viewData('onglet'));
        // Demander « toutes » ou filtrer sur un collègue ne change rien
        $this->get('/finances/caisses?onglet=toutes&responsable_id=' . $collegue->id)->assertOk()
            ->assertSee('Caisse 1')->assertDontSee('Caisse du collègue');

        // Direction : toutes les caisses (elle n'en ouvre pas : pas de « Mes caisses »)
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $page = $this->get('/finances/caisses')->assertOk()
            ->assertSee('id="onglet-toutes-caisses"', false)->assertDontSee('id="onglet-mes-caisses"', false)
            ->assertSee('Caisse 1')->assertSee('Caisse du collègue');
        $this->assertSame('toutes', $page->viewData('onglet'));

        // Administrateur : les deux onglets, « Toutes les caisses » par défaut
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));
        $page = $this->get('/finances/caisses')->assertOk()
            ->assertSee('id="onglet-mes-caisses"', false)->assertSee('id="onglet-toutes-caisses"', false);
        $this->assertSame('toutes', $page->viewData('onglet'));

        // Comptable : ni l'un ni l'autre
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $this->get('/finances/caisses')->assertOk()->assertSee('id="aucun-onglet-caisses"', false)->assertDontSee('Caisse 1');
    }

    public function test_le_filtre_caissier_propose_ceux_qui_ont_reellement_encaisse(): void
    {
        $caisse = $this->ouvrir();
        $collegue = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'COLLEGUE', 'prenom' => 'Yao']);
        $caisseCollegue = $this->creerCaisseOuverte($collegue, 0, ['libelle' => 'Caisse du collègue']);
        $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'JAMAIS', 'prenom' => 'Encaisse']);
        $paiementId = \Illuminate\Support\Facades\DB::table('paiements')->insertGetId(['reference' => 'PAI-CAISSE-1', 'montant' => 10000, 'annee_id' => $this->annee->id, 'etat' => 1]);
        \Illuminate\Support\Facades\DB::table('details')->insert([
            'paiement_id' => $paiementId, 'montant' => 10000, 'type_paiement' => 2, 'annee_id' => $this->annee->id,
            'statut_paiement' => \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value, 'date_encaissement' => now(),
            'caisse_id' => $caisseCollegue->id, 'caissier_id' => $collegue->id, 'etat' => 1,
        ]);

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $page = $this->get('/finances/caisses?onglet=toutes')->assertOk()->assertSee('id="filtre-caissier-caisses"', false);
        $this->assertSame([$collegue->id], $page->viewData('caissiers')->pluck('id')->all(), 'Seuls ceux qui ont encaissé.');
        $page->assertDontSee('JAMAIS Encaisse');

        $this->get('/finances/caisses?onglet=toutes&responsable_id=' . $collegue->id)->assertOk()
            ->assertSee('Caisse du collègue')->assertDontSee('>Caisse 1<', false);

        // Le filtre suit l'année de l'en-tête : sur une autre année, ce caissier n'a rien encaissé
        $autreAnnee = $this->creerAnnee(['libelle' => '2025-2026']);
        $ancien = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'ANCIEN', 'prenom' => 'Kossi']);
        \Illuminate\Support\Facades\DB::table('details')->insert([
            'paiement_id' => $paiementId, 'montant' => 5000, 'type_paiement' => 2, 'annee_id' => $autreAnnee->id,
            'statut_paiement' => \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value, 'date_encaissement' => now(),
            'caissier_id' => $ancien->id, 'etat' => 1,
        ]);
        $this->assertSame([$collegue->id], $this->get('/finances/caisses?onglet=toutes')->viewData('caissiers')->pluck('id')->all());
        $this->assertSame([$ancien->id], $this->get('/finances/caisses?onglet=toutes&annee_id=' . $autreAnnee->id)->viewData('caissiers')->pluck('id')->all());

        unset($caisse);
    }

    public function test_le_tableau_de_bord_compte_les_caisses_ouvertes_et_cloturees_par_periode(): void
    {
        // Aujourd'hui : deux ouvertes, dont une déjà clôturée ; le mois dernier : une ouverte et clôturée
        $this->ouvrir();
        $this->creerCaisseOuverte($this->creerUtilisateur(RoleUtilisateur::Caissier), 0, ['statut' => CaisseStatut::CLOTUREE->value, 'date_cloture' => now()]);
        $this->creerCaisseOuverte($this->creerUtilisateur(RoleUtilisateur::Caissier), 0, [
            'statut' => CaisseStatut::CLOTUREE->value,
            'date_ouverture' => now()->subMonthNoOverflow()->startOfMonth(), 'date_cloture' => now()->subMonthNoOverflow()->startOfMonth()->addHours(8),
        ]);

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $page = $this->get('/finances/caisses')->assertOk();
        $compteurs = $page->viewData('compteurs');

        $this->assertSame(3, $compteurs['total']);
        $this->assertSame(1, $compteurs['ouvertes']);
        $this->assertSame(2, $compteurs['fermees']);
        foreach (['jour', 'semaine', 'mois'] as $periode) {
            $this->assertSame(2, $compteurs['periodes'][$periode]['ouvertes'], $periode);
            $this->assertSame(1, $compteurs['periodes'][$periode]['cloturees'], $periode);
        }
        $page->assertSee('id="total-jour"', false)->assertSee('2 ouverte(s) · 1 clôturée(s)')->assertDontSee(' F</div>', false);
    }

    public function test_le_pdf_du_journal_ne_garde_que_les_totaux_par_type_avec_le_logo_et_le_caissier(): void
    {
        $this->caissier->update(['nom' => 'AMEGAN', 'prenom' => 'Kodjo']);
        $caisse = $this->ouvrir(10000);
        $this->mouvement($caisse, MouvementType::ENTREE_MANUELLE, 2000);
        $this->mouvement($caisse, MouvementType::SORTIE_MANUELLE, 1500);
        $this->mouvement($caisse, MouvementType::SORTIE_MANUELLE, 500);

        // Le PDF se télécharge ; son contenu : logo, caissier en grand, totaux par type, sans le détail des mouvements
        $vue = null;
        \Illuminate\Support\Facades\View::composer('domain.finances.caisses.print', function ($v) use (&$vue) {
            $vue = $v;
        });
        $this->actingAs($this->caissier);
        $this->get(route('finances.caisses.exportPdf', $caisse->id))->assertOk()->assertHeader('content-type', 'application/pdf');
        $html = $vue->render();

        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('<div class="nom" id="nom-caissier">AMEGAN Kodjo</div>', $html);
        $this->assertStringContainsString('Encaissements — totaux par type', $html);
        $this->assertStringContainsString('Décaissements — totaux par type', $html);
        $this->assertStringContainsString('Sortie manuelle', $html);
        $this->assertStringContainsString('2 000,00', $html, 'Total des deux sorties manuelles.');
        $this->assertStringNotContainsString('Journal chronologique', $html);
    }
}
