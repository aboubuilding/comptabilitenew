<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\DTO\EmployeData;
use App\Domain\Rh\Repositories\EmployeRepository;
use App\Domain\Rh\Repositories\ProfessionRepository;
use App\Domain\Rh\Services\EmployeService;
use App\Domain\Rh\Types\Genre;
use App\Domain\Rh\Types\SituationFamiliale;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StoreEmployeRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private EmployeRepository    $employes,
        private ProfessionRepository $professions,
        private EmployeService       $service,
    ) {
        $this->middleware('permission:employes.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:employes.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'profession_id', 'statut', 'annee_id']);

        return view('domain.rh.employes.index', [
            'employes'    => $this->employes->listePaginee($filters),
            'filters'     => $filters,
            'professions' => $this->professions->listePourSelect(),
            'genres'      => Genre::options(),
            'situations'  => SituationFamiliale::options(),
            'stats'       => $this->employes->compterStatistiques(),
            'parProfession' => $this->employes->parProfession(),
        ]);
    }

    public function show(int $employe)
    {
        return view('domain.rh.employes.show', [
            'employe' => $this->employes->findAvecRelations($employe),
        ]);
    }

    public function store(StoreEmployeRequest $request)
    {
        return $this->runAjax(
            fn () => ['employe' => $this->service->creer(EmployeData::fromRequest($request->validated()))],
            'Employé créé.'
        );
    }

    public function update(StoreEmployeRequest $request, int $employe)
    {
        return $this->runAjax(
            fn () => ['employe' => $this->service->modifier($employe, EmployeData::fromRequest($request->validated()))],
            'Employé mis à jour.'
        );
    }

    public function destroy(int $employe)
    {
        return $this->runAjax(function () use ($employe) {
            $this->service->supprimer($employe);
            return null;
        }, 'Employé désactivé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'profession_id', 'statut']);
        $employes = $this->employes->listePaginee($filters, perPage: 10000);

        $filename = 'employes_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($employes) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Nom & prénom', 'Profession', 'N° CNSS', 'Téléphone', 'Email',
                'Ville', 'Salaire', 'Statut',
            ], ';');

            foreach ($employes as $e) {
                fputcsv($out, [
                    $e->nom_prenom,
                    $e->profession?->libelle,
                    $e->numero_cnss,
                    $e->telephone,
                    $e->email,
                    $e->ville,
                    number_format((float) $e->salaire, 2, ',', ' '),
                    $e->isActif() ? 'Actif' : 'Inactif',
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}