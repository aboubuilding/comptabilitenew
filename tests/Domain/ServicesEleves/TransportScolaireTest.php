<?php

namespace Tests\Domain\ServicesEleves;

use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\ServicesEleves\DTO\AbonnementBusData;
use App\Domain\ServicesEleves\DTO\ChauffeurData;
use App\Domain\ServicesEleves\DTO\VoitureData;
use App\Domain\ServicesEleves\Models\AbonnementBus;
use App\Domain\ServicesEleves\Models\AffectationVehicule;
use App\Domain\ServicesEleves\Services\AbonnementBusService;
use App\Domain\ServicesEleves\Services\TransportService;
use App\Domain\ServicesEleves\Types\SensTrajet;
use App\Domain\ServicesEleves\Types\StatutVehicule;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 4, § 4.1 Transport scolaire
 * et règle transversale « Souscription indépendante du paiement ».
 */
class TransportScolaireTest extends DomainTestCase
{
    private TransportService $transport;
    private AbonnementBusService $abonnements;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transport = app(TransportService::class);
        $this->abonnements = app(AbonnementBusService::class);
    }

    private function voitureData(string $plaque = 'TG-1234-AB'): VoitureData
    {
        return new VoitureData('Toyota', 'Coaster', $plaque, 30, 2020, null, null, null, null, 0, StatutVehicule::DISPONIBLE->value);
    }

    private function chauffeurData(?string $permis = 'P-0001'): ChauffeurData
    {
        return new ChauffeurData('AGBO', 'Kodjo', $permis, '2028-12-31', '90333333', null, null, 1);
    }

    private function abonnementData(int $inscriptionId, float $mensuel = 15000, int $mois = 9, ?int $voitureId = null, ?int $zoneId = null): AbonnementBusData
    {
        return new AbonnementBusData(
            $inscriptionId, '2026-09-15', $mensuel, $mois, $zoneId, $voitureId, null, SensTrajet::ALLER_RETOUR->value, 'Carrefour Bè'
        );
    }

    public function test_deux_vehicules_n_ont_pas_la_meme_plaque(): void
    {
        $this->transport->creerVoiture($this->voitureData());

        $this->expectException(RuntimeException::class);

        $this->transport->creerVoiture($this->voitureData());
    }

    public function test_deux_chauffeurs_n_ont_pas_le_meme_numero_de_permis(): void
    {
        $this->transport->creerChauffeur($this->chauffeurData());

        $this->expectException(RuntimeException::class);

        $this->transport->creerChauffeur($this->chauffeurData());
    }

    public function test_affecter_un_chauffeur_cloture_l_affectation_precedente_du_vehicule(): void
    {
        $voiture = $this->transport->creerVoiture($this->voitureData());
        $premier = $this->transport->creerChauffeur($this->chauffeurData('P-0001'));
        $second = $this->transport->creerChauffeur($this->chauffeurData('P-0002'));

        $this->transport->affecter($voiture->id, $premier->id, ['date_debut' => '2026-09-01']);
        $this->transport->affecter($voiture->id, $second->id, []);

        $enCours = AffectationVehicule::where('voiture_id', $voiture->id)->whereNull('date_fin')->get();
        $this->assertCount(1, $enCours);
        $this->assertSame($second->id, (int) $enCours->first()->chauffeur_id);
        $this->assertSame(2, AffectationVehicule::where('voiture_id', $voiture->id)->count());
    }

    public function test_un_vehicule_affecte_ne_peut_pas_etre_supprime(): void
    {
        $voiture = $this->transport->creerVoiture($this->voitureData());
        $chauffeur = $this->transport->creerChauffeur($this->chauffeurData());
        $this->transport->affecter($voiture->id, $chauffeur->id, []);

        $this->expectException(RuntimeException::class);

        $this->transport->supprimerVoiture($voiture->id);
    }

    public function test_les_entretiens_d_un_vehicule_sont_suivis_par_type_et_par_cout(): void
    {
        $voiture = $this->transport->creerVoiture($this->voitureData());
        $entretiens = app(\App\Domain\ServicesEleves\Repositories\EntretienVehiculeRepository::class);
        $base = ['voiture_id' => $voiture->id, 'kilometrage' => 45000, 'annee_id' => $this->annee->id];

        $entretiens->create($base + ['date_entretien' => '2026-09-10', 'type_entretien' => 'Vidange', 'cout' => 35000]);
        $entretiens->create($base + ['date_entretien' => '2026-10-02', 'type_entretien' => 'Pneus', 'cout' => 1200000]);
        $archive = $entretiens->create($base + ['date_entretien' => '2026-10-05', 'type_entretien' => 'Vidange', 'cout' => 40000]);
        $entretiens->delete($archive->id);

        $stats = $entretiens->stats($this->annee->id);
        $parType = $entretiens->parType($this->annee->id);

        $this->assertSame(2, $stats['nb']);
        $this->assertEqualsWithDelta(1235000, $stats['cout_total'], 0.01);
        $this->assertSame(['Pneus', 'Vidange'], $entretiens->typesDistincts());
        $this->assertSame('Pneus', $parType->first()->type_entretien, 'Le type le plus coûteux vient en premier.');
        $this->assertSame(1, $entretiens->listePaginee(['type_entretien' => 'Vidange'])->total());
    }

    public function test_une_ligne_de_bus_est_proposee_a_la_souscription(): void
    {
        $lignes = app(\App\Domain\ServicesEleves\Repositories\LigneRepository::class);
        $lignes->create(['libelle' => 'Ligne Bè - Centre', 'prix_minimal' => 10000, 'prix_plafond' => 20000]);
        $archivee = $lignes->create(['libelle' => 'Ancienne ligne']);
        $lignes->delete($archivee->id);

        $this->assertSame(['Ligne Bè - Centre'], $lignes->listePourSelect()->pluck('libelle')->all());
        $this->assertSame(1, $lignes->listePaginee()->total());
    }

    public function test_souscrire_un_abonnement_bus_fait_apparaitre_le_montant_du_sans_paiement(): void
    {
        $inscription = $this->creerInscription();

        $abonnement = $this->abonnements->souscrire($this->abonnementData($inscription->id, 15000, 9));

        $this->assertTrue($abonnement->isActif());
        $this->assertSame($this->annee->id, (int) $abonnement->fresh()->annee_id);
        $this->assertEqualsWithDelta(135000, (float) $abonnement->montant_total_du, 0.01);
        $this->assertSame($this->annee->date_fin->toDateString(), \Carbon\Carbon::parse($abonnement->date_fin)->toDateString());
        $this->assertEqualsWithDelta(
            135000,
            app(EngagementService::class)->montantDu($inscription->id, NatureCreance::BUS->value),
            0.01
        );
        $this->assertSame(0, DB::table('paiements')->count());
    }

    public function test_un_eleve_n_a_qu_un_abonnement_bus_actif(): void
    {
        $inscription = $this->creerInscription();
        $this->abonnements->souscrire($this->abonnementData($inscription->id));

        $this->expectException(RuntimeException::class);

        $this->abonnements->souscrire($this->abonnementData($inscription->id));
    }

    public function test_un_abonnement_bus_a_un_montant_et_une_duree_positifs(): void
    {
        $inscription = $this->creerInscription();

        try {
            $this->abonnements->souscrire($this->abonnementData($inscription->id, 0, 9));
            $this->fail('Un montant mensuel nul aurait dû être refusé.');
        } catch (RuntimeException) {
        }

        $this->expectException(RuntimeException::class);
        $this->abonnements->souscrire($this->abonnementData($inscription->id, 15000, 0));
    }

    public function test_abandonner_un_abonnement_bus_eteint_l_engagement(): void
    {
        $inscription = $this->creerInscription();
        $abonnement = $this->abonnements->souscrire($this->abonnementData($inscription->id));

        $abandonne = $this->abonnements->abandonner($abonnement->id, 'Déménagement', $this->creerUtilisateur()->id);

        $this->assertFalse($abandonne->isActif());
        $this->assertSame('Déménagement', $abandonne->motif_abandon);
        $this->assertNotNull($abandonne->date_abandon);
        $this->assertEqualsWithDelta(0, app(EngagementService::class)->montantDu($inscription->id, NatureCreance::BUS->value), 0.01);
    }

    public function test_apres_abandon_un_nouvel_abonnement_peut_etre_souscrit(): void
    {
        $inscription = $this->creerInscription();
        $abonnement = $this->abonnements->souscrire($this->abonnementData($inscription->id));
        $this->abonnements->abandonner($abonnement->id, 'Changement de zone', $this->creerUtilisateur()->id);

        $nouveau = $this->abonnements->souscrire($this->abonnementData($inscription->id, 20000, 6));

        $this->assertTrue($nouveau->isActif());
        $this->assertSame(2, AbonnementBus::where('inscription_id', $inscription->id)->count());
    }

    public function test_un_abonnement_est_assigne_a_un_vehicule_une_zone_et_un_sens(): void
    {
        $voiture = $this->transport->creerVoiture($this->voitureData());
        $zoneId = DB::table('zones')->insertGetId(['libelle' => 'Bè', 'etat' => 1]);
        $abonnement = $this->abonnements->souscrire($this->abonnementData($this->creerInscription()->id));

        $assigne = $this->abonnements->assigner($abonnement->id, [
            'voiture_id' => $voiture->id, 'zone_id' => $zoneId, 'sens' => SensTrajet::ALLER->value,
        ]);

        $this->assertSame($voiture->id, (int) $assigne->voiture_id);
        $this->assertSame($zoneId, (int) $assigne->zone_id);
        $this->assertSame(SensTrajet::ALLER->value, $assigne->getRawOriginal('sens'));
        $this->assertSame('Carrefour Bè', $assigne->point_arret);
    }
}
