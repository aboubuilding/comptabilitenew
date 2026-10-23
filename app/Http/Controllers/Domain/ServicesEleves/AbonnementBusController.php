<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\AbonnementBusData;
use App\Domain\ServicesEleves\Repositories\AbonnementBusRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Domain\ServicesEleves\Repositories\ZoneRepository;
use App\Domain\ServicesEleves\Services\AbonnementBusService;
use App\Domain\ServicesEleves\Types\SensTrajet;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreAbonnementBusRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbonnementBusController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private AbonnementBusRepository $abonnements,
        private ZoneRepository          $zones,
        private VoitureRepository       $voitures,
        private AbonnementBusService    $service,
    ) {
        $this->middleware('permission:transport.voir')->only(['index', 'export']);
        $this->middleware('permission:transport.abonner')->only(['store']);
        $this->middleware('permission:transport.gerer')->only(['abandonner', 'assigner', 'positionner', 'convertirSouscriptions']);
    }

    /** Filtres de la liste ; l'année est toujours celle de l'en-tête. */
    private function filtres(Request $request): array
    {
        return ['annee_id' => app(AnneeScolaireContext::class)->id()]
            + $request->only(['zone_id', 'voiture_id', 'statut', 'search', 'gps']);
    }

    public function index(Request $request)
    {
        $filters = $this->filtres($request);
        $anneeId = $filters['annee_id'];

        return view('domain.services-eleves.transport.abonnements.index', [
            'abonnements' => $this->abonnements->listePaginee($filters),
            'filters'     => $filters,
            'zones'       => $this->zones->listePourSelect($anneeId),
            'voitures'    => $this->voitures->listePourSelect(),
            'sens'        => SensTrajet::options(),
            'compteurs'   => $this->abonnements->compterActifs($anneeId),
            'parZone'     => $this->abonnements->actifsParZone($anneeId),
            'aConvertir'  => $this->service->souscriptionsAConvertir($anneeId),
        ]);
    }

    /**
     * Bouton « Synchroniser avec les souscriptions » : les souscriptions
     * transport de l'année deviennent des abonnements bus.
     */
    public function convertirSouscriptions()
    {
        $anneeId = app(AnneeScolaireContext::class)->id();

        return $this->runAjax(
            fn () => ['bilan' => $this->service->convertirSouscriptions($anneeId)],
            'Synchronisation terminée.'
        );
    }

    /** Position GPS du domicile de l'élève, saisie après la souscription. */
    public function positionner(Request $request, int $abonnement)
    {
        $data = $request->validate([
            'latitude'  => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);

        return $this->runAjax(
            fn () => ['abonnement' => $this->service->positionner(
                $abonnement,
                isset($data['latitude']) ? (float) $data['latitude'] : null,
                isset($data['longitude']) ? (float) $data['longitude'] : null,
            )],
            'Position GPS enregistrée.'
        );
    }

    public function store(StoreAbonnementBusRequest $request)
    {
        $data = AbonnementBusData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['abonnement' => $this->service->souscrire($data)],
            'Abonnement bus créé. Le montant dû est visible dans la situation financière de l\'élève.'
        );
    }

    public function abandonner(Request $request, int $abonnement)
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return $this->runAjax(
            fn () => ['abonnement' => $this->service->abandonner($abonnement, $data['motif'], Auth::id())],
            'Abonnement abandonné.'
        );
    }

    public function assigner(Request $request, int $abonnement)
    {
        $data = $request->validate([
            'zone_id' => ['nullable', 'exists:zones,id'],
            'sens'    => ['nullable', 'string', 'in:aller,retour,aller_retour'],
        ]);

        return $this->runAjax(
            fn () => ['abonnement' => $this->service->assigner($abonnement, $data)],
            'Assignation mise à jour.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filtres($request);
        $abonnements = $this->abonnements->listePaginee($filters, perPage: 10000);

        $filename = 'abonnements_bus_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($abonnements) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Élève', 'Matricule', 'Zone', 'Véhicule', 'Ligne', 'Sens',
                'Latitude', 'Longitude', 'Montant mensuel', 'Nombre mois',
                'Total dû', 'Statut', 'Date début', 'Date fin',
            ], ';');

            foreach ($abonnements as $a) {
                fputcsv($out, [
                    trim(($a->inscription?->eleve?->nom ?? '') . ' ' . ($a->inscription?->eleve?->prenom ?? '')),
                    $a->inscription?->eleve?->matricule,
                    $a->zone?->libelle,
                    $a->voiture?->plaque,
                    $a->ligne?->libelle,
                    $a->sens?->label(),
                    $a->latitude,
                    $a->longitude,
                    number_format((float) $a->montant_mensuel, 2, ',', ' '),
                    $a->nombre_mois,
                    number_format((float) $a->montant_total_du, 2, ',', ' '),
                    $a->statut == 1 ? 'Actif' : 'Abandonné',
                    optional($a->date_debut)->format('d/m/Y'),
                    optional($a->date_fin)->format('d/m/Y'),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}