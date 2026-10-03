<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Administration\Models\User;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Services\CaisseService;
use App\Domain\Finances\Types\StatutEcartCaisse;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validation des écarts de caisse par la Direction
 * (cahier des charges 2.4 et règle « Gestion des caisses et des écarts »).
 */
class EcartCaisseController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private CaisseRepository $caisses,
        private CaisseService    $service,
    ) {
        $this->middleware('permission:caisses.valider_ecart')->only(['index', 'statuer']);
    }

    /** Écarts en attente de décision par défaut ; le filtre donne accès à l'historique. */
    public function index(Request $request)
    {
        $filters = $request->only(['statut_ecart', 'responsable_id', 'annee_id']);
        $anneeId = isset($filters['annee_id']) ? (int) $filters['annee_id'] : app(AnneeScolaireContext::class)->id();

        return view('domain.finances.caisses.ecarts', [
            'ecarts'    => $this->caisses->ecartsPagines($filters),
            'filters'   => $filters,
            'compteurs' => $this->caisses->compterEcartsParStatut($anneeId),
            'statuts'   => collect(StatutEcartCaisse::options())->except(StatutEcartCaisse::AUCUN->value)->all(),
            'decisions' => StatutEcartCaisse::decisions(),
            'caissiers' => User::query()->orderBy('nom')->get(['id', 'nom', 'prenom']),
        ]);
    }

    public function statuer(Request $request, int $caisse)
    {
        $data = $request->validate([
            'decision'    => ['required', Rule::in(array_map(fn (StatutEcartCaisse $d) => $d->value, StatutEcartCaisse::decisions()))],
            // Obligatoire sauf pour une validation pure et simple
            'commentaire' => ['nullable', 'required_unless:decision,' . StatutEcartCaisse::VALIDE->value, 'string', 'min:5', 'max:1000'],
        ], [
            'commentaire.required_unless' => "Un commentaire est obligatoire pour refuser un écart ou demander une vérification.",
        ]);

        $decision = StatutEcartCaisse::from($data['decision']);

        return $this->runWeb(
            fn () => $this->service->statuerSurEcart($caisse, $decision, (int) Auth::id(), $data['commentaire'] ?? null),
            'finances.caisses.ecarts.index',
            match ($decision) {
                StatutEcartCaisse::VALIDE  => "Écart validé.",
                StatutEcartCaisse::REFUSE  => "Écart refusé.",
                default                    => "Écart placé en vérification.",
            }
        );
    }
}
