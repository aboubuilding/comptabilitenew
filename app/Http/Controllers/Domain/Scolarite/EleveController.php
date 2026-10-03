<?php

namespace App\Http\Controllers\Domain\Scolarite;

use App\Domain\Scolarite\DTO\AffectationClasseDTO;
use App\Domain\Scolarite\DTO\EleveDTO;
use App\Domain\Scolarite\Exports\ElevesExport;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Scolarite\Models\Cycle;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Models\Nationalite;
use App\Domain\Scolarite\Models\Niveau;
use App\Domain\Scolarite\Repositories\EleveRepository;
use App\Domain\Scolarite\Services\AffectationClasseService;
use App\Domain\Scolarite\Services\EleveService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Concerns\LogsActions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Scolarite\AffecterClasseRequest;
use App\Http\Requests\Domain\Scolarite\StoreEleveRequest;
use App\Support\AnneeScolaireContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Module 1.1 — Élèves.
 *
 * ⚠️ La liste est filtrée par l'année active (AnneeScolaireContext),
 * résolue en amont par le middleware ResolveAnneeScolaire.
 */
class EleveController extends Controller
{
    use HandlesControllerErrors;
    use LogsActions;

    public function __construct(
        private readonly EleveService $service,
        private readonly EleveRepository $repository,
        private readonly AffectationClasseService $affectationService,
        private readonly AnneeScolaireContext $anneeContext
    ) {
        $this->middleware('permission:eleves.voir')->only(['index', 'show', 'export', 'fichePdf']);
        $this->middleware('permission:eleves.gerer')->only(['create', 'store', 'edit', 'update', 'destroy', 'affecterForm', 'affecter']);
    }

    public function index(Request $request)
    {
        $filtres = $request->only([
            'recherche', 'sexe', 'nationalite_id', 'classe_id', 'niveau_id', 'cycle_id', 'situation',
        ]);

        $anneeId = $this->anneeContext->id();

        $eleves       = $this->repository->listePaginee($filtres, 15);
        $nationalites = Nationalite::actif()->orderBy('libelle')->get();
        $cycles       = Cycle::actif()->orderBy('libelle')->get();
        $niveaux      = Niveau::actif()->orderBy('numero_ordre')->get();
        $classes      = Classe::actif()
            ->when($anneeId, fn ($q) => $q->where('annee_id', $anneeId))
            ->orderBy('libelle')
            ->get();
        $stats        = $this->service->statistiques();

        return view('domain.scolarite.eleves.index', compact(
            'eleves', 'nationalites', 'cycles', 'niveaux', 'classes', 'stats', 'filtres', 'anneeId'
        ));
    }

    public function create()
    {
        $nationalites = Nationalite::actif()->orderBy('libelle')->get();

        return view('domain.scolarite.eleves._modal_form', [
            'eleve'        => null,
            'nationalites' => $nationalites,
            'action'       => route('scolarite.eleves.store'),
            'method'       => 'POST',
            'titre'        => 'Nouvel élève',
        ]);
    }

    public function store(StoreEleveRequest $request)
    {
        return $this->runAjax(
            function () use ($request) {
                $dto   = EleveDTO::fromArray($request->validated());
                $eleve = $this->service->creer(
                    $dto,
                    $request->file('photo'),
                    $request->file('carte_identite')
                );

                $this->logAction(
                    'create', 'Eleve', $eleve->id,
                    "Création de l'élève « {$eleve->nom_complet} » ({$eleve->matricule})",
                    ['matricule' => $eleve->matricule]
                );
            },
            'Élève créé. Il rejoindra la liste des inscrits une fois son inscription validée (filtre « Sans inscription validée » pour le retrouver entre-temps).'
        );
    }

    public function show(Eleve $eleve)
    {
        $eleve->load([
            'nationalite',
            'inscriptionActive.classe',
            'inscriptionActive.niveau',
            'inscriptionActive.cycle',
        ]);

        $anneeId = $this->anneeContext->id();
        $eleve->montant_du       = $this->calculerMontantDu($eleve->id, $anneeId);
        $eleve->montant_encaisse = $this->calculerMontantEncaisse($eleve->id, $anneeId);

        return view('domain.scolarite.eleves.show', compact('eleve'));
    }

    public function edit(Eleve $eleve)
    {
        $nationalites = Nationalite::actif()->orderBy('libelle')->get();

        return view('domain.scolarite.eleves._modal_form', [
            'eleve'        => $eleve,
            'nationalites' => $nationalites,
            'action'       => route('scolarite.eleves.update', $eleve),
            'method'       => 'PUT',
            'titre'        => "Modifier l'élève",
        ]);
    }

    public function update(StoreEleveRequest $request, Eleve $eleve)
    {
        return $this->runAjax(
            function () use ($request, $eleve) {
                $dto = EleveDTO::fromArray($request->validated());
                $this->service->mettreAJour(
                    $eleve,
                    $dto,
                    $request->file('photo'),
                    $request->file('carte_identite')
                );

                $this->logAction('update', 'Eleve', $eleve->id, "Modification de l'élève « {$eleve->nom_complet} »");
            },
            'Élève mis à jour avec succès.'
        );
    }

    public function destroy(Eleve $eleve)
    {
        return $this->runAjax(
            function () use ($eleve) {
                $nom = $eleve->nom_complet;
                $this->service->archiver($eleve);
                $this->logAction('delete', 'Eleve', $eleve->id, "Archivage de l'élève « {$nom} »");
            },
            'Élève archivé avec succès.'
        );
    }

