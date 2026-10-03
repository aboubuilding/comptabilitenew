<?php

namespace Tests\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\LivreData;
use App\Domain\ServicesEleves\DTO\PretData;
use App\Domain\ServicesEleves\Models\Livre;
use App\Domain\ServicesEleves\Models\Pret;
use App\Domain\ServicesEleves\Services\BibliothequeService;
use App\Domain\ServicesEleves\Types\StatutPret;
use RuntimeException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 4, § 4.3 Bibliothèque.
 */
class BibliothequeTest extends DomainTestCase
{
    private BibliothequeService $service;
    private int $bibliothecaire;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BibliothequeService::class);
        $this->bibliothecaire = $this->creerUtilisateur()->id;
    }

    private function livre(int $exemplaires = 2): Livre
    {
        return $this->service->creerLivre(
            new LivreData("L'enfant noir", 'L-001', null, null, null, null, null, $exemplaires, null)
        );
    }

    private function preter(Livre $livre, ?int $inscriptionId = null, string $retourPrevu = '2026-10-15'): Pret
    {
        return $this->service->enregistrerPret(
            new PretData($livre->id, $inscriptionId ?? $this->creerInscription()->id, '2026-10-01', $retourPrevu, null, null),
            $this->bibliothecaire
        );
    }

    public function test_les_statuts_de_pret_gardent_les_codes_de_la_base_historique(): void
    {
        $this->assertSame(1, StatutPret::EN_COURS->value);
        $this->assertSame(2, StatutPret::RENDU->value);
    }

    public function test_un_livre_entre_au_catalogue_avec_tous_ses_exemplaires_disponibles(): void
    {
        $livre = $this->livre(3)->fresh();

        $this->assertSame(3, (int) $livre->nombre_exemplaires);
        $this->assertSame(3, (int) $livre->nombre_disponibles);
    }

    public function test_un_livre_a_au_moins_un_exemplaire(): void
    {
        $this->expectException(RuntimeException::class);

        $this->livre(0);
    }

    public function test_un_pret_enregistre_sa_date_de_retour_prevue_et_reserve_un_exemplaire(): void
    {
        $livre = $this->livre(2);

        $pret = $this->preter($livre);

        $this->assertSame(StatutPret::EN_COURS, $pret->statut_pret);
        $this->assertSame('2026-10-15', \Carbon\Carbon::parse($pret->date_retour_pret)->toDateString());
        $this->assertSame(1, (int) $livre->fresh()->nombre_disponibles);
    }

    public function test_un_livre_sans_exemplaire_disponible_ne_se_prete_pas(): void
    {
        $livre = $this->livre(1);
        $this->preter($livre);

        $this->expectException(RuntimeException::class);

        $this->preter($livre);
    }

    public function test_un_eleve_n_emprunte_pas_deux_fois_le_meme_livre(): void
    {
        $livre = $this->livre(3);
        $inscriptionId = $this->creerInscription()->id;
        $this->preter($livre, $inscriptionId);

        $this->expectException(RuntimeException::class);

        $this->preter($livre, $inscriptionId);
    }

    public function test_un_retour_cloture_le_pret_et_libere_l_exemplaire(): void
    {
        $livre = $this->livre(1);
        $pret = $this->preter($livre);

        $rendu = $this->service->enregistrerRetour($pret->id, '2026-10-10');

        $this->assertSame(StatutPret::RENDU, $rendu->statut_pret);
        $this->assertSame('2026-10-10', \Carbon\Carbon::parse($rendu->date_retour_reel)->toDateString());
        $this->assertSame(1, (int) $livre->fresh()->nombre_disponibles);
    }

    public function test_un_pret_deja_rendu_ne_se_rend_pas_deux_fois(): void
    {
        $livre = $this->livre(1);
        $pret = $this->preter($livre);
        $this->service->enregistrerRetour($pret->id);

        try {
            $this->service->enregistrerRetour($pret->id);
            $this->fail('Un second retour aurait dû être refusé.');
        } catch (RuntimeException) {
            $this->assertSame(1, (int) $livre->fresh()->nombre_disponibles);
        }
    }

    public function test_un_pret_dont_l_echeance_est_passee_est_signale_en_retard(): void
    {
        $livre = $this->livre(1);
        $pret = $this->preter($livre, null, now()->subDays(3)->toDateString());

        $nombre = $this->service->recalculerRetards();

        $this->assertSame(1, $nombre);
        $this->assertSame(StatutPret::RETARD, $pret->fresh()->statut_pret);
    }

    public function test_un_livre_en_retard_peut_toujours_etre_rendu(): void
    {
        $livre = $this->livre(1);
        $pret = $this->preter($livre, null, now()->subDays(3)->toDateString());
        $this->service->recalculerRetards();

        $rendu = $this->service->enregistrerRetour($pret->id);

        $this->assertSame(StatutPret::RENDU, $rendu->statut_pret);
        $this->assertSame(1, (int) $livre->fresh()->nombre_disponibles);
    }

    public function test_un_livre_perdu_sort_du_fonds(): void
    {
        $livre = $this->livre(2);
        $pret = $this->preter($livre);

        $perdu = $this->service->marquerPerdu($pret->id, 'Perdu par l\'élève');

        $this->assertSame(StatutPret::PERDU, $perdu->statut_pret);
        $livre->refresh();
        $this->assertSame(1, (int) $livre->nombre_exemplaires);
        $this->assertSame(1, (int) $livre->nombre_disponibles);
    }

    public function test_un_livre_en_pret_ne_peut_pas_etre_supprime(): void
    {
        $livre = $this->livre(1);
        $this->preter($livre);

        $this->expectException(RuntimeException::class);

        $this->service->supprimerLivre($livre->id);
    }

    public function test_on_ne_reduit_pas_le_fonds_en_dessous_des_exemplaires_pretes(): void
    {
        $livre = $this->livre(3);
        $this->preter($livre);
        $this->preter($livre);

        $this->expectException(RuntimeException::class);

        $this->service->modifierLivre($livre->id, new LivreData("L'enfant noir", 'L-001', null, null, null, null, null, 1, null));
    }
}
