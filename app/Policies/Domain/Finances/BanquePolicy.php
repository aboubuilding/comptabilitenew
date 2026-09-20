<?php

namespace App\Policies\Domain\Finances;

use App\Domain\Finances\Models\Banque;
use App\Models\User;

class BanquePolicy
{
    /** Voir la liste des banques. */
    public function viewAny(User $user): bool
    {
        return $user->can('banques.voir');
    }

    /** Voir une banque en particulier. */
    public function view(User $user, Banque $banque): bool
    {
        return $user->can('banques.voir');
    }

    /** Créer une banque. */
    public function create(User $user): bool
    {
        return $user->can('banques.gerer');
    }

    /** Modifier une banque. */
    public function update(User $user, Banque $banque): bool
    {
        return $user->can('banques.gerer');
    }

    /** Désactiver une banque (suppression logique). */
    public function delete(User $user, Banque $banque): bool
    {
        return $user->can('banques.gerer');
    }
}