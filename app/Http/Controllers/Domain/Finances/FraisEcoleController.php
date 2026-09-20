<?php

namespace App\Http\Controllers\Admin\Finances;

use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use App\Domain\Scolarite\Repositories\AnneeRepository;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\FraisEcoleRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Premier Controller du domaine Finances — même pattern que Cycle
 * (modale unique, JSON). "Souscrire un frais pour une famille" (cahier
 * des charges) reste hors scope : ça relève de l'écran financier d'un
 * élève/inscription, pas de ce paramétrage.
 */
class FraisEcoleController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private FraisEcoleRepository $fraisEcoles,
        private NiveauRepository $niveaux,
        private PlanEcheancierRepository $plans,
        private AnneeRepository $annees,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    public function index(Request $request): View
    {
        $anneeId = (int) ($request->input('annee_id') ?: $this->anneeContext->id());

        return view('admin.finances.frais-ecoles.index', [
            'fraisEcoles'    => $this->fraisEcoles->paginateWithFilters($request->only([
                'niveau_id', 'type_forfait', 'search',
            ]), $anneeId),
            'niveauxOptions' => $this->niveaux->all(),
            'plansOptions'   => $this->plans->all(),
            'anneesOptions'  => $this->annees->allForSelector(),
            'anneeId'        => $anneeId,
        ]);
    }

    public function edit(FraisEcole $fraisEcole): JsonResponse
    {
        return response()->json(['fraisEcole' => $fraisEcole]);
    }

    public function store(FraisEcoleRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $data = $request->validated();
            $data['annee_id'] = $this->anneeContext->id();

            $fraisEcole = $this->fraisEcoles->create($data);

            return ['fraisEcole' => $fraisEcole];
        }, 'Frais créé avec succès.');
    }

    public function update(FraisEcoleRequest $request, FraisEcole $fraisEcole): JsonResponse
    {
        return $this->runAjax(function () use ($request, $fraisEcole) {
            $this->fraisEcoles->update($fraisEcole->id, $request->validated());
        }, 'Frais modifié avec succès.');
    }

    public function archiver(FraisEcole $fraisEcole): RedirectResponse
    {
        return $this->runWeb(function () use ($fraisEcole) {
            $this->fraisEcoles->delete($fraisEcole->id);
        }, 'finances.frais-ecoles.index', 'Frais archivé avec succès.');
    }
}