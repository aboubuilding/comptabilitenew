<?php

namespace App\Http\Controllers\Admin\Finances;

use App\Domain\Finances\Models\PlanEcheancier;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\PlanEcheancierRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD minimal du plan lui-même (nom/description) — même pattern que
 * Cycle. La gestion des lignes (échéances) du plan est un écran de
 * détail à part, pas encore construit (prochaine étape, comme la
 * gestion des parents/fratrie sur Espaces).
 */
class PlanEcheancierController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PlanEcheancierRepository $plans,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    public function index(Request $request): View
    {
        $anneeId = (int) ($request->input('annee_id') ?: $this->anneeContext->id());

        return view('admin.finances.plan-echeanciers.index', [
            'plans'   => $this->plans->activeQuery()
                ->where('annee_id', $anneeId)
                ->withCount('lignes')
                ->orderBy('nom')
                ->paginate(20),
            'anneeId' => $anneeId,
        ]);
    }

    public function edit(PlanEcheancier $planEcheancier): JsonResponse
    {
        return response()->json(['plan' => $planEcheancier]);
    }

    public function store(PlanEcheancierRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $data = $request->validated();
            $data['annee_id'] = $this->anneeContext->id();

            $plan = $this->plans->create($data);

            return ['plan' => $plan];
        }, 'Plan d\'échéancier créé avec succès.');
    }

    public function update(PlanEcheancierRequest $request, PlanEcheancier $planEcheancier): JsonResponse
    {
        return $this->runAjax(function () use ($request, $planEcheancier) {
            $this->plans->update($planEcheancier->id, $request->validated());
        }, 'Plan modifié avec succès.');
    }

    public function archiver(PlanEcheancier $planEcheancier): RedirectResponse
    {
        return $this->runWeb(function () use ($planEcheancier) {
            $this->plans->delete($planEcheancier->id);
        }, 'finances.plan-echeanciers.index', 'Plan archivé avec succès.');
    }
}