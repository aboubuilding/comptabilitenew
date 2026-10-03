<?php

namespace Tests\Domain\Finances;

use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Models\DossierRecouvrement;
use App\Domain\Finances\Models\SituationRecouvrement;
use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Services\RecouvrementService;
use App\Domain\Finances\Services\RelanceService;
use App\Domain\Finances\Services\SouscriptionService;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypeRelance;
use App\Domain\Scolarite\Models\Inscription;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.8 Recouvrement
 * et règles transversales « Souscription indépendante du paiement » et
 * « Détection et suivi du recouvrement ».
 */
class RecouvrementTest extends DomainTestCase
{
    private Inscription $inscription;

    protected function setUp(): void
    {
        parent::setUp();
        // Inscrit depuis 60 jours : au-delà du seuil d'ouverture d'un dossier
        $this->inscription = $this->creerInscription(null, ['date_inscription' => now()->subDays(60)->toDateString()]);
    }

    private function souscrireScolarite(float $montant = 469000, float $remise = 0): void
    {
        app(SouscriptionService::class)->creer(new SouscriptionData(
            $this->creerFrais($montant)->id, $this->inscription->id, $remise, now()->subDays(60)->toDateString(), null
        ));
    }

    private function encaisser(float $montant, TypeDetail $type = TypeDetail::FRAIS_SCOLAIRE, bool $encaisse = true): void
    {
        DB::table('details')->insert([
            'inscription_id'    => $this->inscription->id,
            'annee_id'          => $this->annee->id,
            'libelle'           => $type->label(),
            'montant'           => $montant,
            'type_paiement'     => $type->value,
            'statut_paiement'   => ($encaisse ? PaiementStatut::ENCAISSE : PaiementStatut::EN_ATTENTE)->value,
            'date_encaissement' => $encaisse ? now()->toDateString() : null,
            'etat'              => 1,
        ]);
    }

    private function dossier(): DossierRecouvrement
    {
        return DossierRecouvrement::create([
            'inscription_id'     => $this->inscription->id,
            'nature'             => NatureCreance::SCOLARITE->value,
            'montant_initial_du' => 200000,
            'montant_recouvre'   => 0,
            'statut'             => DossierStatut::EN_COURS->value,
            'date_ouverture'     => now()->toDateString(),
            'annee_id'           => $this->annee->id,
            'etat'               => 1,
        ]);
    }

