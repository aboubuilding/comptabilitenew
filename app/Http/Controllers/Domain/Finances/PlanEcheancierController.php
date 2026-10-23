<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\PlanEcheancierData;
use App\Domain\Finances\Repositories\PlanEcheancierRepository;
use App\Domain\Finances\Services\EcheancierService;
use App\Domain\Finances\Services\PlanEcheancierService;
use App\Domain\Scolarite\Models\Annee;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StorePlanEcheancierRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;

/**
 * 2.1 Plans d'échéancier — dispositif unique des paiements échelonnés (les
 * anciennes tranches y sont converties). La liste et la conversion portent
 * sur l'année scolaire choisie dans l'en-tête.
 */
class PlanEcheancierController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PlanEcheancierRepository $plans,
        private PlanEcheancierService    $service,
        private EcheancierService        $echeanciers,
        private AnneeScolaireContext     $contexte,
    ) {
        $this->middleware('permission:frais.voir')->only([
            'index', 'show', 'apercuConversion', 'eleves', 'elevesExport', 'elevesExportPdf',
            'nonAJour', 'nonAJourExport', 'nonAJourExportPdf',
        ]);
        $this->middleware('permission:frais.gerer')->only(['store', 'update', 'destroy', 'convertirTranches']);
    }

    private function anneeId(): int
    {
        return (int) $this->contexte->id();
    }

    public function index(Request $request)
    {
        $filters = ['annee_id' => $this->anneeId(), 'search' => $request->input('search')];

        return view('domain.finances.plan-echeanciers.index', [
            'plans'      => $this->plans->listePaginee($filters),
            'filters'    => $filters,
            'annee'      => Annee::find($this->anneeId()),
            'conversion' => $this->echeanciers->apercuConversion($this->anneeId()),
        ]);
    }

    public function show(int $plan)
    {
        return response()->json([
            'plan' => $this->plans->findAvecLignes($plan),
        ]);
    }

    /**
     * Suivi des élèves d'un plan pour l'intervalle demandé (écran et exports) :
     * [plan, suivi, dates validées].
     */
    private function suiviDemande(Request $request, int $plan): array
    {
        $dates = $request->validate([
            'du'     => ['nullable', 'date'],
            'au'     => ['nullable', 'date', 'after_or_equal:du'],
            'statut' => ['nullable', 'in:a_jour,en_retard'],
        ], [
            'au.after_or_equal' => 'La date de fin doit suivre la date de début.',
        ]);

        // Un plan d'une autre année que celle de l'en-tête ne se consulte pas ici
        $modele = $this->plans->findAvecLignes($plan);
        abort_if((int) $modele->annee_id !== $this->anneeId(), 404);

        $suivi = $this->echeanciers->suiviEleves(
            $modele,
            ! empty($dates['du']) ? \Illuminate\Support\Carbon::parse($dates['du']) : null,
            ! empty($dates['au']) ? \Illuminate\Support\Carbon::parse($dates['au']) : null,
        );

        return [$modele, $suivi, $dates];
    }

    /**
     * Élèves à jour et en retard à chaque échéance du plan, pour les échéances
     * dont la date butoir tombe dans l'intervalle choisi ; la situation est
     * arrêtée à la date de fin.
     */
    public function eleves(Request $request, int $plan)
    {
        [$modele, $suivi, $dates] = $this->suiviDemande($request, $plan);

        return view('domain.finances.plan-echeanciers.eleves', [
            'plan'   => $modele,
            'annee'  => Annee::find($this->anneeId()),
            'suivi'  => $suivi,
            'statut' => $dates['statut'] ?? null,
            'du'     => $dates['du'] ?? null,
            'au'     => $dates['au'] ?? $suivi['au']->toDateString(),
        ]);
    }

    /** Export Excel du suivi affiché : même intervalle, même filtre. */
    public function elevesExport(Request $request, int $plan)
    {
        [$modele, $suivi, $dates] = $this->suiviDemande($request, $plan);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Domain\Finances\Exports\SuiviEcheancesExport($suivi, $dates['statut'] ?? null),
            'suivi_echeances_' . \Illuminate\Support\Str::slug($modele->nom) . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /** Export PDF du suivi affiché : même intervalle, même filtre. */
    public function elevesExportPdf(Request $request, int $plan)
    {
        [$modele, $suivi, $dates] = $this->suiviDemande($request, $plan);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('domain.finances.plan-echeanciers.eleves-print', [
            'plan'      => $modele,
            'annee'     => Annee::find($this->anneeId()),
            'suivi'     => $suivi,
            'statut'    => $dates['statut'] ?? null,
            'genereLe'  => now(),
            'generePar' => $request->user(),
        ])->setPaper('A4', 'portrait')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'dpi' => 110])
            ->download('suivi_echeances_' . \Illuminate\Support\Str::slug($modele->nom) . '_' . now()->format('Ymd_His') . '.pdf');
    }

    /* =====================================================================
       Élèves non à jour à une date, tous échéanciers en cours confondus
       ===================================================================== */

    /** Situation à la date demandée (aujourd'hui par défaut), filtrée par niveau et recherche. */
    private function situationNonAJour(Request $request): array
    {
        $demande = $request->validate([
            'jour'   => ['nullable', 'date'],
            'niveau' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $jour = ! empty($demande['jour']) ? \Illuminate\Support\Carbon::parse($demande['jour']) : \Illuminate\Support\Carbon::today();
        $situation = $this->echeanciers->elevesNonAJour($this->anneeId(), $jour);

        // Niveaux proposés au filtre : ceux des élèves en retard à cette date
        $niveaux = collect($situation['eleves'])->pluck('niveau')->filter()->unique()->sort()->values()->all();
        $niveau = $demande['niveau'] ?? null;
        $recherche = trim((string) ($demande['search'] ?? ''));

        if ($niveau || $recherche !== '') {
            $situation['eleves'] = array_values(array_filter($situation['eleves'], fn (array $e) =>
                (! $niveau || $e['niveau'] === $niveau)
                && ($recherche === '' || str_contains(mb_strtolower($e['nom'] . ' ' . $e['matricule']), mb_strtolower($recherche)))));
            foreach (['du', 'paye', 'retard'] as $cle) {
                $situation[$cle] = round(array_sum(array_column($situation['eleves'], $cle)), 2);
            }
        }

        return [$situation, $niveaux, $niveau, $recherche];
    }

    public function nonAJour(Request $request)
    {
        [$situation, $niveaux, $niveau, $recherche] = $this->situationNonAJour($request);

        // La liste est paginée ; les indicateurs et les exports portent sur l'ensemble
        $page = max(1, (int) $request->input('page', 1));
        $parPage = 25;
        $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($situation['eleves'], ($page - 1) * $parPage, $parPage),
            count($situation['eleves']),
            $parPage,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page')]
        );

        return view('domain.finances.plan-echeanciers.non-a-jour', [
            'annee'     => Annee::find($this->anneeId()),
            'situation' => $situation,
            'pagination' => $pagination,
            'jour'      => $situation['jour']->toDateString(),
            'niveaux'   => $niveaux,
            'niveau'    => $niveau,
            'recherche' => $recherche,
        ]);
    }

    public function nonAJourExport(Request $request)
    {
        [$situation] = $this->situationNonAJour($request);

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Domain\Finances\Exports\ElevesNonAJourExport($situation),
            'eleves_non_a_jour_' . $situation['jour']->format('Ymd') . '.xlsx'
        );
    }

    public function nonAJourExportPdf(Request $request)
    {
        [$situation, , $niveau, $recherche] = $this->situationNonAJour($request);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('domain.finances.plan-echeanciers.non-a-jour-print', [
            'annee'     => Annee::find($this->anneeId()),
            'situation' => $situation,
            'niveau'    => $niveau,
            'recherche' => $recherche,
            'genereLe'  => now(),
            'generePar' => $request->user(),
        ])->setPaper('A4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isHtml5ParserEnabled' => true, 'dpi' => 110])
            ->download('eleves_non_a_jour_' . $situation['jour']->format('Ymd') . '.pdf');
    }

    public function store(StorePlanEcheancierRequest $request)
    {
        return $this->runAjax(
            fn () => ['plan' => $this->service->creer(PlanEcheancierData::fromRequest($request->validated()))],
            'Plan d\'échéancier créé.'
        );
    }

    public function update(StorePlanEcheancierRequest $request, int $plan)
    {
        return $this->runAjax(
            fn () => ['plan' => $this->service->modifier($plan, PlanEcheancierData::fromRequest($request->validated()))],
            'Plan d\'échéancier mis à jour.'
        );
    }

    public function destroy(int $plan)
    {
        return $this->runAjax(function () use ($plan) {
            $this->service->supprimer($plan);
            return null;
        }, 'Plan désactivé.');
    }

    /** Ce que ferait la conversion des tranches de l'année, sans rien écrire. */
    public function apercuConversion()
    {
        $apercu = $this->echeanciers->apercuConversion($this->anneeId());

        return response()->json([
            'frais_a_convertir' => count($apercu['a_convertir']),
            'tranches'          => array_sum(array_map(fn ($c) => count($c['tranches']), $apercu['a_convertir'])),
            'deja_convertis'    => $apercu['deja_convertis'],
            'incoherences'      => $apercu['incoherences'],
            'hors_annee'        => $apercu['hors_annee'],
        ]);
    }

    /** Convertit en plans d'échéancier les tranches des frais de l'année de l'en-tête. */
    public function convertirTranches()
    {
        return $this->runAjax(
            fn () => ['bilan' => $this->echeanciers->convertirTranches($this->anneeId())],
            'Conversion terminée.'
        );
    }
}
