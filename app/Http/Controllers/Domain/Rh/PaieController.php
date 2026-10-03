<?php

namespace App\Http\Controllers\Domain\Rh;

use App\Domain\Rh\Repositories\EmployeRepository;
use App\Domain\Rh\Repositories\PaieRepository;
use App\Domain\Rh\Repositories\PeriodeRepository;
use App\Domain\Rh\Services\PaieService;
use App\Domain\Rh\Types\StatutPaie;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Rh\StorePaieRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaieController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PaieRepository    $paies,
        private PeriodeRepository $periodes,
        private EmployeRepository $employes,
        private PaieService       $service,
    ) {
        $this->middleware('permission:paie.voir')->only([
            'index', 'show', 'export', 'exportPdf', 'exportPeriodePdf',
        ]);
        $this->middleware('permission:paie.gerer')->only([
            'store', 'valider', 'annuler',
        ]);
        // Cahier des charges 5.2 : le règlement des bulletins revient au Comptable.
        $this->middleware('permission:paie.payer')->only(['payer', 'marquerRetrait']);
    }

    /* ================================================================
       ÉCRANS
       ================================================================ */

    public function index(Request $request)
    {
        $filters = $request->only([
            'annee_id', 'periode_id', 'statut', 'employe_id', 'search',
        ]);

        return view('domain.rh.paies.index', [
            'paies'    => $this->paies->listePaginee($filters),
            'filters'  => $filters,
            'periodes' => $this->periodes->listePourSelect(),
            'employes' => $this->employes->listePourSelect(),
            'statuts'  => StatutPaie::options(),
            'stats'    => $this->paies->compterStatistiques($filters['periode_id'] ?? null),
        ]);
    }

    public function show(int $paie)
    {
        return view('domain.rh.paies.show', [
            'paie' => $this->paies->findAvecDetails($paie),
        ]);
    }

    /* ================================================================
       ACTIONS
       ================================================================ */

    /** Génère un bulletin unique (saisie manuelle par le comptable). */
    public function store(StorePaieRequest $request)
    {
        $data = $request->validated();

        return $this->runAjax(
            fn () => ['paie' => $this->service->genererBulletin(
                (int) $data['employe_id'],
                (int) $data['periode_id']
            )],
            'Bulletin de paie généré.'
        );
    }

    public function valider(int $paie)
    {
        return $this->runAjax(
            fn () => ['paie' => $this->service->valider($paie)],
            'Bulletin validé.'
        );
    }

    public function payer(Request $request, int $paie)
    {
        $data = $request->validate([
            'date_paiement' => ['nullable', 'date'],
        ]);

        return $this->runAjax(
            fn () => ['paie' => $this->service->payer(
                $paie,
                Auth::id(),
                $data['date_paiement'] ?? null
            )],
            'Bulletin marqué comme payé.'
        );
    }

    public function annuler(Request $request, int $paie)
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return $this->runAjax(
            fn () => ['paie' => $this->service->annuler($paie, $data['motif'])],
            'Bulletin annulé.'
        );
    }

    /** Confirme le retrait effectif des espèces à la billetterie. */
    public function marquerRetrait(int $paie)
    {
        return $this->runAjax(
            fn () => ['paie' => $this->service->marquerRetrait($paie, Auth::id())],
            'Retrait en espèces enregistré.'
        );
    }

    /* ================================================================
       EXPORTS
       ================================================================ */

    /** Export CSV de la liste filtrée. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'annee_id', 'periode_id', 'statut', 'employe_id', 'search',
        ]);
        $paies = $this->paies->listePaginee($filters, perPage: 10000);

        $filename = 'paies_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($paies) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Référence', 'Employé', 'Période',
                'Salaire base', 'Primes', 'Brut', 'Déductions', 'Retenues',
                'Net à payer', 'Statut', 'Date paiement',
            ], ';');

            foreach ($paies as $p) {
                fputcsv($out, [
                    $p->reference,
                    $p->employe?->nom_prenom,
                    $p->periode?->libelle,
                    number_format((float) $p->salaire_base, 2, ',', ' '),
                    number_format(
                        (float) $p->prime_anciennete
                        + (float) $p->sursalaire
                        + (float) $p->bonus
                        + (float) $p->logement
                        + (float) $p->nourriture
                        + (float) $p->deplacement
                        + (float) $p->autre_avantage,
                        2, ',', ' '
                    ),
                    number_format((float) $p->total_brut, 2, ',', ' '),
                    number_format((float) $p->total_deductions, 2, ',', ' '),
                    number_format((float) $p->total_retenues, 2, ',', ' '),
                    number_format((float) $p->net_a_payer, 2, ',', ' '),
                    $p->statut?->label(),
                    optional($p->date_paiement)->format('d/m/Y'),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** PDF d'un bulletin individuel (reçu pour l'employé). */
    public function exportPdf(int $paie)
    {
        $paieModel = $this->paies->findAvecDetails($paie);

        $pdf = Pdf::loadView('domain.rh.paies.bulletin', [
            'paie'      => $paieModel,
            'genereLe'  => now(),
            'generePar' => auth()->user(),
        ])
        ->setPaper('A4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'dpi'                  => 150,
            'margin_top'           => 12,
            'margin_bottom'        => 12,
            'margin_left'          => 12,
            'margin_right'         => 12,
        ]);

        $filename = sprintf(
            'bulletin_%s_%s.pdf',
            $paieModel->reference ?? ('#' . $paieModel->id),
            now()->format('Ymd_His')
        );

        return $pdf->download($filename);
    }

    /**
     * PDF groupé : un récapitulatif + une page par employé.
     * Reprend la logique du dashboard pour construire le récap.
     */
    public function exportPeriodePdf(int $periode)
    {
        $periodeModel = $this->periodes->findOrFail($periode);

        // Récupération de tous les bulletins avec leurs relations
        $paies = $this->paies->activeQuery()
            ->with([
                'employe:id,nom_prenom,profession_id',
                'employe.profession:id,libelle',
            ])
            ->where('periode_id', $periode)
            ->orderBy('personnel_id')
            ->get();

        if ($paies->isEmpty()) {
            abort(404, "Aucun bulletin pour cette période.");
        }

        // ============ Récap global (clés alignées sur la vue) ============
        $recap = [
            'nb'              => $paies->count(),
            'total_brut'      => round((float) $paies->sum('total_brut'), 2),
            'total_net'       => round((float) $paies->sum('net_a_payer'), 2),
            'total_deductions'=> round((float) $paies->sum('total_deductions'), 2),
            'total_retenues'  => round((float) $paies->sum('total_retenues'), 2),

            // Billetterie : total prévu + déjà payé + retraits
            'billette' => [
                'nb'                  => $paies->where('mode_paiement', 'billette')->count(),
                'prevu'               => round((float) $paies->where('mode_paiement', 'billette')->sum('net_a_payer'), 2),
                'montant'             => round((float) $paies->where('mode_paiement', 'billette')->where('statut', StatutPaie::PAYE->value)->sum('net_a_payer'), 2),
                'retires'             => $paies->where('mode_paiement', 'billette')->where('retire_a_la_billetterie', true)->count(),
                'en_attente_retrait'  => $paies->where('mode_paiement', 'billette')->where('statut', StatutPaie::PAYE->value)->where('retire_a_la_billetterie', false)->count(),
                'montant_en_attente'  => round((float) $paies->where('mode_paiement', 'billette')->where('statut', StatutPaie::PAYE->value)->where('retire_a_la_billetterie', false)->sum('net_a_payer'), 2),
            ],

            // Banque : total prévu + déjà viré
            'banque' => [
                'nb'      => $paies->where('mode_paiement', 'banque')->count(),
                'prevu'   => round((float) $paies->where('mode_paiement', 'banque')->sum('net_a_payer'), 2),
                'montant' => round((float) $paies->where('mode_paiement', 'banque')->where('statut', StatutPaie::PAYE->value)->sum('net_a_payer'), 2),
            ],
        ];

        $pdf = Pdf::loadView('domain.rh.paies.bulletins-groupe', [
            'periode'   => $periodeModel,
            'paies'     => $paies,
            'recap'     => $recap,
            'genereLe'  => now(),
            'generePar' => auth()->user(),
        ])
        ->setPaper('A4', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'dpi'                  => 130,
            'margin_top'           => 12,
            'margin_bottom'        => 12,
            'margin_left'          => 12,
            'margin_right'         => 12,
        ]);

        $filename = sprintf(
            'bulletins_paie_%s_%s.pdf',
            Str::slug($periodeModel->libelle),
            now()->format('Ymd_His')
        );

        return $pdf->download($filename);
    }
}