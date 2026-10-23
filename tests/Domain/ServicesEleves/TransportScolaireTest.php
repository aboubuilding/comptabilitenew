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

    private function abonnementData(int $inscriptionId, float $mensuel = 15000, int $mois = 9, ?int $zoneId = null): AbonnementBusData
    {
        return new AbonnementBusData($inscriptionId, '2026-09-15', $mensuel, $mois, $zoneId, SensTrajet::ALLER_RETOUR->value);
    }

    /** Une ligne (chauffeur + véhicule) et une de ses zones, au tarif indicatif donné. */
    private function ligneEtZone(float $tarif = 25000, string $plaque = 'TG-1234-AB'): array
    {
        $voiture = $this->transport->creerVoiture($this->voitureData($plaque));
        $ligneId = DB::table('lignes')->insertGetId([
            'libelle' => 'Ligne Bè', 'voiture_id' => $voiture->id, 'date_debut' => '2026-09-15', 'date_fin' => '2027-07-15',
            'annee_id' => $this->annee->id, 'etat' => 1,
        ]);
        $zoneId = DB::table('zones')->insertGetId([
            'libelle' => 'Bè Kpota', 'tarif_base' => $tarif, 'ligne_id' => $ligneId, 'annee_id' => $this->annee->id, 'etat' => 1,
        ]);

        return [$ligneId, $zoneId, $voiture->id];
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

    public function test_une_ligne_est_un_parcours_sur_une_periode(): void
    {
        $lignes = app(\App\Domain\ServicesEleves\Repositories\LigneRepository::class);
        $lignes->create(['libelle' => 'Ligne Bè - Centre', 'date_debut' => '2026-09-15', 'date_fin' => '2027-07-15']);
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

    public function test_changer_de_zone_change_la_ligne_et_le_vehicule_de_l_abonnement(): void
    {
        [$ligneId, $zoneId, $voitureId] = $this->ligneEtZone();
        $abonnement = $this->abonnements->souscrire($this->abonnementData($this->creerInscription()->id));
        $this->assertNull($abonnement->zone_id);

        $assigne = $this->abonnements->assigner($abonnement->id, ['zone_id' => $zoneId, 'sens' => SensTrajet::ALLER->value]);

        $this->assertSame($zoneId, (int) $assigne->zone_id);
        $this->assertSame($ligneId, (int) $assigne->ligne_id);
        $this->assertSame($voitureId, (int) $assigne->voiture_id);
        $this->assertSame(SensTrajet::ALLER->value, $assigne->getRawOriginal('sens'));
        $this->assertEqualsWithDelta(135000, $assigne->montant_total_du, 0.01, 'Le montant ne change pas.');
    }

    public function test_on_souscrit_avec_la_zone_seule_et_son_tarif_n_est_qu_indicatif(): void
    {
        [$ligneId, $zoneId, $voitureId] = $this->ligneEtZone(25000);
        $inscription = $this->creerInscription();

        // Au-dessus du tarif de la zone : accepté
        $abonnement = $this->abonnements->souscrire($this->abonnementData($inscription->id, 30000, 9, $zoneId));

        $this->assertSame($ligneId, (int) $abonnement->ligne_id, 'La ligne découle de la zone.');
        $this->assertSame($voitureId, (int) $abonnement->voiture_id, 'Le véhicule est celui de la ligne.');
        $this->assertEqualsWithDelta(270000, $abonnement->montant_total_du, 0.01);

        // En dessous : accepté aussi
        $autre = $this->abonnements->souscrire($this->abonnementData($this->creerInscription()->id, 10000, 9, $zoneId));
        $this->assertEqualsWithDelta(90000, $autre->montant_total_du, 0.01);
    }

    public function test_la_position_gps_du_domicile_se_renseigne_apres_la_souscription(): void
    {
        $abonnement = $this->abonnements->souscrire($this->abonnementData($this->creerInscription()->id));
        $this->assertFalse($abonnement->aUnePosition());

        $this->actingAs($this->creerUtilisateur());
        $this->postJson("/services-eleves/transport/abonnements/{$abonnement->id}/position", ['latitude' => 6.1725, 'longitude' => 1.2314])
            ->assertOk()->assertJsonPath('success', true);

        $abonnement->refresh();
        $this->assertEqualsWithDelta(6.1725, $abonnement->latitude, 0.0000001);
        $this->assertSame('https://www.google.com/maps?q=6.1725,1.2314', $abonnement->lienCarte());

        // Une seule coordonnée : refusé ; hors bornes : refusé
        $this->postJson("/services-eleves/transport/abonnements/{$abonnement->id}/position", ['latitude' => 6.1])->assertStatus(422);
        $this->postJson("/services-eleves/transport/abonnements/{$abonnement->id}/position", ['latitude' => 120, 'longitude' => 1])->assertStatus(422);

        // Vide : la position est effacée
        $this->postJson("/services-eleves/transport/abonnements/{$abonnement->id}/position", [])->assertOk();
        $this->assertFalse($abonnement->refresh()->aUnePosition());
    }

    public function test_les_abonnes_suivent_le_vehicule_de_la_ligne_et_la_ligne_de_leur_zone(): void
    {
        [$ligneId, $zoneId] = $this->ligneEtZone();
        $abonnement = $this->abonnements->souscrire($this->abonnementData($this->creerInscription()->id, 25000, 9, $zoneId));
        $autreVoiture = $this->transport->creerVoiture($this->voitureData('TG-9999-ZZ'));
        $this->actingAs($this->creerUtilisateur());

        // La ligne change de véhicule
        $this->putJson("/services-eleves/transport/lignes/{$ligneId}", [
            'libelle' => 'Ligne Bè', 'voiture_id' => $autreVoiture->id, 'date_debut' => '2026-09-15', 'date_fin' => '2027-07-15',
        ])->assertOk();
        $this->assertSame($autreVoiture->id, (int) $abonnement->refresh()->voiture_id);

        // La zone change de ligne
        $nouvelle = DB::table('lignes')->insertGetId(['libelle' => 'Ligne Agoè', 'annee_id' => $this->annee->id, 'etat' => 1]);
        $this->putJson("/services-eleves/transport/zones/{$zoneId}", ['libelle' => 'Bè Kpota', 'ligne_id' => $nouvelle, 'tarif_base' => 25000])->assertOk();
        $abonnement->refresh();
        $this->assertSame($nouvelle, (int) $abonnement->ligne_id);
        $this->assertNull($abonnement->voiture_id);

        // Une période qui finit avant de commencer est refusée
        $this->putJson("/services-eleves/transport/lignes/{$ligneId}", ['libelle' => 'Ligne Bè', 'date_debut' => '2027-01-01', 'date_fin' => '2026-12-01'])
            ->assertStatus(422);
    }

    public function test_les_souscriptions_transport_deviennent_des_abonnements_bus(): void
    {
        $inscription = $this->creerInscription();
        $repriseId = DB::table('souscriptions')->insertGetId([
            'date_souscription' => '2026-09-20', 'montant_annuel_prevu' => 300000, 'taux_remise' => 0,
            'type_paiement' => \App\Domain\Finances\Types\TypeDetail::BUS->value, 'origine' => 'reprise', 'libelle' => 'Transport',
            'annee_id' => $this->annee->id, 'inscription_id' => $inscription->id, 'etat' => 1,
        ]);
        $paiementId = DB::table('paiements')->insertGetId(['reference' => 'PAI-BUS-1', 'montant' => 100000, 'annee_id' => $this->annee->id, 'etat' => 1]);
        $detailId = DB::table('details')->insertGetId([
            'paiement_id' => $paiementId, 'inscription_id' => $inscription->id, 'souscription_id' => $repriseId,
            'type_paiement' => \App\Domain\Finances\Types\TypeDetail::BUS->value, 'montant' => 100000,
            'statut_paiement' => \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value, 'etat' => 1,
        ]);
        $avant = $this->engagementBus($inscription->id);
        $this->assertSame(1, $this->abonnements->souscriptionsAConvertir($this->annee->id));

        $bilan = $this->abonnements->convertirSouscriptions($this->annee->id);

        $this->assertSame(1, $bilan['convertis']);
        $abonnement = AbonnementBus::where('inscription_id', $inscription->id)->firstOrFail();
        $this->assertSame('reprise', $abonnement->origine);
        $this->assertEqualsWithDelta(300000, $abonnement->montant_total_du, 0.01, 'Le montant annuel est conservé.');
        $this->assertSame(10, $abonnement->nombre_mois, 'Septembre 2026 à juillet 2027 : 10 mois entamés.');
        $this->assertEqualsWithDelta(30000, $abonnement->montant_mensuel, 0.01);
        $this->assertNull($abonnement->zone_id, 'La zone est à préciser ensuite.');

        // La souscription de l'abonnement remplace la reprise ; le paiement la suit
        $this->assertSame(2, (int) DB::table('souscriptions')->where('id', $repriseId)->value('etat'));
        $nouvelle = DB::table('souscriptions')->where('origine', 'bus')->where('origine_id', $abonnement->id)->first();
        $this->assertNotNull($nouvelle);
        $detail = DB::table('details')->find($detailId);
        $this->assertSame($nouvelle->id, (int) $detail->souscription_id);
        $this->assertSame($abonnement->id, (int) $detail->abonnement_bus_id);

        // Ce que l'élève doit et a payé ne change pas
        $apres = $this->engagementBus($inscription->id);
        $this->assertEqualsWithDelta($avant['du'], $apres['du'], 0.01);
        $this->assertEqualsWithDelta($avant['paye'], $apres['paye'], 0.01);

        // Relançable sans doublon
        $this->assertSame(0, $this->abonnements->convertirSouscriptions($this->annee->id)['convertis']);
        $this->assertSame(1, AbonnementBus::count());
        $this->assertSame(0, $this->abonnements->souscriptionsAConvertir($this->annee->id));
    }

    public function test_le_bouton_de_synchronisation_est_reserve_a_la_gestion_du_transport(): void
    {
        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::Comptable));
        $this->postJson('/services-eleves/transport/abonnements/convertir-souscriptions')->assertForbidden();

        $this->actingAs($this->creerUtilisateur(\App\Domain\Administration\Types\RoleUtilisateur::ResponsableTransport));
        $this->postJson('/services-eleves/transport/abonnements/convertir-souscriptions')->assertOk()
            ->assertJsonPath('data.bilan.convertis', 0);
        $this->get('/services-eleves/transport/abonnements')->assertOk()
            ->assertSee('id="btn-convertir-souscriptions"', false)->assertDontSee('name="point_arret"', false);
        $this->get('/services-eleves/transport/lignes')->assertOk()->assertDontSee('prix_minimal', false)->assertSee('name="date_debut"', false);
        $this->get('/services-eleves/transport/zones')->assertOk()->assertSee('name="ligne_id"', false)->assertSee('name="tarif_base"', false);
    }

    /** Engagement bus de l'inscription (dû, payé), tous engagements bus confondus. */
    private function engagementBus(int $inscriptionId): array
    {
        $lignes = collect(app(EngagementService::class)->lignes($inscriptionId))
            ->where('type', \App\Domain\Finances\Types\TypeDetail::BUS->value);

        return ['du' => $lignes->sum('du'), 'paye' => $lignes->sum('paye')];
    }
}
