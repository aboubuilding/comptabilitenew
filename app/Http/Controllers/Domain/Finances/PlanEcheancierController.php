<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\DTO\PlanEcheancierData;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use App\Domain\Finances\Services\PlanEcheancierService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StorePlanEcheancierRequest;
use Illuminate\Http\Request;

class PlanEcheancierController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PlanEcheancierRepository $plans,
        private PlanEcheancierService    $service,
    ) {
        $this->middleware('permission:frais.voir')->only(['index', 'show']);
        $this->middleware('permission:frais.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'search']);

        return view('domain.finances.plan-echeanciers.index', [
            'plans'   => $this->plans->listePaginee($filters),
            'filters' => $filters,
            'annees'  => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function show(int $plan)
    {
        return response()->json([
            'plan' => $this->plans->findAvecLignes($plan),
        ]);
    }

    public function store(StorePlanEcheancierRequest $request)
    {
        return $this->runAjax(
            fn () => ['plan' => $this->service->creer(PlanEcheancierData::fromRequest($request->validated()))],
            'Plan d\'échéancier créé.'
        );
    }

    public function update(StorePlanEcheancierRequest $request, int $plan)
    {
        return $this->runAjax(
            fn () => ['plan' => $this->service->modifier($plan, PlanEcheancierData::fromRequest($request->validated()))],
            'Plan d\'échéancier mis à jour.'
        );
    }

    public function destroy(int $plan)
    {
        return $this->runAjax(function () use ($plan) {
            $this->service->supprimer($plan);
            return null;
        }, 'Plan désactivé.');
    }
}