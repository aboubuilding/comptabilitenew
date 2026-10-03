<?php

namespace Tests\Domain\Rh;

use App\Domain\Rh\DTO\AssureDTO;
use App\Domain\Rh\DTO\AsuranceDTO;
use App\Domain\Rh\Models\Assure;
use App\Domain\Rh\Models\Asurance;
use App\Domain\Rh\Services\AssureService;
use App\Domain\Rh\Services\AsuranceService;
use App\Repositories\BaseRepository;
use RuntimeException;

/**
 * Cahier des charges — Menu 5, § 5.3 Assurances du personnel.
 */
class AssurancesPersonnelTest extends RhTestCase
{
    private AssureService $assures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assures = app(AssureService::class);
    }

    private function police(string $numero = 'POL-2026-001'): Asurance
    {
        return app(AsuranceService::class)->creer(new AsuranceDTO('NSIA Assurances', $numero, 1500000));
    }

    private function souscrire(int $employeId, int $policeId, float $prelevement = 5000): Assure
    {
        return $this->assures->souscrire(new AssureDTO($employeId, $policeId, '2026-09-01', $prelevement));
    }

    public function test_une_police_d_assurance_est_enregistree_avec_son_montant_annuel(): void
    {
        $police = $this->police()->fresh();

        $this->assertSame('NSIA Assurances', $police->nom_assureur);
        $this->assertEqualsWithDelta(1500000, (float) $police->montant_annuel, 0.01);
    }

    public function test_souscrire_une_assurance_pour_un_employe(): void
    {
        $employe = $this->creerEmploye();
        $police = $this->police();

        $assure = $this->souscrire($employe->id, $police->id)->fresh();

        $this->assertSame($employe->id, (int) $assure->employe_id);
        $this->assertSame($police->id, (int) $assure->assurance_id);
        $this->assertEqualsWithDelta(5000, (float) $assure->prelevement_mensuel, 0.01);
        $this->assertSame('actif', $assure->statut);
    }

    public function test_un_employe_n_est_pas_assure_deux_fois_sur_la_meme_police(): void
    {
        $employe = $this->creerEmploye();
        $police = $this->police();
        $this->souscrire($employe->id, $police->id);

        $this->expectException(RuntimeException::class);

        $this->souscrire($employe->id, $police->id);
    }

    public function test_un_employe_peut_avoir_plusieurs_polices(): void
    {
        $employe = $this->creerEmploye();

        $this->souscrire($employe->id, $this->police('POL-A')->id, 5000);
        $this->souscrire($employe->id, $this->police('POL-B')->id, 3000);

        $this->assertSame(2, Assure::where('employe_id', $employe->id)->count());
    }

    public function test_le_suivi_totalise_les_prelevements_mensuels(): void
    {
        $police = $this->police();
        $this->souscrire($this->creerEmploye(['nom_prenom' => 'Employé A'])->id, $police->id, 5000);
        $this->souscrire($this->creerEmploye(['nom_prenom' => 'Employé B'])->id, $police->id, 7500);

        $stats = $this->assures->statistiques();

        $this->assertSame(2, $stats['total_assures']);
        $this->assertSame(2, $stats['total_actifs']);
        $this->assertEqualsWithDelta(12500, (float) $stats['total_prelevements'], 0.01);
    }

    public function test_desactiver_une_souscription_l_archive_et_la_sort_des_prelevements(): void
    {
        $police = $this->police();
        $assure = $this->souscrire($this->creerEmploye()->id, $police->id, 5000);

        $this->assures->desactiver($assure);

        $this->assertSame(BaseRepository::INACTIF, (int) Assure::find($assure->id)->etat);
        $this->assertEqualsWithDelta(0, (float) $this->assures->statistiques()['total_prelevements'], 0.01);
    }

    public function test_modifier_le_prelevement_d_une_souscription(): void
    {
        $employe = $this->creerEmploye();
        $police = $this->police();
        $assure = $this->souscrire($employe->id, $police->id, 5000);

        $modifie = $this->assures->mettreAJour($assure, new AssureDTO($employe->id, $police->id, '2026-09-01', 6500));

        $this->assertEqualsWithDelta(6500, (float) $modifie->prelevement_mensuel, 0.01);
    }
}
