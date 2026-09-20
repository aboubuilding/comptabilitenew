<?php

namespace App\Http\Controllers\Admin\Scolarite;

use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Scolarite\Repositories\CycleRepository;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\NiveauRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ajout/modification via modale — même pattern que CycleController.
 * edit()/store()/update() répondent en JSON, consommés en AJAX par la
 * modale unique du template index. Plus de create() : la modale
 * remplace la page dédiée. destroy() reste un POST classique.
 */
class NiveauController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private NiveauRepository $niveaux,
        private CycleRepository $cycles,
    ) {
    }

    public function index(Request $request): View
    {
        return view('admin.scolarite.niveaux.index', [
            'niveaux'       => $this->niveaux->paginateWithFilters($request->only(['cycle_id', 'search'])),
            'cyclesOptions' => $this->cycles->all(),
        ]);
    }

    /**
     * Alimente la modale en mode édition, appelé en AJAX.
     */
    public function edit(Niveau $niveau): JsonResponse
    {
        return response()->json(['niveau' => $niveau]);
    }

    public function store(NiveauRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $niveau = $this->niveaux->create($request->validated());

            return ['niveau' => $niveau];
        }, 'Niveau créé avec succès.');
    }

    public function update(NiveauRequest $request, Niveau $niveau): JsonResponse
    {
        return $this->runAjax(function () use ($request, $niveau) {
            $this->niveaux->update($niveau->id, $request->validated());
        }, 'Niveau modifié avec succès.');
    }

    public function destroy(Niveau $niveau): RedirectResponse
    {
        if (! $this->niveaux->canDelete($niveau->id)) {
            return back()->with('error', 'Impossible de supprimer ce niveau.');
        }

        return $this->runWeb(function () use ($niveau) {
            $this->niveaux->delete($niveau->id);
        }, 'scolarite.niveaux.index', 'Niveau supprimé avec succès.');
    }
}