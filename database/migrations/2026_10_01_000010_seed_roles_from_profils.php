<?php

use App\Domain\Administration\Types\RoleUtilisateur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Référentiel des profils (table roles) : une ligne par profil du cahier
     * des charges, dont l'identifiant reprend le code du profil (users.role).
     *
     * L'écran Utilisateurs choisit un profil dans cette table (role_id) ; les
     * droits, eux, sont lus sur users.role par la matrice des permissions.
     * Identifiant et code étant égaux, les deux colonnes disent la même chose.
     */
    public function up(): void
    {
        foreach (RoleUtilisateur::cases() as $ordre => $profil) {
            DB::table('roles')->updateOrInsert(
                ['id' => $profil->value],
                [
                    'code'        => Str::upper(Str::snake($profil->name)),
                    'libelle'     => $profil->label(),
                    'description' => 'Domaine : ' . $profil->domainePrincipal(),
                    'niveau'      => $ordre,
                    'etat'        => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }

        // Les utilisateurs existants ne portent que le code historique : on les rattache au référentiel
        DB::table('users')
            ->whereNull('role_id')
            ->whereIn('role', array_map(fn (RoleUtilisateur $p) => $p->value, RoleUtilisateur::cases()))
            ->update(['role_id' => DB::raw('`role`')]);
    }

    public function down(): void
    {
        DB::table('users')->update(['role_id' => null]);
        DB::table('roles')->whereIn('id', array_map(fn (RoleUtilisateur $p) => $p->value, RoleUtilisateur::cases()))->delete();
    }
};
