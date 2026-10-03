<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\CentreAnalytiqueData;
use App\Domain\Finances\Repositories\CentreAnalytiqueRepository;
use App\Domain\Finances\Services\CentreAnalytiqueService;
use App\Domain\Finances\Types\TypeCentreAnalytique;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreCentreAnalytiqueRequest;
use App\Domain\Administration\Models\User;
use Illuminate\Http\Request;

class CentreAnalytiqueController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private CentreAnalytiqueRepository $centres,
        private CentreAnalytiqueService    $service,
    ) {
        $this->middleware('permission:analytique.voir')->only(['index']);
        $this->middleware('permission:analytique.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type']);

        return view('domain.finances.analytique.centres.index', [
            'centres'   => $this->centres->listePaginee($filters),
            'filters'   => $filters,
            'types'     => TypeCentreAnalytique::options(),
            'users'     => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCentreAnalytiqueRequest $request)
    {
        return $this->runAjax(
            fn () => ['centre' => $this->service->creer(CentreAnalytiqueData::fromRequest($request->validated()))],
            'Centre analytique créé.'
        );
    }

    public function update(StoreCentreAnalytiqueRequest $request, int $centre)
    {
        return $this->runAjax(
            fn () => ['centre' => $this->service->modifier($centre, CentreAnalytiqueData::fromRequest($request->validated()))],
            'Centre analytique mis à jour.'
        );
    }

    public function destroy(int $centre)
    {
        return $this->runAjax(function () use ($centre) {
            $this->service->supprimer($centre);
            return null;
        }, 'Centre analytique désactivé.');
    }
}
