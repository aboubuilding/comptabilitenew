<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\PrevisionData;
use App\Domain\Finances\Repositories\PrevisionRepository;
use App\Domain\Finances\Services\PrevisionService;
use App\Domain\Scolarite\Models\Annee;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StorePrevisionRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lignes budgétaires (ancien écran « Prévisions budgétaires ») : un libellé et
 * un montant global par ligne ; les dépenses imputées à la ligne en consomment
 * le montant. Une ligne montre ses dépenses et les caisses qui les ont décaissées.
 */
class PrevisionController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PrevisionRepository $previsions,
        private PrevisionService    $service,
    ) {
        $this->middleware('permission:previsions.voir')->only(['index', 'depenses']);
        $this->middleware('permission:previsions.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $anneeId = app(AnneeScolaireContext::class)->id();
        $filters = ['annee_id' => $anneeId] + $request->only(['search']);
        $lignes = $this->previsions->listePaginee($filters);
        $toutes = $this->previsions->listePaginee(['annee_id' => $anneeId], 10000);

        return view('domain.finances.previsions.index', [
            'previsions' => $lignes,
            'filters'    => $filters,
            'annee'      => Annee::find($anneeId),
            'centres'    => app(\App\Domain\Finances\Repositories\CentreAnalytiqueRepository::class)->listePourSelect(),
            'totaux'     => [
                'global'   => round((float) $toutes->sum('montant_previsionnel'), 2),
                'depenses' => round((float) $toutes->sum('montant_depenses'), 2),
            ],
        ]);
    }

    /** Fragment HTML : les dépenses de la ligne et les caisses qui les ont décaissées. */
    public function depenses(int $prevision)
    {
        $ligne = $this->previsions->findOrFail($prevision);

        return view('domain.finances.previsions.partials.depenses', [
            'ligne'    => $ligne,
            'depenses' => $this->previsions->depensesDe($ligne->id),
        ]);
    }

    public function store(StorePrevisionRequest $request)
    {
        $data = PrevisionData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(
            fn () => ['prevision' => $this->service->creer($data)],
            'Ligne budgétaire enregistrée.'
        );
    }

    public function update(StorePrevisionRequest $request, int $prevision)
    {
        $data = PrevisionData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(
            fn () => ['prevision' => $this->service->modifier($prevision, $data)],
            'Ligne budgétaire mise à jour.'
        );
    }

    public function destroy(int $prevision)
    {
        return $this->runAjax(function () use ($prevision) {
            $this->service->supprimer($prevision);
            return null;
        }, 'Ligne budgétaire désactivée.');
    }
}
