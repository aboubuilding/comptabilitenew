<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\DTO\VenteData;
use App\Domain\Logistique\Repositories\ClientRepository;
use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Domain\Logistique\Repositories\ProduitRepository;
use App\Domain\Logistique\Repositories\VenteRepository;
use App\Domain\Logistique\Services\VenteService;
use App\Domain\Logistique\Types\TypeVente;
use App\Domain\Logistique\Types\VenteStatutPaiement;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\StoreVenteRequest;
use App\Support\AnneeScolaireContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VenteController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private VenteRepository   $ventes,
        private MagasinRepository $magasins,
        private ProduitRepository $produits,
        private ClientRepository  $clients,
        private VenteService      $service,
    ) {
        $this->middleware('permission:ventes.voir')->only(['index', 'show', 'ticket', 'export']);
        $this->middleware('permission:ventes.creer')->only(['store']);
        $this->middleware('permission:ventes.annuler')->only(['annuler', 'marquerPayee']);
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'annee_id', 'magasin_id', 'type_vente', 'statut_paiement',
            'search', 'date_debut', 'date_fin', 'jour',
        ]);

        $anneeId = app(AnneeScolaireContext::class)->id();

        return view('domain.logistique.ventes.index', [
            'ventes'      => $this->ventes->listePaginee($filters),
            'filters'     => $filters,
            'magasins'    => $this->magasins->listePourSelect(),
            'produits'    => $this->produits->listePourSelect(),
            'clients'     => $this->clients->listePourSelect(),
            'inscriptions' => app(\App\Domain\Scolarite\Repositories\InscriptionRepository::class)->listePourSelect($anneeId),
            'types'       => TypeVente::options(),
            'statuts'     => VenteStatutPaiement::options(),
            'stats'       => $this->ventes->compterStatistiques($anneeId),
        ]);
    }

    public function show(int $vente)
    {
        return view('domain.logistique.ventes.show', [
            'vente' => $this->ventes->findAvecDetails($vente),
        ]);
    }

    public function store(StoreVenteRequest $request)
    {
        return $this->runAjax(
            fn () => [
                'vente'     => $this->service->enregistrer(VenteData::fromRequest($request->validated(), Auth::id())),
                'ticket_url' => null, // généré après dans la vue
            ],
            'Vente enregistrée.'
        );
    }

    public function annuler(Request $request, int $vente)
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        return $this->runAjax(
            fn () => ['vente' => $this->service->annuler($vente, $data['motif'], Auth::id())],
            'Vente annulée et stock réintégré.'
        );
    }

    public function marquerPayee(int $vente)
    {
        return $this->runAjax(
            fn () => ['vente' => $this->service->marquerPayee($vente)],
            'Vente marquée comme payée.'
        );
    }

    /** Ticket imprimable (PDF A5, format ticket). */
    public function ticket(int $vente)
    {
        $venteModel = $this->ventes->findAvecDetails($vente);

        $pdf = Pdf::loadView('domain.logistique.ventes.ticket', [
            'vente'     => $venteModel,
            'genereLe'  => now(),
        ])
        ->setPaper('A5', 'portrait')
        ->setOptions([
            'defaultFont'          => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'dpi'                  => 150,
            'margin_top'           => 6,
            'margin_bottom'        => 6,
            'margin_left'          => 8,
            'margin_right'         => 8,
        ]);

        $filename = 'ticket_' . ($venteModel->reference ?? $vente) . '.pdf';

        return $pdf->stream($filename);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'annee_id', 'magasin_id', 'type_vente', 'statut_paiement',
            'search', 'date_debut', 'date_fin',
        ]);
        $ventes = $this->ventes->listePaginee($filters, perPage: 10000);

        $filename = 'ventes_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($ventes) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Référence', 'Date', 'Acheteur', 'Type',
                'Magasin', 'Total HT', 'Total TTC', 'Statut',
            ], ';');

            foreach ($ventes as $v) {
                fputcsv($out, [
                    $v->reference,
                    optional($v->date_vente)->format('d/m/Y'),
                    $v->acheteur_nom,
                    $v->type_vente?->label(),
                    $v->magasin?->libelle,
                    number_format((float) $v->total_ht, 2, ',', ' '),
                    number_format((float) $v->total_ttc, 2, ',', ' '),
                    $v->statut_paiement?->label(),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}