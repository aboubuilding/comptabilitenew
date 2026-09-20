<?php

namespace App\Http\Controllers\Admin\Finances;

use App\Domain\Finances\Models\Detail;
use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\DetailRepository;
use App\Domain\Finances\Services\EncaissementService;
use App\Domain\Finances\Types\StatutPaiement;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finances\AnnulerEncaissementRequest;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * L'écran est bloqué tant que le caissier connecté n'a pas de caisse
 * ouverte — le module Caisses (ouverture/fermeture) n'est pas encore
 * construit ; en attendant, un message explicite s'affiche plutôt qu'une
 * file vide silencieuse. Export Excel hors scope de ce tour.
 */
class EncaissementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private DetailRepository $details,
        private CaisseRepository $caisses,
        private EncaissementService $encaissementService,
        private AnneeScolaireContext $anneeContext,
    ) {
    }

    public function index(): View
    {
        $anneeId = (int) $this->anneeContext->id();
        $caisse = $this->caisses->caisseOuvertePour(Auth::id(), $anneeId);

        $enAttente = collect();
        $historiqueJour = collect();

        if ($caisse) {
            $enAttente = $this->details->activeQuery()
                ->where('annee_id', $anneeId)
                ->where('statut_paiement', StatutPaiement::EnAttente->value)
                ->with(['paiement.inscription.eleve', 'paiement.utilisateur'])
                ->oldest('date_paiement')
                ->get();

            $historiqueJour = $this->details->activeQuery()
                ->where('caissier_id', Auth::id())
                ->whereDate('date_encaissement', today())
                ->with(['paiement.inscription.eleve', 'paiement.utilisateur', 'caisse'])
                ->latest('date_encaissement')
                ->get();
        }

        return view('admin.finances.encaissements.index', [
            'caisse'         => $caisse,
            'enAttente'      => $enAttente,
            'historiqueJour' => $historiqueJour,
        ]);
    }

    public function encaisser(Detail $detail): RedirectResponse
    {
        $anneeId = (int) $this->anneeContext->id();
        $caisse = $this->caisses->caisseOuvertePour(Auth::id(), $anneeId);

        if (! $caisse) {
            return back()->with('error', "Vous n'avez aucune caisse ouverte.");
        }

        return $this->runWeb(function () use ($detail, $caisse) {
            $this->encaissementService->encaisser($detail, $caisse, Auth::id());
        }, 'finances.encaissements.index', 'Paiement encaissé avec succès.');
    }

    public function annuler(AnnulerEncaissementRequest $request, Detail $detail): RedirectResponse
    {
        return $this->runWeb(function () use ($request, $detail) {
            $this->encaissementService->annuler($detail, $request->validated()['motif']);
        }, 'finances.encaissements.index', 'Encaissement annulé.');
    }
}