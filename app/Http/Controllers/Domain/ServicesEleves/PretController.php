<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\DTO\PretData;
use App\Domain\ServicesEleves\Repositories\LivreRepository;
use App\Domain\ServicesEleves\Repositories\PretRepository;
use App\Domain\ServicesEleves\Services\BibliothequeService;
use App\Domain\ServicesEleves\Types\StatutPret;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\ServicesEleves\StorePretRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PretController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PretRepository       $prets,
        private LivreRepository      $livres,
        private BibliothequeService  $service,
    ) {
        $this->middleware('permission:bibliotheque.voir')->only(['index', 'export']);
        $this->middleware('permission:bibliotheque.preter')->only(['store']);
        $this->middleware('permission:bibliotheque.gerer')->only(['retour', 'perdu', 'recalculerRetards']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'statut_pret', 'livre_id', 'en_retard', 'date_debut', 'date_fin']);

        return view('domain.services-eleves.bibliotheque.prets.index', [
            'prets'    => $this->prets->listePaginee($filters),
            'filters'  => $filters,
            'statuts'  => StatutPret::options(),
            'livres'   => $this->livres->listePourSelect(),
            'retards'  => $this->prets->enRetard(),
            'stats'    => $this->prets->compterStatistiques(),
        ]);
    }

    public function store(StorePretRequest $request)
    {
        $data = PretData::fromRequest($request->validated());

        return $this->runAjax(
            fn () => ['pret' => $this->service->enregistrerPret($data, Auth::id())],
            'Prêt enregistré.'
        );
    }

    public function retour(Request $request, int $pret)
    {
        $data = $request->validate([
            'date_retour_reel' => ['nullable', 'date'],
            'observations'     => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->runAjax(
            fn () => ['pret' => $this->service->enregistrerRetour(
                $pret,
                $data['date_retour_reel'] ?? null,
                $data['observations'] ?? null,
            )],
            'Retour enregistré.'
        );
    }

    public function perdu(Request $request, int $pret)
    {
        $data = $request->validate([
            'observations' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        return $this->runAjax(
            fn () => ['pret' => $this->service->marquerPerdu($pret, $data['observations'])],
            'Prêt marqué comme perdu.'
        );
    }

    public function recalculerRetards()
    {
        return $this->runAjax(
            function () {
                $nb = $this->service->recalculerRetards();
                return ['nb_mis_a_jour' => $nb];
            },
            'Retards mis à jour.'
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'statut_pret', 'livre_id', 'en_retard', 'date_debut', 'date_fin']);
        $prets = $this->prets->listePaginee($filters, perPage: 10000);

        $filename = 'prets_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($prets) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Livre', 'Auteur', 'Élève', 'Matricule', 'Classe',
                'Date prêt', 'Retour prévu', 'Retour réel', 'Statut', 'Jours retard',
            ], ';');

            foreach ($prets as $p) {
                fputcsv($out, [
                    $p->livre?->titre,
                    $p->livre?->auteur?->nom_prenom,
                    trim(($p->inscription?->eleve?->nom ?? '') . ' ' . ($p->inscription?->eleve?->prenom ?? '')),
                    $p->inscription?->eleve?->matricule,
                    $p->inscription?->classe?->libelle,
                    optional($p->date_pret)->format('d/m/Y'),
                    optional($p->date_retour_pret)->format('d/m/Y'),
                    optional($p->date_retour_reel)->format('d/m/Y'),
                    $p->statut_pret?->label(),
                    $p->jours_retard,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}