<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\DepenseData;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Models\Mouvement;
use App\Domain\Finances\Repositories\DepenseRepository;
use App\Domain\Finances\Services\DepenseService;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\DepenseStatut;
use App\Domain\Finances\Types\MouvementType;
use App\Repositories\BaseRepository;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.6 Dépenses
 * et règle transversale « Circuit de validation des dépenses ».
 */
class DepensesTest extends DomainTestCase
{
    private DepenseService $service;
    private User $comptable;
    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DepenseService::class);
        $this->comptable = $this->creerUtilisateur(RoleUtilisateur::Comptable);
        $this->directeur = $this->creerUtilisateur(RoleUtilisateur::Directeur);
    }

    /** Montant par défaut au-dessus du seuil de validation d'office : la dépense attend la Direction. */
    private function saisir(float $montant = 250000): Depense
    {
        return $this->service->saisir(
            new DepenseData('Fournitures de bureau', 'Papeterie du Centre', 'Rames de papier', '2026-09-20', $montant, $this->comptable->id)
        );
    }

    private function depenseReglee(): array
    {
        $caisse = $this->creerCaisseOuverte($this->comptable, 100000);
        $depense = $this->saisir();
        $this->service->valider($depense->id, $this->directeur->id);
        $depense = $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);

        return [$depense, $caisse];
    }

    public function test_les_statuts_de_depense_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, DepenseStatut::EN_ATTENTE->value);
        $this->assertSame(2, DepenseStatut::REJETEE->value);
        $this->assertSame(3, DepenseStatut::VALIDEE->value);
        $this->assertSame(4, DepenseStatut::PAYEE->value);
    }

    public function test_une_depense_saisie_attend_la_validation_de_la_direction(): void
    {
        $depense = $this->saisir()->fresh();

        $this->assertTrue($depense->isEnAttente());
        $this->assertSame('Papeterie du Centre', $depense->beneficiaire);
        $this->assertSame($this->comptable->id, (int) $depense->utilisateur_id);
        $this->assertSame($this->annee->id, (int) $depense->annee_id);
    }

    public function test_la_direction_valide_une_depense(): void
    {
        $depense = $this->service->valider($this->saisir()->id, $this->directeur->id);

        $this->assertTrue($depense->isValidee());
        $this->assertSame($this->directeur->id, (int) $depense->valide_par);
        $this->assertNotNull($depense->date_validation);
    }

    public function test_un_rejet_de_depense_exige_un_motif(): void
    {
        $depense = $this->saisir();

        $this->expectException(RuntimeException::class);

        $this->service->rejeter($depense->id, $this->directeur->id, '');
    }

    public function test_une_depense_rejetee_conserve_son_motif(): void
    {
        $depense = $this->service->rejeter($this->saisir()->id, $this->directeur->id, 'Hors budget');

        $this->assertTrue($depense->isRejetee());
        $this->assertSame('Hors budget', $depense->motif_rejet);
    }

    public function test_une_depense_deja_validee_ne_se_modifie_plus(): void
    {
        $depense = $this->saisir();
        $this->service->valider($depense->id, $this->directeur->id);

        $this->expectException(RuntimeException::class);

        $this->service->modifier($depense->id, new DepenseData('Autre', 'Autre', null, '2026-09-21', 1000, $this->comptable->id));
    }

    public function test_seule_une_depense_validee_peut_etre_reglee(): void
    {
        $caisse = $this->creerCaisseOuverte($this->comptable, 100000);
        $depense = $this->saisir();

        $this->expectException(RuntimeException::class);

        $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);
    }

    public function test_regler_une_depense_la_rattache_a_la_caisse_et_cree_une_sortie(): void
    {
        [$depense, $caisse] = $this->depenseReglee();

        $this->assertTrue($depense->isReglee());
        $this->assertSame($caisse->id, (int) $depense->caisse_id);
        $this->assertSame($this->comptable->id, (int) $depense->regle_par);

        $mouvement = Mouvement::where('depense_id', $depense->id)->sole();
        $this->assertSame(MouvementType::DEPENSE, $mouvement->type_mouvement);
        $this->assertFalse($mouvement->type_mouvement->isEntree());
        $this->assertEqualsWithDelta(250000, (float) $mouvement->montant, 0.01);
        $this->assertSame($caisse->id, (int) $mouvement->caisse_id);
    }

    public function test_une_depense_n_est_reglee_qu_une_fois(): void
    {
        [$depense, $caisse] = $this->depenseReglee();

        $this->expectException(RuntimeException::class);

        $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);
    }

    public function test_une_depense_ne_se_regle_pas_sur_une_caisse_cloturee(): void
    {
        $caisse = $this->creerCaisseOuverte($this->comptable, 100000, ['statut' => CaisseStatut::CLOTUREE->value]);
        $depense = $this->saisir();
        $this->service->valider($depense->id, $this->directeur->id);

        $this->expectException(RuntimeException::class);

        $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);
    }

    public function test_archiver_une_depense_reglee_retire_sa_sortie_du_journal_de_caisse(): void
    {
        [$depense] = $this->depenseReglee();

        app(DepenseRepository::class)->delete($depense->id);

        $this->assertSame(BaseRepository::INACTIF, (int) Depense::find($depense->id)->etat);
        $this->assertSame(BaseRepository::INACTIF, (int) Mouvement::where('depense_id', $depense->id)->sole()->etat);
    }

    public function test_une_depense_reglee_sur_une_caisse_cloturee_ne_peut_plus_etre_archivee(): void
    {
        [$depense, $caisse] = $this->depenseReglee();
        Caisse::whereKey($caisse->id)->update(['statut' => CaisseStatut::CLOTUREE->value]);

        try {
            app(DepenseRepository::class)->delete($depense->id);
            $this->fail('Le journal d\'une caisse clôturée est figé : la suppression aurait dû être refusée.');
        } catch (RuntimeException) {
            $this->assertSame(BaseRepository::ACTIF, (int) Depense::find($depense->id)->etat);
            $this->assertSame(BaseRepository::ACTIF, (int) Mouvement::where('depense_id', $depense->id)->sole()->etat);
        }
    }

    public function test_une_depense_sous_le_seuil_est_validee_d_office(): void
    {
        $petite = $this->saisir(149999)->fresh();
        $this->assertTrue($petite->isValidee(), 'Moins de 150 000 F : validée dès la saisie.');
        $this->assertNull($petite->valide_par);
        $this->assertNotNull($petite->date_validation);

        // Au seuil et au-delà : la Direction valide
        $this->assertTrue($this->saisir(150000)->fresh()->isEnAttente());
        $this->assertTrue($this->saisir(300000)->fresh()->isEnAttente());

        // Le seuil se règle dans config/finances.php
        config(['finances.seuil_validation_depense' => 50000]);
        $this->assertTrue($this->saisir(60000)->fresh()->isEnAttente());
    }

    /* ===================== Écran des décaissements ===================== */

    public function test_le_caissier_decaisse_une_depense_validee_dans_sa_caisse_ouverte(): void
    {
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'KAFUI', 'prenom' => 'Ama']);
        $caisse = $this->creerCaisseOuverte($caissier, 200000, ['libelle' => 'Caisse du hall']);
        $petite = $this->saisir(40000);                                      // validée d'office
        $grande = $this->saisir(400000);                                     // attend la Direction
        $this->actingAs($caissier);

        // Onglets : la caisse ouverte par défaut ; seules les dépenses validées sont à décaisser
        $page = $this->get('/finances/decaissements')->assertOk()
            ->assertSee('id="onglet-tous"', false)->assertSee('id="onglet-mes"', false)->assertSee('id="onglet-caisse"', false)
            ->assertSee('id="btn-journal-caisse"', false)->assertSee('id="btn-nouveau-decaissement"', false);
        $this->assertSame('caisse', $page->viewData('onglet'));
        $this->assertSame([$petite->id], $page->viewData('aDecaisser')->pluck('id')->all());

        // Une dépense en attente ne se décaisse pas
        $this->postJson("/finances/decaissements/depense/{$grande->id}/decaisser", ['montant_decaisse' => 400000, 'caisse_id' => $caisse->id])->assertStatus(422);

        // Décaissement : la dépense est réglée dans MA caisse, la sortie figure au journal
        $this->postJson("/finances/decaissements/depense/{$petite->id}/decaisser", ['montant_decaisse' => 40000, 'caisse_id' => $caisse->id])
            ->assertOk()->assertJsonPath('success', true);
        $petite->refresh();
        $this->assertTrue($petite->isReglee());
        $this->assertSame($caisse->id, (int) $petite->caisse_id);
        $this->assertSame($caissier->id, (int) $petite->regle_par);
        $mouvement = Mouvement::where('depense_id', $petite->id)->sole();
        $this->assertSame(MouvementType::DEPENSE, $mouvement->type_mouvement);
        $this->assertSame($caissier->id, (int) $mouvement->utilisateur_id);
        $this->get(route('finances.caisses.show', $caisse->id))->assertOk()->assertSee('Fournitures de bureau');

        // Elle apparaît dans les trois onglets, et n'est plus à décaisser
        foreach (['caisse', 'mes', 'tous'] as $onglet) {
            $page = $this->get('/finances/decaissements?onglet=' . $onglet)->assertOk();
            $this->assertSame(1, $page->viewData('decaissements')->total(), $onglet);
            $this->assertEqualsWithDelta(40000, $page->viewData('total')['montant'], 0.01, $onglet);
        }
        $page->assertSee('KAFUI Ama')->assertSee('Caisse du hall');
        $this->assertCount(0, $page->viewData('aDecaisser'));
        // Une seule fois
        $this->postJson("/finances/decaissements/depense/{$petite->id}/decaisser", ['montant_decaisse' => 40000, 'caisse_id' => $caisse->id])->assertStatus(422);
    }

    public function test_sans_caisse_ouverte_pas_d_onglet_caisse_et_pas_de_decaissement(): void
    {
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $depense = $this->saisir(40000);
        $this->actingAs($caissier);

        $page = $this->get('/finances/decaissements')->assertOk()
            ->assertSee('id="onglet-mes"', false)->assertDontSee('id="onglet-caisse"', false)
            ->assertDontSee('id="btn-nouveau-decaissement"', false);
        $this->assertSame('mes', $page->viewData('onglet'));
        $autreCaisse = $this->creerCaisseOuverte($this->creerUtilisateur(RoleUtilisateur::Caissier), 0, ['libelle' => 'Caisse d\'un collègue']);
        $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", ['montant_decaisse' => 40000, 'caisse_id' => $autreCaisse->id])
            ->assertStatus(422)->assertJsonPath('message', "Vous n'avez pas de caisse ouverte : ouvrez votre caisse pour décaisser.");

        // Comptable et Direction suivent les décaissements, sans décaisser
        foreach ([$this->comptable, $this->directeur] as $profil) {
            $this->actingAs($profil);
            $page = $this->get('/finances/decaissements')->assertOk()
                ->assertSee('id="onglet-tous"', false)->assertDontSee('id="onglet-mes"', false);
            $this->assertSame('tous', $page->viewData('onglet'));
            $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", ['montant_decaisse' => 40000, 'caisse_id' => $autreCaisse->id])->assertForbidden();
        }
    }

    public function test_tous_les_decaissements_se_filtrent_par_caissier(): void
    {
        $premier = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'PREMIER', 'prenom' => 'Kossi']);
        $second = $this->creerUtilisateur(RoleUtilisateur::Caissier, ['nom' => 'SECOND', 'prenom' => 'Afi']);
        $this->service->reglerViaCaisse($this->saisir(10000)->id, $this->creerCaisseOuverte($premier)->id, $premier->id);
        $this->service->reglerViaCaisse($this->saisir(20000)->id, $this->creerCaisseOuverte($second)->id, $second->id);

        $this->actingAs($this->directeur);
        $page = $this->get('/finances/decaissements?onglet=tous')->assertOk()->assertSee('id="filtre-caissier"', false);
        $this->assertSame(2, $page->viewData('decaissements')->total());
        $this->assertEqualsCanonicalizing([$premier->id, $second->id], $page->viewData('caissiers')->pluck('id')->all());

        $page = $this->get('/finances/decaissements?onglet=tous&caissier_id=' . $second->id)->assertOk();
        $this->assertSame(1, $page->viewData('decaissements')->total());
        $this->assertEqualsWithDelta(20000, $page->viewData('total')['montant'], 0.01);
    }

    public function test_l_onglet_depenses_en_attente_liste_les_depenses_non_decaissees_validees_ou_non(): void
    {
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $caisse = $this->creerCaisseOuverte($caissier, 100000);
        $nonValidee = $this->saisir(300000);                                         // attend la Direction
        $validee = $this->saisir(20000);                                             // validée d'office
        $rejetee = $this->service->rejeter($this->saisir(400000)->id, $this->directeur->id, 'Hors budget');
        $dejaDecaissee = $this->saisir(10000);
        $this->service->reglerViaCaisse($dejaDecaissee->id, $caisse->id, $caissier->id);

        $this->actingAs($caissier);
        $page = $this->get('/finances/decaissements?onglet=attente')->assertOk()
            ->assertSee('id="onglet-attente"', false)->assertSee('id="table-attente"', false)
            ->assertDontSee('id="table-decaissements"', false);
        $liste = $page->viewData('enAttente');
        $this->assertSame([$validee->id, $nonValidee->id], $liste->pluck('id')->all(), 'Validées d\'abord ; ni rejetée ni déjà décaissée.');
        $this->assertSame(['nb' => 2, 'montant' => 320000.0, 'validees' => 1, 'montant_validees' => 20000.0], $page->viewData('compteurAttente'));

        // « Décaisser » n'est actif que sur la dépense validée ; le serveur refuse l'autre
        $html = $page->getContent();
        $this->assertMatchesRegularExpression('/data-depense="' . $nonValidee->id . '"[^>]*disabled/s', $html);
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*data-depense="' . $validee->id . '"[^>]*disabled/s', $html);
        $this->postJson("/finances/decaissements/depense/{$nonValidee->id}/decaisser", ['montant_decaisse' => 300000, 'caisse_id' => $caisse->id])->assertStatus(422)
            ->assertJsonPath('message', 'Seule une dépense validée peut être réglée via une caisse.');

        // Filtre « validées »
        $page = $this->get('/finances/decaissements?onglet=attente&statut=' . \App\Domain\Finances\Types\DepenseStatut::VALIDEE->value);
        $this->assertSame([$validee->id], $page->viewData('enAttente')->pluck('id')->all());

        // Une fois validée par la Direction puis décaissée, elle quitte l'onglet
        $this->service->valider($nonValidee->id, $this->directeur->id);
        $this->postJson("/finances/decaissements/depense/{$nonValidee->id}/decaisser", ['montant_decaisse' => 300000, 'caisse_id' => $caisse->id])->assertOk();
        $this->assertSame([$validee->id], $this->get('/finances/decaissements?onglet=attente')->viewData('enAttente')->pluck('id')->all());

        // La Direction et le Comptable voient aussi l'onglet, sans bouton « Décaisser »
        $this->actingAs($this->directeur);
        $html = $this->get('/finances/decaissements?onglet=attente')->assertOk()->assertSee('id="table-attente"', false)->getContent();
        $this->assertDoesNotMatchRegularExpression('/<button[^>]*btn-decaisser/', $html);
        unset($rejetee);
    }

    public function test_le_decaissement_confirme_le_montant_reellement_decaisse_et_la_caisse(): void
    {
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $caisse = $this->creerCaisseOuverte($caissier, 100000, ['libelle' => 'Caisse du hall']);
        $depense = $this->saisir(45000);                                     // validée d'office
        $this->actingAs($caissier);

        // La fenêtre montre MA caisse ouverte (pas de liste de caisses), avec son solde
        $this->get('/finances/decaissements?onglet=attente')->assertOk()
            ->assertSee('id="decaisserModal"', false)->assertSee('name="montant_decaisse"', false)
            ->assertSee('<input type="hidden" name="caisse_id" id="dec-caisse" value="' . $caisse->id . '"', false)
            ->assertDontSee('<select name="caisse_id"', false)
            ->assertSee('id="dec-ma-caisse"', false)->assertSee('Caisse du hall')->assertSee('100 000 F')
            ->assertSee('data-montant-brut="45000"', false);

        // Montant et caisse obligatoires
        $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", [])
            ->assertStatus(422)->assertJsonValidationErrors(['montant_decaisse', 'caisse_id']);
        $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", ['montant_decaisse' => 0, 'caisse_id' => $caisse->id])
            ->assertStatus(422)->assertJsonValidationErrors(['montant_decaisse']);

        // Montant réellement décaissé différent de la dépense : c'est lui qui sort de la caisse
        $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", ['montant_decaisse' => 43500, 'caisse_id' => $caisse->id])->assertOk();
        $depense->refresh();
        $this->assertEqualsWithDelta(43500, (float) $depense->montant_decaisse, 0.01);
        $this->assertEqualsWithDelta(45000, (float) $depense->montant, 0.01, 'Le montant de la dépense ne change pas.');
        $this->assertEqualsWithDelta(43500, (float) Mouvement::where('depense_id', $depense->id)->sole()->montant, 0.01);
        $this->assertEqualsWithDelta(100000 - 43500, Caisse::find($caisse->id)->solde_theorique, 0.01);
    }

    public function test_on_ne_decaisse_que_dans_sa_propre_caisse_ouverte(): void
    {
        $collegue = $this->creerCaisseOuverte($this->creerUtilisateur(RoleUtilisateur::Caissier), 500000, ['libelle' => 'Caisse du collègue']);
        $admin = $this->creerUtilisateur(RoleUtilisateur::Administrateur);
        $maCaisse = $this->creerCaisseOuverte($admin, 0, ['libelle' => 'Caisse de l\'administrateur']);
        $depense = $this->saisir(30000);

        // Même l'Administrateur : uniquement sa caisse, jamais celle d'un autre
        $this->actingAs($admin);
        $this->get('/finances/decaissements?onglet=attente')->assertOk()
            ->assertSee('Caisse de l&#039;administrateur', false)->assertDontSee('Caisse du collègue');
        $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", ['montant_decaisse' => 30000, 'caisse_id' => $collegue->id])
            ->assertStatus(422)->assertJsonPath('message', 'On ne décaisse que dans sa propre caisse ouverte.');
        $this->assertNull($depense->fresh()->date_reglement);

        $this->postJson("/finances/decaissements/depense/{$depense->id}/decaisser", ['montant_decaisse' => 30000, 'caisse_id' => $maCaisse->id])->assertOk();
        $this->assertSame($maCaisse->id, (int) $depense->fresh()->caisse_id);
    }

    public function test_les_decaissements_sans_depense_generent_des_depenses_decaissees(): void
    {
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $caisse = $this->creerCaisseOuverte($caissier, 500000, ['libelle' => 'Caisse 12/09']);
        $mouvement = fn (array $attributs) => Mouvement::create($attributs + [
            'libelle' => 'Achat d\'un switch', 'beneficiaire' => 'MALIKI', 'motif' => 'Réseau', 'date_mouvement' => '2026-09-12',
            'montant' => 55000, 'type_mouvement' => MouvementType::DEPENSE->value, 'caisse_id' => $caisse->id,
            'utilisateur_id' => $caissier->id, 'annee_id' => $this->annee->id, 'statut_mouvement' => 1, 'etat' => 1,
        ]);
        $sansDepense = $mouvement([]);
        $mouvement(['libelle' => 'Vidange', 'montant' => 25000]);
        // Ne sont pas repris : une sortie manuelle, une entrée, un décaissement qui a déjà sa dépense, une autre année
        $mouvement(['libelle' => 'Sortie manuelle', 'type_mouvement' => MouvementType::SORTIE_MANUELLE->value]);
        $mouvement(['libelle' => 'Entrée', 'type_mouvement' => MouvementType::ENTREE_MANUELLE->value]);
        $dejaLiee = $this->saisir(10000);
        $this->service->reglerViaCaisse($dejaLiee->id, $caisse->id, $caissier->id);
        $mouvement(['libelle' => 'Année passée', 'annee_id' => $this->creerAnnee(['libelle' => '2025-2026'])->id]);

        $this->actingAs($this->comptable);
        $page = $this->get('/finances/depenses')->assertOk()->assertSee('id="btn-generer-depenses"', false);
        $this->assertSame(['nb' => 2, 'montant' => 80000.0], $page->viewData('aGenerer'));

        $this->postJson('/finances/depenses/generer-depuis-decaissements')->assertOk()
            ->assertJsonPath('data.bilan.creees', 2)->assertJsonPath('data.bilan.montant', 80000);

        // La dépense reprend le décaissement : décaissée, dans la caisse utilisée, par son auteur
        $depense = Depense::find($sansDepense->fresh()->depense_id);
        $this->assertNotNull($depense);
        $this->assertSame('Achat d\'un switch', $depense->libelle);
        $this->assertSame('MALIKI', $depense->beneficiaire);
        $this->assertSame('Réseau', $depense->motif_depense);
        $this->assertSame('2026-09-12', $depense->date_depense->toDateString());
        $this->assertEqualsWithDelta(55000, (float) $depense->montant, 0.01);
        $this->assertTrue($depense->isValidee());
        $this->assertTrue($depense->isReglee());
        $this->assertSame($caisse->id, (int) $depense->caisse_id);
        $this->assertSame($caissier->id, (int) $depense->regle_par);
        $this->assertEqualsWithDelta(55000, (float) $depense->montant_decaisse, 0.01);
        $this->assertSame($this->annee->id, (int) $depense->annee_id);

        // Aucun mouvement en plus : la caisse et son journal ne changent pas
        $this->assertSame(1, Mouvement::where('depense_id', $depense->id)->count());
        $this->assertSame(6, Mouvement::count());

        // Relançable sans doublon ; le bouton disparaît ; la liste dit « Décaissée »
        $this->postJson('/finances/depenses/generer-depuis-decaissements')->assertOk()->assertJsonPath('data.bilan.creees', 0);
        $this->get('/finances/depenses')->assertOk()->assertDontSee('id="btn-generer-depenses"', false)->assertSee('Décaissée');

        // Réservé à ceux qui saisissent les dépenses
        $this->actingAs($this->directeur);
        $this->postJson('/finances/depenses/generer-depuis-decaissements')->assertForbidden();
    }

    public function test_les_justificatifs_des_depenses_se_suivent_et_se_joignent_depuis_les_decaissements(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $caisse = $this->creerCaisseOuverte($caissier, 500000);
        $decaissee = $this->saisir(30000);
        $this->service->reglerViaCaisse($decaissee->id, $caisse->id, $caissier->id);
        $nonDecaissee = $this->saisir(20000);
        $avecPiece = $this->service->saisir(
            new DepenseData('Avec facture', 'Fournisseur', null, '2026-09-20', 15000, $this->comptable->id),
            \Illuminate\Http\UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf')
        );
        $rejetee = $this->service->rejeter($this->saisir(500000)->id, $this->directeur->id, 'Hors budget');

        // Onglet « Sans justificatif » : décaissées d'abord ; ni celle qui a sa pièce, ni la rejetée
        $this->actingAs($caissier);
        $page = $this->get('/finances/decaissements?onglet=justificatifs')->assertOk()
            ->assertSee('id="table-justificatifs"', false)->assertSee('id="justificatifModal"', false);
        $this->assertSame([$decaissee->id, $nonDecaissee->id], $page->viewData('sansJustificatif')->pluck('id')->all());
        $this->assertSame(['nb' => 2, 'montant' => 50000.0, 'decaissees' => 1], $page->viewData('compteurJustif'));
        // Les autres listes signalent la pièce manquante
        $this->get('/finances/decaissements?onglet=tous')->assertOk()->assertSee('Sans justificatif');

        // Joindre le justificatif d'une dépense déjà décaissée
        $this->post("/finances/decaissements/depense/{$decaissee->id}/justificatif",
            ['justificatif' => \Illuminate\Http\UploadedFile::fake()->create('recu.pdf', 80, 'application/pdf')],
            ['Accept' => 'application/json'])->assertOk()->assertJsonPath('message', 'Justificatif enregistré.');
        $decaissee->refresh();
        $this->assertSame('recu.pdf', $decaissee->justificatif_nom_original);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($decaissee->justificatif_path);
        $this->assertSame([$nonDecaissee->id], $this->get('/finances/decaissements?onglet=justificatifs')->viewData('sansJustificatif')->pluck('id')->all());

        // Le remplacer supprime l'ancien fichier
        $ancien = $decaissee->justificatif_path;
        $this->post("/finances/decaissements/depense/{$decaissee->id}/justificatif",
            ['justificatif' => \Illuminate\Http\UploadedFile::fake()->image('recu.jpg')], ['Accept' => 'application/json'])->assertOk();
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($ancien);

        // Fichier obligatoire, formats et taille contrôlés ; pas de pièce sur une dépense rejetée
        $this->post("/finances/decaissements/depense/{$nonDecaissee->id}/justificatif", [], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['justificatif']);
        $this->post("/finances/decaissements/depense/{$nonDecaissee->id}/justificatif",
            ['justificatif' => \Illuminate\Http\UploadedFile::fake()->create('virus.exe', 10)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['justificatif']);
        $this->post("/finances/decaissements/depense/{$rejetee->id}/justificatif",
            ['justificatif' => \Illuminate\Http\UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422);

        // Le justificatif peut aussi être joint au moment du décaissement
        $this->post("/finances/decaissements/depense/{$nonDecaissee->id}/decaisser", [
            'montant_decaisse' => 20000, 'caisse_id' => $caisse->id,
            'justificatif' => \Illuminate\Http\UploadedFile::fake()->create('ticket.pdf', 30, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $nonDecaissee->refresh();
        $this->assertTrue($nonDecaissee->isReglee());
        $this->assertSame('ticket.pdf', $nonDecaissee->justificatif_nom_original);
        $this->assertCount(0, $this->get('/finances/decaissements?onglet=justificatifs')->viewData('sansJustificatif'));

        // Le Comptable joint aussi les pièces ; la Direction consulte seulement
        $this->actingAs($this->comptable);
        $this->post("/finances/decaissements/depense/{$avecPiece->id}/justificatif",
            ['justificatif' => \Illuminate\Http\UploadedFile::fake()->create('facture2.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertOk();
        $this->actingAs($this->directeur);
        $this->get('/finances/decaissements?onglet=justificatifs')->assertOk()->assertDontSee('id="justificatifModal"', false);
        $this->post("/finances/decaissements/depense/{$avecPiece->id}/justificatif",
            ['justificatif' => \Illuminate\Http\UploadedFile::fake()->create('f.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertForbidden();
    }
}
