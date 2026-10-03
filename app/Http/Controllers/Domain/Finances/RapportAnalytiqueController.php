<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\Repositories\CentreAnalytiqueRepository;
use App\Domain\Finances\Services\RapportAnalytiqueService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

use App\Domain\Finances\Exports\RapportAnalytiqueExport;
use Maatwebsite\Excel\Facades\Excel;

class RapportAnalytiqueController extends Controller
{
    public function __construct(
        private RapportAnalytiqueService     $service,
        private CentreAnalytiqueRepository   $centres,
    ) {
        $this->middleware('permission:analytique.voir');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'date_debut', 'date_fin']);

        return view('domain.finances.analytique.rapports.index', [
            'lignes'   => $this->service->tableauDeBord(
                $filters['annee_id'] ?? null,
                $filters['date_debut'] ?? null,
                $filters['date_fin'] ?? null,
            ),
            'filters'  => $filters,
            'annees'   => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'date_debut', 'date_fin']);
        $lignes  = $this->service->tableauDeBord(
            $filters['annee_id'] ?? null,
            $filters['date_debut'] ?? null,
            $filters['date_fin'] ?? null,
        );

        $filename = 'rapport_analytique_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($lignes) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Code', 'Centre analytique', 'Recettes', 'Dépenses',
                'Résultat', 'Budget', 'Écart', 'Taux de réalisation (%)',
            ], ';');

            foreach ($lignes as $l) {
                fputcsv($out, [
                    $l['code'],
                    $l['libelle'],
                    number_format($l['recettes'], 2, ',', ' '),
                    number_format($l['depenses'], 2, ',', ' '),
                    number_format($l['resultat'], 2, ',', ' '),
                    number_format($l['budget'], 2, ',', ' '),
                    number_format($l['ecart_budget'], 2, ',', ' '),
                    number_format($l['taux_realisation'], 2, ',', ' '),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $filters = $request->only(['annee_id', 'date_debut', 'date_fin']);
        $lignes  = $this->service->tableauDeBord(
            $filters['annee_id'] ?? null,
            $filters['date_debut'] ?? null,
            $filters['date_fin'] ?? null,
        );

        $pdf = Pdf::loadView('domain.finances.analytique.rapports.print', [
            'lignes'    => $lignes,
            'filters'   => $filters,
            'genereLe'  => now(),
            'generePar' => auth()->user(),
        ])
        ->setPaper('A4', 'landscape')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'dpi'                  => 130,
        ]);

        return $pdf->download('rapport_analytique_' . now()->format('Ymd_His') . '.pdf');
    }


    // app/Http/Controllers/Domain/Finances/RapportAnalytiqueController.php

public function dashboard(Request $request)
{
    $filters = $request->only(['annee_id', 'date_debut', 'date_fin']);

    $lignes = $this->service->tableauDeBord(
        $filters['annee_id'] ?? null,
        $filters['date_debut'] ?? null,
        $filters['date_fin'] ?? null,
    );

    // Agrégats globaux
    $totaux = [
        'recettes' => round($lignes->sum('recettes'), 2),
        'depenses' => round($lignes->sum('depenses'), 2),
        'budget'   => round($lignes->sum('budget'), 2),
    ];
    $totaux['resultat'] = round($totaux['recettes'] - $totaux['depenses'], 2);
    $totaux['taux_realisation'] = $totaux['budget'] > 0
        ? round(($totaux['resultat'] / $totaux['budget']) * 100, 2)
        : 0.0;

    // Prépare les datasets Chart.js (une seule fois, côté serveur —
    // évite de faire le tri/formatage en JS).
    $chartData = [
        'labels'   => $lignes->pluck('libelle')->all(),
        'codes'    => $lignes->pluck('code')->all(),
        'recettes' => $lignes->pluck('recettes')->all(),
        'depenses' => $lignes->pluck('depenses')->all(),
        'resultats' => $lignes->pluck('resultat')->all(),
    ];

    return view('domain.finances.analytique.dashboard', [
        'lignes'    => $lignes,
        'totaux'    => $totaux,
        'chartData' => $chartData,
        'filters'   => $filters,
        'annees'    => \App\Domain\Scolarite\Models\Annee::orderByDesc('libelle')->get(['id', 'libelle']),
    ]);
}


// app/Http/Controllers/Domain/Finances/RapportAnalytiqueController.php — ajout


public function exportExcel(Request $request)
{
    $filters = $request->only(['annee_id', 'date_debut', 'date_fin']);

    $lignes = $this->service->tableauDeBord(
        $filters['annee_id'] ?? null,
        $filters['date_debut'] ?? null,
        $filters['date_fin'] ?? null,
    );

    $totaux = [
        'recettes' => round($lignes->sum('recettes'), 2),
        'depenses' => round($lignes->sum('depenses'), 2),
        'budget'   => round($lignes->sum('budget'), 2),
    ];
    $totaux['resultat'] = round($totaux['recettes'] - $totaux['depenses'], 2);
    $totaux['taux_realisation'] = $totaux['budget'] > 0
        ? round(($totaux['resultat'] / $totaux['budget']) * 100, 2)
        : 0.0;

    $filename = 'rapport_analytique_multi_' . now()->format('Ymd_His') . '.xlsx';

    return Excel::download(
        new RapportAnalytiqueExport($lignes, $filters, $totaux),
        $filename
    );
}
}