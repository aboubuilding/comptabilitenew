<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\ChauffeurRepository;
use App\Domain\ServicesEleves\Repositories\EntretienVehiculeRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreEntretienRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EntretienVehiculeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private EntretienVehiculeRepository $entretiens,
        private VoitureRepository           $voitures,
        private ChauffeurRepository         $chauffeurs,
    ) {
        $this->middleware('permission:transport.voir')->only(['index', 'export']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'voiture_id', 'type_entretien', 'search', 'date_debut', 'date_fin']);
        $anneeId = app(AnneeScolaireContext::class)->id();

        return view('domain.services-eleves.transport.entretiens.index', [
            'entretiens'  => $this->entretiens->listePaginee($filters),
            'filters'     => $filters,
            'voitures'    => $this->voitures->listePourSelect(),
            'chauffeurs'  => $this->chauffeurs->listePourSelect(),
            'types'       => $this->entretiens->typesDistincts(),
            'stats'       => $this->entretiens->stats($anneeId, $filters['date_debut'] ?? null, $filters['date_fin'] ?? null),
            'parType'     => $this->entretiens->parType($anneeId, $filters['date_debut'] ?? null, $filters['date_fin'] ?? null),
        ]);
    }

    public function store(StoreEntretienRequest $request)
    {
        return $this->runAjax(function () use ($request) {
            $data      = $request->validated();
            $entretien = $this->entretiens->create($data);
            app(\App\Domain\ServicesEleves\Services\TransportService::class)->releverKilometrage((int) $data['voiture_id'], $data['kilometrage'] ?? null);

            return ['entretien' => $entretien];
        }, 'Entretien enregistré.');
    }

    public function update(StoreEntretienRequest $request, int $entretien)
    {
        return $this->runAjax(function () use ($request, $entretien) {
            $data = $request->validated();
            $this->entretiens->update($entretien, $data);
            app(\App\Domain\ServicesEleves\Services\TransportService::class)->releverKilometrage((int) $data['voiture_id'], $data['kilometrage'] ?? null);
            return null;
        }, 'Entretien mis à jour.');
    }

    public function destroy(int $entretien)
    {
        return $this->runAjax(function () use ($entretien) {
            $this->entretiens->delete($entretien);
            return null;
        }, 'Entretien désactivé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'voiture_id', 'type_entretien', 'search', 'date_debut', 'date_fin']);
        $entretiens = $this->entretiens->listePaginee($filters, perPage: 10000);

        $filename = 'entretiens_vehicules_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($entretiens) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Date', 'Véhicule', 'Type', 'Kilométrage', 'Coût',
                'Effectué par', 'Observations',
            ], ';');

            foreach ($entretiens as $e) {
                fputcsv($out, [
                    optional($e->date_entretien)->format('d/m/Y'),
                    $e->voiture?->plaque,
                    $e->type_entretien,
                    number_format((float) $e->kilometrage, 0, ',', ' '),
                    number_format((float) $e->cout, 2, ',', ' '),
                    $e->chauffeur?->nom_complet,
                    $e->observations,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}