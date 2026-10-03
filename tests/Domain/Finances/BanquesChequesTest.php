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
}
