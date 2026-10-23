<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\LigneData;
use App\Domain\ServicesEleves\Repositories\ChauffeurRepository;
use App\Domain\ServicesEleves\Repositories\LigneRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Domain\ServicesEleves\Services\AbonnementBusService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreLigneRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;

/**
 * Lignes de transport : un parcours desservi par un chauffeur et un véhicule
 * pendant une période. Les zones lui appartiennent.
 */
class LigneController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private LigneRepository      $lignes,
        private ChauffeurRepository  $chauffeurs,
        private VoitureRepository    $voitures,
        private AbonnementBusService $abonnements,
    ) {
        $this->middleware('permission:transport.voir')->only(['index']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $filters = ['annee_id' => app(AnneeScolaireContext::class)->id()] + $request->only(['search']);

        return view('domain.services-eleves.transport.lignes.index', [
            'lignes'     => $this->lignes->listePaginee($filters),
            'filters'    => $filters,
            'chauffeurs' => $this->chauffeurs->listePourSelect(),
            'voitures'   => $this->voitures->listePourSelect(),
        ]);
    }

    public function store(StoreLigneRequest $request)
    {
        $data = LigneData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['ligne' => $this->lignes->create($data->toArray())],
            'Ligne créée.'
        );
    }

    public function update(StoreLigneRequest $request, int $ligne)
    {
        $data = LigneData::fromRequest($request->validated());

        return $this->runAjax(function () use ($ligne, $data) {
            DB::transaction(function () use ($ligne, $data) {
                $this->lignes->update($ligne, $data->toArray());
                // Les zones de la ligne et leurs abonnés suivent le chauffeur et le véhicule
                DB::table('zones')->where('ligne_id', $ligne)
                    ->update(['chauffeur_id' => $data->chauffeurId, 'voiture_id' => $data->voitureId, 'updated_at' => now()]);
                $this->abonnements->realigner(ligneId: $ligne);
            });

            return null;
        }, 'Ligne mise à jour.');
    }

    public function destroy(int $ligne)
    {
        return $this->runAjax(function () use ($ligne) {
            $this->lignes->delete($ligne);
            return null;
        }, 'Ligne désactivée.');
    }
}
