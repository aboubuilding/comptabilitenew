<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\VoitureData;
use App\Domain\ServicesEleves\Repositories\ChauffeurRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Domain\ServicesEleves\Services\TransportService;
use App\Domain\ServicesEleves\Types\StatutVehicule;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreVoitureRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Domain\ServicesEleves\Exports\ParcTransportExport;
use App\Support\AnneeScolaireContext;
use Maatwebsite\Excel\Facades\Excel;

class VoitureController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private VoitureRepository   $voitures,
        private ChauffeurRepository $chauffeurs,
        private TransportService    $service,
    ) {
        $this->middleware('permission:transport.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy', 'affecter']);
        $this->middleware('permission:transport.voir')->only(['exportParc']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'statut', 'annee_id']);

        return view('domain.services-eleves.transport.voitures.index', [
            'voitures'    => $this->voitures->listePaginee($filters),
            'filters'     => $filters,
            'statuts'     => StatutVehicule::options(),
            'compteurs'   => $this->voitures->compterParStatut(),
        ]);
    }

    public function show(int $voiture)
    {
        return view('domain.services-eleves.transport.voitures.show', [
            'voiture'    => $this->voitures->findAvecDetails($voiture),
            'chauffeurs' => $this->chauffeurs->listePourSelect(),
            'statuts'    => StatutVehicule::options(),
        ]);
    }

    public function store(StoreVoitureRequest $request)
    {
        return $this->runAjax(
            fn () => ['voiture' => $this->service->creerVoiture(VoitureData::fromRequest($request->validated()))],
            'Véhicule créé.'
        );
    }

    public function update(StoreVoitureRequest $request, int $voiture)
    {
        return $this->runAjax(
            fn () => ['voiture' => $this->service->modifierVoiture($voiture, VoitureData::fromRequest($request->validated()))],
            'Véhicule mis à jour.'
        );
    }

    public function destroy(int $voiture)
    {
        return $this->runAjax(function () use ($voiture) {
            $this->service->supprimerVoiture($voiture);
            return null;
        }, 'Véhicule désactivé.');
    }

    public function affecter(Request $request, int $voiture)
    {
        $data = $request->validate([
            'chauffeur_id'     => ['required', 'exists:chauffeurs,id'],
            'date_debut'       => ['required', 'date'],
            'date_fin'         => ['nullable', 'date', 'after:date_debut'],
            'motif'            => ['nullable', 'string', 'max:500'],
            'type_affectation' => ['nullable', 'integer', 'in:1,2'],
        ]);

        return $this->runAjax(function () use ($voiture, $data) {
            $this->service->affecter($voiture, (int) $data['chauffeur_id'], $data);
            return null;
        }, 'Affectation enregistrée.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters  = $request->only(['search', 'statut']);
        $voitures = $this->voitures->listePaginee($filters, perPage: 10000);

        $filename = 'voitures_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($voitures) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Plaque', 'Marque', 'Modèle', 'Année', 'Places',
                'Kilométrage', 'Statut', 'Chauffeur actuel',
            ], ';');

            foreach ($voitures as $v) {
                fputcsv($out, [
                    $v->plaque,
                    $v->marque,
                    $v->modele,
                    $v->annee_fabrication,
                    $v->nombre_place,
                    number_format((float) $v->kilometrage_actuel, 0, ',', ' '),
                    $v->statut?->label(),
                    $v->chauffeurActuel()?->nom_complet,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }


    public function exportParc()
{
    $anneeId = app(AnneeScolaireContext::class)->id();

    $filename = 'parc_transport_' . now()->format('Ymd_His') . '.xlsx';

    return Excel::download(new ParcTransportExport($anneeId), $filename);
}
}