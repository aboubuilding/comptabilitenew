<?php

namespace Tests\Domain;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Types\StatutAnnee;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Support\AnneeScolaireContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Socle des tests par domaine (un dossier par menu du cahier des charges).
 *
 * Chaque test tourne dans une transaction annulée à la fin : la base de test
 * est reconstruite une fois par exécution, jamais la base de travail
 * (voir Tests\CreatesApplication).
 */
abstract class DomainTestCase extends TestCase
{
    use RefreshDatabase;

    protected Annee $annee;

    protected function setUp(): void
    {
        parent::setUp();

        // Règle transversale « Cloisonnement par année scolaire » : tout test
        // s'exécute dans une année active, comme derrière ResolveAnneeScolaire.
        $this->annee = $this->creerAnnee();
        app(AnneeScolaireContext::class)->set($this->annee->id);
    }

    /* ===================== Jeux de données ===================== */

    protected function creerAnnee(array $attributs = []): Annee
    {
        $id = DB::table('annees')->insertGetId($attributs + [
            'libelle'                      => '2026-2027',
            'date_rentree'                 => '2026-09-15',
            'date_fin'                     => '2027-07-15',
            'date_ouverture_inscription'   => '2026-06-01',
            'date_fermeture_reinscription' => '2027-01-31',
            'statut_annee'                 => StatutAnnee::Ouvert->value,
            'etat'                         => 1,
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ]);

        return Annee::findOrFail($id);
    }

    protected function creerUtilisateur(RoleUtilisateur $role = RoleUtilisateur::Administrateur, array $attributs = []): User
    {
        static $n = 0;
        $n++;

        $id = DB::table('users')->insertGetId($attributs + [
            'nom'        => 'Agent',
            'prenom'     => "Test{$n}",
            'login'      => "agent{$n}",
            'email'      => "agent{$n}@ecole.test",
            'mot_passe'  => bcrypt('secret'),
            'role'       => $role->value,
            'etat'       => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    /** @return array{cycle_id:int, niveau_id:int, classe_id:int} */
    protected function creerStructure(string $niveau = 'CP', ?int $anneeId = null): array
    {
        $cycleId = DB::table('cycles')->insertGetId(['libelle' => 'Primaire', 'etat' => 1]);
        $niveauId = DB::table('niveaux')->insertGetId(['libelle' => $niveau, 'cycle_id' => $cycleId, 'etat' => 1]);
        $classeId = DB::table('classes')->insertGetId([
            'libelle'   => "{$niveau} A",
            'cycle_id'  => $cycleId,
            'niveau_id' => $niveauId,
            'annee_id'  => $anneeId ?? $this->annee->id,
            'etat'      => 1,
        ]);

        return ['cycle_id' => $cycleId, 'niveau_id' => $niveauId, 'classe_id' => $classeId];
    }

    protected function creerEleve(array $attributs = []): Eleve
    {
        static $n = 0;
        $n++;

        $id = DB::table('eleves')->insertGetId($attributs + [
            'matricule'  => sprintf('MAT%05d', $n),
            'nom'        => 'ELEVE',
            'prenom'     => "Numero{$n}",
            'sexe'       => 1,
            'etat'       => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Eleve::findOrFail($id);
    }

    /** Inscription validée par défaut, dans l'année active. */
    protected function creerInscription(?Eleve $eleve = null, array $attributs = []): Inscription
    {
        $eleve ??= $this->creerEleve();

        $id = DB::table('inscriptions')->insertGetId($attributs + [
            'eleve_id'          => $eleve->id,
            'annee_id'          => $this->annee->id,
            'date_inscription'  => '2026-09-01',
            'type_inscription'  => 1,
            'statut_validation' => StatutValidationInscription::VALIDEE->value,
            'statut_abandon'    => 0,
            'etat'              => 1,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return Inscription::findOrFail($id);
    }

    protected function creerFrais(float $montant = 300000, TypeDetail $type = TypeDetail::FRAIS_SCOLAIRE, array $attributs = []): FraisEcole
    {
        $id = DB::table('frais_ecoles')->insertGetId($attributs + [
            'libelle'       => $type->label(),
            'montant'       => $montant,
            'type_paiement' => $type->value,
            'annee_id'      => $this->annee->id,
            'etat'          => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return FraisEcole::findOrFail($id);
    }

    protected function creerCaisseOuverte(User $caissier, float $soldeInitial = 0, array $attributs = []): Caisse
    {
        $id = DB::table('caisses')->insertGetId($attributs + [
            'libelle'        => 'Caisse principale',
            'solde_initial'  => $soldeInitial,
            'date_ouverture' => now(),
            'statut'         => CaisseStatut::OUVERTE->value,
            'utilisateur_id' => $caissier->id,
            'responsable_id' => $caissier->id,
            'annee_id'       => $this->annee->id,
            'etat'           => 1,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return Caisse::findOrFail($id);
    }
}
