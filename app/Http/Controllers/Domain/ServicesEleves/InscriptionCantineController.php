<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\ServicesEleves\DTO\InscriptionCantineData;
use App\Domain\ServicesEleves\Repositories\InscriptionCantineRepository;
use App\Domain\ServicesEleves\Repositories\MenuRepository;
use App\Domain\ServicesEleves\Services\CantineService;
use App\Domain\ServicesEleves\Types\StatutInscriptionCantine;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\AbandonnerInscriptionCantineRequest;
use App\Http\Requests\Domain\ServicesEleves\StoreInscriptionCantineRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InscriptionCantineController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private InscriptionCantineRepository $inscriptions,
        private MenuRepository               $menus,
        private FraisEcoleRepository         $frais,
        private CantineService               $service,
    ) {
        $this->middleware('permission:cantine.voir')->only(['index', 'export']);
        $this->middleware('permission:cantine.inscrire')->only(['store']);
        $this->middleware('permission:cantine.gerer')->only(['abandonner']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'statut', 'search']);
        $anneeId = app(AnneeScolaireContext::class)->id();

        return view('domain.services-eleves.cantine.inscriptions.index', [
            'inscriptions' => $this->inscriptions->listePaginee($filters),
            'filters'      => $filters,
            'statuts'      => StatutInscriptionCantine::options(),
            'frais'        => $this->frais->listePourSelect($anneeId),
            'compteurs'    => $this->inscriptions->compterActifs(),
            'menuDuJour'   => $this->menus->menuDuJour(),
            'menuSemaine'  => $this->menus->menusSemaine(),
        ]);
    }

    public function store(StoreInscriptionCantineRequest $request)
    {
        $data = InscriptionCantineData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['inscription' => $this->service->souscrire($data)],
            'Élève souscrit à la cantine. Le montant dû est visible dans sa situation financière.'
        );
    }

    public function abandonner(AbandonnerInscriptionCantineRequest $request, int $inscription)
    {
        return $this->runAjax(
            fn () => ['inscription' => $this->service->abandonner(
                $inscription, $request->validated()['motif'], Auth::id()
            )],
            'Abonnement cantine abandonné.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'statut', 'search']);
        $inscriptions = $this->inscriptions->listePaginee($filters, perPage: 10000);

        $filename = 'inscriptions_cantine_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($inscriptions) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Élève', 'Matricule', 'Classe', 'Début', 'Fin',
                'Mensuel', 'Nb mois', 'Total dû', 'Statut',
            ], ';');

            foreach ($inscriptions as $i) {
                fputcsv($out, [
                    trim(($i->inscription?->eleve?->nom ?? '') . ' ' . ($i->inscription?->eleve?->prenom ?? '')),
                    $i->inscription?->eleve?->matricule,
                    $i->inscription?->classe?->libelle,
                    optional($i->date_debut)->format('d/m/Y'),
                    optional($i->date_fin)->format('d/m/Y'),
                    number_format((float) $i->montant_mensuel, 2, ',', ' '),
                    $i->nombre_mois,
                    number_format((float) $i->montant_total_du, 2, ',', ' '),
                    $i->statut?->label(),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}