<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\EvenementData;
use App\Domain\ServicesEleves\Repositories\EvenementRepository;
use App\Domain\ServicesEleves\Services\EvenementService;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use App\Domain\ServicesEleves\Types\TypeEvenement;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreEvenementRequest;
use App\Http\Requests\Domain\ServicesEleves\StorePreInscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvenementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private EvenementRepository $evenements,
        private EvenementService    $service,
    ) {
        $this->middleware('permission:activites.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:activites.gerer')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:activites.inscrire')->only(['preInscrire', 'annulerPreInscription']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['type', 'search', 'a_venir', 'capacite_limitee', 'date_debut', 'date_fin']);

        return view('domain.services-eleves.activites.evenements.index', [
            'evenements' => $this->evenements->listePaginee($filters),
            'filters'    => $filters,
            'types'      => TypeEvenement::options(),
            'aVenir'     => $this->evenements->aVenir(5),
            'stats'      => $this->evenements->compterStatistiques(),
        ]);
    }

    public function show(int $evenement)
    {
        return view('domain.services-eleves.activites.evenements.show', [
            'evenement' => $this->evenements->findAvecParticipations($evenement),
            'statuts'   => StatutParticipation::options(),
        ]);
    }

    public function store(StoreEvenementRequest $request)
    {
        return $this->runAjax(
            fn () => ['evenement' => $this->service->creer(EvenementData::fromRequest($request->validated()))],
            'Événement créé.'
        );
    }

    public function update(StoreEvenementRequest $request, int $evenement)
    {
        return $this->runAjax(
            fn () => ['evenement' => $this->service->modifier($evenement, EvenementData::fromRequest($request->validated()))],
            'Événement mis à jour.'
        );
    }

    public function destroy(int $evenement)
    {
        return $this->runAjax(function () use ($evenement) {
            $this->service->supprimer($evenement);
            return null;
        }, 'Événement désactivé.');
    }

    /** Pré-inscription d'un élève. */
    public function preInscrire(StorePreInscriptionRequest $request)
    {
        $data = $request->validated();

        return $this->runAjax(
            fn () => ['participation' => $this->service->preInscrire(
                (int) $data['evenement_id'],
                (int) $data['inscription_id'],
                Auth::id()
            )],
            'Élève pré-inscrit. Le statut passera à « Confirmé » à l\'encaissement du paiement.'
        );
    }

    public function annulerPreInscription(int $participation)
    {
        return $this->runAjax(
            fn () => ['participation' => $this->service->annulerPreInscription($participation, Auth::id())],
            'Pré-inscription annulée.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['type', 'search', 'a_venir', 'capacite_limitee']);
        $evenements = $this->evenements->listePaginee($filters, perPage: 10000);

        $filename = 'evenements_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($evenements) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Nom', 'Type', 'Date', 'Participation',
                'Capacité limitée', 'Places', 'Confirmés', 'Pré-inscrits',
                'Taux de remplissage (%)',
            ], ';');

            foreach ($evenements as $e) {
                fputcsv($out, [
                    $e->nom,
                    $e->type?->label(),
                    optional($e->date_evenement)->format('d/m/Y'),
                    number_format((float) $e->participation, 2, ',', ' '),
                    $e->isCapaciteLimitee() ? 'Oui' : 'Non',
                    $e->capacite ?? '—',
                    $e->nombre_confirmes,
                    $e->nombre_pre_inscrits,
                    $e->taux_remplissage,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}