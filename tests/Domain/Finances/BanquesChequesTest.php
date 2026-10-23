<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\DTO\BanqueData;
use App\Domain\Finances\DTO\ChequeData;
use App\Domain\Finances\Models\Cheque;
use App\Domain\Finances\Services\BanqueService;
use App\Domain\Finances\Services\ChequeService;
use App\Domain\Finances\Types\ChequeStatut;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.5 Banques & Chèques.
 */
class BanquesChequesTest extends DomainTestCase
{
    private ChequeService $service;
    private int $comptableId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChequeService::class);
        $this->comptableId = $this->creerUtilisateur()->id;
    }

    private function cheque(string $numero = 'CHQ-0001'): Cheque
    {
        $banque = app(BanqueService::class)->creer(new BanqueData('ECOBANK'));

        return $this->service->enregistrer(
            new ChequeData($numero, 'KOFFI Yao', 225000, $banque->id, '2026-09-10')
        );
    }

    public function test_un_compte_bancaire_est_cree_actif(): void
    {
        $banque = app(BanqueService::class)->creer(new BanqueData('ORABANK'));

        $this->assertSame('ORABANK', $banque->nom);
        $this->assertSame(1, (int) $banque->fresh()->etat);
    }

    public function test_un_cheque_enregistre_entre_au_portefeuille_au_statut_emis(): void
    {
        $cheque = $this->cheque()->fresh();

        $this->assertSame(ChequeStatut::EMIS, $cheque->statut);
        $this->assertEqualsWithDelta(225000, (float) $cheque->montant, 0.01);
        $this->assertSame($this->annee->id, (int) $cheque->annee_id);
    }

    public function test_le_rapprochement_marque_un_cheque_comme_encaisse(): void
    {
        $cheque = $this->service->marquerEncaisse($this->cheque()->id, $this->comptableId, '2026-09-15');

        $this->assertSame(ChequeStatut::ENCAISSE, $cheque->statut);
        $this->assertSame($this->comptableId, (int) $cheque->rapproche_par);
        $this->assertNotNull($cheque->date_rapprochement);
        $this->assertSame('2026-09-15', \Carbon\Carbon::parse($cheque->date_encaissement)->toDateString());
    }

    public function test_un_cheque_deja_encaisse_ne_l_est_pas_deux_fois(): void
    {
        $cheque = $this->cheque();
        $this->service->marquerEncaisse($cheque->id, $this->comptableId);

        $this->expectException(RuntimeException::class);

        $this->service->marquerEncaisse($cheque->id, $this->comptableId);
    }

    public function test_le_rejet_d_un_cheque_exige_un_motif(): void
    {
        $cheque = $this->cheque();

        $this->expectException(RuntimeException::class);

        $this->service->marquerRejete($cheque->id, $this->comptableId, '   ');
    }

    public function test_un_cheque_rejete_conserve_le_motif_du_rejet(): void
    {
        $cheque = $this->service->marquerRejete($this->cheque()->id, $this->comptableId, 'Provision insuffisante');

        $this->assertSame(ChequeStatut::REJETE, $cheque->statut);
        $this->assertSame('Provision insuffisante', $cheque->motif_rejet);
    }

    public function test_un_cheque_traite_peut_etre_remis_en_attente(): void
    {
        $cheque = $this->cheque();
        $this->service->marquerRejete($cheque->id, $this->comptableId, 'Erreur de saisie');

        $remis = $this->service->remettreEnAttente($cheque->id);

        $this->assertSame(ChequeStatut::EMIS, $remis->statut);
        $this->assertNull($remis->motif_rejet);
        $this->assertNull($remis->rapproche_par);
    }

    public function test_un_cheque_encore_emis_n_a_pas_a_etre_remis_en_attente(): void
    {
        $cheque = $this->cheque();

        $this->expectException(RuntimeException::class);

        $this->service->remettreEnAttente($cheque->id);
    }

    /** Un paiement réglé par un chèque de la banque, au statut donné. */
    private function paiementParCheque(int $banqueId, string $numero, float $montant, ChequeStatut $statut, ?int $anneeId = null): int
    {
        $inscription = $this->creerInscription();
        $paiementId = \Illuminate\Support\Facades\DB::table('paiements')->insertGetId([
            'reference' => 'PAI-' . $numero, 'montant' => $montant, 'payeur' => 'Parent ' . $numero, 'date_paiement' => '2026-10-01',
            'inscription_id' => $inscription->id, 'annee_id' => $anneeId ?? $this->annee->id, 'etat' => 1,
        ]);
        \Illuminate\Support\Facades\DB::table('cheques')->insert([
            'numero' => $numero, 'emetteur' => 'Émetteur ' . $numero, 'montant' => $montant, 'banque_id' => $banqueId,
            'paiement_id' => $paiementId, 'statut' => $statut->value, 'date_emission' => '2026-10-01',
            'annee_id' => $anneeId ?? $this->annee->id, 'etat' => 1,
        ]);

        return $paiementId;
    }

    public function test_chaque_banque_montre_ce_qui_est_percu_et_ce_qui_reste_avec_les_paiements_et_leur_cheque(): void
    {
        $banque = app(BanqueService::class)->creer(new BanqueData('ECOBANK'));
        $autre = app(BanqueService::class)->creer(new BanqueData('ORABANK'));
        $this->paiementParCheque($banque->id, 'CHQ-ATT-1', 100000, ChequeStatut::EMIS);
        $this->paiementParCheque($banque->id, 'CHQ-ATT-2', 50000, ChequeStatut::EMIS);
        $this->paiementParCheque($banque->id, 'CHQ-ENC-1', 300000, ChequeStatut::ENCAISSE);
        $this->paiementParCheque($banque->id, 'CHQ-REJ-1', 20000, ChequeStatut::REJETE);
        $this->paiementParCheque($autre->id, 'CHQ-ORA-1', 70000, ChequeStatut::EMIS);
        $precedente = $this->creerAnnee(['libelle' => '2025-2026']);
        $this->paiementParCheque($banque->id, 'CHQ-ANCIEN', 999000, ChequeStatut::ENCAISSE, $precedente->id);

        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Comptable));

        // Liste : perçu / reste de l'année, et l'action vers les paiements de la banque
        $page = $this->get('/finances/banques')->assertOk()
            ->assertSee('Réellement perçu')->assertSee('Reste à percevoir')
            ->assertSee(route('finances.banques.show', ['banque' => $banque->id, 'onglet' => 'attente']), false);
        $ecobank = $page->viewData('banques')->firstWhere('id', $banque->id);
        $this->assertEqualsWithDelta(300000, $ecobank->montant_percu, 0.01, 'Sans le chèque de l\'année précédente.');
        $this->assertEqualsWithDelta(150000, $ecobank->montant_reste, 0.01);
        $this->assertSame(4, (int) $ecobank->cheques_total);
        $this->assertSame(2, (int) $ecobank->cheques_en_attente);

        // Fiche : chèques en attente par défaut, chacun avec son paiement
        $page = $this->get(route('finances.banques.show', $banque->id))->assertOk()
            ->assertSee('PAI-CHQ-ATT-1')->assertSee('N° CHQ-ATT-1')->assertSee('PAI-CHQ-ATT-2')
            ->assertDontSee('CHQ-ENC-1')->assertDontSee('CHQ-ORA-1')->assertDontSee('CHQ-ANCIEN');
        $this->assertSame('attente', $page->viewData('onglet'));
        $situation = $page->viewData('situation');
        $this->assertEqualsWithDelta(300000, $situation['percu']['montant'], 0.01);
        $this->assertEqualsWithDelta(150000, $situation['attente']['montant'], 0.01);
        $this->assertEqualsWithDelta(20000, $situation['rejete']['montant'], 0.01);

        $this->get(route('finances.banques.show', ['banque' => $banque->id, 'onglet' => 'encaisse']))->assertOk()
            ->assertSee('CHQ-ENC-1')->assertDontSee('CHQ-ATT-1');
        $this->assertSame(4, $this->get(route('finances.banques.show', ['banque' => $banque->id, 'onglet' => 'tous']))->viewData('lignes')->total());
        // Recherche par élève, payeur ou numéro
        $this->assertSame(1, $this->get(route('finances.banques.show', ['banque' => $banque->id, 'onglet' => 'tous', 'search' => 'REJ']))->viewData('lignes')->total());
    }

    public function test_le_contenu_du_paiement_adosse_au_cheque_se_consulte_depuis_la_banque(): void
    {
        $banque = app(BanqueService::class)->creer(new BanqueData('ECOBANK'));
        $autre = app(BanqueService::class)->creer(new BanqueData('ORABANK'));
        $paiementId = $this->paiementParCheque($banque->id, 'CHQ-0100', 250000, ChequeStatut::EMIS);
        \Illuminate\Support\Facades\DB::table('details')->insert([
            ['paiement_id' => $paiementId, 'libelle' => 'Scolarité 1re tranche', 'montant' => 200000, 'type_paiement' => 2,
             'statut_paiement' => \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE->value, 'annee_id' => $this->annee->id, 'etat' => 1],
            ['paiement_id' => $paiementId, 'libelle' => 'Cantine octobre', 'montant' => 50000, 'type_paiement' => 8,
             'statut_paiement' => \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE->value, 'annee_id' => $this->annee->id, 'etat' => 1],
        ]);
        $paiementAutreBanque = $this->paiementParCheque($autre->id, 'CHQ-0200', 10000, ChequeStatut::EMIS);

        // Un comptable qui n'a pas saisi ce paiement le consulte depuis la banque
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Comptable));
        $url = route('finances.banques.paiement', ['banque' => $banque->id, 'paiement' => $paiementId]);
        $this->get(route('finances.banques.show', $banque->id))->assertOk()
            ->assertSee('btn-voir-paiement', false)->assertSee($url, false);

        $this->get($url)->assertOk()
            ->assertSee('PAI-CHQ-0100')->assertSee('N° CHQ-0100')
            ->assertSee('Scolarité 1re tranche')->assertSee('Cantine octobre')
            ->assertSee('250 000 F')->assertDontSee('id="ecart-cheque-paiement"', false);

        // Un paiement réglé sur une autre banque n'est pas consultable ici
        $this->get(route('finances.banques.paiement', ['banque' => $banque->id, 'paiement' => $paiementAutreBanque]))->assertNotFound();
    }

    public function test_l_ecran_des_cheques_n_a_plus_de_creation_et_montre_le_paiement_de_chaque_cheque(): void
    {
        $banque = app(BanqueService::class)->creer(new BanqueData('ECOBANK'));
        $paiementId = $this->paiementParCheque($banque->id, 'CHQ-0300', 120000, ChequeStatut::EMIS);
        \Illuminate\Support\Facades\DB::table('details')->insert([
            'paiement_id' => $paiementId, 'libelle' => 'Scolarité 2e tranche', 'montant' => 120000, 'type_paiement' => 2,
            'statut_paiement' => \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE->value, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        // Un chèque dont le lien est porté par le paiement (cheque_id), et un chèque sans paiement
        $parPaiement = $this->paiementParCheque($banque->id, 'CHQ-0301', 5000, ChequeStatut::EMIS);
        $idCheque = \Illuminate\Support\Facades\DB::table('cheques')->where('numero', 'CHQ-0301')->value('id');
        \Illuminate\Support\Facades\DB::table('cheques')->where('id', $idCheque)->update(['paiement_id' => null]);
        \Illuminate\Support\Facades\DB::table('paiements')->where('id', $parPaiement)->update(['cheque_id' => $idCheque]);
        $orphelin = $this->cheque('CHQ-0302');

        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Comptable));
        $page = $this->get('/finances/cheques')->assertOk()
            ->assertDontSee('data-bs-target="#chequeModal"', false)->assertDontSee('id="cheque-form"', false)
            ->assertSee(route('finances.cheques.paiement', \Illuminate\Support\Facades\DB::table('cheques')->where('numero', 'CHQ-0300')->value('id')), false)
            ->assertSee(route('finances.cheques.paiement', $idCheque), false)
            ->assertDontSee(route('finances.cheques.paiement', $orphelin->id), false);

        $this->get(route('finances.cheques.paiement', \Illuminate\Support\Facades\DB::table('cheques')->where('numero', 'CHQ-0300')->value('id')))
            ->assertOk()->assertSee('PAI-CHQ-0300')->assertSee('Scolarité 2e tranche')->assertSee('120 000 F')->assertSee('ECOBANK');
        $this->get(route('finances.cheques.paiement', $idCheque))->assertOk()->assertSee('PAI-CHQ-0301');
        $this->get(route('finances.cheques.paiement', $orphelin->id))->assertNotFound();
    }
}
