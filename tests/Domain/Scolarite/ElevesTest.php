<?php

namespace Tests\Domain\Scolarite;

use App\Domain\Scolarite\DTO\EleveDTO;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Services\EleveService;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Repositories\BaseRepository;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 1, § 1.1 Élèves
 * et règle transversale « Statuts et états génériques ».
 */
class ElevesTest extends DomainTestCase
{
    private EleveService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EleveService::class);
    }

    private function dto(array $donnees = []): EleveDTO
    {
        return EleveDTO::fromArray($donnees + [
            'nom'            => 'KOFFI',
            'prenom'         => 'Ama',
            'sexe'           => 2,
            'date_naissance' => '2015-03-12',
        ]);
    }

    public function test_creer_une_fiche_eleve_attribue_un_matricule(): void
    {
        $eleve = $this->service->creer($this->dto());

        $this->assertNotEmpty($eleve->matricule);
        $this->assertSame('KOFFI', $eleve->nom);
        $this->assertSame(BaseRepository::ACTIF, (int) $eleve->fresh()->etat);
    }

    public function test_deux_eleves_n_ont_jamais_le_meme_matricule(): void
    {
        $premier = $this->service->creer($this->dto());
        $second  = $this->service->creer($this->dto(['prenom' => 'Kossi', 'sexe' => 1]));

        $this->assertNotSame($premier->matricule, $second->matricule);
    }

    public function test_modifier_une_fiche_ne_change_pas_le_matricule(): void
    {
        $eleve = $this->service->creer($this->dto());
        $matricule = $eleve->matricule;

        $modifie = $this->service->mettreAJour($eleve, $this->dto(['prenom' => 'Amavi']));

        $this->assertSame('Amavi', $modifie->prenom);
        $this->assertSame($matricule, $modifie->matricule);
    }

    public function test_archiver_desactive_la_fiche_sans_la_supprimer(): void
    {
        $eleve = $this->creerEleve();

        $this->service->archiver($eleve);

        $enBase = Eleve::query()->find($eleve->id);
        $this->assertNotNull($enBase, "L'archivage est logique : la ligne doit rester en base.");
        $this->assertSame(BaseRepository::INACTIF, (int) $enBase->etat);
    }

    public function test_les_statistiques_ne_comptent_que_les_inscrits_valides_de_l_annee_active(): void
    {
        $this->creerInscription($this->creerEleve(['sexe' => 1]));
        $this->creerInscription($this->creerEleve(['sexe' => 2]));
        // En attente de validation : non compté
        $this->creerInscription($this->creerEleve(['sexe' => 2]), [
            'statut_validation' => StatutValidationInscription::EN_ATTENTE->value,
        ]);
        // Validé, mais dans une autre année : non compté
        $autreAnnee = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => 3]);
        $this->creerInscription($this->creerEleve(['sexe' => 1]), ['annee_id' => $autreAnnee->id]);
        // Élève archivé : non compté
        $this->creerInscription($this->creerEleve(['sexe' => 1, 'etat' => BaseRepository::INACTIF]));

        $stats = $this->service->statistiques();

        $this->assertSame($this->annee->id, $stats['annee_id']);
        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['masculins']);
        $this->assertSame(1, $stats['feminins']);
    }
}
