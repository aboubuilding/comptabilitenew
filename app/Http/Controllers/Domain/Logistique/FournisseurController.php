<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\DTO\FournisseurData;
use App\Domain\Logistique\Repositories\FournisseurRepository;
use App\Domain\Logistique\Services\FournisseurService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreFournisseurRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private FournisseurRepository $fournisseurs,
        private FournisseurService    $service,
    ) {
        $this->middleware('permission:fournisseurs.voir')->only(['index', 'show', 'export', 'exportPdf']);
        $this->middleware('permission:fournisseurs.gerer')->only(['store', 'update', 'destroy']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search']);

        return view('domain.logistique.fournisseurs.index', [
            'fournisseurs' => $this->fournisseurs->listePaginee($filters),
            'filters'      => $filters,
        ]);
    }

    public function show(int $fournisseur)
    {
        return view('domain.logistique.fournisseurs.show', [
            'fournisseur' => $this->fournisseurs->findAvecHistorique($fournisseur),
        ]);
    }

    public function store(StoreFournisseurRequest $request)
    {
        return $this->runAjax(
            fn () => ['fournisseur' => $this->service->creer(
                FournisseurData::fromRequest($request->validated())
            )],
            'Fournisseur créé.'
        );
    }

    public function update(StoreFournisseurRequest $request, int $fournisseur)
    {
        return $this->runAjax(
            fn () => ['fournisseur' => $this->service->modifier(
                $fournisseur,
                FournisseurData::fromRequest($request->validated())
            )],
            'Fournisseur mis à jour.'
        );
    }

    public function destroy(int $fournisseur)
    {
        return $this->runAjax(function () use ($fournisseur) {
            $this->service->supprimer($fournisseur);
            return null;
        }, 'Fournisseur désactivé.');
    }

    /** Export CSV de la liste filtrée. */
    public function export(Request $request): StreamedResponse
    {
        $filters      = $request->only(['search']);
        $fournisseurs = $this->fournisseurs->listePaginee($filters, perPage: 10000);

        $filename = 'fournisseurs_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($fournisseurs) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Raison sociale', 'Contact', 'Téléphone', 'Email',
                'Adresse', 'NIF', 'RCCM',
                'Nombre d\'achats', 'Montant total achats',
            ], ';');

            foreach ($fournisseurs as $f) {
                fputcsv($out, [
                    $f->raison_social,
                    $f->nom_contact,
                    $f->telephone_contact,
                    $f->email_contact,
                    $f->adresse,
                    $f->nif,
                    $f->rccm,
                    $f->nombre_achats,
                    number_format($f->montant_total_achats, 2, ',', ' '),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Export PDF du catalogue fournisseurs. */
    public function exportPdf(Request $request)
    {
        $filters      = $request->only(['search']);
        $fournisseurs = $this->fournisseurs->listePaginee($filters, perPage: 5000);

        $pdf = Pdf::loadView('domain.logistique.fournisseurs.print', [
            'fournisseurs' => $fournisseurs,
            'nbFournisseurs' => $fournisseurs->total(),
            'genereLe'     => now(),
            'generePar'    => auth()->user(),
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

        return $pdf->download('fournisseurs_' . now()->format('Ymd_His') . '.pdf');
    }
}