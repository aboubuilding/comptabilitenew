<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\AssuranceVehiculeRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssuranceVehiculeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private AssuranceVehiculeRepository $assurances,
        private VoitureRepository           $voitures,
    ) {
        $this->middleware('permission:transport.voir')->only(['index', 'export']);
        $this->middleware('permission:transport.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['voiture_id', 'statut', 'search', 'date_debut', 'date_fin']);

        return view('domain.services-eleves.transport.assurances.index', [
            'assurances' => $this->assurances->listePaginee($filters),
            'filters'    => $filters,
            'voitures'   => $this->voitures->listePourSelect(),
            'stats'      => $this->assurances->stats(),
            'bientot'    => $this->assurances->expirantBientot(30),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'voiture_id'          => ['required', 'exists:voitures,id'],
            'compagnie_assurance' => ['required', 'string', 'max:150'],
            'numero_contrat'      => ['required', 'string', 'max:100'],
            'date_debut'          => ['required', 'date'],
            'date_fin'            => ['required', 'date', 'after:date_debut'],
            'prime'               => ['required', 'numeric', 'min:0'],
            'type_assurance'      => ['required', 'string', 'max:100'],
        ]);

        return $this->runAjax(
            fn () => ['assurance' => $this->assurances->create($data)],
            'Assurance enregistrée.'
        );
    }

    public function update(Request $request, int $assurance)
    {
        $data = $request->validate([
            'voiture_id'          => ['required', 'exists:voitures,id'],
            'compagnie_assurance' => ['required', 'string', 'max:150'],
            'numero_contrat'      => ['required', 'string', 'max:100'],
            'date_debut'          => ['required', 'date'],
            'date_fin'            => ['required', 'date', 'after:date_debut'],
            'prime'               => ['required', 'numeric', 'min:0'],
            'type_assurance'      => ['required', 'string', 'max:100'],
        ]);

        return $this->runAjax(function () use ($assurance, $data) {
            $this->assurances->update($assurance, $data);
            return null;
        }, 'Assurance mise à jour.');
    }

    public function destroy(int $assurance)
    {
        return $this->runAjax(function () use ($assurance) {
            $this->assurances->delete($assurance);
            return null;
        }, 'Assurance supprimée.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['voiture_id', 'statut', 'search', 'date_debut', 'date_fin']);
        $assurances = $this->assurances->listePaginee($filters, perPage: 10000);

        $filename = 'assurances_vehicules_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($assurances) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Véhicule', 'Compagnie', 'N° contrat', 'Type',
                'Période', 'Prime', 'Statut',
            ], ';');

            foreach ($assurances as $a) {
                fputcsv($out, [
                    $a->voiture?->plaque,
                    $a->compagnie_assurance,
                    $a->numero_contrat,
                    $a->type_assurance,
                    $a->date_debut->format('d/m/Y') . ' → ' . $a->date_fin->format('d/m/Y'),
                    number_format((float) $a->prime, 2, ',', ' '),
                    $a->isActive() ? 'Active' : 'Expirée',
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}