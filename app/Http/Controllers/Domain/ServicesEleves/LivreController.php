<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\LivreData;
use App\Domain\ServicesEleves\Repositories\AuteurRepository;
use App\Domain\ServicesEleves\Repositories\CategorieLivreRepository;
use App\Domain\ServicesEleves\Repositories\LivreRepository;
use App\Domain\ServicesEleves\Repositories\MaisonEditionRepository;
use App\Domain\ServicesEleves\Services\BibliothequeService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StoreLivreRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;


class LivreController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private LivreRepository          $livres,
        private AuteurRepository         $auteurs,
        private MaisonEditionRepository  $maisons,
        private CategorieLivreRepository $categories,
        private BibliothequeService      $service,
    ) {
        $this->middleware('permission:bibliotheque.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:bibliotheque.gerer')->only(['store', 'update', 'destroy']);
        $this->middleware('permission:bibliotheque.voir')->only(['exportPdf']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'categorie_livre_id', 'auteur_id', 'disponible']);

        return view('domain.services-eleves.bibliotheque.livres.index', [
            'livres'      => $this->livres->listePaginee($filters),
            'filters'     => $filters,
            'auteurs'     => $this->auteurs->listePourSelect(),
            'maisons'     => $this->maisons->listePourSelect(),
            'categories'  => $this->categories->listePourSelect(),
            'stats'       => $this->livres->compterStatistiques(),
        ]);
    }

    public function show(int $livre)
    {
        return view('domain.services-eleves.bibliotheque.livres.show', [
            'livre' => $this->livres->findAvecRelations($livre),
        ]);
    }

    public function store(StoreLivreRequest $request)
    {
        return $this->runAjax(
            fn () => ['livre' => $this->service->creerLivre(LivreData::fromRequest($request->validated()))],
            'Livre ajouté au catalogue.'
        );
    }

    public function update(StoreLivreRequest $request, int $livre)
    {
        return $this->runAjax(
            fn () => ['livre' => $this->service->modifierLivre($livre, LivreData::fromRequest($request->validated()))],
            'Livre mis à jour.'
        );
    }

    public function destroy(int $livre)
    {
        return $this->runAjax(function () use ($livre) {
            $this->service->supprimerLivre($livre);
            return null;
        }, 'Livre désactivé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'categorie_livre_id', 'auteur_id', 'disponible']);
        $livres = $this->livres->listePaginee($filters, perPage: 10000);

        $filename = 'catalogue_livres_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($livres) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Titre', 'Auteur', 'Catégorie', 'Éditeur', 'Année édition',
                'N° ISBN', 'Exemplaires', 'Disponibles',
            ], ';');

            foreach ($livres as $l) {
                fputcsv($out, [
                    $l->titre,
                    $l->auteur?->nom_prenom,
                    $l->categorie?->libelle,
                    $l->maisonEdition?->libelle,
                    $l->anneeEdition?->libelle,
                    $l->numero_isbn,
                    $l->nombre_exemplaires,
                    $l->nombre_disponibles,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

public function exportPdf(Request $request)
{
    $filters = $request->only(['search', 'categorie_livre_id', 'auteur_id', 'disponible']);
    $livres  = $this->livres->listePaginee($filters, perPage: 5000);

    $totaux = [
        'nb_titres'          => $livres->total(),
        'nb_exemplaires'     => (int) $livres->sum('nombre_exemplaires'),
        'nb_disponibles'     => (int) $livres->sum('nombre_disponibles'),
        'nb_indisponibles'   => $livres->filter(fn ($l) => $l->nombre_disponibles === 0)->count(),
    ];

    $filtreLabels = array_filter([
        ! empty($filters['search'])             ? 'Recherche : « ' . $filters['search'] . ' »' : null,
        ! empty($filters['categorie_livre_id']) ? 'Catégorie filtrée' : null,
        ! empty($filters['auteur_id'])          ? 'Auteur filtré' : null,
        ! empty($filters['disponible'])         ? 'Disponibles uniquement' : null,
    ]);

    $pdf = Pdf::loadView('domain.services-eleves.bibliotheque.livres.print', [
        'livres'       => $livres,
        'totaux'       => $totaux,
        'filtreLabels' => $filtreLabels,
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
        'margin_left'          => 10,
        'margin_right'         => 10,
    ]);

    return $pdf->download('catalogue_bibliotheque_' . now()->format('Ymd_His') . '.pdf');
}


}