<?php

namespace Tests\Domain\Administration;

use App\Domain\Administration\DTO\UserDTO;
use App\Domain\Administration\Models\User;
use App\Domain\Administration\Repositories\UserRepository;
use App\Domain\Administration\Services\AuthService;
use App\Domain\Administration\Services\UserService;
use App\Domain\Administration\Types\RoleUtilisateur;
use App\Http\Middleware\CheckPermission;
use App\Repositories\BaseRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Domain\DomainTestCase;

/**
 * Cahier des charges — Menu 6, § 6.1 Utilisateurs & Profils (et § 3, profils utilisateurs).
 */
class UtilisateursProfilsTest extends DomainTestCase
{
    private function roleId(): int
    {
        // Le référentiel des profils est fourni par les migrations : roles.id = code du profil
        return RoleUtilisateur::Comptable->value;
    }

    private function dto(array $donnees = []): UserDTO
    {
        return UserDTO::fromArray($donnees + [
            'nom' => 'MENSAH', 'prenom' => 'Akouvi', 'login' => 'amensah',
            'email' => 'amensah@ecole.test', 'mot_passe' => 'Secret-2026', 'role_id' => $this->roleId(),
        ]);
    }

    private function passerLeControleDePermission(?User $user): bool
    {
        Auth::shouldUse('web');
        $user ? Auth::setUser($user) : Auth::logout();

        try {
            (new CheckPermission())->handle(Request::create('/finances/caisses'), fn () => response('ok'), 'caisses.voir');

            return true;
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());

            return false;
        }
    }

    /* ---------- Profils ---------- */

    public function test_les_profils_historiques_gardent_leur_code(): void
    {
        $this->assertSame(1, RoleUtilisateur::Administrateur->value);
        $this->assertSame(2, RoleUtilisateur::Directeur->value);
        $this->assertSame(3, RoleUtilisateur::Comptable->value);
        $this->assertSame(5, RoleUtilisateur::Caissier->value);
        $this->assertSame(6, RoleUtilisateur::Secretaire->value);
    }

    public function test_chaque_profil_a_un_libelle_et_un_domaine(): void
    {
        foreach (RoleUtilisateur::cases() as $role) {
            $this->assertNotEmpty($role->label());
            $this->assertNotEmpty($role->domainePrincipal());
            $this->assertNotEmpty($role->badgeClass());
        }
    }

    public function test_le_profil_d_un_utilisateur_est_lu_depuis_son_code_historique(): void
    {
        $secretaire = $this->creerUtilisateur(RoleUtilisateur::Secretaire);

        $this->assertSame(RoleUtilisateur::Secretaire, $secretaire->role);
        $this->assertSame('Secrétaire', $secretaire->role_label);
        $this->assertFalse($secretaire->isAdmin());
    }

    /* ---------- Comptes ---------- */

    public function test_creer_un_utilisateur_chiffre_son_mot_de_passe(): void
    {
        $user = app(UserService::class)->creer($this->dto())->fresh();

        $this->assertSame('amensah', $user->login);
        $this->assertNotSame('Secret-2026', $user->mot_passe);
        $this->assertTrue(Hash::check('Secret-2026', $user->mot_passe));
        $this->assertTrue($user->isActive());
    }

    public function test_modifier_un_utilisateur_sans_mot_de_passe_conserve_l_ancien(): void
    {
        $service = app(UserService::class);
        $user = $service->creer($this->dto());
        $role = $user->role_id;

        $modifie = $service->mettreAJour($user, UserDTO::fromArray([
            'nom' => 'MENSAH', 'prenom' => 'Akouvi Afi', 'login' => 'amensah', 'role_id' => $role,
        ]));

        $this->assertSame('Akouvi Afi', $modifie->prenom);
        $this->assertTrue(Hash::check('Secret-2026', $modifie->mot_passe));
    }

    public function test_desactiver_un_compte_l_archive_sans_le_supprimer(): void
    {
        $user = app(UserService::class)->creer($this->dto());

        app(UserService::class)->desactiver($user);

        $enBase = User::find($user->id);
        $this->assertNotNull($enBase);
        $this->assertSame(BaseRepository::INACTIF, (int) $enBase->etat);
        $this->assertFalse($enBase->isActive());
    }

    public function test_le_dernier_administrateur_ne_peut_pas_etre_supprime(): void
    {
        $admin = $this->creerUtilisateur(RoleUtilisateur::Administrateur);
        $depot = app(UserRepository::class);

        $this->assertFalse($depot->canDelete($admin));

        $this->creerUtilisateur(RoleUtilisateur::Administrateur);
        $this->assertTrue($depot->canDelete($admin));
    }

    /* ---------- Connexion ---------- */

    public function test_la_connexion_reussit_par_login_ou_par_email(): void
    {
        $this->creerUtilisateur(RoleUtilisateur::Comptable, ['login' => 'compta', 'email' => 'compta@ecole.test', 'mot_passe' => bcrypt('Secret-2026')]);
        $auth = app(AuthService::class);

        $parLogin = $auth->attempt('compta', 'Secret-2026');
        Auth::logout();
        $parEmail = $auth->attempt('compta@ecole.test', 'Secret-2026');

        $this->assertTrue($parLogin['success']);
        $this->assertTrue($parEmail['success']);
        $this->assertSame($this->annee->id, $parEmail['annee']->id);
    }

    public function test_un_mot_de_passe_errone_est_refuse_sans_reveler_si_le_compte_existe(): void
    {
        $this->creerUtilisateur(RoleUtilisateur::Comptable, ['login' => 'compta', 'mot_passe' => bcrypt('Secret-2026')]);
        $auth = app(AuthService::class);

        $mauvaisMotDePasse = $auth->attempt('compta', 'faux');
        $compteInconnu = $auth->attempt('inconnu', 'faux');

        $this->assertFalse($mauvaisMotDePasse['success']);
        $this->assertFalse($compteInconnu['success']);
        $this->assertSame($mauvaisMotDePasse['message'], $compteInconnu['message']);
        $this->assertFalse(Auth::check());
    }

    public function test_un_compte_desactive_ne_peut_pas_se_connecter(): void
    {
        $this->creerUtilisateur(RoleUtilisateur::Comptable, [
            'login' => 'ancien', 'mot_passe' => bcrypt('Secret-2026'), 'etat' => BaseRepository::INACTIF,
        ]);

        $resultat = app(AuthService::class)->attempt('ancien', 'Secret-2026');

        $this->assertFalse($resultat['success']);
        $this->assertSame('ACCOUNT_INACTIVE', $resultat['code']);
        $this->assertFalse(Auth::check());
    }

    /* ---------- Droits d'accès ---------- */

    public function test_l_administrateur_accede_aux_modules_proteges(): void
    {
        $this->assertTrue($this->passerLeControleDePermission($this->creerUtilisateur(RoleUtilisateur::Administrateur)));
    }

    public function test_le_controle_d_acces_suit_la_matrice_du_cahier_des_charges(): void
    {
        // « caisses.voir » : Comptable en consultation (2.4), pas le Secrétariat
        $this->assertTrue($this->passerLeControleDePermission($this->creerUtilisateur(RoleUtilisateur::Comptable)));
        $this->assertFalse($this->passerLeControleDePermission($this->creerUtilisateur(RoleUtilisateur::Secretaire)));
    }

    public function test_les_formulaires_appliquent_la_meme_matrice_que_les_pages(): void
    {
        $admin = $this->creerUtilisateur(RoleUtilisateur::Administrateur);
        $comptable = $this->creerUtilisateur(RoleUtilisateur::Comptable);
        $caissier = $this->creerUtilisateur(RoleUtilisateur::Caissier);

        $this->assertTrue($admin->can('caisses.ouvrir'));
        $this->assertTrue($caissier->can('caisses.ouvrir'));
        $this->assertFalse($comptable->can('caisses.ouvrir'));
        $this->assertFalse($comptable->can('depenses.valider'));
    }

    public function test_un_visiteur_non_connecte_est_refuse(): void
    {
        $this->assertFalse($this->passerLeControleDePermission(null));
    }
}
