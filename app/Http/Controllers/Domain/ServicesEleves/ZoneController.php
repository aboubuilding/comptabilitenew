<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\ZoneData;
use App\Domain\ServicesEleves\Models\Ligne;
use App\Domain\ServicesEleves\Repositories\LigneRepository;
use App\Domain\ServicesEleves\Repositories\ZoneRepository;
use App\Domain\ServicesEleves\Services\AbonnementBusService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreZoneRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;

/**
 * Zones de ramassage : chacune appartient à une ligne (qui fournit chauffeur
 * et véhicule) et porte un tarif mensuel indicatif.
 */
class ZoneController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ZoneRepository       $zones,
        private LigneRepository      $lignes,
        private AbonnementBusService $abonnements,
    ) {
        $this->middleware('permission:transport.voir')->only(['index']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $anneeId = app(AnneeScolaireContext::class)->id();
        $filters = ['annee_id' => $anneeId] + $request->only(['search', 'ligne_id']);

        return view('domain.services-eleves.transport.zones.index', [
            'zones'   => $this->zones->listePaginee($filters),
            'filters' => $filters,
            'lignes'  => $this->lignes->listePourSelect($anneeId),
        ]);
    }

    public function store(StoreZoneRequest $request)
    {
        $data = ZoneData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['zone' => $this->zones->create($this->valeurs($data))],
            'Zone créée.'
        );
    }

    public function update(StoreZoneRequest $request, int $zone)
    {
        $data = ZoneData::fromRequest($request->validated());

        return $this->runAjax(function () use ($zone, $data) {
            DB::transaction(function () use ($zone, $data) {
                $this->zones->update($zone, $this->valeurs($data));
                // Les abonnés de la zone suivent sa (nouvelle) ligne
                $this->abonnements->realigner(zoneId: $zone);
            });

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

    /** Le chauffeur et le véhicule de la zone sont ceux de sa ligne. */
    private function valeurs(ZoneData $data): array
    {
        $ligne = $data->ligneId ? Ligne::find($data->ligneId) : null;

        return [
            'libelle'      => $data->libelle,
            'description'  => $data->description,
            'tarif_base'   => $data->tarifBase,
            'ligne_id'     => $data->ligneId,
            'chauffeur_id' => $ligne?->chauffeur_id,
            'voiture_id'   => $ligne?->voiture_id,
        ];
    }
}
