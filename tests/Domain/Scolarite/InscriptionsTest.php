<?php

namespace Tests\Domain\Scolarite;

use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Scolarite\Services\InscriptionService;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 1, § 1.3 Inscriptions
 * et règle transversale « Cycle de vie d'une inscription ».
 */
class InscriptionsTest extends DomainTestCase
{
    private InscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InscriptionService::class);
    }

    public function test_les_statuts_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, StatutValidationInscription::EN_ATTENTE->value);
        $this->assertSame(2, StatutValidationInscription::VALIDEE->value);
        $this->assertSame(3, StatutValidationInscription::REJETEE->value);
        $this->assertSame(4, StatutValidationInscription::EN_COURS->value);
    }

    public function test_la_direction_valide_une_inscription_en_attente(): void
    {
        $directeur = $this->creerUtilisateur(RoleUtilisateur::Directeur);
        $inscription = $this->creerInscription(null, [
            'statut_validation' => StatutValidationInscription::EN_ATTENTE->value,
        ]);

        $this->service->valider($inscription, $directeur->id);

        $inscription->refresh();
        $this->assertSame(StatutValidationInscription::VALIDEE->value, (int) $inscription->statut_validation);
        $this->assertSame($directeur->id, (int) $inscription->utilisateur_id);
        $this->assertNotNull($inscription->date_validation);
        $this->assertNull($inscription->motif_rejet);
    }

    public function test_un_rejet_conserve_son_motif(): void
    {
        $directeur = $this->creerUtilisateur(RoleUtilisateur::Directeur);
        $inscription = $this->creerInscription(null, [
            'statut_validation' => StatutValidationInscription::EN_ATTENTE->value,
        ]);

        $this->service->rejeter($inscription, 'Dossier incomplet', $directeur->id);

        $inscription->refresh();
        $this->assertSame(StatutValidationInscription::REJETEE->value, (int) $inscription->statut_validation);
        $this->assertSame('Dossier incomplet', $inscription->motif_rejet);
    }

    public function test_valider_apres_un_rejet_efface_le_motif(): void
    {
        $directeur = $this->creerUtilisateur(RoleUtilisateur::Directeur);
        $inscription = $this->creerInscription(null, [
            'statut_validation' => StatutValidationInscription::REJETEE->value,
            'motif_rejet'       => 'Dossier incomplet',
        ]);

        $this->service->valider($inscription, $directeur->id);

        $this->assertNull($inscription->refresh()->motif_rejet);
    }

    public function test_la_liste_affiche_le_statut_de_chaque_inscription(): void
    {
        $enAttente = $this->creerInscription(null, ['statut_validation' => StatutValidationInscription::EN_ATTENTE->value]);
        $validee = $this->creerInscription();
        $rejetee = $this->creerInscription(null, ['statut_validation' => StatutValidationInscription::REJETEE->value]);

        $this->assertSame('En attente', $enAttente->statutAffiche()['label']);
        $this->assertTrue($enAttente->isEnAttente());
        $this->assertSame('Validée', $validee->statutAffiche()['label']);
        $this->assertTrue($validee->isValidee());
        $this->assertSame('Rejetée', $rejetee->statutAffiche()['label']);
        $this->assertFalse($rejetee->isEnAttente());
    }

    public function test_une_inscription_abandonnee_s_affiche_comme_telle_meme_validee(): void
    {
        $inscription = $this->creerInscription();
        $this->service->enregistrerAbandon($inscription, 'Déménagement');

        $inscription->refresh();
        $this->assertTrue($inscription->isAbandonnee());
        $this->assertSame('Abandonnée', $inscription->statutAffiche()['label']);
        $this->assertSame('badge-statut-abandon', $inscription->statutAffiche()['classe']);
    }

    public function test_un_abandon_en_cours_d_annee_est_date_et_motive(): void
    {
        $inscription = $this->creerInscription();

        $this->service->enregistrerAbandon($inscription, 'Déménagement');

        $inscription->refresh();
        $this->assertSame(1, (int) $inscription->statut_abandon);
        $this->assertSame('Déménagement', $inscription->motif_abandon);
        $this->assertNotNull($inscription->date_abandon);
        // L'abandon ne remet pas en cause la validation déjà acquise
        $this->assertSame(StatutValidationInscription::VALIDEE->value, (int) $inscription->statut_validation);
    }
}
