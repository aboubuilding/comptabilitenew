<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Logistique\DTO\AchatData;
use App\Domain\Logistique\Repositories\AchatRepository;
use App\Domain\Logistique\Repositories\FournisseurRepository;
use App\Domain\Logistique\Services\AchatService;
use App\Domain\Logistique\Types\AchatStatutLivraison;
use App\Domain\Logistique\Types\AchatStatutPaiement;
use App\Domain\Logistique\Types\AchatType;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Logistique\AnnulerAchatRequest;
use App\Http\Requests\Domain\Logistique\ReceptionnerAchatRequest;
use App\Http\Requests\Domain\Logistique\StoreAchatRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AchatController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private AchatRepository       $achats,
        private FournisseurRepository $fournisseurs,
        private AchatService          $service,
    ) {
        $this->middleware('permission:achats.voir')->only(['index', 'show', 'export']);
        $this->middleware('permission:achats.gerer')->only(['store', 'update', 'annuler']);
        $this->middleware('permission:achats.receptionner')->only(['receptionner']);
    }

    public function index(Request $request)
    {
        $filters = $request->only([
            'annee_id', 'fournisseur_id', 'statut_livraison', 'statut_paiement',
            'search', 'date_debut', 'date_fin',
        ]);

        return view('domain.logistique.achats.index', [
            'achats'           => $this->achats->listePaginee($filters),
            'filters'          => $filters,
            'fournisseurs'     => $this->fournisseurs->listePourSelect(),
            'annees'           => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'statutsLivraison' => AchatStatutLivraison::options(),
            'statutsPaiement'  => AchatStatutPaiement::options(),
            'typesAchat'       => AchatType::options(),
            'compteurs'        => $this->achats->compterParStatut(),
            'montants'         => $this->achats->montantsParStatut(),
            'produitsDispo'    => Schema::hasTable('produits')
                ? \DB::table('produits')->where('etat', 1)->orderBy('libelle')->limit(500)
                    ->get(['id', 'libelle', 'code'])
                : collect(),
        ]);
    }

    public function show(int $achat)
    {
        return view('domain.logistique.achats.show', [
            'achat' => $this->achats->findAvecDetails($achat),
        ]);
    }

    public function store(StoreAchatRequest $request)
    {
        return $this->runAjax(
            fn () => ['achat' => $this->service->creer(AchatData::fromRequest($request->validated()))],
            'Achat enregistré (en attente de livraison).'
        );
    }

    public function update(StoreAchatRequest $request, int $achat)
    {
        return $this->runAjax(
            fn () => ['achat' => $this->service->modifier($achat, AchatData::fromRequest($request->validated()))],
            'Achat mis à jour.'
        );
    }

    public function receptionner(ReceptionnerAchatRequest $request, int $achat)
    {
        return $this->runAjax(
            fn () => ['achat' => $this->service->receptionner($achat, Auth::id())],
            'Achat réceptionné. Stock mis à jour.'
        );
    }

    public function annuler(AnnulerAchatRequest $request, int $achat)
    {
        return $this->runAjax(
            fn () => ['achat' => $this->service->annuler($achat, $request->validated()['motif'])],
            'Achat annulé.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only([
            'annee_id', 'fournisseur_id', 'statut_livraison', 'statut_paiement',
            'search', 'date_debut', 'date_fin',
        ]);
        $achats = $this->achats->listePaginee($filters, perPage: 10000);

        $filename = 'achats_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($achats) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Référence', 'Bon de commande', 'Date', 'Fournisseur',
                'Acheteur', 'Type', 'Montant total',
                'Livraison', 'Paiement',
            ], ';');

            foreach ($achats as $a) {
                fputcsv($out, [
                    $a->reference,
                    $a->bon_commande,
                    optional($a->date_achat)->format('d/m/Y'),
                    $a->fournisseur?->raison_social,
                    $a->nom_acheteur,
                    $a->type_achat?->label(),
                    number_format((float) $a->montant_total, 2, ',', ' '),
                    $a->statut_livraison?->label(),
                    $a->statut_paiement?->label(),
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}