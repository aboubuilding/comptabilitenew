<?php

namespace App\Http\Controllers\Admin\Scolarite;

use App\Domain\Scolarite\Models\Cycle;
use App\Domain\Scolarite\Repositories\CycleRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\CycleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ajout/modification via modale — pas de pages create/edit séparées.
 * edit()/store()/update() répondent en JSON, consommés en AJAX par la
 * modale unique du template index. destroy() reste un POST classique.
 */
class CycleController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(private CycleRepository $cycles)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.scolarite.cycles.index', [
            'cycles' => $this->cycles->paginateWithFilters($request->only(['search', 'etat'])),
        ]);
    }

    public function edit(Cycle $cycle): JsonResponse
    {
        return response()->json(['cycle' => $cycle]);
    }

    public function store(CycleRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $cycle = $this->cycles->create($request->validated());

            return ['cycle' => $cycle];
        }, 'Cycle créé avec succès.');
    }

    public function update(CycleRequest $request, Cycle $cycle): JsonResponse
    {
        return $this->runAjax(function () use ($request, $cycle) {
            $this->cycles->update($cycle->id, $request->validated());
        }, 'Cycle modifié avec succès.');
    }

    public function destroy(Cycle $cycle): RedirectResponse
    {
        if ($this->cycles->hasNiveaux($cycle->id)) {
            return back()->with('error', 'Impossible de désactiver un cycle qui a des niveaux actifs.');
        }

        return $this->runWeb(function () use ($cycle) {
            $this->cycles->delete($cycle->id);
        }, 'scolarite.cycles.index', 'Cycle désactivé avec succès.');
    }
}