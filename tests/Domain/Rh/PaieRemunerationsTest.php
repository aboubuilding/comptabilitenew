<?php

namespace Tests\Domain\Rh;

use App\Domain\Rh\Services\IrppService;
use App\Domain\Rh\Services\PaieService;
use App\Domain\Rh\Types\StatutPaie;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cahier des charges — Menu 5, § 5.2 Paie & Rémunérations.
 */
class PaieRemunerationsTest extends RhTestCase
{
    private PaieService $paie;
    private int $periode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paie = app(PaieService::class);
        $this->periode = $this->creerPeriode();
    }

    private function remuneration(int $employeId, array $elements): void
    {
        DB::table('remunerations')->insert($elements + ['employe_id' => $employeId, 'etat' => 1]);
    }

    /* ---------- Calcul de l'IRPP (barème progressif) ---------- */

    public function test_un_revenu_dans_la_premiere_tranche_n_est_pas_impose(): void
    {
        $irpp = app(IrppService::class);

        $this->assertSame(0.0, $irpp->calculer(0));
        $this->assertSame(0.0, $irpp->calculer(60000));
    }

    public function test_l_impot_est_calcule_tranche_par_tranche(): void
    {
        $irpp = app(IrppService::class);

        // 100 000 : 40 000 imposés à 3 %
        $this->assertEqualsWithDelta(1200, $irpp->calculer(100000), 0.01);
        // 200 000 : 90 000 à 3 % + 50 000 à 10 %
        $this->assertEqualsWithDelta(7700, $irpp->calculer(200000), 0.01);
    }

    public function test_l_impot_progresse_sans_saut_entre_deux_tranches(): void
    {
        $irpp = app(IrppService::class);

        $this->assertEqualsWithDelta(2700, $irpp->calculer(150000), 0.01);
        $this->assertEqualsWithDelta(2700.10, $irpp->calculer(150001), 0.01);
    }

    public function test_le_detail_de_l_impot_totalise_le_montant_calcule(): void
    {
        $irpp = app(IrppService::class);

        $detail = $irpp->detailler(400000);

        $this->assertEqualsWithDelta($irpp->calculer(400000), array_sum(array_column($detail, 'impot')), 0.01);
    }

    /* ---------- Bulletins ---------- */

    public function test_un_bulletin_additionne_le_salaire_de_base_les_primes_et_les_avantages(): void
    {
        $employe = $this->creerEmploye(['salaire' => 150000]);
        $this->remuneration($employe->id, [
            'salaire_base' => 150000, 'prime_anciennete' => 10000, 'logement' => 20000, 'deplacement' => 5000,
        ]);

        $bulletin = $this->paie->genererBulletin($employe->id, $this->periode);

        $this->assertEqualsWithDelta(185000, (float) $bulletin->total_brut, 0.01);
        $this->assertSame(StatutPaie::BROUILLON, $bulletin->statut);
        $this->assertNotEmpty($bulletin->reference);
    }

    public function test_sans_remuneration_detaillee_le_bulletin_part_du_salaire_de_la_fiche(): void
    {
        $employe = $this->creerEmploye(['salaire' => 150000]);

        $bulletin = $this->paie->genererBulletin($employe->id, $this->periode);

        $this->assertEqualsWithDelta(150000, (float) $bulletin->salaire_base, 0.01);
        $this->assertEqualsWithDelta(150000, (float) $bulletin->total_brut, 0.01);
    }

    public function test_le_net_a_payer_retranche_les_deductions_et_l_irpp(): void
    {
        $employe = $this->creerEmploye(['salaire' => 200000]);
        DB::table('deductions')->insert(['personnel_id' => $employe->id, 'cnss' => 8000, 'charges_familiale' => 2000, 'etat' => 1]);

        $bulletin = $this->paie->genererBulletin($employe->id, $this->periode);

        // Revenu imposable : 200 000 − 10 000 = 190 000 → 2 700 + 40 000 × 10 % = 6 700
        $this->assertEqualsWithDelta(10000, (float) $bulletin->total_deductions, 0.01);
        $this->assertEqualsWithDelta(6700, (float) $bulletin->total_retenues, 0.01);
        $this->assertEqualsWithDelta(183300, (float) $bulletin->net_a_payer, 0.01);
    }

    public function test_un_irpp_saisi_a_la_main_remplace_le_bareme(): void
    {
        $employe = $this->creerEmploye(['salaire' => 200000, 'irpp_mode' => 'manuel']);
        DB::table('retenues')->insert(['employe_id' => $employe->id, 'irpp' => 5000, 'etat' => 1]);

        $bulletin = $this->paie->genererBulletin($employe->id, $this->periode);

        $this->assertEqualsWithDelta(5000, (float) $bulletin->total_retenues, 0.01);
        $this->assertEqualsWithDelta(195000, (float) $bulletin->net_a_payer, 0.01);
    }

    public function test_un_employe_n_a_qu_un_bulletin_par_periode(): void
    {
        $employe = $this->creerEmploye();
        $this->paie->genererBulletin($employe->id, $this->periode);

        $this->expectException(RuntimeException::class);

        $this->paie->genererBulletin($employe->id, $this->periode);
    }

    public function test_la_generation_en_masse_couvre_tous_les_employes_actifs_sans_doublon(): void
    {
        $this->creerEmploye(['nom_prenom' => 'Employé A', 'salaire' => 100000]);
        $this->creerEmploye(['nom_prenom' => 'Employé B', 'salaire' => 200000, 'mode_paiement' => 'banque', 'banque' => 'ECOBANK']);

        $premier = $this->paie->genererBulletinsPeriode($this->periode);
        $second = $this->paie->genererBulletinsPeriode($this->periode);

        $this->assertSame(2, $premier['crees']);
        $this->assertEqualsWithDelta(300000, $premier['total_brut'], 0.01);
        $this->assertSame(1, $premier['repartition']['billette']['nb']);
        $this->assertSame(1, $premier['repartition']['banque']['nb']);
        $this->assertSame(0, $second['crees']);
        $this->assertSame(2, $second['ignores']);
    }

    public function test_un_bulletin_suit_le_cycle_brouillon_valide_paye(): void
    {
        $employe = $this->creerEmploye();
        $payeur = $this->creerUtilisateur()->id;
        $bulletin = $this->paie->genererBulletin($employe->id, $this->periode);

        $valide = $this->paie->valider($bulletin->id);
        $this->assertSame(StatutPaie::VALIDE, $valide->statut);

        $paye = $this->paie->payer($bulletin->id, $payeur, '2026-09-30');
        $this->assertTrue($paye->isPaye());
        $this->assertSame($payeur, (int) $paye->paye_par);
    }

    public function test_un_bulletin_deja_valide_ne_se_valide_pas_deux_fois(): void
    {
        $bulletin = $this->paie->genererBulletin($this->creerEmploye()->id, $this->periode);
        $this->paie->valider($bulletin->id);

        $this->expectException(RuntimeException::class);

        $this->paie->valider($bulletin->id);
    }

    public function test_un_bulletin_paye_ne_peut_plus_etre_annule(): void
    {
        $bulletin = $this->paie->genererBulletin($this->creerEmploye()->id, $this->periode);
        $this->paie->payer($bulletin->id, $this->creerUtilisateur()->id);

        $this->expectException(RuntimeException::class);

        $this->paie->annuler($bulletin->id, 'Erreur');
    }

    public function test_un_bulletin_annule_ne_peut_pas_etre_paye(): void
    {
        $bulletin = $this->paie->genererBulletin($this->creerEmploye()->id, $this->periode);
        $this->paie->annuler($bulletin->id, 'Départ de l\'employé');

        $this->expectException(RuntimeException::class);

        $this->paie->payer($bulletin->id, $this->creerUtilisateur()->id);
    }

    public function test_le_retrait_a_la_billetterie_n_est_enregistre_qu_une_fois_apres_paiement(): void
    {
        $agent = $this->creerUtilisateur()->id;
        $bulletin = $this->paie->genererBulletin($this->creerEmploye()->id, $this->periode);

        try {
            $this->paie->marquerRetrait($bulletin->id, $agent);
            $this->fail('Un bulletin non payé ne peut pas être retiré.');
        } catch (RuntimeException) {
        }

        $this->paie->payer($bulletin->id, $agent);
        $retire = $this->paie->marquerRetrait($bulletin->id, $agent);
        $this->assertTrue((bool) $retire->retire_a_la_billetterie);

        $this->expectException(RuntimeException::class);
        $this->paie->marquerRetrait($bulletin->id, $agent);
    }

    public function test_un_salaire_vire_en_banque_n_attend_pas_de_retrait(): void
    {
        $employe = $this->creerEmploye(['mode_paiement' => 'banque', 'banque' => 'ECOBANK', 'iban' => 'TG53TG0090604310346500400070']);
        $bulletin = $this->paie->genererBulletin($employe->id, $this->periode);

        $paye = $this->paie->payer($bulletin->id, $this->creerUtilisateur()->id);

        $this->assertTrue((bool) $paye->retire_a_la_billetterie);
        $this->assertSame('ECOBANK', $paye->banque);
    }
}
