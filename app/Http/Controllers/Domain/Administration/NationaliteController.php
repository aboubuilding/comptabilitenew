<?php

namespace App\Http\Controllers\Domain\Administration;

use App\Domain\Scolarite\Models\Nationalite;
use App\Domain\Scolarite\Repositories\NationaliteRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Référentiel des nationalités (cahier des charges § 4, 6.2 « Référentiels &
 * Paramètres généraux », réservé à l'Administrateur). Les zones et les
 * lignes de transport, autres référentiels cités, ont leurs écrans dans le
 * module Transport.
 */
class NationaliteController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(private NationaliteRepository $nationalites)
    {
        $this->middleware('permission:referentiels.gerer');
    }

    public function index(Request $request): View
    {
        $recherche = trim((string) $request->query('search', ''));

        $nationalites = $this->nationalites->activeQuery()
            ->when($recherche !== '', fn ($q) => $q->where('libelle', 'like', "%{$recherche}%"))
            ->addSelect([
                'nationalites.*',
                'eleves_count' => DB::table('eleves')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('eleves.nationalite_id', 'nationalites.id')
                    ->where('eleves.etat', 1),
            ])
            ->orderBy('libelle')
            ->paginate(20)
            ->withQueryString();

        return view('domain.administration.nationalites.index', compact('nationalites', 'recherche'));
    }

    public function store(Request $request): JsonResponse
    {
        $donnees = $this->valider($request);

        return $this->runAjax(
            fn () => ['nationalite' => $this->nationalites->create($donnees)],
            'Nationalité ajoutée avec succès.'
        );
    }

    public function update(Request $request, Nationalite $nationalite): JsonResponse
    {
        $donnees = $this->valider($request, $nationalite->id);

        return $this->runAjax(function () use ($nationalite, $donnees) {
            $this->nationalites->update($nationalite->id, $donnees);
        }, 'Nationalité modifiée avec succès.');
    }

    public function destroy(Nationalite $nationalite): RedirectResponse
    {
        $utilisee = DB::table('eleves')->where('nationalite_id', $nationalite->id)->where('etat', 1)->exists();

        if ($utilisee) {
            return back()->with('error', 'Impossible de désactiver une nationalité attribuée à des élèves.');
        }

        return $this->runWeb(function () use ($nationalite) {
            $this->nationalites->delete($nationalite->id);
        }, 'administration.nationalites.index', 'Nationalité désactivée avec succès.');
    }

    private function valider(Request $request, ?int $nationaliteId = null): array
    {
        return $request->validate([
            'libelle' => [
                'required', 'string', 'max:150',
                Rule::unique('nationalites', 'libelle')->where('etat', 1)->ignore($nationaliteId),
            ],
        ], [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.unique'   => 'Cette nationalité existe déjà.',
        ]);
    }
}
