<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\Logistique\Types\TypeProduit;
use App\Domain\Logistique\Repositories\StockMouvementRepository;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreProduitRequest;
use App\Support\FileUploadService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Barryvdh\DomPDF\Facade\Pdf;

class ProduitController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ProduitRepository         $produits,
        private MagasinRepository         $magasins,
        private StockMouvementRepository  $mouvements,
        private FileUploadService         $files,
    ) {
        $this->middleware('permission:stock.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:stock.gerer')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:stock.voir')->only(['exportPdf']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'type_produit']);

        return view('domain.logistique.produits.index', [
            'produits'    => $this->produits->listePaginee($filters),
            'filters'     => $filters,
            'types'       => TypeProduit::options(),
            'stats'       => $this->produits->compterStatistiques(),
        ]);
    }

    public function show(int $produit)
    {
        $produitModel = $this->produits->findAvecStocks($produit);

        return view('domain.logistique.produits.show', [
            'produit'  => $produitModel,
            'magasins' => $this->magasins->listePourSelect(),
            'historique' => $this->mouvements->listePaginee(['produit_id' => $produit], perPage: 50),
        ]);
    }

    public function store(StoreProduitRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->files->store($request->file('photo'), 'produits/photos');
        }

        return $this->runAjax(
            fn () => ['produit' => $this->produits->create($data)],
            'Produit créé.'
        );
    }

    public function update(StoreProduitRequest $request, int $produit)
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $ancien = $this->produits->findOrFail($produit);
            $this->files->delete($ancien->photo);
            $data['photo'] = $this->files->store($request->file('photo'), 'produits/photos');
        }

        $this->produits->update($produit, $data);

        return $this->runAjax(fn () => null, 'Produit mis à jour.');
    }

    public function destroy(int $produit)
    {
        return $this->runAjax(function () use ($produit) {
            $this->produits->delete($produit);
            return null;
        }, 'Produit désactivé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters  = $request->only(['search', 'type_produit']);
        $produits = $this->produits->listePaginee($filters, perPage: 10000);

        $filename = 'produits_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($produits) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Libellé', 'Unité stock', 'Prix unitaire', 'Type',
                'Stock total', 'Valeur stock',
            ], ';');

            foreach ($produits as $p) {
                fputcsv($out, [
                    $p->libelle,
                    $p->unite_stock,
                    number_format((float) $p->prix_unitaire, 2, ',', ' '),
                    $p->type_produit?->label(),
                    number_format($p->stock_total, 2, ',', ' '),
                    number_format($p->valeur_stock_total, 2, ',', ' '),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // app/Http/Controllers/Domain/Logistique/ProduitController.php — ajout


public function exportPdf(Request $request)
{
    $filters  = $request->only(['search', 'type_produit', 'alerte']);
    $produits = $this->produits->listePaginee($filters, perPage: 5000);

    // Agrégats sur le jeu filtré
    $totaux = [
        'nb'                => $produits->total(),
        'valeur_stock'      => round((float) $produits->sum(fn ($p) => $p->valeur_stock_total), 2),
        'quantite_totale'   => round((float) $produits->sum(fn ($p) => $p->stock_total), 2),
        'en_alerte'         => $produits->filter(fn ($p) => $p->est_en_alerte)->count(),
        'en_rupture'        => $produits->filter(fn ($p) => $p->est_en_rupture)->count(),
    ];

    // Filtres appliqués (pour le sous-titre)
    $filtreLabels = array_filter([
        ! empty($filters['search'])       ? 'Recherche : « ' . $filters['search'] . ' »' : null,
        ! empty($filters['type_produit']) ? 'Type : ' . (\App\Domain\Logistique\Types\TypeProduit::tryFrom((int) $filters['type_produit'])?->label() ?? '') : null,
        ! empty($filters['alerte'])       ? 'Produits en alerte uniquement' : null,
    ]);

    $pdf = Pdf::loadView('domain.logistique.produits.print', [
        'produits'     => $produits,
        'totaux'       => $totaux,
        'filtreLabels' => $filtreLabels,
        'genereLe'     => now(),
        'generePar'    => auth()->user(),
    ])
    ->setPaper('A4', 'landscape')
    ->setOptions([
        'defaultFont'          => 'DejaVu Sans',
        'isHtml5ParserEnabled' => true,
        'dpi'                  => 130,
        'margin_top'           => 12,
        'margin_bottom'        => 12,
        'margin_left'          => 10,
        'margin_right'         => 10,
    ]);

    $filename = 'catalogue_produits_' . now()->format('Ymd_His') . '.pdf';

    return $pdf->download($filename);
}
}