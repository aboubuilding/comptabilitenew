<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\ChauffeurData;
use App\Domain\ServicesEleves\Repositories\ChauffeurRepository;
use App\Domain\ServicesEleves\Services\TransportService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreChauffeurRequest;
use Illuminate\Http\Request;

class ChauffeurController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ChauffeurRepository $chauffeurs,
        private TransportService    $service,
    ) {
        $this->middleware('permission:transport.voir')->only(['index', 'show']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'statut', 'annee_id']);

        return view('domain.services-eleves.transport.chauffeurs.index', [
            'chauffeurs'   => $this->chauffeurs->listePaginee($filters),
            'filters'      => $filters,
            'permisExpirant' => $this->chauffeurs->permisExpirant(60),
        ]);
    }

    public function show(int $chauffeur)
    {
        return view('domain.services-eleves.transport.chauffeurs.show', [
            'chauffeur' => $this->chauffeurs->findAvecAffectations($chauffeur),
        ]);
    }

    public function store(StoreChauffeurRequest $request)
    {
        return $this->runAjax(
            fn () => ['chauffeur' => $this->service->creerChauffeur(ChauffeurData::fromRequest($request->validated()))],
            'Chauffeur créé.'
        );
    }

    public function update(StoreChauffeurRequest $request, int $chauffeur)
    {
        return $this->runAjax(
            fn () => ['chauffeur' => $this->service->modifierChauffeur($chauffeur, ChauffeurData::fromRequest($request->validated()))],
            'Chauffeur mis à jour.'
        );
    }

    public function destroy(int $chauffeur)
    {
        return $this->runAjax(function () use ($chauffeur) {
            $this->service->supprimerChauffeur($chauffeur);
            return null;
        }, 'Chauffeur désactivé.');
    }
}