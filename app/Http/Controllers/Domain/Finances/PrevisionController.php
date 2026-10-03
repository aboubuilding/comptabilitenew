<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\DTO\PrevisionData;
use App\Domain\Finances\Repositories\CentreAnalytiqueRepository;
use App\Domain\Finances\Repositories\PrevisionRepository;
use App\Domain\Finances\Services\PrevisionService;
use App\Domain\Finances\Types\TypePrevision;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StorePrevisionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrevisionController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PrevisionRepository        $previsions,
        private CentreAnalytiqueRepository $centres,
        private PrevisionService           $service,
    ) {
        $this->middleware('permission:previsions.voir')->only(['index']);
        $this->middleware('permission:previsions.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'type', 'centre_analytique_id', 'search']);

        return view('domain.finances.previsions.index', [
            'previsions' => $this->previsions->listePaginee($filters),
            'filters'    => $filters,
            'types'      => TypePrevision::options(),
            'centres'    => $this->centres->listePourSelect(),
            'annees'     => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function store(StorePrevisionRequest $request)
    {
        $data = PrevisionData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(
            fn () => ['prevision' => $this->service->creer($data)],
            'Prévision enregistrée.'
        );
    }

    public function update(StorePrevisionRequest $request, int $prevision)
    {
        $data = PrevisionData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(
            fn () => ['prevision' => $this->service->modifier($prevision, $data)],
            'Prévision mise à jour.'
        );
    }

    public function destroy(int $prevision)
    {
        return $this->runAjax(function () use ($prevision) {
            $this->service->supprimer($prevision);
            return null;
        }, 'Prévision désactivée.');
    }
}