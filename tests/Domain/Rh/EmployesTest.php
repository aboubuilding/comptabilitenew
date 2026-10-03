<?php

namespace Tests\Domain\Rh;

use App\Domain\Rh\DTO\ProfessionData;
use App\Domain\Rh\Models\Employe;
use App\Domain\Rh\Services\EmployeService;
use App\Domain\Rh\Services\ProfessionService;
use App\Repositories\BaseRepository;
use RuntimeException;

/**
 * Cahier des charges — Menu 5, § 5.1 Employés.
 */
class EmployesTest extends RhTestCase
{
    public function test_une_fiche_employe_est_creee_active(): void
    {
        $employe = $this->creerEmploye()->fresh();

        $this->assertSame('AMOUZOU Kossi', $employe->nom_prenom);
        $this->assertEqualsWithDelta(150000, (float) $employe->salaire, 0.01);
        $this->assertSame(BaseRepository::ACTIF, (int) $employe->etat);
    }

    public function test_un_salaire_superieur_au_million_est_accepte(): void
    {
        $employe = $this->creerEmploye(['salaire' => 1250000])->fresh();

        $this->assertEqualsWithDelta(1250000, (float) $employe->salaire, 0.01);
    }

    public function test_un_employe_a_un_nom(): void
    {
        $this->expectException(RuntimeException::class);

        $this->creerEmploye(['nom_prenom' => '']);
    }

    public function test_un_salaire_ne_peut_pas_etre_negatif(): void
    {
        $this->expectException(RuntimeException::class);

        $this->creerEmploye(['salaire' => -1]);
    }

    public function test_deux_employes_n_ont_pas_le_meme_numero_cnss(): void
    {
        $this->creerEmploye(['numero_cnss' => 'CNSS-001']);

        $this->expectException(RuntimeException::class);

        $this->creerEmploye(['nom_prenom' => 'KPODAR Afi', 'numero_cnss' => 'CNSS-001']);
    }

    public function test_modifier_un_employe_conserve_son_numero_cnss(): void
    {
        $employe = $this->creerEmploye(['numero_cnss' => 'CNSS-001']);

        $modifie = app(EmployeService::class)->modifier(
            $employe->id, $this->employeData(['numero_cnss' => 'CNSS-001', 'salaire' => 180000])
        );

        $this->assertEqualsWithDelta(180000, (float) $modifie->salaire, 0.01);
    }

    public function test_supprimer_un_employe_l_archive(): void
    {
        $employe = $this->creerEmploye();

        app(EmployeService::class)->supprimer($employe->id);

        $this->assertSame(BaseRepository::INACTIF, (int) Employe::find($employe->id)->etat);
    }

    public function test_une_profession_porte_un_salaire_de_base(): void
    {
        $profession = app(ProfessionService::class)->creer(new ProfessionData('Enseignant du primaire', 120000));

        $this->assertEqualsWithDelta(120000, (float) $profession->fresh()->salaire_base, 0.01);
    }

    public function test_deux_professions_n_ont_pas_le_meme_libelle(): void
    {
        $service = app(ProfessionService::class);
        $service->creer(new ProfessionData('Surveillant', 80000));

        $this->expectException(RuntimeException::class);

        $service->creer(new ProfessionData('Surveillant', 90000));
    }

    public function test_une_profession_exercee_ne_peut_pas_etre_supprimee(): void
    {
        $profession = app(ProfessionService::class)->creer(new ProfessionData('Comptable', 200000));
        $this->creerEmploye(['profession_id' => $profession->id]);

        $this->expectException(RuntimeException::class);

        app(ProfessionService::class)->supprimer($profession->id);
    }
}