    public function export(Request $request)
    {
        $filtres = $request->only(['recherche', 'sexe', 'situation']);
        $eleves  = $this->repository->listePourExport($filtres);

        $this->logAction('export', 'Eleve', null, 'Export Excel de la liste des élèves (année active)', $filtres);

        return Excel::download(
            new ElevesExport($eleves),
            'eleves_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function fichePdf(Eleve $eleve)
    {
        $eleve->load([
            'nationalite',
            'inscriptionActive.classe',
            'inscriptionActive.niveau',
            'inscriptionActive.cycle',
        ]);

        $pdf = Pdf::loadView('domain.scolarite.eleves.fiche_pdf', compact('eleve'))->setPaper('A4');

        $this->logAction('export', 'Eleve', $eleve->id, "Impression PDF de la fiche de « {$eleve->nom_complet} »");

        return $pdf->download("fiche_eleve_{$eleve->matricule}.pdf");
    }

    /**
     * Formulaire (modale) d'affectation de classe.
     */
    public function affecterForm(Request $request)
    {
        $anneeId  = $this->anneeContext->id();
        $eleveIds = array_map('intval', $request->input('eleve_ids', []));

        if (empty($eleveIds)) {
            abort(422, 'Aucun élève sélectionné.');
        }

        $inscriptions = Inscription::with('niveau')
            ->whereIn('eleve_id', $eleveIds)
            ->where('annee_id', $anneeId)
            ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value)
            ->get();

        if ($inscriptions->isEmpty()) {
            abort(422, 'Aucune inscription active trouvée.');
        }

        $niveaux  = $inscriptions->pluck('niveau_id')->unique()->filter()->values();
        $niveauId = $niveaux->count() === 1 ? $niveaux->first() : null;

        $classes = Classe::actif()
            ->where('annee_id', $anneeId)
            ->when($niveauId, fn ($q) => $q->where('niveau_id', $niveauId))
            ->orderBy('libelle')
            ->get();

        $eleves = Eleve::whereIn('id', $eleveIds)->get(['id', 'nom', 'prenom', 'matricule']);

        return view('domain.scolarite.eleves._modal_affectation', [
            'eleves'   => $eleves,
            'classes'  => $classes,
            'niveauId' => $niveauId,
            'niveau'   => $niveauId ? Niveau::find($niveauId) : null,
            'action'   => route('scolarite.eleves.affecter'),
        ]);
    }

    /**
     * Enregistre l'affectation (individuelle ou groupée) ou la désaffectation.
     */
    public function affecter(AffecterClasseRequest $request)
{
    return $this->runAjax(
        function () use ($request) {
            $dto      = AffectationClasseDTO::fromArray($request->validated());
            $resultat = $this->affectationService->affecter($dto);

            $classe = $dto->classe_id ? Classe::find($dto->classe_id) : null;

            if ($dto->estDesaffectation()) {
                $this->logAction(
                    'desaffecter_classe', 'Inscription', null,
                    "Désaffectation de classe pour {$resultat['affectes']} élève(s)",
                    [
                        'eleve_ids' => $dto->eleve_ids,
                        'affectes'  => $resultat['affectes'],
                        'ignores'   => $resultat['ignores'],
                    ]
                );
            } else {
                $this->logAction(
                    'affecter_classe', 'Inscription', null,
                    "Affectation de la classe « {$classe->libelle} » à {$resultat['affectes']} élève(s)",
                    [
                        'classe_id' => $dto->classe_id,
                        'eleve_ids' => $dto->eleve_ids,
                        'affectes'  => $resultat['affectes'],
                        'ignores'   => $resultat['ignores'],
                    ]
                );
            }

            // On enrichit le résultat pour le message dynamique
            $resultat['classe_libelle'] = $classe?->libelle;
            return $resultat;
        },
        // Message dynamique : reçoit le résultat de la closure
        function (array $resultat) {
            $n      = $resultat['affectes'];
            $ignore = count($resultat['ignores']);

            if ($resultat['desaffecte']) {
                $msg = "Classe retirée pour {$n} élève(s).";
            } else {
                $lib = $resultat['classe_libelle'] ?? '';
                $msg = "Classe « {$lib} » affectée à {$n} élève(s).";
            }

            if ($ignore > 0) {
                $msg .= " {$ignore} élève(s) ignoré(s) (pas d'inscription active).";
            }

            return $msg;
        }
    );
}

    protected function calculerMontantDu(int $eleveId, ?int $anneeId): float
    {
        if (!\Schema::hasTable('situations_recouvrement')) {
            return 0.0;
        }

        return (float) \DB::table('situations_recouvrement as sr')
            ->join('inscriptions as i', 'i.id', '=', 'sr.inscription_id')
            ->where('i.eleve_id', $eleveId)
            ->when($anneeId, fn ($q) => $q->where('i.annee_id', $anneeId))
            ->sum('sr.retard');
    }

    protected function calculerMontantEncaisse(int $eleveId, ?int $anneeId): float
    {
        if (!\Schema::hasTable('paiements') || !\Schema::hasTable('details')) {
            return 0.0;
        }

        return (float) \DB::table('details as d')
            ->join('paiements as p', 'p.id', '=', 'd.paiement_id')
            ->join('inscriptions as i', 'i.id', '=', 'p.inscription_id')
            ->where('i.eleve_id', $eleveId)
            ->whereNotNull('d.date_encaissement')
            ->when($anneeId, fn ($q) => $q->where('i.annee_id', $anneeId))
            ->sum('d.montant');
    }
}