<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\DTO\CentreAnalytiqueData;
use App\Domain\Finances\DTO\DepenseData;
use App\Domain\Finances\DTO\ImputationData;
use App\Domain\Finances\Models\CentreAnalytique;
use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Models\ImputationAnalytique;
use App\Domain\Finances\Services\CentreAnalytiqueService;
use App\Domain\Finances\Services\DepenseService;
use App\Domain\Finances\Services\RegleVentilationService;
use App\Domain\Finances\Services\VentilationAnalytiqueService;
use App\Domain\Finances\Types\SensAnalytique;
use App\Repositories\BaseRepository;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.7 Comptabilité analytique
 * et règle transversale « Ventilation analytique systématique ».
 */
class ComptabiliteAnalytiqueTest extends DomainTestCase
{
    private VentilationAnalytiqueService $service;
    private CentreAnalytique $cantine;
    private CentreAnalytique $transport;
    private Depense $depense;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VentilationAnalytiqueService::class);
        $this->cantine = $this->centre('CANT', 'Cantine');
        $this->transport = $this->centre('TRAN', 'Transport');
        $this->depense = app(DepenseService::class)->saisir(
            new DepenseData('Gaz et carburant', 'Station Total', null, '2026-09-20', 100000, $this->creerUtilisateur()->id)
        );
    }

    private function centre(string $code, string $libelle): CentreAnalytique
    {
        return app(CentreAnalytiqueService::class)->creer(new CentreAnalytiqueData($code, $libelle, null, 'service', null));
    }

    private function imputationsActives()
    {
        return ImputationAnalytique::where('imputable_type', Depense::class)
            ->where('imputable_id', $this->depense->id)
            ->where('etat', BaseRepository::ACTIF)
            ->get();
    }

    public function test_un_centre_analytique_est_cree_avec_son_code(): void
    {
        $this->assertSame('CANT', $this->cantine->code);
        $this->assertSame(BaseRepository::ACTIF, (int) $this->cantine->fresh()->etat);
    }

    public function test_une_ventilation_en_pourcentage_calcule_le_montant_de_chaque_centre(): void
    {
        $this->service->ventiler($this->depense, [
            new ImputationData($this->cantine->id, null, 60, SensAnalytique::DEPENSE->value),
            new ImputationData($this->transport->id, null, 40, SensAnalytique::DEPENSE->value),
        ], SensAnalytique::DEPENSE->value, 100000);

        $imputations = $this->imputationsActives();
        $this->assertCount(2, $imputations);
        $this->assertEqualsWithDelta(60000, (float) $imputations->firstWhere('centre_analytique_id', $this->cantine->id)->montant, 0.01);
        $this->assertEqualsWithDelta(40000, (float) $imputations->firstWhere('centre_analytique_id', $this->transport->id)->montant, 0.01);
        $this->assertEqualsWithDelta(100000, (float) $imputations->sum('montant'), 0.01);
    }

    public function test_les_quotes_parts_doivent_totaliser_100_pour_cent(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->ventiler($this->depense, [
            new ImputationData($this->cantine->id, null, 60, SensAnalytique::DEPENSE->value),
            new ImputationData($this->transport->id, null, 30, SensAnalytique::DEPENSE->value),
        ], SensAnalytique::DEPENSE->value, 100000);
    }

    public function test_les_montants_imputes_doivent_egaler_le_montant_source(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->ventiler($this->depense, [
            new ImputationData($this->cantine->id, 60000, null, SensAnalytique::DEPENSE->value),
            new ImputationData($this->transport->id, 30000, null, SensAnalytique::DEPENSE->value),
        ], SensAnalytique::DEPENSE->value, 100000);
    }

    public function test_une_ventilation_ne_melange_pas_montants_et_pourcentages(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->ventiler($this->depense, [
            new ImputationData($this->cantine->id, 60000, null, SensAnalytique::DEPENSE->value),
            new ImputationData($this->transport->id, null, 40, SensAnalytique::DEPENSE->value),
        ], SensAnalytique::DEPENSE->value, 100000);
    }

    public function test_reventiler_remplace_la_ventilation_precedente(): void
    {
        $this->service->ventiler($this->depense, [
            new ImputationData($this->cantine->id, null, 100, SensAnalytique::DEPENSE->value),
        ], SensAnalytique::DEPENSE->value, 100000);

        $this->service->ventiler($this->depense, [
            new ImputationData($this->transport->id, null, 100, SensAnalytique::DEPENSE->value),
        ], SensAnalytique::DEPENSE->value, 100000);

        $actives = $this->imputationsActives();
        $this->assertCount(1, $actives);
        $this->assertSame($this->transport->id, (int) $actives->first()->centre_analytique_id);
    }

    public function test_les_regles_automatiques_ventilent_une_depense_selon_sa_nature(): void
    {
        $regles = app(RegleVentilationService::class);
        $regles->creer(['nature' => 'carburant', 'sens' => SensAnalytique::DEPENSE->value, 'centre_analytique_id' => $this->transport->id, 'pourcentage' => 75, 'priorite' => 1]);
        $regles->creer(['nature' => 'carburant', 'sens' => SensAnalytique::DEPENSE->value, 'centre_analytique_id' => $this->cantine->id, 'pourcentage' => 25, 'priorite' => 2]);

        $nombre = $this->service->appliquerReglesAuto($this->depense, 'carburant', SensAnalytique::DEPENSE->value, 100000);

        $this->assertSame(2, $nombre);
        $this->assertEqualsWithDelta(75000, (float) $this->imputationsActives()->firstWhere('centre_analytique_id', $this->transport->id)->montant, 0.01);
    }

    public function test_sans_regle_pour_une_nature_aucune_ventilation_n_est_appliquee(): void
    {
        $nombre = $this->service->appliquerReglesAuto($this->depense, 'inconnue', SensAnalytique::DEPENSE->value, 100000);

        $this->assertSame(0, $nombre);
        $this->assertCount(0, $this->imputationsActives());
    }
}
