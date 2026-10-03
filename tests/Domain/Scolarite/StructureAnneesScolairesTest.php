<?php

namespace Tests\Domain\Scolarite;

use App\Domain\Scolarite\DTO\AffectationClasseDTO;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Services\AffectationClasseService;
use App\Domain\Scolarite\Services\AnneeService;
use App\Domain\Scolarite\Types\StatutAnnee;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 1, § 1.4 Structure pédagogique & Années scolaires
 * et règle transversale « Cloisonnement par année scolaire ».
 */
class StructureAnneesScolairesTest extends DomainTestCase
{
    public function test_les_statuts_d_annee_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, StatutAnnee::NonOuvert->value);
        $this->assertSame(2, StatutAnnee::Ouvert->value);
        $this->assertSame(3, StatutAnnee::Cloture->value);
    }

    public function test_l_annee_active_est_celle_qui_est_ouverte(): void
    {
        $this->creerAnnee(['libelle' => '2025-2026', 'date_rentree' => '2025-09-15', 'statut_annee' => StatutAnnee::Cloture->value]);

        $active = app(AnneeService::class)->resolveActive();

        $this->assertSame($this->annee->id, $active->id);
        $this->assertTrue($active->isOuverte());
    }

    public function test_sans_annee_ouverte_la_plus_recente_sert_d_annee_de_travail(): void
    {
        DB::table('annees')->update(['statut_annee' => StatutAnnee::Cloture->value]);
        $ancienne = $this->creerAnnee(['libelle' => '2024-2025', 'date_rentree' => '2024-09-15', 'statut_annee' => StatutAnnee::Cloture->value]);

        $active = app(AnneeService::class)->resolveActive();

        $this->assertSame($this->annee->id, $active->id);
        $this->assertNotSame($ancienne->id, $active->id);
    }

    public function test_une_annee_est_creee_par_defaut_quand_il_n_en_existe_aucune(): void
    {
        DB::table('annees')->delete();

        $active = app(AnneeService::class)->resolveActive();

        $this->assertSame(1, Annee::count());
        $this->assertSame(StatutAnnee::Ouvert, $active->statut_annee);
    }

    public function test_cloturer_une_annee_la_passe_au_statut_cloture(): void
    {
        app(AnneeService::class)->cloturer($this->annee);

        $annee = $this->annee->fresh();
        $this->assertSame(StatutAnnee::Cloture, $annee->statut_annee);
        $this->assertTrue($annee->isCloturee());
        $this->assertFalse($annee->isOuverte());
    }

    public function test_affecter_des_eleves_a_une_classe_met_a_jour_leur_inscription(): void
    {
        $structure = $this->creerStructure();
        $inscription = $this->creerInscription(null, ['niveau_id' => $structure['niveau_id']]);

        $resultat = app(AffectationClasseService::class)->affecter(
            new AffectationClasseDTO($structure['classe_id'], [$inscription->eleve_id])
        );

        $this->assertSame(1, $resultat['affectes']);
        $this->assertSame($structure['classe_id'], (int) $inscription->fresh()->classe_id);
    }

    public function test_seules_les_inscriptions_validees_sont_affectees(): void
    {
        $structure = $this->creerStructure();
        $valide = $this->creerInscription(null, ['niveau_id' => $structure['niveau_id']]);
        $enAttente = $this->creerInscription(null, [
            'niveau_id'         => $structure['niveau_id'],
            'statut_validation' => StatutValidationInscription::EN_ATTENTE->value,
        ]);

        $resultat = app(AffectationClasseService::class)->affecter(
            new AffectationClasseDTO($structure['classe_id'], [$valide->eleve_id, $enAttente->eleve_id])
        );

        $this->assertSame(1, $resultat['affectes']);
        $this->assertSame([$enAttente->eleve_id], $resultat['ignores']);
        $this->assertNull($enAttente->fresh()->classe_id);
    }

    public function test_une_classe_d_une_autre_annee_est_refusee(): void
    {
        $autreAnnee = $this->creerAnnee(['libelle' => '2025-2026', 'statut_annee' => StatutAnnee::Cloture->value]);
        $structure = $this->creerStructure('CP', $autreAnnee->id);
        $inscription = $this->creerInscription(null, ['niveau_id' => $structure['niveau_id']]);

        $this->expectException(RuntimeException::class);

        app(AffectationClasseService::class)->affecter(
            new AffectationClasseDTO($structure['classe_id'], [$inscription->eleve_id])
        );
    }

    public function test_des_eleves_de_niveaux_differents_ne_vont_pas_dans_la_meme_classe(): void
    {
        $cp = $this->creerStructure('CP');
        $ce1 = $this->creerStructure('CE1');
        $eleveCp = $this->creerInscription(null, ['niveau_id' => $cp['niveau_id']]);
        $eleveCe1 = $this->creerInscription(null, ['niveau_id' => $ce1['niveau_id']]);

        $this->expectException(RuntimeException::class);

        app(AffectationClasseService::class)->affecter(
            new AffectationClasseDTO($cp['classe_id'], [$eleveCp->eleve_id, $eleveCe1->eleve_id])
        );
    }
}
