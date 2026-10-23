<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\Repositories\CaisseRepository;
use App\Domain\Finances\Repositories\DecaissementRepository;
use App\Domain\Finances\Services\DepenseService;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Décaissements — sur le modèle des encaissements : le caissier enregistre la
 * sortie d'argent d'une dépense validée dans sa caisse ouverte ; le mouvement
 * figure au journal de la caisse. Quatre onglets : tous les décaissements,
 * les dépenses en attente (validées ou non, pas encore décaissées), mes
 * décaissements, et ceux de ma caisse ouverte (seulement si j'en ai une).
 * Seule une dépense validée se décaisse.
 */
class DecaissementController extends Controller
{
    use HandlesControllerErrors;

    private const ONGLETS = ['tous', 'attente', 'justificatifs', 'mes', 'caisse'];

    public function __construct(
        private DecaissementRepository $decaissements,
        private CaisseRepository       $caisses,
        private DepenseService         $depenses,
    ) {
        $this->middleware('permission:decaissements.voir')->only(['index']);
        $this->middleware('permission:decaissements.creer')->only(['decaisser']);
        $this->middleware('permission:depenses.justifier')->only(['justificatif']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $anneeId = app(AnneeScolaireContext::class)->id();
        $peutDecaisser = $user->can('decaissements.creer');
        $caisseOuverte = $this->caisses->findOuverteParCaissier((int) $user->id);

        $disponibles = array_values(array_filter(self::ONGLETS, fn ($o) => match ($o) {
            'mes'    => $peutDecaisser,
            'caisse' => $peutDecaisser && $caisseOuverte,
            default  => true,
        }));
        $onglet = in_array($request->input('onglet'), $disponibles, true)
            ? $request->input('onglet')
            : ($peutDecaisser ? ($caisseOuverte ? 'caisse' : 'mes') : 'tous');

        $filtres = ['annee_id' => $anneeId] + $request->only(['search', 'caissier_id', 'date_debut', 'date_fin', 'type']);
        $filtresTous = $filtres + ['utilisateur_id' => $request->input('caissier_id')];
        $filtresMes = ['annee_id' => $anneeId, 'utilisateur_id' => (int) $user->id] + $request->only(['search', 'date_debut', 'date_fin']);
        // Caisse ouverte : tout ce qui en est sorti, quelle que soit l'année de la dépense
        $filtresCaisse = $caisseOuverte ? ['caisse_id' => (int) $caisseOuverte->id] : null;

        $liste = match ($onglet) {
            'mes'     => $filtresMes,
            'caisse'  => $filtresCaisse,
            'attente', 'justificatifs' => ['utilisateur_id' => -1],   // pas de mouvements sur ces onglets
            default   => $filtresTous,
        };

        return view('domain.finances.decaissements.index', [
            'onglet'        => $onglet,
            'onglets'       => $disponibles,
            'annee'         => \App\Domain\Scolarite\Models\Annee::find($anneeId),
            'filtres'       => $filtres,
            'decaissements' => $this->decaissements->pagines($liste),
            'total'         => $this->decaissements->total($liste),
            'compteurs'     => [
                'tous'   => $this->decaissements->total(['annee_id' => $anneeId]),
                'mes'    => $peutDecaisser ? $this->decaissements->total(['annee_id' => $anneeId, 'utilisateur_id' => (int) $user->id]) : null,
                'caisse' => $filtresCaisse ? $this->decaissements->total($filtresCaisse) : null,
            ],
            'caissiers'     => $this->decaissements->caissiers($anneeId),
            'aDecaisser'    => $this->decaissements->depensesADecaisser($anneeId),
            'enAttente'     => $onglet === 'attente'
                ? $this->decaissements->depensesEnAttente($anneeId, $request->only(['search', 'statut'])) : null,
            'compteurAttente' => $this->decaissements->compterEnAttente($anneeId),
            'sansJustificatif' => $onglet === 'justificatifs'
                ? $this->decaissements->depensesSansJustificatif($anneeId, $request->only(['search'])) : null,
            'compteurJustif' => $this->decaissements->compterSansJustificatif($anneeId),
            'caisseOuverte' => $caisseOuverte,
            'peutDecaisser' => $peutDecaisser,
        ]);
    }

    /**
     * Décaisse une dépense validée depuis MA caisse ouverte : le caissier
     * confirme le montant réellement décaissé ; la sortie est inscrite au
     * journal de sa caisse. On ne décaisse jamais dans la caisse d'un autre.
     */
    public function decaisser(Request $request, int $depense)
    {
        $valide = $request->validate([
            'montant_decaisse' => ['required', 'numeric', 'min:1'],
            'caisse_id'        => ['required', 'integer'],
            // Le justificatif peut être joint au moment du décaissement
            'justificatif'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ], [
            'montant_decaisse.required' => 'Indiquez le montant réellement décaissé.',
            'montant_decaisse.min'      => 'Le montant décaissé doit être strictement positif.',
            'caisse_id.required'        => 'Choisissez la caisse qui a servi au décaissement.',
        ]);

        return $this->runAjax(function () use ($request, $depense, $valide) {
            $caisse = $this->caisses->findOuverteParCaissier((int) $request->user()->id);
            if (! $caisse) {
                throw new \RuntimeException("Vous n'avez pas de caisse ouverte : ouvrez votre caisse pour décaisser.");
            }
            if ((int) $valide['caisse_id'] !== (int) $caisse->id) {
                throw new \RuntimeException("On ne décaisse que dans sa propre caisse ouverte.");
            }

            $resultat = $this->depenses->reglerViaCaisse($depense, $caisse->id, (int) Auth::id(), (float) $valide['montant_decaisse']);
            if ($request->hasFile('justificatif')) {
                $resultat = $this->depenses->joindreJustificatif($depense, $request->file('justificatif'));
            }

            return ['depense' => $resultat];
        }, 'Décaissement enregistré dans la caisse.');
    }

    /** Joint (ou remplace) le justificatif d'une dépense. */
    public function justificatif(Request $request, int $depense)
    {
        $request->validate([
            'justificatif' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
        ], [
            'justificatif.required' => 'Choisissez le fichier du justificatif.',
            'justificatif.mimes'    => 'Le justificatif doit être un PDF, une image (JPG, PNG) ou un document Word.',
            'justificatif.max'      => 'Le justificatif ne doit pas dépasser 5 Mo.',
        ]);

        return $this->runAjax(
            fn () => ['depense' => $this->depenses->joindreJustificatif($depense, $request->file('justificatif'))],
            'Justificatif enregistré.'
        );
    }
}
