<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\DTO\PrevisionData;
use App\Domain\Finances\Models\Prevision;
use App\Domain\Finances\Services\PrevisionService;
use App\Domain\Finances\Types\TypePrevision;
use App\Repositories\BaseRepository;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.9 Prévisions budgétaires.
 */
class PrevisionsBudgetairesTest extends DomainTestCase
{
    private function data(float $montant = 5000000, string $type = 'recette'): PrevisionData
    {
        return new PrevisionData(
            'Écolage premier trimestre', $type, $montant, null, '2026-09-01', '2026-12-31', $this->creerUtilisateur()->id
        );
    }

    public function test_une_prevision_est_soit_une_recette_soit_une_depense(): void
    {
        $this->assertSame(['recette', 'depense'], array_keys(TypePrevision::options()));

        $prevision = app(PrevisionService::class)->creer($this->data(type: 'depense'))->fresh();

        $this->assertSame(TypePrevision::DEPENSE, $prevision->type);
        $this->assertSame($this->annee->id, (int) $prevision->annee_id);
    }

    public function test_une_prevision_a_un_montant_strictement_positif(): void
    {
        $this->expectException(RuntimeException::class);

        app(PrevisionService::class)->creer($this->data(0));
    }

    public function test_l_ecart_compare_le_previsionnel_au_realise(): void
    {
        $prevision = app(PrevisionService::class)->creer($this->data(5000000));
        Prevision::whereKey($prevision->id)->update(['montant_realise' => 4200000]);

        $this->assertEqualsWithDelta(800000, $prevision->fresh()->ecart(), 0.01);
    }

    public function test_sans_realise_l_ecart_est_le_montant_previsionnel(): void
    {
        $prevision = app(PrevisionService::class)->creer($this->data(5000000));

        $this->assertEqualsWithDelta(5000000, $prevision->fresh()->ecart(), 0.01);
    }

    public function test_modifier_une_prevision_met_a_jour_son_montant(): void
    {
        $service = app(PrevisionService::class);
        $prevision = $service->creer($this->data(5000000));

        $modifiee = $service->modifier($prevision->id, $this->data(6000000));

        $this->assertEqualsWithDelta(6000000, (float) $modifiee->montant_previsionnel, 0.01);
    }

    public function test_supprimer_une_prevision_l_archive(): void
    {
        $service = app(PrevisionService::class);
        $prevision = $service->creer($this->data());

        $service->supprimer($prevision->id);

        $this->assertSame(BaseRepository::INACTIF, (int) Prevision::find($prevision->id)->etat);
    }
}
