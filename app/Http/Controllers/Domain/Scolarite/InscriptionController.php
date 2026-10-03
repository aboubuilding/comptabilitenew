<?php

namespace App\Http\Controllers\Domain\Scolarite;

use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Repositories\ClasseRepository;
use App\Domain\Scolarite\Repositories\CycleRepository;
use App\Domain\Scolarite\Repositories\EleveRepository;
use App\Domain\Scolarite\Repositories\InscriptionRepository;
use App\Domain\Scolarite\Repositories\NiveauRepository;
use App\Domain\Scolarite\Services\InscriptionService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Scolarite\AbandonInscriptionRequest;
use App\Http\Requests\Domain\Scolarite\InscriptionRequest;
use App\Http\Requests\Domain\Scolarite\RejeterInscriptionRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * "Éditer un certificat de scolarité" et l'export Excel (cahier des
 * charges) restent hors scope de ce tour, comme les autres modules —
 * l'accent est mis sur la liste + le cycle de vie complet
 * (créer -> valider/rejeter -> abandon).
 */
class InscriptionController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private InscriptionRepository $inscriptions,
        private InscriptionService $inscriptionService,
        private EleveRepository $eleves,
        private CycleRepository $cycles,
        private NiveauRepository $niveaux,
        private ClasseRepository $classes,
        private AnneeScolaireContext $anneeContext,
    ) {
        $this->middleware('permission:inscriptions.voir')->only(['index', 'certificat']);
        $this->middleware('permission:inscriptions.saisir')->only(['store']);
        $this->middleware('permission:inscriptions.valider')->only(['valider', 'rejeter']);
        $this->middleware('permission:inscriptions.saisir,inscriptions.valider')->only(['enregistrerAbandon']);
    }

    public function index(Request $request): View
    {
        $anneeId = (int) ($request->input('annee_id') ?: $this->anneeContext->id());

        return view('domain.scolarite.inscriptions.index', [
            'inscriptions'   => $this->inscriptions->paginateWithFilters($request->only([
                'cycle_id', 'niveau_id', 'classe_id', 'statut', 'search',
            ]), $anneeId),
            'cyclesOptions'  => $this->cycles->all(),
            'niveauxOptions' => $this->niveaux->all(),
            'classesOptions' => $this->classes->byAnnee($anneeId),
            'elevesOptions'  => $this->eleves->all(),
            'anneeId'        => $anneeId,
        ]);
    }

    public function store(InscriptionRequest $request): JsonResponse
    {
        return $this->runAjax(function () use ($request) {
            $data = $request->validated();
            $data['annee_id']          = $this->anneeContext->id();
            $data['statut_validation'] = \App\Domain\Scolarite\Types\StatutValidationInscription::EN_ATTENTE->value;

            $inscription = $this->inscriptions->create($data);

            return ['inscription' => $inscription];
        }, "Inscription créée avec succès, en attente de validation.");
    }

    /**
     * Certificat de scolarité (cahier des charges § 4, 1.3) : délivré pour une
     * inscription validée et non abandonnée.
     */
    public function certificat(Inscription $inscription)
    {
        abort_unless(
            $inscription->isValidee() && ! $inscription->isAbandonnee(),
            422,
            "Un certificat de scolarité ne se délivre que pour une inscription validée et en cours."
        );

        $inscription->load(['eleve', 'cycle', 'niveau', 'classe']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('domain.scolarite.inscriptions.certificat_pdf', [
            'inscription' => $inscription,
            'eleve'       => $inscription->eleve,
            'annee'       => \App\Domain\Scolarite\Models\Annee::find($inscription->annee_id),
        ])->setPaper('A4');

        return $pdf->download('certificat_scolarite_' . ($inscription->eleve?->matricule ?? $inscription->id) . '.pdf');
    }

    public function valider(Inscription $inscription): RedirectResponse
    {
        return $this->runWeb(function () use ($inscription) {
            $this->inscriptionService->valider($inscription, Auth::id());
        }, 'scolarite.inscriptions.index', 'Inscription validée avec succès.');
    }

    public function rejeter(RejeterInscriptionRequest $request, Inscription $inscription): RedirectResponse
    {
        return $this->runWeb(function () use ($request, $inscription) {
            $this->inscriptionService->rejeter($inscription, $request->validated()['motif'], Auth::id());
        }, 'scolarite.inscriptions.index', 'Inscription rejetée.');
    }

    public function enregistrerAbandon(AbandonInscriptionRequest $request, Inscription $inscription): RedirectResponse
    {
        return $this->runWeb(function () use ($request, $inscription) {
            $this->inscriptionService->enregistrerAbandon($inscription, $request->validated()['motif']);
        }, 'scolarite.inscriptions.index', 'Abandon enregistré avec succès.');
    }
}