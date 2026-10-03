<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\DTO\AssureDTO;
use App\Domain\Rh\Models\Asurance;
use App\Domain\Rh\Models\Assure;
use App\Domain\Rh\Models\Employe;
use App\Domain\Rh\Repositories\AssureRepository;
use App\Domain\Rh\Services\AssureService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StoreAssureRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Domain\Rh\Exports\AssuresExport;

/**
 * Gestion des assurances du personnel (Module 5.3).
 * Création et modification via modales AJAX.
 */
class AssureController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private readonly AssureService $service,
        private readonly AssureRepository $repository
    ) {
        $this->middleware('permission:assurances.voir')->only(['index', 'export']);
        $this->middleware('permission:assurances.gerer')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Liste des assurés avec filtres.
     */
    public function index(Request $request)
    {
        $filtres = $request->only(['employe_id', 'assurance_id', 'statut', 'recherche']);

        $assures    = $this->repository->listePaginee($filtres, 15);
        $assurances = Asurance::actif()->orderBy('nom_assureur')->get();
        $employes   = Employe::where('etat', 1)->orderBy('nom_prenom')->get();
        $stats      = $this->service->statistiques();

        return view('domain.rh.assures.index', compact(
            'assures', 'assurances', 'employes', 'stats', 'filtres'
        ));
    }

    /**
     * Formulaire de création (retourné en HTML partiel pour la modale).
     */
    public function create()
    {
        $assurances = Asurance::actif()->orderBy('nom_assureur')->get();
        $employes   = Employe::where('etat', 1)->orderBy('nom_prenom')->get();

        return view('domain.rh.assures._modal_form', [
            'assure'     => null,
            'assurances' => $assurances,
            'employes'   => $employes,
            'action'     => route('rh.assures.store'),
            'method'     => 'POST',
            'titre'      => 'Nouvelle souscription d\'assurance',
        ]);
    }

    /**
     * Enregistrement d'une nouvelle souscription (AJAX).
     */
    public function store(StoreAssureRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $dto = AssureDTO::fromArray($request->validated());
                $this->service->souscrire($dto);
            },
            'Assurance souscrite avec succès.'
        );
    }

    /**
     * Formulaire d'édition (retourné en HTML partiel pour la modale).
     */
    public function edit(Assure $assure)
    {
        $assure->load(['employe', 'assurance']);
        $assurances = Asurance::actif()->orderBy('nom_assureur')->get();
        $employes   = Employe::where('etat', 1)->orderBy('nom_prenom')->get();

        return view('domain.rh.assures._modal_form', [
            'assure'     => $assure,
            'assurances' => $assurances,
            'employes'   => $employes,
            'action'     => route('rh.assures.update', $assure),
            'method'     => 'PUT',
            'titre'      => 'Modifier la souscription',
        ]);
    }

    /**
     * Mise à jour d'une souscription (AJAX).
     */
    public function update(StoreAssureRequest $request, Assure $assure)
    {
        return $this->runAjax(
            function () use ($request, $assure) {
                $dto = AssureDTO::fromArray($request->validated());
                $this->service->mettreAJour($assure, $dto);
            },
            'Assurance mise à jour avec succès.'
        );
    }

    /**
     * Désactivation (archivage logique) — AJAX.
     */
    public function destroy(Assure $assure)
    {
        return $this->runAjax(
            function () use ($assure) {
                $this->service->desactiver($assure);
            },
            'Assurance désactivée avec succès.'
        );
    }

    /**
     * Export Excel de la liste filtrée.
     */
    public function export(Request $request)
    {
        $filtres = $request->only(['employe_id', 'assurance_id', 'statut']);

        $assures = $this->repository->listePourExport($filtres);

        $nomFichier = 'assurances_personnel_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new AssuresExport($assures), $nomFichier);
    }
}