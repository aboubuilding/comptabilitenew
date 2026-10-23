<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\DepenseData;
use App\Domain\Finances\DTO\PrevisionData;
use App\Domain\Finances\Models\Prevision;
use App\Domain\Finances\Services\DepenseService;
use App\Domain\Finances\Services\PrevisionService;
use App\Domain\Finances\Types\TypePrevision;
use App\Repositories\BaseRepository;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.9 : lignes budgétaires (ancien écran
 * « Prévisions budgétaires »). Une ligne : un libellé et un montant global ; les
 * dépenses qui lui sont imputées en consomment le montant.
 */
class PrevisionsBudgetairesTest extends DomainTestCase
{
    private function data(float $montant = 5000000, string $libelle = 'Fournitures de bureau'): PrevisionData
    {
        return new PrevisionData($libelle, $montant, $this->creerUtilisateur()->id);
    }

    /** Une dépense imputée à la ligne, de montant donné (sous 150 000 F : validée d'office). */
    private function depense(int $ligneId, float $montant, string $libelle = 'Rames de papier')
    {
        return app(DepenseService::class)->saisir(
            new DepenseData($libelle, 'Papeterie du Centre', null, '2026-09-20', $montant, $this->creerUtilisateur(RoleUtilisateur::Comptable)->id, $ligneId)
        );
    }

    public function test_une_ligne_budgetaire_n_a_ni_type_ni_periode_et_appartient_a_l_annee(): void
    {
        $ligne = app(PrevisionService::class)->creer($this->data())->fresh();

        $this->assertSame('Fournitures de bureau', $ligne->libelle);
        $this->assertSame(TypePrevision::DEPENSE, $ligne->type, 'Une ligne porte des dépenses.');
        $this->assertNull($ligne->date_debut);
        $this->assertNull($ligne->date_fin);
        $this->assertSame($this->annee->id, (int) $ligne->annee_id);
    }

    public function test_une_ligne_a_un_montant_global_strictement_positif(): void
    {
        $this->expectException(RuntimeException::class);

        app(PrevisionService::class)->creer($this->data(0));
    }

    public function test_modifier_une_ligne_met_a_jour_son_libelle_et_son_montant(): void
    {
        $service = app(PrevisionService::class);
        $ligne = $service->creer($this->data(5000000));

        $modifiee = $service->modifier($ligne->id, $this->data(6000000, 'Fournitures et consommables'));

        $this->assertEqualsWithDelta(6000000, (float) $modifiee->montant_previsionnel, 0.01);
        $this->assertSame('Fournitures et consommables', $modifiee->libelle);
    }

    public function test_supprimer_une_ligne_l_archive(): void
    {
        $service = app(PrevisionService::class);
        $ligne = $service->creer($this->data());

        $service->supprimer($ligne->id);

        $this->assertSame(BaseRepository::INACTIF, (int) Prevision::find($ligne->id)->etat);
    }

    public function test_seules_les_depenses_decaissees_sont_imputees_a_la_ligne(): void
    {
        $ligne = app(PrevisionService::class)->creer($this->data(500000));
        $petite = app(PrevisionService::class)->creer($this->data(100000, 'Entretien'));
        $vide = app(PrevisionService::class)->creer($this->data(300000, 'Sorties pédagogiques'));
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);
        $caisse = $this->creerCaisseOuverte($caissier, 900000, ['libelle' => 'Caisse du hall']);
        $decaisser = fn ($depense, ?float $montant = null) => app(DepenseService::class)->reglerViaCaisse($depense->id, $caisse->id, $caissier->id, $montant);

        // Sur la ligne : 120 000 pas encore décaissée (non imputée), 80 000 décaissée pour 75 000 (imputée 75 000),
        // une rejetée (jamais comptée)
        $this->depense($ligne->id, 120000);
        $decaisser($this->depense($ligne->id, 80000, 'Toner'), 75000);
        $rejetee = $this->depense($ligne->id, 400000, 'Photocopieur');
        app(DepenseService::class)->rejeter($rejetee->id, $this->creerUtilisateur(RoleUtilisateur::Directeur)->id, 'Hors budget');
        // Dépassement sur l'autre ligne, une fois décaissée
        $decaisser($this->depense($petite->id, 130000, 'Réparations'));

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $page = $this->get('/finances/previsions')->assertOk()
            ->assertSee('Lignes budgétaires')->assertDontSee('name="type"', false)->assertDontSee('name="date_debut"', false);
        $lignes = $page->viewData('previsions')->keyBy('id');

