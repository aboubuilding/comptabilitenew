<?php

namespace App\Support;

use App\Domain\Administration\Types\RoleUtilisateur;

/**
 * Lecture de la matrice profil → permissions (config/permissions.php).
 *
 * Point d'entrée unique des droits : le Gate (AuthServiceProvider), le
 * middleware `permission:` et les @can des vues passent tous par ici.
 */
class Permissions
{
    /** L'Administrateur a tous les droits ; les autres profils suivent la matrice. */
    public function autorise(?RoleUtilisateur $role, string $permission): bool
    {
        if ($role === null) {
            return false;
        }

        if ($role === RoleUtilisateur::Administrateur) {
            return true;
        }

        if (in_array($permission, config('permissions.reservees_administrateur', []), true)) {
            return false;
        }

        foreach ($this->pour($role) as $accordee) {
            if ($this->correspond($accordee, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Un utilisateur qui cumule plusieurs profils (cahier des charges § 3) a
     * les droits de chacun d'eux.
     *
     * @param RoleUtilisateur[] $profils
     */
    public function autoriseUnDesProfils(array $profils, string $permission): bool
    {
        foreach ($profils as $profil) {
            if ($this->autorise($profil, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Permissions inscrites dans la matrice pour un profil (jokers non développés).
     *
     * @return string[]
     */
    public function pour(RoleUtilisateur $role): array
    {
        return config('permissions.profils.' . $role->name, []);
    }

    /** « module.* » couvre toutes les actions du module, « *.voir » la consultation de tous. */
    private function correspond(string $accordee, string $permission): bool
    {
        if ($accordee === $permission) {
            return true;
        }

        [$module, $action] = array_pad(explode('.', $permission, 2), 2, '');
        [$moduleAccorde, $actionAccordee] = array_pad(explode('.', $accordee, 2), 2, '');

        return ($moduleAccorde === '*' || $moduleAccorde === $module)
            && ($actionAccordee === '*' || $actionAccordee === $action);
    }
}
