<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\ZoneData;
use App\Domain\ServicesEleves\Repositories\ChauffeurRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Domain\ServicesEleves\Repositories\ZoneRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreZoneRequest;

class ZoneController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ZoneRepository      $zones,
        private ChauffeurRepository $chauffeurs,
        private VoitureRepository   $voitures,
    ) {
        $this->middleware('permission:transport.voir')->only(['index']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $filters = $request->only(['search', 'annee_id']);

        return view('domain.services-eleves.transport.zones.index', [
            'zones'      => $this->zones->listePaginee($filters),
            'filters'    => $filters,
            'chauffeurs' => $this->chauffeurs->listePourSelect(),
            'voitures'   => $this->voitures->listePourSelect(),
        ]);
    }

    public function store(StoreZoneRequest $request)
    {
        $data = ZoneData::fromRequest($request->validated());

        return $this->runAjax(function () use ($data) {
            return ['zone' => $this->zones->create([
                'libelle'      => $data->libelle,
                'description'  => $data->description,
                'chauffeur_id' => $data->chauffeurId,
                'voiture_id'   => $data->voitureId,
            ])];
        }, 'Zone créée.');
    }

    public function update(StoreZoneRequest $request, int $zone)
    {
        $data = ZoneData::fromRequest($request->validated());

        return $this->runAjax(function () use ($zone, $data) {
            $this->zones->update($zone, [
                'libelle'      => $data->libelle,
                'description'  => $data->description,
                'chauffeur_id' => $data->chauffeurId,
                'voiture_id'   => $data->voitureId,
            ]);
            return null;
        }, 'Zone mise à jour.');
    }

    public function destroy(int $zone)
    {
        return $this->runAjax(function () use ($zone) {
            $this->zones->delete($zone);
            return null;
        }, 'Zone désactivée.');
    }
}