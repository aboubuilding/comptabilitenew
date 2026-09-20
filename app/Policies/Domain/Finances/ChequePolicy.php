<?php

namespace App\Policies\Domain\Finances;

use App\Domain\Finances\Models\Cheque;
use App\Models\User;

class ChequePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cheques.voir');
    }

    public function view(User $user, Cheque $cheque): bool
    {
        return $user->can('cheques.voir');
    }

    public function create(User $user): bool
    {
        return $user->can('cheques.gerer');
    }

    public function update(User $user, Cheque $cheque): bool
    {
        // Un chèque dont le statut est "final" (encaissé ou rejeté)
        // ne peut plus être modifié — seul le rapprochement reste
        // possible pour le rouvrir le cas échéant.
        return $user->can('cheques.gerer') && ! $cheque->statut?->isFinal();
    }

    public function delete(User $user, Cheque $cheque): bool
    {
        return $user->can('cheques.gerer') && ! $cheque->statut?->isFinal();
    }

    /** Rapprochement : action distincte (rôle caissier/comptable). */
    public function rapprocher(User $user, Cheque $cheque): bool
    {
        return $user->can('cheques.rapprocher');
    }
}