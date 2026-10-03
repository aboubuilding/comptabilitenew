<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Repositories\TrancheRepository;
use App\Domain\Finances\Services\TrancheService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreTrancheRequest;
use Illuminate\Http\Request;

class TrancheController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private TrancheRepository   $tranches,
        private FraisEcoleRepository $frais,
        private TrancheService      $service,
    ) {
        $this->middleware('permission:frais.voir')->only(['index']);
        $this->middleware('permission:frais.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['frais_ecole_id', 'search']);

        return view('domain.finances.tranches.index', [
            'tranches' => $this->tranches->listePaginee($filters),
            'filters'  => $filters,
            'frais'    => $this->frais->listePourSelect(),
        ]);
    }

    public function store(StoreTrancheRequest $request)
    {
        return $this->runAjax(
            fn () => ['tranche' => $this->service->creer($request->validated())],
            'Tranche créée.'
        );
    }

    public function update(StoreTrancheRequest $request, int $tranche)
    {
        return $this->runAjax(
            fn () => ['tranche' => $this->service->modifier($tranche, $request->validated())],
            'Tranche mise à jour.'
        );
    }

    public function destroy(int $tranche)
    {
        return $this->runAjax(function () use ($tranche) {
            $this->service->supprimer($tranche);
            return null;
        }, 'Tranche désactivée.');
    }
}