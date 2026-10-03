<?php

namespace Tests\Domain\Scolarite;

use App\Domain\Scolarite\DTO\CompteDTO;
use App\Domain\Scolarite\DTO\EspaceDTO;
use App\Domain\Scolarite\DTO\ParentDTO;
use App\Domain\Scolarite\Models\Espace;
use App\Domain\Scolarite\Models\ParentEleve;
use App\Domain\Scolarite\Services\CompteService;
use App\Domain\Scolarite\Services\EspaceService;
use App\Domain\Scolarite\Services\ParentEleveService;
use App\Domain\Scolarite\Types\StatutCompte;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 1, § 1.2 Parents & Familles.
 */
class ParentsFamillesTest extends DomainTestCase
{
    private function espace(string $nom = 'Famille KOFFI'): Espace
    {
        return app(EspaceService::class)->creer(new EspaceDTO($nom));
    }

    private function parent(Espace $espace, array $donnees = []): ParentEleve
    {
        return app(ParentEleveService::class)->creer(ParentDTO::fromArray($donnees + [
            'nom_parent'    => 'KOFFI',
            'prenom_parent' => 'Yao',
            'telephone'     => '90000000',
            'espace_id'     => $espace->id,
        ]));
    }

    public function test_un_espace_familial_est_rattache_a_l_annee_active(): void
    {
        $espace = $this->espace();

        $this->assertSame($this->annee->id, (int) $espace->annee_id);
    }

    public function test_un_parent_ne_peut_pas_etre_cree_sans_espace_familial(): void
    {
        $this->expectException(RuntimeException::class);

        app(ParentEleveService::class)->creer(ParentDTO::fromArray([
            'nom_parent' => 'KOFFI', 'prenom_parent' => 'Yao',
        ]));
    }

    public function test_le_premier_parent_d_un_espace_devient_parent_principal(): void
    {
        $parent = $this->parent($this->espace());

        $this->assertSame(1, (int) $parent->fresh()->is_principal);
    }

    public function test_designer_un_parent_principal_retire_ce_role_aux_autres(): void
    {
        $espace = $this->espace();
        $pere = $this->parent($espace);
        $mere = $this->parent($espace, ['prenom_parent' => 'Afi']);

        app(ParentEleveService::class)->designerPrincipal($mere);

        $this->assertSame(1, (int) $mere->fresh()->is_principal);
        $this->assertSame(0, (int) $pere->fresh()->is_principal);
        $this->assertSame(
            1,
            ParentEleve::where('espace_id', $espace->id)->where('is_principal', 1)->count(),
            'Un espace familial a un seul parent principal.'
        );
    }

    public function test_regrouper_une_fratrie_cree_l_espace_et_y_rattache_les_eleves(): void
    {
        $aine = $this->creerEleve();
        $cadet = $this->creerEleve();

        $resultat = app(EspaceService::class)->regrouper([$aine->id, $cadet->id], null, 'Famille MENSAH');

        $this->assertSame(2, $resultat['eleves_affectes']);
        $this->assertSame([], $resultat['deja_affectes']);
        $this->assertSame($resultat['espace_id'], (int) $aine->fresh()->espace_id);
        $this->assertSame($resultat['espace_id'], (int) $cadet->fresh()->espace_id);
    }

    public function test_regrouper_ne_deplace_pas_un_eleve_deja_rattache_a_une_autre_famille(): void
    {
        $autreFamille = $this->espace('Famille ADJO');
        $dejaRattache = $this->creerEleve(['espace_id' => $autreFamille->id]);
        $libre = $this->creerEleve();

        $resultat = app(EspaceService::class)->regrouper([$dejaRattache->id, $libre->id], null, 'Famille MENSAH');

        $this->assertSame(1, $resultat['eleves_affectes']);
        $this->assertCount(1, $resultat['deja_affectes']);
        $this->assertSame($autreFamille->id, (int) $dejaRattache->fresh()->espace_id);
    }

    public function test_regrouper_exige_un_nom_de_famille_pour_un_nouvel_espace(): void
    {
        $this->expectException(RuntimeException::class);

        app(EspaceService::class)->regrouper([$this->creerEleve()->id], null, null);
    }

    public function test_creer_un_compte_portail_envoie_les_identifiants_au_parent(): void
    {
        Mail::fake();
        $espace = $this->espace();
        $parent = $this->parent($espace);

        $resultat = app(CompteService::class)->creer(new CompteDTO('yao.koffi@exemple.test', $espace->id, $parent->id));

        $compte = $resultat['compte']->fresh();
        $this->assertSame(StatutCompte::ACTIF, $compte->statut_compte);
        $this->assertTrue(Hash::check($resultat['mot_passe_clair'], $compte->mot_passe));
        Mail::assertSent(\App\Mail\ComptePortailCreeMail::class);
    }

    public function test_deux_comptes_portail_ne_peuvent_pas_partager_le_meme_email(): void
    {
        Mail::fake();
        $espace = $this->espace();
        $parent = $this->parent($espace);
        app(CompteService::class)->creer(new CompteDTO('yao.koffi@exemple.test', $espace->id, $parent->id));

        $this->expectException(RuntimeException::class);

        app(CompteService::class)->creer(new CompteDTO('yao.koffi@exemple.test', $espace->id, $parent->id));
    }

    public function test_un_compte_portail_refuse_un_parent_d_une_autre_famille(): void
    {
        Mail::fake();
        $parent = $this->parent($this->espace());
        $autreEspace = $this->espace('Famille ADJO');

        $this->expectException(RuntimeException::class);

        app(CompteService::class)->creer(new CompteDTO('yao.koffi@exemple.test', $autreEspace->id, $parent->id));
    }
}
