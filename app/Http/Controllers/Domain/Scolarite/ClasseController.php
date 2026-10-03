<?php

namespace App\Http\Controllers\Domain\Scolarite;

use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Repositories\ClasseRepository;
use App\Domain\Scolarite\Repositories\CycleRepository;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Scolarite\ClasseRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Classes de l'année scolaire de travail (cahier des charges § 4, 1.4 :
 * « Créer / modifier cycles, niveaux et classes »). Même fonctionnement
 * que les niveaux : ajout et modification dans une fenêtre modale.
 */
class ClasseController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ClasseRepository $classes,
        private NiveauRepository $niveaux,
        private CycleRepository $cycles,
        private AnneeScolaireContext $annee,
    ) {
        $this->middleware('permission:structure.voir')->only(['index', 'edit']);
        $this->middleware('permission:structure.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request): View
    {
        return view('domain.scolarite.classes.index', [
            'classes'        => $this->classes->paginateWithFilters(
                $request->only(['cycle_id', 'niveau_id', 'search']),
                (int) $this->annee->id()
            ),
            'cyclesOptions'  => $this->cycles->all(),
            'niveauxOptions' => $this->niveaux->listePourSelect(),
        ]);
    }

    public function edit(Classe $classe): JsonResponse
    {
        return response()->json(['classe' => $classe->only(['id', 'libelle', 'emplacement', 'niveau_id'])]);
    }

    public function store(ClasseRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            return ['classe' => $this->classes->create($this->donnees($request))];
        }, 'Classe créée avec succès.');
    }

    public function update(ClasseRequest $request, Classe $classe): JsonResponse
    {
        return $this->runAjax(function () use ($request, $classe) {
            $donnees = $this->donnees($request);
            unset($donnees['annee_id']); // une classe ne change pas d'année

            $this->classes->update($classe->id, $donnees);
        }, 'Classe modifiée avec succès.');
    }

    public function destroy(Classe $classe): RedirectResponse
    {
        if ($this->classes->aDesInscrits($classe->id)) {
            return back()->with('error', 'Impossible de désactiver une classe qui compte des élèves inscrits.');
        }

        return $this->runWeb(function () use ($classe) {
            $this->classes->delete($classe->id);
        }, 'scolarite.classes.index', 'Classe désactivée avec succès.');
    }

    /** Le cycle d'une classe est celui de son niveau ; l'année est l'année de travail. */
    private function donnees(ClasseRequest $request): array
    {
        $donnees = $request->validated();
        $niveau  = $this->niveaux->find((int) $donnees['niveau_id']);

        return $donnees + [
            'cycle_id' => $niveau?->cycle_id,
            'annee_id' => $this->annee->id(),
        ];
    }
}
