<?php

namespace Database\Seeders;

use App\Domain\Administration\Models\User;
use App\Domain\Administration\Types\RoleUtilisateur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Crée un compte pour chaque profil qui n'a encore aucun utilisateur dans
 * l'établissement et dont l'application a besoin pour fonctionner :
 *
 *  - Caissier : seul profil autorisé à encaisser (cahier des charges 2.3) ;
 *  - Administrateur adjoint et Enseignant : profils hérités de la base historique.
 *
 *     php artisan db:seed --class=ComptesProfilsSeeder
 *
 * Le mot de passe est tiré au hasard et affiché UNE SEULE FOIS dans la
 * console : il n'est stocké nulle part en clair. Un compte dont le login
 * existe déjà n'est pas touché (le seeder peut être relancé sans risque).
 */
class ComptesProfilsSeeder extends Seeder
{
    /** login => [profil, nom, prénom] */
    private const COMPTES = [
        'caissier'   => [RoleUtilisateur::Caissier, 'CAISSE', 'Principale'],
        'adjoint'    => [RoleUtilisateur::AdministrateurAdjoint, 'ADMINISTRATION', 'Adjoint'],
        'enseignant' => [RoleUtilisateur::Enseignant, 'ENSEIGNANT', 'Compte'],
    ];

    public function run(): void
    {
        $crees = [];

        foreach (self::COMPTES as $login => [$profil, $nom, $prenom]) {
            if (User::withTrashed()->where('login', $login)->exists()) {
                $this->command?->line("  Compte « {$login} » déjà présent : inchangé.");
                continue;
            }

            $motDePasse = Str::random(12);

            User::create([
                'nom'       => $nom,
                'prenom'    => $prenom,
                'login'     => $login,
                'mot_passe' => bcrypt($motDePasse),
                'role'      => $profil->value,
                'role_id'   => $profil->value, // roles.id reprend le code du profil
                'etat'      => 1,
            ]);

            $crees[] = [$login, $motDePasse, $profil->label()];
        }

        if ($crees !== []) {
            $this->command?->table(['Login', 'Mot de passe (à changer)', 'Profil'], $crees);
            $this->command?->warn('Ces mots de passe ne seront plus affichés : notez-les maintenant.');
        }
    }
}
