<?php

namespace App\Http\Controllers\Domain\Administration;

use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Concerns\LogsActions;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * « Mon profil » : chaque utilisateur consulte son compte et change son
 * propre mot de passe (notamment le mot de passe provisoire remis par
 * l'Administrateur). Aucune permission particulière : on n'agit que sur
 * son propre compte.
 */
class ProfilController extends Controller
{
    use HandlesControllerErrors;
    use LogsActions;

    public function show(): View
    {
        $utilisateur = Auth::user();

        return view('domain.administration.profil', [
            'utilisateur' => $utilisateur,
            'profils'     => $utilisateur->profils(),
        ]);
    }

    public function changerMotDePasse(Request $request): RedirectResponse
    {
        $utilisateur = Auth::user();

        $donnees = $request->validate([
            'mot_passe_actuel' => ['required', 'string'],
            'mot_passe'        => ['required', 'string', 'confirmed', 'different:mot_passe_actuel', Password::min(8)->letters()->numbers()],
        ], [
            'mot_passe_actuel.required' => 'Le mot de passe actuel est obligatoire.',
            'mot_passe.required'        => 'Le nouveau mot de passe est obligatoire.',
            'mot_passe.confirmed'       => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'mot_passe.different'       => "Le nouveau mot de passe doit être différent de l'actuel.",
        ], [
            'mot_passe' => 'nouveau mot de passe',
        ]);

        if (! Hash::check($donnees['mot_passe_actuel'], $utilisateur->getAuthPassword())) {
            throw ValidationException::withMessages([
                'mot_passe_actuel' => 'Le mot de passe actuel est incorrect.',
            ]);
        }

        return $this->runWeb(function () use ($utilisateur, $donnees) {
            $utilisateur->forceFill(['mot_passe' => Hash::make($donnees['mot_passe'])])->save();

            $this->logAction('update', 'User', $utilisateur->id, 'Changement de son mot de passe');
        }, 'profil.show', 'Votre mot de passe a été modifié.');
    }
}