    public function test_le_du_apparait_des_la_souscription_sans_aucun_paiement(): void
    {
        $this->souscrireScolarite(469000);

        $engagements = app(EngagementService::class);

        $this->assertEqualsWithDelta(469000, $engagements->montantDu($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
        $this->assertEqualsWithDelta(0, $engagements->montantPaye($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
    }

    public function test_la_remise_accordee_reduit_le_montant_du(): void
    {
        $this->souscrireScolarite(400000, 25);

        $du = app(EngagementService::class)->montantDu($this->inscription->id, NatureCreance::SCOLARITE->value);

        $this->assertEqualsWithDelta(300000, $du, 0.01);
    }

    public function test_seuls_les_encaissements_effectifs_reduisent_le_reste_a_payer(): void
    {
        $this->souscrireScolarite(469000);
        $this->encaisser(225000);
        $this->encaisser(100000, encaisse: false); // saisi par le comptable, pas encore en caisse

        $engagements = app(EngagementService::class);

        $this->assertEqualsWithDelta(225000, $engagements->montantPaye($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
        $this->assertEqualsWithDelta(244000, $engagements->resteDu($this->inscription->id, NatureCreance::SCOLARITE->value), 0.01);
    }

    public function test_un_encaissement_de_cantine_ne_solde_pas_l_ecolage(): void
    {
        $this->souscrireScolarite(469000);
        $this->encaisser(45000, TypeDetail::CANTINE);

        $paye = app(EngagementService::class)->montantPaye($this->inscription->id, NatureCreance::SCOLARITE->value);

        $this->assertEqualsWithDelta(0, $paye, 0.01);
    }

    public function test_le_recalcul_photographie_la_situation_par_nature(): void
    {
        $this->souscrireScolarite(469000);
        $this->encaisser(225000);

        $situation = app(RecouvrementService::class)->recalculerUne($this->inscription->id, NatureCreance::SCOLARITE->value);

        $this->assertNotNull($situation);
        $this->assertEqualsWithDelta(469000, (float) $situation->montant_du, 0.01);
        $this->assertEqualsWithDelta(225000, (float) $situation->montant_paye, 0.01);
        $this->assertEqualsWithDelta(244000, (float) $situation->retard, 0.01);
        $this->assertSame(60, (int) $situation->jours_retard);
    }

    public function test_un_eleve_sans_engagement_n_a_pas_de_situation(): void
    {
        $situation = app(RecouvrementService::class)->recalculerUne($this->inscription->id, NatureCreance::CANTINE->value);

        $this->assertNull($situation);
    }

    public function test_recalculer_deux_fois_le_meme_jour_ecrase_la_photo(): void
    {
        $this->souscrireScolarite(469000);
        $service = app(RecouvrementService::class);

        $service->recalculerTout();
        $this->encaisser(225000);
        $service->recalculerTout();

        $situations = SituationRecouvrement::where('inscription_id', $this->inscription->id)->get();
        $this->assertCount(1, $situations);
        $this->assertEqualsWithDelta(244000, (float) $situations->first()->retard, 0.01);
    }

    public function test_un_dossier_s_ouvre_automatiquement_quand_le_retard_depasse_les_seuils(): void
    {
        $this->souscrireScolarite(469000);

        $stats = app(RecouvrementService::class)->recalculerTout();

        $this->assertSame(1, $stats['dossiers_ouverts']);
        $dossier = DossierRecouvrement::where('inscription_id', $this->inscription->id)->sole();
        $this->assertSame(DossierStatut::EN_COURS, $dossier->statut);
        $this->assertSame(NatureCreance::SCOLARITE->value, $dossier->getRawOriginal('nature'));
        $this->assertEqualsWithDelta(469000, (float) $dossier->montant_initial_du, 0.01);
    }

    public function test_aucun_dossier_tant_que_le_retard_est_recent(): void
    {
        DB::table('inscriptions')->where('id', $this->inscription->id)->update(['date_inscription' => now()->subDays(5)->toDateString()]);
        $this->souscrireScolarite(469000);

        $stats = app(RecouvrementService::class)->recalculerTout();

        $this->assertSame(0, $stats['dossiers_ouverts']);
        $this->assertSame(0, DossierRecouvrement::count());
    }

    public function test_un_eleve_a_jour_n_ouvre_pas_de_dossier(): void
    {
        $this->souscrireScolarite(469000);
        $this->encaisser(469000);

        $stats = app(RecouvrementService::class)->recalculerTout();

        $this->assertSame(0, $stats['dossiers_ouverts']);
    }

    public function test_chaque_relance_est_historisee_avec_son_canal_et_son_resultat(): void
    {
        $dossier = $this->dossier();

        $relance = app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::APPEL->value, now()->toDateString(), 'Parent injoignable',
            ResultatRelance::SANS_REPONSE->value, now()->addWeek()->toDateString(), $this->creerUtilisateur()->id
        );

        $this->assertSame($dossier->id, (int) $relance->dossier_recouvrement_id);
        $this->assertEqualsWithDelta(200000, (float) $relance->montant_du_moment, 0.01);
        $this->assertSame(1, $dossier->relances()->count());
        $this->assertSame(DossierStatut::EN_COURS, $dossier->fresh()->statut);
    }

    public function test_une_promesse_de_paiement_place_le_dossier_sous_echeancier(): void
    {
        $dossier = $this->dossier();

        app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::APPEL->value, now()->toDateString(), null,
            ResultatRelance::PROMESSE_PAIEMENT->value, null, null
        );

        $this->assertSame(DossierStatut::ECHEANCIER, $dossier->fresh()->statut);
    }

    public function test_un_paiement_total_solde_le_dossier(): void
    {
        $dossier = $this->dossier();

        app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::EMAIL->value, now()->toDateString(), null,
            ResultatRelance::PAIEMENT_TOTAL->value, null, null
        );

        $dossier->refresh();
        $this->assertSame(DossierStatut::SOLDE, $dossier->statut);
        $this->assertTrue($dossier->isClos());
        $this->assertNotNull($dossier->date_cloture);
        $this->assertEqualsWithDelta(0, $dossier->montant_restant, 0.01);
    }

    public function test_un_dossier_clos_ne_recoit_plus_de_relance(): void
    {
        $dossier = $this->dossier();
        app(RelanceService::class)->changerStatut($dossier->id, DossierStatut::IRRECOUVRABLE->value, 'Famille partie');

        $this->expectException(RuntimeException::class);

        app(RelanceService::class)->enregistrer(
            $dossier->id, TypeRelance::SMS->value, now()->toDateString(), null, null, null, null
        );
    }
}
