<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Repositories\SouscriptionRepository;
use App\Domain\Finances\Services\SouscriptionService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\StoreSouscriptionRequest;
use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Repositories\InscriptionRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SouscriptionController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private SouscriptionRepository $souscriptions,
        private FraisEcoleRepository   $frais,
        private SouscriptionService    $service,
        private InscriptionRepository  $inscriptions,
    ) {
        $this->middleware('permission:souscriptions.voir')->only(['index', 'export']);
        $this->middleware('permission:souscriptions.gerer')->only(['store', 'destroy']);
        // Cahier des charges § 5.2 : la Direction arbitre aussi les taux de remise.
        $this->middleware('permission:souscriptions.remise')->only(['update']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'frais_ecole_id', 'inscription_id', 'search']);

        return view('domain.finances.souscriptions.index', [
            'souscriptions' => $this->souscriptions->listePaginee($filters),
            'filters'       => $filters,
            'frais'         => $this->frais->listePourSelect(),
            'annees'        => Annee::orderByDesc('libelle')->get(['id', 'libelle']),
            'inscriptions'  => $this->inscriptions->listePourSelect(app(AnneeScolaireContext::class)->id()),
        ]);
    }

    public function store(StoreSouscriptionRequest $request)
    {
        return $this->runAjax(
            fn () => ['souscription' => $this->service->creer(
                SouscriptionData::fromRequest($request->validated(), Auth::id())
            )],
            'Souscription enregistrée.'
        );
    }

    public function update(Request $request, int $souscription)
    {
        $taux = (float) $request->validate([
            'taux_remise' => ['required', 'numeric', 'min:0', 'max:100'],
        ])['taux_remise'];

        return $this->runAjax(
            fn () => ['souscription' => $this->service->modifierRemise($souscription, $taux)],
            'Remise mise à jour.'
        );
    }

    public function destroy(int $souscription)
    {
        return $this->runAjax(function () use ($souscription) {
            $this->service->supprimer($souscription);
            return null;
        }, 'Souscription désactivée.');
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['annee_id', 'frais_ecole_id', 'inscription_id', 'search']);
        $souscriptions = $this->souscriptions->listePaginee($filters, perPage: 10000);

        $filename = 'souscriptions_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($souscriptions) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Date', 'Élève', 'Matricule', 'Frais',
                'Montant annuel', 'Remise (%)', 'Montant remise',
                'Montant net', 'Souscrit par',
            ], ';');

            foreach ($souscriptions as $s) {
                fputcsv($out, [
                    optional($s->date_souscription)->format('d/m/Y'),
                    trim(($s->inscription?->eleve?->nom ?? '') . ' ' . ($s->inscription?->eleve?->prenom ?? '')),
                    $s->inscription?->eleve?->matricule,
                    $s->fraisEcole?->libelle,
                    number_format((float) $s->montant_annuel_prevu, 2, ',', ' '),
                    number_format((float) $s->taux_remise, 2, ',', ' '),
                    number_format($s->montant_remise, 2, ',', ' '),
                    number_format($s->montant_net, 2, ',', ' '),
                    $s->auteur?->name,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}