        $this->assertEqualsWithDelta(75000, (float) $lignes[$ligne->id]->montant_depenses, 0.01, 'Seule la dépense décaissée est imputée.');
        $this->assertSame(1, (int) $lignes[$ligne->id]->nb_depenses);
        $this->assertEqualsWithDelta(120000, (float) $lignes[$ligne->id]->montant_en_attente, 0.01);
        $this->assertSame(1, (int) $lignes[$ligne->id]->nb_en_attente);
        $this->assertEqualsWithDelta(130000, (float) $lignes[$petite->id]->montant_depenses, 0.01);
        $this->assertEqualsWithDelta(0, (float) $lignes[$vide->id]->montant_depenses, 0.01);
        $page->assertSee('425 000 F')             // reste de la ligne : 500 000 − 75 000
            ->assertSee('120 000 F en attente de décaissement')
            ->assertSee('Dépassement 30 000 F');  // 100 000 − 130 000
        $this->assertEqualsWithDelta(900000, $page->viewData('totaux')['global'], 0.01);
        $this->assertEqualsWithDelta(205000, $page->viewData('totaux')['depenses'], 0.01);

        // Les dépenses de la ligne, avec la caisse qui a servi au décaissement
        $this->get(route('finances.previsions.depenses', $ligne->id))->assertOk()
            ->assertSee('Rames de papier')->assertSee('Toner')->assertSee('Caisse du hall')
            ->assertSee('75 000 F')->assertSee("non imputée tant qu'elle n'est pas décaissée", false)
            ->assertSee('Photocopieur')->assertSee('non comptée');
    }

    public function test_une_depense_est_hors_ligne_ou_sur_une_ligne_obligatoirement_choisie(): void
    {
        $ligne = app(PrevisionService::class)->creer($this->data(500000));
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $saisie = ['beneficiaire' => 'Papeterie', 'date_depense' => '2026-10-01', 'montant' => 5000];

        $this->get('/finances/depenses')->assertOk()
            ->assertSee('id="imputation-hors-ligne"', false)->assertSee('id="imputation-ligne"', false)
            ->assertSee('name="ligne_budgetaire_id"', false)->assertSee('Fournitures de bureau (500 000 F)');

        // Sur ligne budgétaire : la ligne est obligatoire
        $this->postJson('/finances/depenses', $saisie + ['libelle' => 'Sans ligne', 'imputation' => 'ligne'])
            ->assertStatus(422)->assertJsonValidationErrors(['ligne_budgetaire_id'])
            ->assertJsonPath('errors.ligne_budgetaire_id.0', 'Choisissez la ligne budgétaire de cette dépense.');
        $this->postJson('/finances/depenses', $saisie + ['libelle' => 'Agrafes', 'imputation' => 'ligne', 'ligne_budgetaire_id' => $ligne->id])->assertOk();
        $this->assertSame($ligne->id, (int) \App\Domain\Finances\Models\Depense::where('libelle', 'Agrafes')->value('ligne_budgetaire_id'));

        // Hors ligne budgétaire : aucune ligne, même si une valeur est envoyée
        $this->postJson('/finances/depenses', $saisie + ['libelle' => 'Taxi', 'imputation' => 'hors_ligne', 'ligne_budgetaire_id' => $ligne->id])->assertOk();
        $this->assertNull(\App\Domain\Finances\Models\Depense::where('libelle', 'Taxi')->value('ligne_budgetaire_id'));

        // La liste des dépenses dit de quel type est chacune
        $this->get('/finances/depenses')->assertOk()->assertSee('Ligne : Fournitures de bureau')->assertSee('Hors ligne budgétaire');
    }

    public function test_une_ligne_peut_former_le_budget_d_un_centre_analytique(): void
    {
        $centre = app(\App\Domain\Finances\Services\CentreAnalytiqueService::class)
            ->creer(new \App\Domain\Finances\DTO\CentreAnalytiqueData('CANT', 'Cantine', null, 'service', null));
        $auteur = $this->creerUtilisateur()->id;
        $ligne = app(PrevisionService::class)->creer(new PrevisionData('Denrées cantine', 950000, $auteur, $centre->id));
        app(PrevisionService::class)->creer(new PrevisionData('Sans centre', 100000, $auteur));
        $this->assertSame($centre->id, (int) $ligne->fresh()->centre_analytique_id);

        // Le rapport analytique en tire le budget du centre (une ligne de dépense : budget net négatif), même sur une période
        foreach ([[null, null], ['2026-09-01', '2027-06-30']] as [$debut, $fin]) {
            $rapport = app(\App\Domain\Finances\Services\RapportAnalytiqueService::class)
                ->tableauDeBord($this->annee->id, $debut, $fin)->keyBy('centre_id');
            $this->assertEqualsWithDelta(-950000, $rapport[$centre->id]['budget'], 0.01);
        }

        // Centre facultatif, modifiable depuis l'écran
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $this->get('/finances/previsions')->assertOk()->assertSee('name="centre_analytique_id"', false)->assertSee('CANT — Cantine');
        $this->putJson(route('finances.previsions.update', $ligne->id), ['libelle' => 'Denrées cantine', 'montant_previsionnel' => 950000])->assertOk();
        $this->assertNull($ligne->fresh()->centre_analytique_id);
    }
}
