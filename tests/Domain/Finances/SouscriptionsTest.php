<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\Models\Souscription;
use App\Domain\Finances\Repositories\SouscriptionRepository;
use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Services\SouscriptionChargementService;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;
use Tests\Domain\DomainTestCase;

/**
 * 2.1 Souscriptions — cantine, transport, activités extrascolaires et
 * événements : reprise depuis la base historique, liste en cinq colonnes,
 * paiements liés, tableau de bord réservé à la Direction et à l'Administrateur.
 */
class SouscriptionsTest extends DomainTestCase
{
    private Inscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inscription = $this->creerInscription(null, ['date_inscription' => '2026-09-01']);
        // Montants portés par l'inscription, comme dans la base historique
        DB::table('inscriptions')->where('id', $this->inscription->id)->update(['frais_cantine' => 200000, 'frais_bus' => 150000]);
    }

    private function payer(TypeDetail $type, float $montant, string $date, ?int $fraisId = null, PaiementStatut $statut = PaiementStatut::ENCAISSE): void
    {
        $paiement = DB::table('paiements')->insertGetId([
            'reference' => 'PAI-' . uniqid(), 'montant' => $montant, 'payeur' => 'Parent', 'date_paiement' => $date,
            'statut_paiement' => $statut->value, 'inscription_id' => $this->inscription->id,
            'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        DB::table('details')->insert([
            'paiement_id' => $paiement, 'inscription_id' => $this->inscription->id, 'annee_id' => $this->annee->id,
            'libelle' => $type->label(), 'montant' => $montant, 'type_paiement' => $type->value,
            'frais_ecole_id' => $fraisId, 'statut_paiement' => $statut->value, 'date_paiement' => $date,
            'date_encaissement' => $statut === PaiementStatut::ENCAISSE ? $date : null, 'etat' => 1,
        ]);
    }

    private function recharger(): array
    {
        return app(SouscriptionChargementService::class)->recharger();
    }

    public function test_le_rechargement_reprend_cantine_transport_activites_et_evenements(): void
    {
        $football = $this->creerFrais(30000, TypeDetail::ACTIVITE, ['libelle' => 'FOOTBALL (AES)']);
        $sortie = $this->creerFrais(5000, TypeDetail::EVENEMENT, ['libelle' => 'Sortie de fin d\'année']);
        $this->payer(TypeDetail::CANTINE, 50000, '2026-09-20');
        $this->payer(TypeDetail::CANTINE, 50000, '2026-09-10');      // premier paiement de cantine
        $this->payer(TypeDetail::ACTIVITE, 30000, '2026-10-02', $football->id);
        $this->payer(TypeDetail::EVENEMENT, 5000, '2026-10-05', $sortie->id);
        // Une ancienne souscription de scolarité : elle est archivée, plus listée
        $ancienne = DB::table('souscriptions')->insertGetId([
            'inscription_id' => $this->inscription->id, 'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
            'montant_annuel_prevu' => 469000, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);

        $bilan = $this->recharger();

        $this->assertSame(1, $bilan['cantine']);
        $this->assertSame(1, $bilan['transport']);
        $this->assertSame(1, $bilan['activites']);
        $this->assertSame(1, $bilan['evenements']);
        $this->assertDatabaseMissing('souscriptions', ['id' => $ancienne]);
        $this->assertDatabaseHas('souscriptions_archive', ['id' => $ancienne]);

        $parType = Souscription::where('inscription_id', $this->inscription->id)->get()->keyBy(fn ($s) => $s->type_paiement->value);
        $this->assertCount(4, $parType);

        // Cantine : montant de l'inscription, date du premier paiement de cantine
        $cantine = $parType[TypeDetail::CANTINE->value];
        $this->assertEqualsWithDelta(200000, $cantine->montant_annuel_prevu, 0.01);
        $this->assertSame('2026-09-10', $cantine->date_souscription->toDateString());

        // Transport : aucun paiement, la date d'inscription sert de repère
        $transport = $parType[TypeDetail::BUS->value];
        $this->assertEqualsWithDelta(150000, $transport->montant_annuel_prevu, 0.01);
        $this->assertSame('2026-09-01', $transport->date_souscription->toDateString());

        // Activité et événement : le frais réglé, à la date de son premier paiement
        $activite = $parType[TypeDetail::ACTIVITE->value];
        $this->assertSame('FOOTBALL (AES)', $activite->libelle);
        $this->assertSame($football->id, (int) $activite->frais_ecole_id);
        $this->assertEqualsWithDelta(30000, $activite->montant_annuel_prevu, 0.01);
        $this->assertSame('2026-10-02', $activite->date_souscription->toDateString());
        $this->assertSame('2026-10-05', $parType[TypeDetail::EVENEMENT->value]->date_souscription->toDateString());

        // Relancer ne crée pas de doublon
        $this->recharger();
        $this->assertSame(4, Souscription::where('inscription_id', $this->inscription->id)->where('etat', 1)->count());
    }

    public function test_le_du_de_la_cantine_reste_celui_de_l_inscription_apres_rechargement(): void
    {
        $avant = app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::CANTINE->value);
        $this->recharger();

        $this->assertEqualsWithDelta(200000, $avant, 0.01);
        $this->assertEqualsWithDelta($avant, app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::CANTINE->value), 0.01);
    }

    public function test_un_abonnement_cantine_du_module_remplace_la_reprise_sans_double_compte(): void
    {
        $this->recharger();
        DB::table('inscriptions_cantine')->insert([
            'inscription_id' => $this->inscription->id, 'date_debut' => '2026-10-01', 'montant_mensuel' => 25000,
            'nombre_mois' => 9, 'montant_total_du' => 225000, 'statut' => 1, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);

        // Le dû est celui de l'abonnement, pas la somme des deux
        $this->assertEqualsWithDelta(225000, app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::CANTINE->value), 0.01);

        app(SouscriptionChargementService::class)->synchroniserModules();

        $cantines = Souscription::where('inscription_id', $this->inscription->id)
            ->where('type_paiement', TypeDetail::CANTINE->value)->where('etat', 1)->get();
        $this->assertCount(1, $cantines);
        $this->assertSame('cantine', $cantines[0]->origine);
        $this->assertEqualsWithDelta(225000, $cantines[0]->montant_annuel_prevu, 0.01);
        $this->assertEqualsWithDelta(225000, app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::CANTINE->value), 0.01);
    }

    public function test_la_liste_tient_en_cinq_colonnes_et_dit_si_le_paiement_est_termine(): void
    {
        $this->payer(TypeDetail::CANTINE, 200000, '2026-09-10');                                   // soldée
        $this->payer(TypeDetail::BUS, 50000, '2026-09-12');                                        // en cours
        $this->payer(TypeDetail::BUS, 40000, '2026-09-15', null, PaiementStatut::EN_ATTENTE);      // saisi, pas encore encaissé
        $this->recharger();
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));

        $page = $this->get('/finances/souscriptions?onglet=toutes')->assertOk();

        $entete = explode('</thead>', explode('id="table-souscriptions"', $page->getContent())[1])[0];
        $this->assertSame(5, preg_match_all('/<th[ >]/', $entete));
        $page->assertSeeInOrder(['Élève', 'Souscription', 'Montant', 'Règlement', 'Actions'])
            ->assertSee('Voir les paiements');

        $liste = $page->viewData('souscriptions')->getCollection()->keyBy(fn ($s) => $s->type_paiement->value);
        $this->assertSame('solde', $liste[TypeDetail::CANTINE->value]->etat_paiement);
        $this->assertSame('partiel', $liste[TypeDetail::BUS->value]->etat_paiement);
        $this->assertEqualsWithDelta(100000, $liste[TypeDetail::BUS->value]->reste, 0.01);   // seul l'encaissé compte

        // Le filtre « Règlement »
        $this->assertSame(1, $this->get('/finances/souscriptions?onglet=toutes&paiement=solde')->viewData('souscriptions')->total());
        $this->assertSame(1, $this->get('/finances/souscriptions?onglet=toutes&paiement=partiel')->viewData('souscriptions')->total());
        $this->assertSame(0, $this->get('/finances/souscriptions?onglet=toutes&paiement=impaye')->viewData('souscriptions')->total());
    }

    public function test_les_paiements_lies_a_une_souscription_se_consultent(): void
    {
        $this->payer(TypeDetail::BUS, 50000, '2026-09-12');
        $this->payer(TypeDetail::BUS, 40000, '2026-09-15', null, PaiementStatut::EN_ATTENTE);
        $this->payer(TypeDetail::CANTINE, 10000, '2026-09-16');   // autre service : n'apparaît pas
        $this->recharger();
        $bus = Souscription::where('type_paiement', TypeDetail::BUS->value)->firstOrFail();
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));

        $this->get("/finances/souscriptions/{$bus->id}/paiements")
            ->assertOk()
            ->assertSee('Paiement en cours')
            ->assertSee('150 000 F')    // attendu
            ->assertSee('50 000 F')     // encaissé
            ->assertSee('100 000 F')    // reste
            ->assertSee('40 000 F')     // ligne en attente d'encaissement, listée
            ->assertSee("En attente d'encaissement")
            ->assertDontSee('10 000 F');

        $this->assertCount(2, app(SouscriptionRepository::class)->paiementsDe($bus));
    }

    public function test_le_tableau_de_bord_compte_chaque_service_avec_l_attendu_et_le_reste(): void
    {
        $football = $this->creerFrais(30000, TypeDetail::ACTIVITE, ['libelle' => 'FOOTBALL']);
        $this->payer(TypeDetail::CANTINE, 80000, '2026-09-10');
        $this->payer(TypeDetail::ACTIVITE, 30000, '2026-10-02', $football->id);
        $this->recharger();

        $tableau = collect(app(SouscriptionRepository::class)->tableauDeBord($this->annee->id))->keyBy(fn ($l) => $l['type']->value);

        $this->assertSame(1, $tableau[TypeDetail::CANTINE->value]['nb']);
        $this->assertEqualsWithDelta(200000, $tableau[TypeDetail::CANTINE->value]['attendu'], 0.01);
        $this->assertEqualsWithDelta(120000, $tableau[TypeDetail::CANTINE->value]['reste'], 0.01);
        $this->assertSame(1, $tableau[TypeDetail::BUS->value]['nb']);
        $this->assertEqualsWithDelta(150000, $tableau[TypeDetail::BUS->value]['reste'], 0.01);
        $this->assertSame(1, $tableau[TypeDetail::ACTIVITE->value]['nb']);
        $this->assertEqualsWithDelta(0, $tableau[TypeDetail::ACTIVITE->value]['reste'], 0.01);
        $this->assertSame(0, $tableau[TypeDetail::EVENEMENT->value]['nb']);
    }

    public function test_l_onglet_non_a_jour_ne_liste_que_les_souscriptions_avec_un_reste(): void
    {
        $this->payer(TypeDetail::CANTINE, 200000, '2026-09-10');   // soldée
        $this->payer(TypeDetail::BUS, 50000, '2026-09-12');        // partielle
        $this->recharger();
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));

        // Onglet par défaut : les élèves non à jour
        $page = $this->get('/finances/souscriptions')->assertOk()
            ->assertSee('Élèves non à jour')->assertSee('Toutes les souscriptions');
        $this->assertSame('non-a-jour', $page->viewData('onglet'));
        $this->assertSame([TypeDetail::BUS->value], $page->viewData('souscriptions')->getCollection()->map(fn ($s) => $s->type_paiement->value)->all());
        $this->assertSame(1, $page->viewData('compteNonAJour'));
        $this->assertSame(2, $page->viewData('compteToutes'));

        // Ses indicateurs ne portent que sur ce qui reste dû
        $stats = $page->viewData('statistiques');
        $this->assertSame(1, $stats['eleves']);
        $this->assertSame([0, 1, 0], [$stats['soldees'], $stats['partielles'], $stats['impayees']]);
        $this->assertEqualsWithDelta(150000, $stats['attendu'], 0.01);
        $this->assertEqualsWithDelta(100000, $stats['reste'], 0.01);

        // « Toutes » : les deux souscriptions du même élève, compté une seule fois
        $toutes = $this->get('/finances/souscriptions?onglet=toutes')->viewData('statistiques');
        $this->assertSame(1, $toutes['eleves']);
        $this->assertSame(2, $toutes['souscriptions']);
        $this->assertSame([1, 1, 0], [$toutes['soldees'], $toutes['partielles'], $toutes['impayees']]);
        $this->assertEqualsWithDelta(350000, $toutes['attendu'], 0.01);
        $this->assertEqualsWithDelta(250000, $toutes['percu'], 0.01);
    }

    public function test_les_listes_les_indicateurs_et_les_exports_suivent_l_annee_de_l_en_tete(): void
    {
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $ancienne = $this->creerInscription($this->creerEleve(['nom' => 'ANCIEN', 'prenom' => 'Eleve']), ['annee_id' => $precedente->id, 'frais_cantine' => 90000]);
        $this->recharger();
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));

        $page = $this->get('/finances/souscriptions?onglet=toutes')->assertOk()
            ->assertSee('Année scolaire : ' . $this->annee->libelle)->assertDontSee('ANCIEN Eleve');
        $this->assertSame(2, $page->viewData('compteToutes'));
        $this->assertEqualsWithDelta(350000, $page->viewData('statistiques')['attendu'], 0.01);
        // Aucun filtre d'année dans l'écran : seule l'année de l'en-tête compte
        $filtres = explode('</form>', explode('id="filtres-souscriptions"', $page->getContent())[1])[0];
        $this->assertStringNotContainsString('annee_id', $filtres);

        // Répartition par niveau sans double compte
        $this->assertSame(2, (int) $page->viewData('statistiques')['niveaux']->sum('souscriptions'));

        // Exports : même périmètre
        $excel = $this->get('/finances/souscriptions/export?onglet=toutes')->assertOk();
        $this->assertStringContainsString('.xlsx', $excel->headers->get('content-disposition'));
        $this->get('/finances/souscriptions/export-pdf?onglet=non-a-jour')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertCount(2, app(SouscriptionRepository::class)->listePourExport(['annee_id' => $this->annee->id]));

        // L'année précédente choisie dans l'en-tête : ses souscriptions seulement
        $page = $this->get('/finances/souscriptions?onglet=toutes&annee_id=' . $precedente->id)->assertOk()->assertSee('ANCIEN Eleve');
        $this->assertSame(1, $page->viewData('compteToutes'));
        $this->assertSame($ancienne->id, (int) $page->viewData('souscriptions')->first()->inscription_id);
    }

    public function test_la_synchronisation_cree_les_souscriptions_manquantes_de_l_annee_sans_doublon(): void
    {
        $precedente = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => \App\Domain\Scolarite\Types\StatutAnnee::Cloture->value]);
        $this->creerInscription(null, ['annee_id' => $precedente->id, 'frais_cantine' => 90000]);
        $football = $this->creerFrais(30000, TypeDetail::ACTIVITE, ['libelle' => 'FOOTBALL']);
        $this->payer(TypeDetail::ACTIVITE, 30000, '2026-10-02', $football->id);
        // Un paiement de transport sans montant de transport sur l'inscription : signalé, pas créé
        $sansMontant = $this->creerInscription();
        DB::table('details')->insert([
            'inscription_id' => $sansMontant->id, 'annee_id' => $this->annee->id, 'libelle' => 'Bus', 'montant' => 10000,
            'type_paiement' => TypeDetail::BUS->value, 'statut_paiement' => PaiementStatut::ENCAISSE->value, 'etat' => 1,
        ]);
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));

        $bilan = $this->postJson('/finances/souscriptions/synchroniser')->assertOk()->json('data.bilan');

        $this->assertSame(3, $bilan['creees']);          // cantine, transport, activité de l'élève de l'année
        $this->assertSame(0, $bilan['mises_a_jour']);
        $this->assertCount(1, $bilan['erreurs']);
        $this->assertStringContainsString('transport', $bilan['erreurs'][0]);
        // L'autre année n'est pas touchée
        $this->assertSame(0, Souscription::where('annee_id', $precedente->id)->count());

        // Relance : rien de créé, tout est ignoré
        $relance = $this->postJson('/finances/souscriptions/synchroniser')->assertOk()->json('data.bilan');
        $this->assertSame(0, $relance['creees']);
        $this->assertSame(3, $relance['ignorees']);
        $this->assertSame(3, Souscription::where('etat', 1)->count());

        // Le montant de cantine corrigé sur l'inscription est repris
        DB::table('inscriptions')->where('id', $this->inscription->id)->update(['frais_cantine' => 220000]);
        $maj = $this->postJson('/finances/souscriptions/synchroniser')->assertOk()->json('data.bilan');
        $this->assertSame(1, $maj['mises_a_jour']);
        $this->assertEqualsWithDelta(220000, Souscription::where('type_paiement', TypeDetail::CANTINE->value)->value('montant_annuel_prevu'), 0.01);

        // La Direction consulte mais ne synchronise pas
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $this->postJson('/finances/souscriptions/synchroniser')->assertForbidden();
    }

    /** @dataProvider profilsDuTableauDeBord */
    public function test_le_tableau_de_bord_est_reserve_a_l_administrateur_et_a_la_direction(RoleUtilisateur $role, bool $visible): void
    {
        $this->recharger();
        $this->actingAs($this->creerUtilisateur($role));

        $page = $this->get('/finances/souscriptions')->assertOk();

        if ($visible) {
            $page->assertSee('id="tableau-de-bord"', false)->assertSee('Reste à percevoir');
            $this->assertNotNull($page->viewData('tableauDeBord'));
        } else {
            $page->assertDontSee('id="tableau-de-bord"', false)->assertDontSee('id="indicateurs"', false);
            $this->assertNull($page->viewData('tableauDeBord'));
            $this->assertNull($page->viewData('statistiques'));
        }
    }

    public function profilsDuTableauDeBord(): array
    {
        return [
            'Administrateur'         => [RoleUtilisateur::Administrateur, true],
            'Directeur'              => [RoleUtilisateur::Directeur, true],
            'Comptable'              => [RoleUtilisateur::Comptable, false],
            'Administrateur adjoint' => [RoleUtilisateur::AdministrateurAdjoint, false],
        ];
    }
}
