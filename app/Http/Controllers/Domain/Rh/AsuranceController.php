<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\DTO\AsuranceDTO;
use App\Domain\Rh\Exports\AsurancesExport;
use App\Domain\Rh\Models\Asurance;
use App\Domain\Rh\Repositories\AsuranceRepository;
use App\Domain\Rh\Services\AsuranceService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StoreAsuranceRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * CRUD du catalogue des assurances (assureurs / polices).
 */
class AsuranceController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private readonly AsuranceService $service,
        private readonly AsuranceRepository $repository
    ) {
        $this->middleware('permission:assurances.voir')->only(['index', 'export']);
        $this->middleware('permission:assurances.gerer')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filtres    = $request->only(['recherche']);
        $asurances  = $this->repository->listePaginee($filtres, 15);

        return view('domain.rh.asurances.index', compact('asurances', 'filtres'));
    }

    /**
     * Formulaire de création (HTML partiel pour modale).
     */
    public function create()
    {
        return view('domain.rh.asurances._modal_form', [
            'asurance' => null,
            'action'   => route('rh.asurances.store'),
            'method'   => 'POST',
            'titre'    => 'Nouvelle assurance',
        ]);
    }

    public function store(StoreAsuranceRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $dto = AsuranceDTO::fromArray($request->validated());
                $this->service->creer($dto);
            },
            'Assurance créée avec succès.'
        );
    }

    /**
     * Formulaire d'édition (HTML partiel pour modale).
     */
    public function edit(Asurance $asurance)
    {
        return view('domain.rh.asurances._modal_form', [
            'asurance' => $asurance,
            'action'   => route('rh.asurances.update', $asurance),
            'method'   => 'PUT',
            'titre'    => 'Modifier l\'assurance',
        ]);
    }

    public function update(StoreAsuranceRequest $request, Asurance $asurance)
    {
        return $this->runAjax(
            function () use ($request, $asurance) {
                $dto = AsuranceDTO::fromArray($request->validated());
                $this->service->mettreAJour($asurance, $dto);
            },
            'Assurance mise à jour avec succès.'
        );
    }

    public function destroy(Asurance $asurance)
    {
        return $this->runAjax(
            function () use ($asurance) {
                $this->service->desactiver($asurance);
            },
            'Assurance désactivée avec succès.'
        );
    }

    public function export(Request $request)
    {
        $filtres  = $request->only(['recherche']);
        $asurances = $this->repository->listePourExport($filtres);

        $nomFichier = 'catalogue_assurances_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AsurancesExport($asurances), $nomFichier);
    }
}