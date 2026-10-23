<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\DTO\FraisEcoleData;
use App\Domain\Finances\DTO\PlanEcheancierData;
use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Services\FraisEcoleService;
use App\Domain\Finances\Services\PlanEcheancierService;
use App\Domain\Finances\Services\SouscriptionService;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeForfait;
use App\Domain\Finances\Types\TypePaiement;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.1 Frais scolaires & Échéanciers
 * et règle transversale « Souscription indépendante du paiement ».
 */
class FraisEcheanciersTest extends DomainTestCase
{
    private function fraisData(array $donnees = []): FraisEcoleData
    {
        return FraisEcoleData::fromRequest($donnees + [
            'libelle'           => 'Scolarité CP',
            'montant'           => 469000,
            'type_paiement'     => TypeDetail::FRAIS_SCOLAIRE->value,
            'modalite_paiement' => TypePaiement::COMPTANT->value,
            'type_forfait'      => TypeForfait::OBLIGATOIRE->value,
        ]);
    }

    private function plan(array $lignes): \App\Domain\Finances\Models\PlanEcheancier
    {
        return app(PlanEcheancierService::class)->creer(new PlanEcheancierData('Trois tranches', null, $lignes));
    }

    public function test_les_natures_de_frais_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, TypeDetail::FRAIS_INSCRIPTION->value);
        $this->assertSame(2, TypeDetail::FRAIS_SCOLAIRE->value);
        $this->assertSame(4, TypeDetail::PRODUIT->value);
        $this->assertSame(7, TypeDetail::BUS->value);
        $this->assertSame(8, TypeDetail::CANTINE->value);
        $this->assertSame(10, TypeDetail::FRAIS_ASSURANCE->value);
        $this->assertSame(12, TypeDetail::FRAIS_EXAMEN->value);
    }

    public function test_un_frais_porte_sa_nature_et_sa_modalite_de_paiement_separement(): void
    {
        $frais = app(FraisEcoleService::class)->creer($this->fraisData());

        $frais->refresh();
        $this->assertSame(TypeDetail::FRAIS_SCOLAIRE, $frais->type_paiement);
        $this->assertSame(TypePaiement::COMPTANT, $frais->modalite_paiement);
        $this->assertSame($this->annee->id, (int) $frais->annee_id);
    }

    public function test_un_frais_doit_avoir_un_montant_strictement_positif(): void
    {
        $this->expectException(RuntimeException::class);

        app(FraisEcoleService::class)->creer($this->fraisData(['montant' => 0]));
    }

    public function test_un_frais_echelonne_exige_un_plan_d_echeancier(): void
    {
        $this->expectException(RuntimeException::class);

        app(FraisEcoleService::class)->creer($this->fraisData([
            'modalite_paiement' => TypePaiement::ECHELONNE->value,
        ]));
    }

    public function test_un_frais_echelonne_est_rattache_a_son_plan(): void
    {
        $plan = $this->plan([
            ['libelle' => 'Tranche 1', 'pourcentage' => 50],
            ['libelle' => 'Tranche 2', 'pourcentage' => 50],
        ]);

        $frais = app(FraisEcoleService::class)->creer($this->fraisData([
            'modalite_paiement'  => TypePaiement::ECHELONNE->value,
            'plan_echeancier_id' => $plan->id,
        ]));

        $this->assertSame($plan->id, (int) $frais->fresh()->plan_echeancier_id);
    }

    public function test_un_plan_d_echeancier_est_cree_avec_ses_lignes_ordonnees(): void
    {
        $plan = $this->plan([
            ['libelle' => 'Rentrée', 'pourcentage' => 50],
            ['libelle' => 'Décembre', 'pourcentage' => 30],
            ['libelle' => 'Février', 'pourcentage' => 20],
        ]);

        $this->assertCount(3, $plan->lignes);
        $this->assertSame([1, 2, 3], $plan->lignes->sortBy('ordre')->pluck('ordre')->map(fn ($o) => (int) $o)->values()->all());
    }

    public function test_les_pourcentages_d_un_plan_doivent_totaliser_100(): void
    {
        $this->expectException(RuntimeException::class);

        $this->plan([
            ['libelle' => 'Rentrée', 'pourcentage' => 50],
            ['libelle' => 'Décembre', 'pourcentage' => 30],
        ]);
    }

    public function test_un_plan_ne_melange_pas_pourcentages_et_montants(): void
    {
        $this->expectException(RuntimeException::class);

        $this->plan([
            ['libelle' => 'Rentrée', 'pourcentage' => 100],
            ['libelle' => 'Décembre', 'montant' => 100000],
        ]);
    }

    public function test_un_plan_utilise_par_un_frais_ne_peut_pas_etre_supprime(): void
    {
        $plan = $this->plan([['libelle' => 'Unique', 'pourcentage' => 100]]);
        app(FraisEcoleService::class)->creer($this->fraisData([
            'modalite_paiement'  => TypePaiement::ECHELONNE->value,
            'plan_echeancier_id' => $plan->id,
        ]));

        $this->expectException(RuntimeException::class);

        app(PlanEcheancierService::class)->supprimer($plan->id);
    }

    public function test_souscrire_un_service_cree_l_engagement_sans_aucun_paiement(): void
    {
        $frais = $this->creerFrais(469000, TypeDetail::CANTINE);
        $inscription = $this->creerInscription();

        $souscription = app(SouscriptionService::class)->creer(
            new SouscriptionData($frais->id, $inscription->id, 10, '2026-09-01', null)
        );

        $this->assertEqualsWithDelta(469000, (float) $souscription->montant_annuel_prevu, 0.01);
        $this->assertEqualsWithDelta(10, (float) $souscription->taux_remise, 0.01);
        $this->assertSame(TypeDetail::CANTINE->value, (int) $souscription->fresh()->getRawOriginal('type_paiement'));
        $this->assertDatabaseCount('paiements', 0);
    }

    public function test_la_scolarite_ne_se_souscrit_pas(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("portées par l'inscription");

        app(SouscriptionService::class)->creer(
            new SouscriptionData($this->creerFrais(469000)->id, $this->creerInscription()->id, 0, '2026-09-01', null)
        );
    }

    public function test_un_eleve_ne_souscrit_pas_deux_fois_le_meme_frais(): void
    {
        $frais = $this->creerFrais(108000, TypeDetail::CANTINE);
        $inscription = $this->creerInscription();
        $service = app(SouscriptionService::class);
        $service->creer(new SouscriptionData($frais->id, $inscription->id, 0, '2026-09-01', null));

        $this->expectException(RuntimeException::class);

        $service->creer(new SouscriptionData($frais->id, $inscription->id, 0, '2026-09-01', null));
    }

    public function test_une_remise_reste_comprise_entre_0_et_100_pour_cent(): void
    {
        $this->expectException(RuntimeException::class);

        app(SouscriptionService::class)->creer(
            new SouscriptionData($this->creerFrais(108000, TypeDetail::CANTINE)->id, $this->creerInscription()->id, 120, '2026-09-01', null)
        );
    }
}
