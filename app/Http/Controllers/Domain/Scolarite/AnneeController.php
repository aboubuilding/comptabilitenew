<?php

namespace App\Http\Controllers\Admin\Scolarite;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Domain\Scolarite\Repositories\CycleRepository;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Domain\Scolarite\Services\AnneeService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scolarite\AnneeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Nombre de classes par cycle et niveau" et l'arborescence cycles →
 * niveaux → classes (cahier des charges §4, 1.4) ne sont PAS encore
 * implémentées : le modèle Classe n'existe pas encore dans le projet.
 * En attendant, l'index affiche un résumé cycles/niveaux — à enrichir
 * dès que le module Classes sera construit.
 */
class AnneeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private AnneeRepository $annees,
        private AnneeService $anneeService,
        private CycleRepository $cycles,
        private NiveauRepository $niveaux,
    ) {
    }

    public function index(Request $request): View
    {
        return view('admin.scolarite.annees.index', [
            'annees'       => $this->annees->paginateWithFilters($request->only(['search'])),
            'cyclesCount'  => $this->cycles->activeQuery()->count(),
            'niveauxCount' => $this->niveaux->activeQuery()->count(),
        ]);
    }

    public function edit(Annee $annee): JsonResponse
    {
        return response()->json(['annee' => $annee]);
    }

    public function store(AnneeRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $annee = $this->annees->create($request->validated());

            return ['annee' => $annee];
        }, 'Année scolaire créée avec succès.');
    }

    public function update(AnneeRequest $request, Annee $annee): JsonResponse
    {
        return $this->runAjax(function () use ($request, $annee) {
            $this->annees->update($annee->id, $request->validated());
        }, 'Année scolaire modifiée avec succès.');
    }

    /**
     * Action métier distincte d'une simple modification : clôturer une
     * année change son statut, pas ses attributs de formulaire — d'où sa
     * propre route/méthode plutôt qu'un passage par update().
     */
    public function cloturer(Annee $annee): RedirectResponse
    {
        return $this->runWeb(function () use ($annee) {
            $this->anneeService->cloturer($annee);
        }, 'scolarite.annees.index', 'Année scolaire clôturée avec succès.');
    }
}