<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\ActiviteData;
use App\Domain\ServicesEleves\DTO\InscriptionActiviteData;
use App\Domain\ServicesEleves\Repositories\ActiviteRepository;
use App\Domain\ServicesEleves\Repositories\InscriptionActiviteRepository;
use App\Domain\ServicesEleves\Services\ActiviteService;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Domain\ServicesEleves\Types\TypeActivite;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreActiviteRequest;
use App\Http\Requests\Domain\ServicesEleves\StoreInscriptionActiviteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActiviteController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ActiviteRepository            $activites,
        private InscriptionActiviteRepository $inscriptions,
        private NiveauRepository              $niveaux,
        private ActiviteService               $service,
    ) {
        $this->middleware('permission:activites.voir')->only(['index', 'export']);
        $this->middleware('permission:activites.gerer')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:activites.inscrire')->only(['souscrire', 'annulerInscription']);
        $this->middleware('permission:activites.voir')->only(['inscriptions']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['niveau_id', 'type', 'search']);

        return view('domain.services-eleves.activites.activites.index', [
            'activites' => $this->activites->listePaginee($filters),
            'filters'   => $filters,
            'types'     => TypeActivite::options(),
            'niveaux'   => $this->niveaux->listePourSelect(),
            'stats'     => $this->activites->compterStatistiques(),
        ]);
    }

    public function store(StoreActiviteRequest $request)
    {
        return $this->runAjax(
            fn () => ['activite' => $this->service->creer(ActiviteData::fromRequest($request->validated()))],
            'Activité créée.'
        );
    }

    public function update(StoreActiviteRequest $request, int $activite)
    {
        return $this->runAjax(
            fn () => ['activite' => $this->service->modifier($activite, ActiviteData::fromRequest($request->validated()))],
            'Activité mise à jour.'
        );
    }

    public function destroy(int $activite)
    {
        return $this->runAjax(function () use ($activite) {
            $this->service->supprimer($activite);
            return null;
        }, 'Activité désactivée.');
    }

    /** Souscrire un élève à une activité (côté admin). */
    public function souscrire(StoreInscriptionActiviteRequest $request)
    {
        $data = InscriptionActiviteData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['inscription' => $this->service->souscrire($data)],
            'Élève inscrit à l\'activité. Le montant dû est visible dans sa situation financière.'
        );
    }

    public function annulerInscription(int $inscription)
    {
        return $this->runAjax(
            fn () => ['inscription' => $this->service->annulerInscription($inscription)],
            'Inscription annulée.'
        );
    }

    /** Liste des inscriptions (écran séparé). */
    public function inscriptions(Request $request)
    {
        $filters = $request->only(['annee_id', 'activite_id', 'statut', 'search']);

        return view('domain.services-eleves.activites.inscriptions.index', [
            'inscriptions' => $this->inscriptions->listePaginee($filters),
            'filters'      => $filters,
            'activites'    => $this->activites->listePourSelect(),
            'statuts'      => StatutInscriptionActivite::options(),
            'stats'        => $this->inscriptions->compterStatistiques(
                isset($filters['annee_id']) ? (int) $filters['annee_id'] : app(\App\Support\AnneeScolaireContext::class)->id()
            ),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['niveau_id', 'type', 'search']);
        $activites = $this->activites->listePaginee($filters, perPage: 10000);

        $filename = 'activites_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($activites) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Libellé', 'Type', 'Niveau', 'Encadreur', 'Contact', 'Montant', 'Inscrits',
            ], ';');

            foreach ($activites as $a) {
                fputcsv($out, [
                    $a->libelle,
                    $a->type?->label(),
                    $a->niveau?->libelle,
                    $a->encadreur,
                    $a->contact_encadreur,
                    number_format((float) $a->montant, 2, ',', ' '),
                    $a->inscriptions_count ?? 0,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}