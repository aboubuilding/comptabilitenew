<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\CarburantVehiculeRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarburantVehiculeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private CarburantVehiculeRepository $carburants,
        private VoitureRepository           $voitures,
    ) {
        $this->middleware('permission:transport.voir')->only(['index', 'export']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'voiture_id', 'search', 'date_debut', 'date_fin']);
        $anneeId = app(AnneeScolaireContext::class)->id();

        return view('domain.services-eleves.transport.carburants.index', [
            'carburants'   => $this->carburants->listePaginee($filters),
            'filters'      => $filters,
            'voitures'     => $this->voitures->listePourSelect(),
            'stats'        => $this->carburants->stats($anneeId, $filters['date_debut'] ?? null, $filters['date_fin'] ?? null),
            'parVehicule'  => $this->carburants->parVehicule($anneeId, $filters['date_debut'] ?? null, $filters['date_fin'] ?? null),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'voiture_id'      => ['required', 'exists:voitures,id'],
            'date_plein'      => ['required', 'date'],
            'quantite_litres' => ['required', 'numeric', 'min:0.01'],
            'prix_unitaire'   => ['required', 'numeric', 'min:0'],
            'kilometrage'     => ['required', 'integer', 'min:0'],
            'station_service' => ['nullable', 'string', 'max:150'],
            'facture'         => ['nullable', 'string', 'max:100'],
        ]);

        // Calcul automatique du montant total
        $data['montant_total'] = round($data['quantite_litres'] * $data['prix_unitaire'], 2);

        return $this->runAjax(function () use ($data) {
            $carburant = $this->carburants->create($data);
            app(\App\Domain\ServicesEleves\Services\TransportService::class)->releverKilometrage((int) $data['voiture_id'], $data['kilometrage']);

            return ['carburant' => $carburant];
        }, 'Plein enregistré.');
    }

    public function update(Request $request, int $carburant)
    {
        $data = $request->validate([
            'voiture_id'      => ['required', 'exists:voitures,id'],
            'date_plein'      => ['required', 'date'],
            'quantite_litres' => ['required', 'numeric', 'min:0.01'],
            'prix_unitaire'   => ['required', 'numeric', 'min:0'],
            'kilometrage'     => ['required', 'integer', 'min:0'],
            'station_service' => ['nullable', 'string', 'max:150'],
            'facture'         => ['nullable', 'string', 'max:100'],
        ]);

        $data['montant_total'] = round($data['quantite_litres'] * $data['prix_unitaire'], 2);

        return $this->runAjax(function () use ($carburant, $data) {
            $this->carburants->update($carburant, $data);
            app(\App\Domain\ServicesEleves\Services\TransportService::class)->releverKilometrage((int) $data['voiture_id'], $data['kilometrage']);
            return null;
        }, 'Plein mis à jour.');
    }

    public function destroy(int $carburant)
    {
        return $this->runAjax(function () use ($carburant) {
            $this->carburants->delete($carburant);
            return null;
        }, 'Plein supprimé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'voiture_id', 'search', 'date_debut', 'date_fin']);
        $carburants = $this->carburants->listePaginee($filters, perPage: 10000);

        $filename = 'carburant_vehicules_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($carburants) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Date', 'Véhicule', 'Litres', 'Prix unitaire',
                'Montant', 'Kilométrage', 'Station', 'Facture',
            ], ';');

            foreach ($carburants as $c) {
                fputcsv($out, [
                    optional($c->date_plein)->format('d/m/Y'),
                    $c->voiture?->plaque,
                    number_format((float) $c->quantite_litres, 2, ',', ' '),
                    number_format((float) $c->prix_unitaire, 2, ',', ' '),
                    number_format((float) $c->montant_total, 2, ',', ' '),
                    number_format((float) $c->kilometrage, 0, ',', ' '),
                    $c->station_service,
                    $c->facture,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}