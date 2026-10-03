<?php

namespace Tests\Domain\Finances;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\DTO\DepenseData;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Models\Mouvement;
use App\Domain\Finances\Repositories\DepenseRepository;
use App\Domain\Finances\Services\DepenseService;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\DepenseStatut;
use App\Domain\Finances\Types\MouvementType;
use App\Repositories\BaseRepository;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 2, § 2.6 Dépenses
 * et règle transversale « Circuit de validation des dépenses ».
 */
class DepensesTest extends DomainTestCase
{
    private DepenseService $service;
    private User $comptable;
    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DepenseService::class);
        $this->comptable = $this->creerUtilisateur(RoleUtilisateur::Comptable);
        $this->directeur = $this->creerUtilisateur(RoleUtilisateur::Directeur);
    }

    private function saisir(float $montant = 25000): Depense
    {
        return $this->service->saisir(
            new DepenseData('Fournitures de bureau', 'Papeterie du Centre', 'Rames de papier', '2026-09-20', $montant, $this->comptable->id)
        );
    }

    private function depenseReglee(): array
    {
        $caisse = $this->creerCaisseOuverte($this->comptable, 100000);
        $depense = $this->saisir();
        $this->service->valider($depense->id, $this->directeur->id);
        $depense = $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);

        return [$depense, $caisse];
    }

    public function test_les_statuts_de_depense_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, DepenseStatut::EN_ATTENTE->value);
        $this->assertSame(2, DepenseStatut::REJETEE->value);
        $this->assertSame(3, DepenseStatut::VALIDEE->value);
        $this->assertSame(4, DepenseStatut::PAYEE->value);
    }

    public function test_une_depense_saisie_attend_la_validation_de_la_direction(): void
    {
        $depense = $this->saisir()->fresh();

        $this->assertTrue($depense->isEnAttente());
        $this->assertSame('Papeterie du Centre', $depense->beneficiaire);
        $this->assertSame($this->comptable->id, (int) $depense->utilisateur_id);
        $this->assertSame($this->annee->id, (int) $depense->annee_id);
    }

    public function test_la_direction_valide_une_depense(): void
    {
        $depense = $this->service->valider($this->saisir()->id, $this->directeur->id);

        $this->assertTrue($depense->isValidee());
        $this->assertSame($this->directeur->id, (int) $depense->valide_par);
        $this->assertNotNull($depense->date_validation);
    }

    public function test_un_rejet_de_depense_exige_un_motif(): void
    {
        $depense = $this->saisir();

        $this->expectException(RuntimeException::class);

        $this->service->rejeter($depense->id, $this->directeur->id, '');
    }

    public function test_une_depense_rejetee_conserve_son_motif(): void
    {
        $depense = $this->service->rejeter($this->saisir()->id, $this->directeur->id, 'Hors budget');

        $this->assertTrue($depense->isRejetee());
        $this->assertSame('Hors budget', $depense->motif_rejet);
    }

    public function test_une_depense_deja_validee_ne_se_modifie_plus(): void
    {
        $depense = $this->saisir();
        $this->service->valider($depense->id, $this->directeur->id);

        $this->expectException(RuntimeException::class);

        $this->service->modifier($depense->id, new DepenseData('Autre', 'Autre', null, '2026-09-21', 1000, $this->comptable->id));
    }

    public function test_seule_une_depense_validee_peut_etre_reglee(): void
    {
        $caisse = $this->creerCaisseOuverte($this->comptable, 100000);
        $depense = $this->saisir();

        $this->expectException(RuntimeException::class);

        $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);
    }

    public function test_regler_une_depense_la_rattache_a_la_caisse_et_cree_une_sortie(): void
    {
        [$depense, $caisse] = $this->depenseReglee();

        $this->assertTrue($depense->isReglee());
        $this->assertSame($caisse->id, (int) $depense->caisse_id);
        $this->assertSame($this->comptable->id, (int) $depense->regle_par);

        $mouvement = Mouvement::where('depense_id', $depense->id)->sole();
        $this->assertSame(MouvementType::DEPENSE, $mouvement->type_mouvement);
        $this->assertFalse($mouvement->type_mouvement->isEntree());
        $this->assertEqualsWithDelta(25000, (float) $mouvement->montant, 0.01);
        $this->assertSame($caisse->id, (int) $mouvement->caisse_id);
    }

    public function test_une_depense_n_est_reglee_qu_une_fois(): void
    {
        [$depense, $caisse] = $this->depenseReglee();

        $this->expectException(RuntimeException::class);

        $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);
    }

    public function test_une_depense_ne_se_regle_pas_sur_une_caisse_cloturee(): void
    {
        $caisse = $this->creerCaisseOuverte($this->comptable, 100000, ['statut' => CaisseStatut::CLOTUREE->value]);
        $depense = $this->saisir();
        $this->service->valider($depense->id, $this->directeur->id);

        $this->expectException(RuntimeException::class);

        $this->service->reglerViaCaisse($depense->id, $caisse->id, $this->comptable->id);
    }

    public function test_archiver_une_depense_reglee_retire_sa_sortie_du_journal_de_caisse(): void
    {
        [$depense] = $this->depenseReglee();

        app(DepenseRepository::class)->delete($depense->id);

        $this->assertSame(BaseRepository::INACTIF, (int) Depense::find($depense->id)->etat);
        $this->assertSame(BaseRepository::INACTIF, (int) Mouvement::where('depense_id', $depense->id)->sole()->etat);
    }

    public function test_une_depense_reglee_sur_une_caisse_cloturee_ne_peut_plus_etre_archivee(): void
    {
        [$depense, $caisse] = $this->depenseReglee();
        Caisse::whereKey($caisse->id)->update(['statut' => CaisseStatut::CLOTUREE->value]);

        try {
            app(DepenseRepository::class)->delete($depense->id);
            $this->fail('Le journal d\'une caisse clôturée est figé : la suppression aurait dû être refusée.');
        } catch (RuntimeException) {
            $this->assertSame(BaseRepository::ACTIF, (int) Depense::find($depense->id)->etat);
            $this->assertSame(BaseRepository::ACTIF, (int) Mouvement::where('depense_id', $depense->id)->sole()->etat);
        }
    }
}
