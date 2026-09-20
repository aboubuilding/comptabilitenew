<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Administration\Models\Annee;
use App\Domain\Finances\DTO\ChequeData;
use App\Domain\Finances\Repositories\BanqueRepository;
use App\Domain\Finances\Repositories\ChequeRepository;
use App\Domain\Finances\Services\ChequeService;
use App\Domain\Finances\Types\ChequeStatut;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\RapprocherChequeRequest;
use App\Http\Requests\Domain\Finances\StoreChequeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private ChequeRepository $cheques,
        private BanqueRepository $banques,
        private ChequeService    $service,
    ) {
        $this->middleware('permission:cheques.voir')->only(['index', 'export']);
        $this->middleware('permission:cheques.gerer')->only(['store']);
        $this->middleware('permission:cheques.rapprocher')->only(['rapprocher']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'statut', 'banque_id', 'search', 'date_debut', 'date_fin']);

        return view('domain.finances.cheques.index', [
            'cheques'         => $this->cheques->listePaginee($filters),
            'filters'         => $filters,
            'statuts'         => ChequeStatut::options(),
            'banques'         => $this->banques->listePourSelect(),
            'annees'          => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'compteurs'       => $this->cheques->compterParStatut(),
            'montantAttente'  => $this->cheques->montantEnAttente(),
        ]);
    }

    public function store(StoreChequeRequest $request)
    {
        return $this->runAjax(
            fn () => ['cheque' => $this->service->enregistrer(ChequeData::fromRequest($request->validated()))],
            'Chèque enregistré.'
        );
    }

    public function rapprocher(RapprocherChequeRequest $request, int $cheque)
    {
        $data   = $request->validated();
        $statut = ChequeStatut::from((int) $data['statut']);
        $userId = Auth::id();

        return $this->runAjax(function () use ($cheque, $statut, $data, $userId) {
            $result = match ($statut) {
                ChequeStatut::ENCAISSE => $this->service->marquerEncaisse(
                    $cheque, $userId, $data['date_encaissement'] ?? null
                ),
                ChequeStatut::REJETE   => $this->service->marquerRejete(
                    $cheque, $userId, $data['motif_rejet']
                ),
                default                => null,
            };

            return ['cheque' => $result];
        }, 'Rapprochement enregistré.');
    }

    /** Export CSV (ouvrable dans Excel) de la liste filtrée. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'statut', 'banque_id', 'search', 'date_debut', 'date_fin']);
        $cheques = $this->cheques->listePaginee($filters, perPage: 10000);

        $filename = 'cheques_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($cheques) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Numéro', 'Émetteur', 'Montant', 'Banque',
                'Date émission', 'Statut', "Date d'encaissement", 'Motif de rejet',
            ], ';');

            foreach ($cheques as $c) {
                fputcsv($out, [
                    $c->numero,
                    $c->emetteur,
                    number_format((float) $c->montant, 2, ',', ' '),
                    $c->banque?->nom,
                    optional($c->date_emission)->format('d/m/Y'),
                    $c->statut?->label(),
                    optional($c->date_encaissement)->format('d/m/Y'),
                    $c->motif_rejet,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}