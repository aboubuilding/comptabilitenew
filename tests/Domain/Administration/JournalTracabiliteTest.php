<?php

namespace Tests\Domain\Administration;

use App\Domain\Administration\Models\JournalAction;
use App\Domain\Administration\Services\AuthService;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Http\Controllers\Concerns\LogsActions;
use Illuminate\Support\Facades\Auth;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 6, § 6.4 Journal & Traçabilité.
 */
class JournalTracabiliteTest extends DomainTestCase
{
    public function test_une_connexion_reussie_enregistre_la_date_et_l_adresse_ip(): void
    {
        $user = $this->creerUtilisateur(RoleUtilisateur::Comptable, ['login' => 'compta', 'mot_passe' => bcrypt('Secret-2026')]);

        app(AuthService::class)->attempt('compta', 'Secret-2026');

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
        $this->assertNotNull($user->last_login_ip);
    }

    public function test_une_connexion_est_inscrite_au_journal(): void
    {
        $user = $this->creerUtilisateur(RoleUtilisateur::Comptable, ['login' => 'compta', 'mot_passe' => bcrypt('Secret-2026')]);

        app(AuthService::class)->attempt('compta', 'Secret-2026');

        $entree = JournalAction::where('user_id', $user->id)->where('action', 'login')->first();
        $this->assertNotNull($entree, 'La connexion doit laisser une trace dans le journal.');
        $this->assertSame('Administration', $entree->module);
        $this->assertSame('compta', $entree->user_login);
    }

    public function test_une_connexion_echouee_ne_modifie_pas_la_derniere_connexion(): void
    {
        $user = $this->creerUtilisateur(RoleUtilisateur::Comptable, ['login' => 'compta', 'mot_passe' => bcrypt('Secret-2026')]);

        app(AuthService::class)->attempt('compta', 'faux');

        $this->assertNull($user->fresh()->last_login_at);
        $this->assertSame(0, JournalAction::where('action', 'login')->count());
    }

    public function test_une_action_metier_est_journalisee_avec_son_auteur_et_son_module(): void
    {
        $user = $this->creerUtilisateur(RoleUtilisateur::Directeur);
        Auth::setUser($user);

        $controleur = new class {
            use LogsActions;

            public function valider(): void
            {
                $this->logAction('validation', 'Depense', 42, 'Dépense validée', ['montant' => 25000]);
            }
        };
        $controleur->valider();

        $entree = JournalAction::where('action', 'validation')->sole();
        $this->assertSame($user->id, (int) $entree->user_id);
        $this->assertSame('Depense', $entree->entite);
        $this->assertSame(42, (int) $entree->entite_id);
        $this->assertSame(['montant' => 25000], $entree->payload);
        $this->assertNotNull($entree->created_at);
    }

    public function test_le_journal_garde_le_nom_de_l_auteur_meme_si_son_compte_change(): void
    {
        $user = $this->creerUtilisateur(RoleUtilisateur::Directeur, ['nom' => 'ADJO', 'prenom' => 'Kafui']);
        Auth::setUser($user);
        $controleur = new class {
            use LogsActions;

            public function agir(): void
            {
                $this->logAction('export');
            }
        };
        $controleur->agir();

        $user->update(['nom' => 'AUTRE']);

        $this->assertSame('ADJO Kafui', JournalAction::where('action', 'export')->sole()->user_nom);
    }
}
