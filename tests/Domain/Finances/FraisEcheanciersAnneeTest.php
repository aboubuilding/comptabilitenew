<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Models\PlanEcheancier;
use App\Domain\Finances\Services\EcheancierService;
use App\Domain\Finances\Services\FraisSynchronisationService;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use App\Domain\Scolarite\Types\StatutAnnee;
use Illuminate\Support\Facades\DB;
use Tests\Domain\DomainTestCase;

/**
 * 2.1 Frais scolaires & échéanciers — règle transversale : tout suit l'année
 * scolaire choisie dans l'en-tête. Onglets des frais, création limitée à
 * l'inscription / l'assurance / la scolarité, échéancier saisi avec le frais,
 * conversion des tranches, suivi des échéances, synchronisation des catalogues.
 */
class FraisEcheanciersAnneeTest extends DomainTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
    }

    private function autreAnnee()
    {
        return $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => StatutAnnee::Cloture->value]);
    }

    /** Texte d'un export Excel (les exports CSV sont convertis en .xlsx par l'application). */
    private function texteDuClasseur($reponse): string
    {
        $base = $reponse->baseResponse;
        $contenu = $base instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            ? file_get_contents($base->getFile()->getPathname())
            : ($reponse->getContent() ?: $reponse->streamedContent());

        $fichier = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($fichier, $contenu);
        $zip = new \ZipArchive();
        if ($zip->open($fichier) !== true) {
            return (string) $contenu;   // export resté en CSV
        }
        $texte = '';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $texte .= $zip->getFromIndex($i);
        }
        $zip->close();

        return html_entity_decode(strip_tags(str_replace('<', ' <', $texte)));
    }

    private function donneesFrais(array $surcharge = []): array
    {
        return $surcharge + [
            'libelle' => 'Scolarité CM1', 'montant' => 300000,
            'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value,
            'modalite_paiement' => TypePaiement::COMPTANT->value,
            'type_forfait' => TypeForfait::OBLIGATOIRE->value,
        ];
    }

    /* ------------------------------------------------------------------
       Année de l'en-tête
       ------------------------------------------------------------------ */

    public function test_la_liste_des_frais_ne_montre_que_l_annee_de_l_en_tete(): void
    {
        $precedente = $this->autreAnnee();
        $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'SCOL-COURANTE']);
        $this->creerFrais(280000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'SCOL-ANCIENNE', 'annee_id' => $precedente->id]);

        $page = $this->get('/finances/frais-ecoles?onglet=tous')->assertOk()
            ->assertSee('SCOL-COURANTE')->assertDontSee('SCOL-ANCIENNE')
            ->assertSee('Année scolaire : ' . $this->annee->libelle);
        // Pas de second sélecteur d'année : les filtres de l'écran n'en contiennent pas
        $filtres = explode('</form>', explode('id="filtres-frais"', $page->getContent())[1])[0];
        $this->assertStringNotContainsString('annee_id', $filtres);
        $this->assertSame(1, $page->viewData('compteursOnglets')['tous']);

        // L'export suit la même année
        $csv = $this->texteDuClasseur($this->get('/finances/frais-ecoles/export?onglet=tous')->assertOk());
        $this->assertStringContainsString('SCOL-COURANTE', $csv);
        $this->assertStringNotContainsString('SCOL-ANCIENNE', $csv);

        // Changer l'année dans l'en-tête change la liste
        $this->get('/finances/frais-ecoles?onglet=tous&annee_id=' . $precedente->id)->assertOk()
            ->assertSee('SCOL-ANCIENNE')->assertDontSee('SCOL-COURANTE');
    }

    public function test_les_cinq_onglets_classent_les_frais_par_type(): void
    {
        $this->creerFrais(50000, TypeDetail::FRAIS_INSCRIPTION, ['libelle' => 'F-INSCRIPTION']);
        $this->creerFrais(10000, TypeDetail::FRAIS_ASSURANCE, ['libelle' => 'F-ASSURANCE']);
        $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'F-SCOLARITE']);
        $this->creerFrais(108000, TypeDetail::CANTINE, ['libelle' => 'F-CANTINE']);
        $this->creerFrais(90000, TypeDetail::BUS, ['libelle' => 'F-TRANSPORT']);
        $this->creerFrais(7500, TypeDetail::PRODUIT, ['libelle' => 'F-PRODUIT']);

        $attendu = [
            'inscription' => ['F-INSCRIPTION', 'F-ASSURANCE'],
            'scolarite'   => ['F-SCOLARITE'],
            'cantine'     => ['F-CANTINE'],
            'transport'   => ['F-TRANSPORT'],
            'tous'        => ['F-INSCRIPTION', 'F-ASSURANCE', 'F-SCOLARITE', 'F-CANTINE', 'F-TRANSPORT', 'F-PRODUIT'],
        ];

        foreach ($attendu as $onglet => $libelles) {
            $page = $this->get('/finances/frais-ecoles?onglet=' . $onglet)->assertOk();
            $this->assertEqualsCanonicalizing($libelles, $page->viewData('fraisEcoles')->pluck('libelle')->all(), "onglet {$onglet}");
            $this->assertSame(count($libelles), $page->viewData('compteursOnglets')[$onglet]);
            // Cinq colonnes au plus, dans tous les onglets ; plus de colonne « Type de paiement »
            $entete = explode('</thead>', explode('id="table-frais"', $page->getContent())[1])[0];
            $this->assertSame(5, preg_match_all('/<th[ >]/', $entete), "onglet {$onglet}");
            $this->assertStringNotContainsString('Type de paiement', $entete, "onglet {$onglet}");
        }

        // Sans paramètre : premier onglet
        $this->assertSame('inscription', $this->get('/finances/frais-ecoles')->viewData('onglet'));
    }

    public function test_le_montant_recolte_par_frais_est_reserve_a_l_administrateur_et_a_la_direction(): void
    {
        ['niveau_id' => $cp] = $this->creerStructure('CP');
        ['niveau_id' => $ce1] = $this->creerStructure('CE1');
        $scolCp = $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CP', 'niveau_id' => $cp]);
        $scolCe1 = $this->creerFrais(320000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CE1', 'niveau_id' => $ce1]);
        $cantine = $this->creerFrais(108000, TypeDetail::CANTINE, ['libelle' => 'Cantine']);
        $eleveCp = $this->creerInscription(null, ['niveau_id' => $cp, 'frais_scolarite' => 300000]);
        $eleveCe1 = $this->creerInscription(null, ['niveau_id' => $ce1, 'frais_scolarite' => 320000]);

        $payer = fn ($inscription, TypeDetail $type, float $montant, ?int $fraisId = null, PaiementStatut $statut = PaiementStatut::ENCAISSE)
            => DB::table('details')->insert([
                'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'libelle' => $type->label(), 'montant' => $montant,
                'type_paiement' => $type->value, 'frais_ecole_id' => $fraisId, 'statut_paiement' => $statut->value, 'etat' => 1,
            ]);
        $payer($eleveCp, TypeDetail::FRAIS_SCOLAIRE, 100000);                              // scolarité : le paiement ne cite pas le frais
        $payer($eleveCp, TypeDetail::FRAIS_SCOLAIRE, 50000);
        $payer($eleveCp, TypeDetail::FRAIS_SCOLAIRE, 70000, null, PaiementStatut::EN_ATTENTE);   // pas encore encaissé
        $payer($eleveCe1, TypeDetail::FRAIS_SCOLAIRE, 80000);
        $payer($eleveCp, TypeDetail::CANTINE, 54000, $cantine->id);                        // cantine : le paiement cite le frais

        $recoltes = app(\App\Domain\Finances\Repositories\FraisEcoleRepository::class)
            ->montantsRecoltes([$scolCp, $scolCe1, $cantine], $this->annee->id);
        $this->assertEqualsWithDelta(150000, $recoltes[$scolCp->id], 0.01);
        $this->assertEqualsWithDelta(80000, $recoltes[$scolCe1->id], 0.01);
        $this->assertEqualsWithDelta(54000, $recoltes[$cantine->id], 0.01);

        foreach ([[RoleUtilisateur::Administrateur, true], [RoleUtilisateur::Directeur, true], [RoleUtilisateur::Comptable, false]] as [$role, $visible]) {
            $this->actingAs($this->creerUtilisateur($role));
            $page = $this->get('/finances/frais-ecoles?onglet=scolarite')->assertOk();

            if ($visible) {
                $page->assertSee('id="col-recolte"', false)->assertSee('150 000 F')->assertSee('80 000 F');
            } else {
                $page->assertDontSee('id="col-recolte"', false)->assertDontSee('150 000 F');
                $this->assertNull($page->viewData('recoltes'));
            }
        }
    }

    public function test_cantine_et_transport_affichent_l_encaisse_par_type_de_paiement_dont_la_part_sans_frais(): void
    {
        $zone = $this->creerFrais(35000, TypeDetail::BUS, ['libelle' => 'Zone Kégué']);
        $eleve = $this->creerInscription();
        $ligne = fn (float $montant, $fraisId, int $etat = 1, PaiementStatut $statut = PaiementStatut::ENCAISSE)
            => DB::table('details')->insert([
                'inscription_id' => $eleve->id, 'annee_id' => $this->annee->id, 'libelle' => 'Paiement des frais de bus', 'montant' => $montant,
                'type_paiement' => TypeDetail::BUS->value, 'frais_ecole_id' => $fraisId, 'statut_paiement' => $statut->value, 'etat' => $etat,
            ]);
        $ligne(35000, $zone->id);        // rattaché à un frais
        $ligne(30000, 0);                // référence 0 : aucun frais cité, mais bien un paiement de transport
        $ligne(20000, null);
        $ligne(99000, 0, 2);             // paiement inactif (etat ≠ 1) : ignoré
        $ligne(15000, 0, 1, PaiementStatut::EN_ATTENTE);   // pas encore encaissé : ignoré

        $bilan = app(\App\Domain\Finances\Repositories\FraisEcoleRepository::class)
            ->encaisseParType($this->annee->id, [TypeDetail::BUS->value]);
        $this->assertEqualsWithDelta(85000, $bilan['total'], 0.01);
        $this->assertEqualsWithDelta(50000, $bilan['sans_frais'], 0.01);
        $this->assertSame(2, $bilan['lignes_sans_frais']);

        // Visible de l'Administrateur, pas du Comptable ; absent des onglets qui ne citent pas de frais
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));
        $this->get('/finances/frais-ecoles?onglet=transport')->assertOk()
            ->assertSee('id="encaisse-type"', false)->assertSee('85 000 F')->assertSee('50 000 F');
        $this->get('/finances/frais-ecoles?onglet=scolarite')->assertOk()->assertDontSee('id="encaisse-type"', false);
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $this->get('/finances/frais-ecoles?onglet=transport')->assertOk()->assertDontSee('id="encaisse-type"', false);
    }

    public function test_un_paiement_est_rattache_a_l_annee_ou_le_frais_a_ete_cree(): void
    {
        // Un frais de transport créé l'année précédente, encore réglé cette année
        $precedente = $this->autreAnnee();
        $zone = $this->creerFrais(35000, TypeDetail::BUS, ['libelle' => 'Zone Kégué', 'annee_id' => $precedente->id]);
        $transportCourant = $this->creerFrais(5000, TypeDetail::BUS, ['libelle' => 'Inscription transport']);
        $ancienEleve = $this->creerInscription(null, ['annee_id' => $precedente->id]);
        $eleve = $this->creerInscription();

        foreach ([[$ancienEleve, $precedente->id, 35000], [$eleve, $this->annee->id, 70000]] as [$inscription, $anneePaiement, $montant]) {
            DB::table('details')->insert([
                'inscription_id' => $inscription->id, 'annee_id' => $anneePaiement, 'libelle' => 'Transport', 'montant' => $montant,
                'type_paiement' => TypeDetail::BUS->value, 'frais_ecole_id' => $zone->id,
                'statut_paiement' => PaiementStatut::ENCAISSE->value, 'etat' => 1,
            ]);
        }
        $depot = app(\App\Domain\Finances\Repositories\FraisEcoleRepository::class);

        // Les deux versements comptent sur le frais de l'année précédente, quel que soit l'année du paiement
        $this->assertEqualsWithDelta(105000, $depot->montantsRecoltes([$zone], $precedente->id)[$zone->id], 0.01);
        // … et rien n'est reporté sur le frais de transport de l'année en cours
        $this->assertEqualsWithDelta(0, $depot->montantsRecoltes([$transportCourant], $this->annee->id)[$transportCourant->id], 0.01);

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Administrateur));
        $this->get('/finances/frais-ecoles?onglet=transport&annee_id=' . $precedente->id)->assertOk()
            ->assertSee('Zone Kégué')->assertSee('105 000 F');
    }

    /* ------------------------------------------------------------------
       Création
       ------------------------------------------------------------------ */

    public function test_seuls_inscription_assurance_et_scolarite_se_creent_depuis_les_frais(): void
    {
        foreach ([TypeDetail::FRAIS_INSCRIPTION, TypeDetail::FRAIS_ASSURANCE, TypeDetail::FRAIS_SCOLAIRE] as $type) {
            $this->postJson('/finances/frais-ecoles', $this->donneesFrais(['libelle' => 'OK ' . $type->name, 'type_paiement' => $type->value]))
                ->assertOk();
        }
        foreach ([TypeDetail::CANTINE, TypeDetail::BUS, TypeDetail::ACTIVITE, TypeDetail::EVENEMENT, TypeDetail::PRODUIT] as $type) {
            $this->postJson('/finances/frais-ecoles', $this->donneesFrais(['libelle' => 'KO ' . $type->name, 'type_paiement' => $type->value]))
                ->assertStatus(422)->assertJsonValidationErrors('type_paiement');
        }

        $this->assertSame(3, FraisEcole::count());
        // Le frais créé est rattaché à l'année de l'en-tête
        $this->assertSame([$this->annee->id], FraisEcole::pluck('annee_id')->unique()->map(fn ($a) => (int) $a)->all());

        // Le formulaire ne propose que ces trois natures
        $this->assertEqualsCanonicalizing(
            [TypeDetail::FRAIS_INSCRIPTION->value, TypeDetail::FRAIS_ASSURANCE->value, TypeDetail::FRAIS_SCOLAIRE->value],
            array_keys($this->get('/finances/frais-ecoles')->viewData('naturesCreables'))
        );
    }

    public function test_un_frais_de_cantine_existant_reste_modifiable_sans_changer_de_nature(): void
    {
        $cantine = $this->creerFrais(108000, TypeDetail::CANTINE, ['libelle' => 'Cantine CP']);

        $this->putJson("/finances/frais-ecoles/{$cantine->id}", $this->donneesFrais([
            'libelle' => 'Cantine CP', 'montant' => 120000, 'type_paiement' => TypeDetail::CANTINE->value,
            'type_forfait' => TypeForfait::OPTIONNEL->value,
        ]))->assertOk();
        $this->assertEqualsWithDelta(120000, $cantine->fresh()->montant, 0.01);

        // … mais un frais de scolarité ne devient pas un frais de cantine
        $scolarite = $this->creerFrais(300000);
        $this->putJson("/finances/frais-ecoles/{$scolarite->id}", $this->donneesFrais(['type_paiement' => TypeDetail::CANTINE->value]))
            ->assertStatus(422);
        $this->assertSame(TypeDetail::FRAIS_SCOLAIRE, $scolarite->fresh()->type_paiement);
    }

    public function test_un_frais_echelonne_se_cree_avec_son_echeancier(): void
    {
        $this->postJson('/finances/frais-ecoles', $this->donneesFrais([
            'modalite_paiement' => TypePaiement::ECHELONNE->value,
            'echeances' => [
                ['libelle' => 'Rentrée', 'date_echeance' => '2026-09-15', 'montant' => 150000],
                ['libelle' => '', 'date_echeance' => '2026-12-15', 'montant' => 75000],
                ['libelle' => 'Solde', 'date_echeance' => '2027-02-15', 'montant' => 75000],
            ],
        ]))->assertOk();

        $frais = FraisEcole::with('planEcheancier.lignes')->firstOrFail();
        $this->assertSame(TypePaiement::ECHELONNE, $frais->modalite_paiement);
        $this->assertSame($this->annee->id, (int) $frais->planEcheancier->annee_id);
        $lignes = $frais->planEcheancier->lignes;
        $this->assertSame(['Rentrée', 'Échéance 2', 'Solde'], $lignes->pluck('libelle')->all());
        $this->assertSame([1, 2, 3], $lignes->pluck('ordre')->all());
        $this->assertEqualsWithDelta(300000, $lignes->sum('montant'), 0.01);
        $this->assertSame('2026-12-15', $lignes[1]->date_echeance->toDateString());
        $this->assertEqualsWithDelta(50, $lignes[0]->pourcentage, 0.01);

        // Ressaisir l'échéancier remplace les échéances sans créer un second plan
        $this->putJson("/finances/frais-ecoles/{$frais->id}", $this->donneesFrais([
            'modalite_paiement' => TypePaiement::ECHELONNE->value,
            'echeances' => [
                ['libelle' => 'A', 'date_echeance' => '2026-09-15', 'montant' => 200000],
                ['libelle' => 'B', 'date_echeance' => '2026-12-15', 'montant' => 100000],
            ],
        ]))->assertOk();
        $this->assertSame(1, PlanEcheancier::count());
        $this->assertSame(['A', 'B'], $frais->fresh('planEcheancier.lignes')->planEcheancier->lignes->pluck('libelle')->all());
    }

    public function test_la_somme_des_echeances_doit_egaler_le_montant_du_frais(): void
    {
        $this->postJson('/finances/frais-ecoles', $this->donneesFrais([
            'modalite_paiement' => TypePaiement::ECHELONNE->value,
            'echeances' => [
                ['libelle' => 'A', 'date_echeance' => '2026-09-15', 'montant' => 150000],
                ['libelle' => 'B', 'date_echeance' => '2026-12-15', 'montant' => 100000],
            ],
        ]))->assertStatus(422)->assertJsonPath('success', false);

        // Rien n'est resté en base (transaction)
        $this->assertSame(0, FraisEcole::count());
        $this->assertSame(0, PlanEcheancier::count());
    }

    public function test_un_frais_comptant_ne_cree_aucun_echeancier(): void
    {
        $this->postJson('/finances/frais-ecoles', $this->donneesFrais())->assertOk();

        $this->assertNull(FraisEcole::firstOrFail()->plan_echeancier_id);
        $this->assertSame(0, PlanEcheancier::count());
    }

    /* ------------------------------------------------------------------
       Tranches → plans d'échéancier
       ------------------------------------------------------------------ */

    private function tranches(FraisEcole $frais, array $taux = [52.94, 23.53, 23.53]): void
    {
        foreach ($taux as $i => $t) {
            DB::table('tranches')->insert([
                'libelle' => 'Tranche ' . ($i + 1), 'date_butoire' => ['2026-09-15', '2026-12-15', '2027-02-15'][$i],
                'frais_ecole_id' => $frais->id, 'taux' => $t, 'etat' => 1,
            ]);
        }
    }

    public function test_la_conversion_des_tranches_cree_les_plans_de_l_annee_sans_rien_supprimer(): void
    {
        $precedente = $this->autreAnnee();
        $courant = $this->creerFrais(425000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'TPS']);
        $ancien = $this->creerFrais(400000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'TPS 2025', 'annee_id' => $precedente->id]);
        $this->tranches($courant);
        $this->tranches($ancien);
        $service = app(EcheancierService::class);

        // Bilan préalable, sans écriture
        $apercu = $service->apercuConversion($this->annee->id);
        $this->assertCount(1, $apercu['a_convertir']);
        $this->assertSame(3, $apercu['hors_annee']);
        $this->assertSame(0, PlanEcheancier::count());

        $bilan = $service->convertirTranches($this->annee->id);
        $this->assertSame(['plans_crees' => 1, 'echeances_creees' => 3], array_intersect_key($bilan, ['plans_crees' => 0, 'echeances_creees' => 0]));

        $courant->refresh()->load('planEcheancier.lignes');
        $lignes = $courant->planEcheancier->lignes;
        $this->assertSame(TypePaiement::ECHELONNE, $courant->modalite_paiement);
        $this->assertSame(['Tranche 1', 'Tranche 2', 'Tranche 3'], $lignes->pluck('libelle')->all());
        $this->assertSame('2026-09-15', $lignes[0]->date_echeance->toDateString());
        $this->assertEqualsWithDelta(52.94, $lignes[0]->pourcentage, 0.001);
        // 425 000 × 52,94 % arrondi au franc ; la dernière échéance absorbe l'écart
        $this->assertEqualsWithDelta(224995, $lignes[0]->montant, 0.01);
        $this->assertEqualsWithDelta(425000, $lignes->sum('montant'), 0.01);

        // Les tranches sont conservées et tracées ; l'autre année n'est pas touchée
        $this->assertSame(6, DB::table('tranches')->where('etat', 1)->count());
        $this->assertSame(3, DB::table('tranches')->whereNotNull('plan_echeancier_ligne_id')->count());
        $this->assertNull($ancien->fresh()->plan_echeancier_id);

        // Relancer ne crée rien de plus
        $relance = $service->convertirTranches($this->annee->id);
        $this->assertSame(0, $relance['plans_crees']);
        $this->assertSame(1, $relance['deja_convertis']);
        $this->assertSame(1, PlanEcheancier::count());
    }

    public function test_des_tranches_incoherentes_sont_signalees_et_non_converties(): void
    {
        $frais = $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'CP']);
        $this->tranches($frais, [50, 30]);   // 80 % seulement

        $bilan = app(EcheancierService::class)->convertirTranches($this->annee->id);

        $this->assertSame(0, $bilan['plans_crees']);
        $this->assertCount(1, $bilan['incoherences']);
        $this->assertStringContainsString('80', $bilan['incoherences'][0]);
        $this->assertNull($frais->fresh()->plan_echeancier_id);
    }

    public function test_l_ecran_des_plans_propose_la_conversion_et_le_menu_tranches_a_disparu(): void
    {
        $frais = $this->creerFrais(425000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'TPS']);
        $this->tranches($frais);

        $this->get('/finances/plan-echeanciers')->assertOk()
            ->assertSee('id="btn-convertir-tranches"', false)
            ->assertSee('<span id="conversion-nb-tranches">3</span>', false)
            ->assertSee('Année scolaire : ' . $this->annee->libelle);

        $this->getJson('/finances/plan-echeanciers/conversion-tranches')->assertOk()
            ->assertJsonPath('frais_a_convertir', 1)->assertJsonPath('tranches', 3);
        $this->postJson('/finances/plan-echeanciers/conversion-tranches')->assertOk()
            ->assertJsonPath('data.bilan.plans_crees', 1);
        $this->get('/finances/plan-echeanciers')->assertOk()
            ->assertDontSee('id="btn-convertir-tranches"', false)
            ->assertSee('Échéancier — TPS');

        // L'ancienne adresse renvoie vers les plans ; le menu ne propose plus « Tranches »
        $this->get('/finances/tranches')->assertRedirect('/finances/plan-echeanciers');
        $entrees = collect(config('menu'))->flatMap(fn ($m) => collect($m['groupes'])->flatten(1))->pluck(0);
        $this->assertFalse($entrees->contains('Tranches'));

        // La Direction consulte mais ne convertit pas
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $this->postJson('/finances/plan-echeanciers/conversion-tranches')->assertForbidden();
    }

    public function test_la_liste_des_plans_donne_le_frais_lie_les_montants_et_les_dates_butoirs_en_cinq_colonnes(): void
    {
        $frais = $this->creerFrais(425000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité TPS']);
        $this->tranches($frais);
        app(EcheancierService::class)->convertirTranches($this->annee->id);
        // Un plan en pourcentages, sans frais : ses échéances restent exprimées en %
        $libre = PlanEcheancier::create(['nom' => 'Plan libre', 'annee_id' => $this->annee->id, 'etat' => 1]);
        \App\Domain\Finances\Models\PlanEcheancierLigne::create([
            'plan_echeancier_id' => $libre->id, 'ordre' => 1, 'libelle' => 'Unique', 'pourcentage' => 100, 'etat' => 1,
        ]);

        $page = $this->get('/finances/plan-echeanciers')->assertOk();

        $entete = explode('</thead>', explode('table-plans', $page->getContent(), 3)[2])[0];
        $this->assertSame(5, preg_match_all('/<th[ >]/', $entete));
        $page->assertSeeInOrder(['Plan', 'Frais lié', 'Échéances', 'Total', 'Actions'])
            // Frais lié, puis chaque tranche avec son montant et sa date butoir
            ->assertSee('Scolarité TPS')
            ->assertSeeInOrder(['Tranche 1', '224 995 F', '15/09/2026', 'Tranche 2', '100 003 F', '15/12/2026', 'Tranche 3', '15/02/2027'])
            ->assertSee('425 000 F')
            ->assertSee('Aucun frais rattaché')->assertSee('100 %');

        // La recherche trouve un plan par le libellé de son frais
        $this->assertSame(1, $this->get('/finances/plan-echeanciers?search=TPS')->viewData('plans')->total());
    }

    public function test_les_plans_listes_sont_ceux_de_l_annee_de_l_en_tete(): void
    {
        $precedente = $this->autreAnnee();
        PlanEcheancier::create(['nom' => 'PLAN-COURANT', 'annee_id' => $this->annee->id, 'etat' => 1]);
        PlanEcheancier::create(['nom' => 'PLAN-ANCIEN', 'annee_id' => $precedente->id, 'etat' => 1]);

        $this->get('/finances/plan-echeanciers')->assertOk()->assertSee('PLAN-COURANT')->assertDontSee('PLAN-ANCIEN');
        // Le formulaire des frais ne propose que les plans de l'année
        $this->assertSame(['PLAN-COURANT'], $this->get('/finances/frais-ecoles')->viewData('plansOptions')->pluck('nom')->all());
    }

    /* ------------------------------------------------------------------
       Suivi des échéances
       ------------------------------------------------------------------ */

    public function test_le_suivi_affecte_chaque_paiement_une_seule_fois_dans_l_ordre_des_echeances(): void
    {
        ['niveau_id' => $niveauId] = $this->creerStructure('CP');
        $frais = $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CP', 'niveau_id' => $niveauId]);
        app(EcheancierService::class)->definirPourFrais($frais, [
            ['libelle' => 'T1', 'date_echeance' => now()->subMonth()->toDateString(), 'montant' => 150000],
            ['libelle' => 'T2', 'date_echeance' => now()->subDay()->toDateString(), 'montant' => 75000],
            ['libelle' => 'T3', 'date_echeance' => now()->addMonths(2)->toDateString(), 'montant' => 75000],
        ]);

        // Élève A : 300 000 dus, 200 000 encaissés (+ 50 000 saisis non encaissés, qui ne comptent pas)
        $a = $this->creerInscription(null, ['niveau_id' => $niveauId, 'frais_scolarite' => 300000]);
        // Élève B : 10 % de remise → 270 000 dus, rien de payé
        $b = $this->creerInscription(null, ['niveau_id' => $niveauId, 'frais_scolarite' => 300000, 'taux_remise' => 10]);
        foreach ([[200000, PaiementStatut::ENCAISSE], [50000, PaiementStatut::EN_ATTENTE]] as [$montant, $statut]) {
            DB::table('details')->insert([
                'inscription_id' => $a->id, 'annee_id' => $this->annee->id, 'libelle' => 'Scolarité', 'montant' => $montant,
                'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value, 'statut_paiement' => $statut->value, 'etat' => 1,
            ]);
        }

        $suivi = app(EcheancierService::class)->suivi($frais->fresh()->load('planEcheancier.lignes'));

        $this->assertSame(2, $suivi['eleves']);
        $this->assertEqualsWithDelta(570000, $suivi['prevu'], 0.01);
        $this->assertEqualsWithDelta(200000, $suivi['paye'], 0.01);
        $this->assertEqualsWithDelta(370000, $suivi['reste'], 0.01);

        [$t1, $t2, $t3] = $suivi['lignes'];
        // T1 : A 150 000 + B 135 000 prévus ; A a réglé les siens
        $this->assertEqualsWithDelta(285000, $t1['prevu'], 0.01);
        $this->assertEqualsWithDelta(150000, $t1['paye'], 0.01);
        $this->assertSame('partielle', $t1['statut']);
        $this->assertSame(1, $t1['a_jour']);
        // T2 : A y affecte le reste de ses 200 000 (50 000 sur 75 000)
        $this->assertEqualsWithDelta(50000, $t2['paye'], 0.01);
        $this->assertSame('partielle', $t2['statut']);
        // T3 : rien de payé, échéance à venir
        $this->assertEqualsWithDelta(0, $t3['paye'], 0.01);
        $this->assertSame('a_venir', $t3['statut']);

        // La fiche du frais présente ce suivi
        $this->get("/finances/frais-ecoles/{$frais->id}")->assertOk()
            ->assertSee('id="suivi-echeances"', false)->assertSee('Partiellement payée')->assertSee('À venir');
    }

    public function test_le_suivi_par_eleve_dit_qui_est_a_jour_a_chaque_echeance_sur_un_intervalle_de_dates(): void
    {
        ['niveau_id' => $niveauId] = $this->creerStructure('CM2');
        $frais = $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CM2', 'niveau_id' => $niveauId]);
        $plan = app(EcheancierService::class)->definirPourFrais($frais, [
            ['libelle' => 'T1', 'date_echeance' => '2026-09-15', 'montant' => 150000],
            ['libelle' => 'T2', 'date_echeance' => '2026-12-15', 'montant' => 75000],
            ['libelle' => 'T3', 'date_echeance' => '2027-02-15', 'montant' => 75000],
        ]);
        $ama = $this->creerInscription($this->creerEleve(['nom' => 'AJOUR', 'prenom' => 'Ama']), ['niveau_id' => $niveauId, 'frais_scolarite' => 300000]);
        $kofi = $this->creerInscription($this->creerEleve(['nom' => 'RETARD', 'prenom' => 'Kofi']), ['niveau_id' => $niveauId, 'frais_scolarite' => 300000]);
        $encaisser = fn ($inscription, float $montant, string $date) => DB::table('details')->insert([
            'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'libelle' => 'Scolarité', 'montant' => $montant,
            'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value, 'statut_paiement' => PaiementStatut::ENCAISSE->value,
            'date_paiement' => $date, 'date_encaissement' => $date, 'etat' => 1,
        ]);
        $encaisser($ama, 150000, '2026-09-10');     // T1 réglée à temps
        $encaisser($ama, 75000, '2026-12-20');      // T2 réglée, mais après le 15/12
        $encaisser($kofi, 100000, '2026-09-12');    // T1 payée en partie
        $service = app(EcheancierService::class);
        $noms = fn (array $eleves) => array_column($eleves, 'nom');

        // Intervalle septembre → 15 décembre : T1 et T2, situation arrêtée au 15/12
        $suivi = $service->suiviEleves($plan, \Illuminate\Support\Carbon::parse('2026-09-01'), \Illuminate\Support\Carbon::parse('2026-12-15'));
        $this->assertSame(['T1', 'T2'], array_column($suivi['echeances'], 'libelle'));
        [$t1, $t2] = $suivi['echeances'];
        $this->assertSame(['AJOUR Ama'], $noms($t1['a_jour']));
        $this->assertSame(['RETARD Kofi'], $noms($t1['en_retard']));
        $this->assertEqualsWithDelta(50000, $t1['en_retard'][0]['reste'], 0.01);
        // Au 15/12 le second versement d'Ama (20/12) n'est pas encore encaissé
        $this->assertSame([], $noms($t2['a_jour']));
        $this->assertEqualsCanonicalizing(['AJOUR Ama', 'RETARD Kofi'], $noms($t2['en_retard']));

        // Situation arrêtée au 31/12 : Ama est à jour à T2
        $suivi = $service->suiviEleves($plan, \Illuminate\Support\Carbon::parse('2026-12-01'), \Illuminate\Support\Carbon::parse('2026-12-31'));
        $this->assertSame(['T2'], array_column($suivi['echeances'], 'libelle'));
        $this->assertSame(['AJOUR Ama'], $noms($suivi['echeances'][0]['a_jour']));
        $this->assertSame(['RETARD Kofi'], $noms($suivi['echeances'][0]['en_retard']));

        // L'écran : action depuis la liste des plans, intervalle de dates, filtre « non à jour »
        $this->get('/finances/plan-echeanciers')->assertOk()->assertSee("/finances/plan-echeanciers/{$plan->id}/eleves", false);
        $this->get("/finances/plan-echeanciers/{$plan->id}/eleves?du=2026-09-01&au=2026-12-31")->assertOk()
            ->assertSee('name="du"', false)->assertSee('name="au"', false)
            ->assertSee('Scolarité CM2')->assertSeeInOrder(['T1', 'T2'])
            ->assertSee('AJOUR Ama')->assertSee('RETARD Kofi')->assertDontSee('T3');
        $this->get("/finances/plan-echeanciers/{$plan->id}/eleves?du=2026-09-01&au=2026-09-30&statut=en_retard")->assertOk()
            ->assertSee('RETARD Kofi')->assertDontSee('AJOUR Ama');

        // Exports Excel et PDF : même intervalle, même filtre que l'écran
        $requete = '?du=2026-09-01&au=2026-09-30&statut=en_retard';
        $this->get("/finances/plan-echeanciers/{$plan->id}/eleves{$requete}")->assertOk()
            ->assertSee('eleves/export?du=2026-09-01&amp;au=2026-09-30&amp;statut=en_retard', false)
            ->assertSee('eleves/export-pdf?du=2026-09-01', false);
        $excel = $this->get("/finances/plan-echeanciers/{$plan->id}/eleves/export{$requete}")->assertOk();
        $this->assertStringContainsString('.xlsx', $excel->headers->get('content-disposition'));
        $texte = $this->texteDuClasseur($excel);
        $this->assertStringContainsString('RETARD Kofi', $texte);
        $this->assertStringNotContainsString('AJOUR Ama', $texte);
        $this->assertDoesNotMatchRegularExpression('/\bT2\b/', $texte);   // le mot « T2 », pas l'heure (T22:00) des métadonnées
        $lignes = (new \App\Domain\Finances\Exports\SuiviEcheancesExport(
            $service->suiviEleves($plan, \Illuminate\Support\Carbon::parse('2026-09-01'), \Illuminate\Support\Carbon::parse('2026-12-31'))
        ))->collection();
        $this->assertCount(4, $lignes);     // 2 élèves × 2 échéances
        $this->get("/finances/plan-echeanciers/{$plan->id}/eleves/export-pdf{$requete}")->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        // Dates incohérentes refusées ; plan d'une autre année introuvable depuis cet en-tête
        $this->get("/finances/plan-echeanciers/{$plan->id}/eleves?du=2026-12-31&au=2026-09-01")->assertSessionHasErrors('au');
        $autre = PlanEcheancier::create(['nom' => 'Ancien', 'annee_id' => $this->autreAnnee()->id, 'etat' => 1]);
        $this->get("/finances/plan-echeanciers/{$autre->id}/eleves")->assertNotFound();
    }

    public function test_les_eleves_non_a_jour_a_une_date_tous_echeanciers_confondus_s_affichent_et_s_exportent(): void
    {
        $encaisser = fn ($inscription, float $montant, string $date) => DB::table('details')->insert([
            'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'libelle' => 'Scolarité', 'montant' => $montant,
            'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value, 'statut_paiement' => PaiementStatut::ENCAISSE->value,
            'date_paiement' => $date, 'date_encaissement' => $date, 'etat' => 1,
        ]);
        $echeances = [
            ['libelle' => 'T1', 'date_echeance' => '2026-09-15', 'montant' => 150000],
            ['libelle' => 'T2', 'date_echeance' => '2026-12-15', 'montant' => 150000],
        ];
        // Deux échéanciers en cours, un par niveau
        ['niveau_id' => $cp] = $this->creerStructure('CP');
        ['niveau_id' => $ce1] = $this->creerStructure('CE1');
        app(EcheancierService::class)->definirPourFrais($this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CP', 'niveau_id' => $cp]), $echeances);
        app(EcheancierService::class)->definirPourFrais($this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CE1', 'niveau_id' => $ce1]), $echeances);

        $ama = $this->creerInscription($this->creerEleve(['nom' => 'AJOUR', 'prenom' => 'Ama']), ['niveau_id' => $cp, 'frais_scolarite' => 300000]);
        $kofi = $this->creerInscription($this->creerEleve(['nom' => 'RETARD', 'prenom' => 'Kofi']), ['niveau_id' => $cp, 'frais_scolarite' => 300000]);
        $yao = $this->creerInscription($this->creerEleve(['nom' => 'PARTIEL', 'prenom' => 'Yao']), ['niveau_id' => $ce1, 'frais_scolarite' => 300000]);
        $encaisser($ama, 150000, '2026-09-10');
        $encaisser($yao, 60000, '2026-09-20');
        $service = app(EcheancierService::class);

        // Au 1er octobre : seule T1 est échue. Ama est à jour ; Kofi et Yao ne le sont pas.
        $situation = $service->elevesNonAJour($this->annee->id, \Illuminate\Support\Carbon::parse('2026-10-01'));
        $this->assertSame(2, $situation['plans']);
        $this->assertSame(3, $situation['suivis']);
        $this->assertSame(['PARTIEL Yao', 'RETARD Kofi'], array_column($situation['eleves'], 'nom'));   // triés par niveau puis nom
        $yaoLigne = $situation['eleves'][0];
        $this->assertEqualsWithDelta(150000, $yaoLigne['du'], 0.01);
        $this->assertEqualsWithDelta(60000, $yaoLigne['paye'], 0.01);
        $this->assertEqualsWithDelta(90000, $yaoLigne['retard'], 0.01);
        $this->assertSame(['T1'], array_column($yaoLigne['echeances'], 'libelle'));
        $this->assertEqualsWithDelta(240000, $situation['retard'], 0.01);

        // Le 14 septembre, avant la première date butoir : personne n'est en retard
        $this->assertSame([], $service->elevesNonAJour($this->annee->id, \Illuminate\Support\Carbon::parse('2026-09-14'))['eleves']);
        // Au 20 décembre, T2 est échue aussi : Ama devient non à jour
        $this->assertCount(3, $service->elevesNonAJour($this->annee->id, \Illuminate\Support\Carbon::parse('2026-12-20'))['eleves']);

        // L'écran, ouvert par le bouton de la liste des plans
        $this->get('/finances/plan-echeanciers')->assertOk()->assertSee('id="btn-non-a-jour"', false);
        $page = $this->get('/finances/plan-echeanciers/non-a-jour?jour=2026-10-01')->assertOk()
            ->assertSee('name="jour"', false)->assertSee('RETARD Kofi')->assertSee('PARTIEL Yao')->assertDontSee('AJOUR Ama')
            ->assertSee('240 000 F');
        $entete = explode('</thead>', explode('id="table-non-a-jour"', $page->getContent())[1])[0];
        $this->assertSame(5, preg_match_all('/<th[ >]/', $entete));
        // Le frais porte son libellé complet ; la liste est paginée
        $page->assertSee('Frais de scolarité Scolarité CP');
        $this->assertSame(2, $page->viewData('pagination')->total());
        $this->assertSame(25, $page->viewData('pagination')->perPage());
        // Filtre par niveau
        $this->get('/finances/plan-echeanciers/non-a-jour?jour=2026-10-01&niveau=CE1')->assertOk()
            ->assertSee('PARTIEL Yao')->assertDontSee('RETARD Kofi');

        // Exports Excel et PDF de la même situation
        $excel = $this->get('/finances/plan-echeanciers/non-a-jour/export?jour=2026-10-01&niveau=CE1')->assertOk();
        $this->assertStringContainsString('eleves_non_a_jour_20261001.xlsx', $excel->headers->get('content-disposition'));
        $texte = $this->texteDuClasseur($excel);
        $this->assertStringContainsString('PARTIEL Yao', $texte);
        $this->assertStringNotContainsString('RETARD Kofi', $texte);
        $this->get('/finances/plan-echeanciers/non-a-jour/export-pdf?jour=2026-10-01')->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_une_echeance_echue_sans_paiement_est_exigible_et_une_echeance_couverte_est_reglee(): void
    {
        ['niveau_id' => $niveauId] = $this->creerStructure('CE1');
        $frais = $this->creerFrais(100000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité CE1', 'niveau_id' => $niveauId]);
        app(EcheancierService::class)->definirPourFrais($frais, [
            ['libelle' => 'T1', 'date_echeance' => now()->subMonths(2)->toDateString(), 'montant' => 60000],
            ['libelle' => 'T2', 'date_echeance' => now()->subDay()->toDateString(), 'montant' => 40000],
        ]);
        $inscription = $this->creerInscription(null, ['niveau_id' => $niveauId, 'frais_scolarite' => 100000]);
        DB::table('details')->insert([
            'inscription_id' => $inscription->id, 'annee_id' => $this->annee->id, 'libelle' => 'Scolarité', 'montant' => 60000,
            'type_paiement' => TypeDetail::FRAIS_SCOLAIRE->value, 'statut_paiement' => PaiementStatut::ENCAISSE->value, 'etat' => 1,
        ]);

        $lignes = app(EcheancierService::class)->suivi($frais->fresh()->load('planEcheancier.lignes'))['lignes'];

        $this->assertSame('reglee', $lignes[0]['statut']);
        $this->assertSame('exigible', $lignes[1]['statut']);
    }

    /* ------------------------------------------------------------------
       Synchronisation des catalogues
       ------------------------------------------------------------------ */

    public function test_la_synchronisation_alimente_produits_activites_et_evenements_sans_doublon(): void
    {
        $precedente = $this->autreAnnee();
        $this->creerFrais(7500, TypeDetail::PRODUIT, ['libelle' => 'T-SHIRT']);
        $this->creerFrais(7500, TypeDetail::PRODUIT, ['libelle' => 't-shirt ']);       // même article, autre niveau
        $this->creerFrais(30000, TypeDetail::ACTIVITE, ['libelle' => 'FOOTBALL']);
        $this->creerFrais(5000, TypeDetail::EVENEMENT, ['libelle' => 'Sortie de fin d\'année']);
        $this->creerFrais(30000, TypeDetail::ACTIVITE, ['libelle' => 'RUGBY 2025', 'annee_id' => $precedente->id]);
        $this->creerFrais(300000, TypeDetail::FRAIS_SCOLAIRE, ['libelle' => 'Scolarité']);   // ne génère rien

        $attendu = ['produits' => ['produits', 1], 'activites' => ['activites', 1], 'evenements' => ['evenements', 1]];
        foreach ($attendu as $cible => [$table, $nb]) {
            $this->postJson("/finances/frais-ecoles/synchroniser/{$cible}")->assertOk()->assertJsonPath('data.bilan.crees', $nb);
            // Relance : rien de créé, tout est reconnu
            $this->postJson("/finances/frais-ecoles/synchroniser/{$cible}")->assertOk()
                ->assertJsonPath('data.bilan.crees', 0)->assertJsonPath('data.bilan.ignores', $nb);
            $this->assertSame($nb, DB::table($table)->count(), $table);
        }

        $this->assertDatabaseHas('activites', ['libelle' => 'FOOTBALL', 'annee_id' => $this->annee->id, 'montant' => 30000]);
        $this->assertDatabaseMissing('activites', ['libelle' => 'RUGBY 2025']);      // autre année : non touchée
        $this->assertDatabaseHas('evenements', ['nom' => 'Sortie de fin d\'année', 'participation' => 5000, 'annee_id' => $this->annee->id]);
        // Les deux frais « T-shirt » pointent vers la même fiche produit
        $this->assertSame(1, FraisEcole::where('type_paiement', TypeDetail::PRODUIT->value)->pluck('produit_id')->unique()->count());
        $this->assertNotNull(FraisEcole::where('libelle', 'FOOTBALL')->value('activite_id'));

        // Un montant de frais modifié met la fiche à jour
        FraisEcole::where('libelle', 'FOOTBALL')->update(['montant' => 35000]);
        $bilan = app(FraisSynchronisationService::class)->synchroniser('activites', $this->annee->id);
        $this->assertSame(1, $bilan['mis_a_jour']);
        $this->assertDatabaseHas('activites', ['libelle' => 'FOOTBALL', 'montant' => 35000]);
    }

    public function test_la_synchronisation_est_reservee_a_qui_gere_les_frais(): void
    {
        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Directeur));
        $this->postJson('/finances/frais-ecoles/synchroniser/produits')->assertForbidden();

        $this->actingAs($this->creerUtilisateur(RoleUtilisateur::Comptable));
        $this->postJson('/finances/frais-ecoles/synchroniser/inconnu')->assertNotFound();
    }
}
