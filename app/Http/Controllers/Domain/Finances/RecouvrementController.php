<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Scolarite\Models\Annee;
use App\Domain\Finances\Repositories\DossierRecouvrementRepository;
use App\Domain\Finances\Repositories\SituationRecouvrementRepository;
use App\Domain\Finances\Services\RecouvrementService;
use App\Domain\Finances\Services\RelanceService;
use App\Domain\Finances\Types\DossierStatut;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\ResultatRelance;
use App\Domain\Finances\Types\TypeRelance;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\ChangeStatutDossierRequest;
use App\Http\Requests\Domain\Finances\StoreRelanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecouvrementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private DossierRecouvrementRepository   $dossiers,
        private SituationRecouvrementRepository $situations,
        private RecouvrementService             $recouvrement,
        private RelanceService                  $relances,
    ) {
        $this->middleware('permission:recouvrement.voir')->only(['index', 'show', 'export', 'planification']);
        $this->middleware('permission:recouvrement.gerer')->only(['changerStatut', 'recalculer']);
        $this->middleware('permission:recouvrement.relancer')->only(['storeRelance', 'relancerParMessage', 'envoyerRelances']);
    }

    /** Onglets : tous les dossiers, dossiers clôturés, historique des relances. */
    private const ONGLETS = ['tous', 'clotures', 'relances'];

    /** Natures suivies par le recouvrement (filtre « Nature »). */
    private function natures(): array
    {
        return collect(\App\Domain\Finances\Services\EcheancesEleveService::NATURES)
            ->mapWithKeys(fn (NatureCreance $n) => [$n->value => $n->label()])->all();
    }

    /** Écran principal : liste des dossiers avec compteurs. */
    public function index(Request $request)
    {
        // L'année est celle de l'en-tête : pas d'autre sélecteur
        $anneeId = app(\App\Support\AnneeScolaireContext::class)->id();
        $onglet = in_array($request->input('onglet'), self::ONGLETS, true) ? $request->input('onglet') : 'tous';
        $filters = ['annee_id' => $anneeId, 'onglet' => $onglet] + $request->only(['statut', 'nature', 'search', 'canal']);
        $clos = [DossierStatut::SOLDE->value, DossierStatut::IRRECOUVRABLE->value];

        return view('domain.finances.recouvrement.index', [
            'onglet'     => $onglet,
            'dossiers'   => $onglet === 'relances' ? null : $this->dossiers->listePaginee($filters, 20),
            'historique' => $onglet === 'relances' ? $this->historiqueRelances($anneeId, $request) : null,
            'filters'    => $filters,
            'statuts'    => DossierStatut::options(),
            'natures'    => $this->natures(),
            'canaux'     => [TypeRelance::EMAIL->value => TypeRelance::EMAIL->label(), TypeRelance::SMS->value => TypeRelance::SMS->label()],
            'modeles'    => RelanceService::MODELES,
            'envoiActif' => (bool) config('finances.relances_envoi_actif'),
            'annee'      => Annee::find($anneeId),
            'compteurs'  => $this->dossiers->compterParStatut($anneeId),
            'montants'   => $this->dossiers->montantsEnCours($anneeId),
            'nbOnglets'  => [
                'tous'     => \App\Domain\Finances\Models\DossierRecouvrement::where('etat', 1)->where('annee_id', $anneeId)->count(),
                'clotures' => \App\Domain\Finances\Models\DossierRecouvrement::where('etat', 1)->where('annee_id', $anneeId)->whereIn('statut', $clos)->count(),
                'relances' => \App\Domain\Finances\Models\Relance::where('etat', 1)->where('annee_id', $anneeId)->count(),
            ],
        ]);
    }

    /** Historique des relances de l'année (toutes, ou d'un canal). */
    private function historiqueRelances(?int $anneeId, Request $request)
    {
        $search = trim((string) $request->input('search'));

        return \App\Domain\Finances\Models\Relance::query()
            ->with(['inscription:id,eleve_id,classe_id,niveau_id', 'inscription.eleve:id,nom,prenom,matricule',
                    'inscription.classe:id,libelle', 'inscription.niveau:id,libelle', 'auteur:id,nom,prenom', 'dossier:id,inscription_id'])
            ->where('etat', 1)
            ->where('annee_id', $anneeId)
            ->when($request->input('canal'), fn ($q, $v) => $q->where('type_relance', $v))
            ->when($search !== '', fn ($q) => $q->whereHas('inscription.eleve', fn ($e) => $e
                ->where('nom', 'like', "%{$search}%")->orWhere('prenom', 'like', "%{$search}%")->orWhere('matricule', 'like', "%{$search}%")))
            ->orderByDesc('date_relance')->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
    }

    /**
     * Relances par message (email ou SMS), à un élève ou en groupe : depuis la
     * liste des dossiers (dossiers[]) ou depuis la planification (inscriptions[]
     * non à jour au jour choisi, pour les natures et l'échéance filtrées).
     */
    public function relancerParMessage(Request $request)
    {
        $valide = $request->validate([
            'canal'          => ['required', 'in:email,sms'],
            'message'        => ['required', 'string', 'max:2000'],
            'dossiers'       => ['nullable', 'array'],
            'dossiers.*'     => ['integer'],
            'inscriptions'   => ['nullable', 'array'],
            'inscriptions.*' => ['integer'],
            'tous'           => ['nullable', 'boolean'],
            'jour'           => ['nullable', 'date'],
            'nature'         => ['nullable', 'string'],
            'niveau_id'      => ['nullable', 'integer'],
            'libelle'        => ['nullable', 'string', 'max:100'],
        ], [
            'canal.required'   => 'Choisissez le canal : email ou SMS.',
            'message.required' => 'Rédigez le message de relance.',
        ]);
        $anneeId = app(\App\Support\AnneeScolaireContext::class)->id();
        $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
        $libelleEcheances = fn ($echeances) => collect($echeances)
            ->map(fn ($e) => $e['libelle'] . ($e['date'] ? ' (' . \Illuminate\Support\Carbon::parse($e['date'])->format('d/m') . ')' : '') . ' : ' . $fmt($e['reste']) . ' F')
            ->implode(', ');

        $cibles = [];
        if (! empty($valide['dossiers'])) {
            $dossiers = \App\Domain\Finances\Models\DossierRecouvrement::with('echeances')
                ->where('etat', 1)->where('annee_id', $anneeId)->whereIn('id', $valide['dossiers'])
                ->whereNotIn('statut', [DossierStatut::SOLDE->value, DossierStatut::IRRECOUVRABLE->value])->get();
            foreach ($dossiers as $d) {
                $cibles[$d->inscription_id] = [
                    'montant'   => $d->montant_restant,
                    'echeances' => $libelleEcheances($d->echeances->filter(fn ($e) => $e->reste > 0.5)
                        ->map(fn ($e) => ['libelle' => $e->libelle, 'date' => $e->date_echeance, 'reste' => $e->reste])),
                ];
            }
        } elseif (! empty($valide['inscriptions']) || ! empty($valide['tous'])) {
            // Planification : élèves non à jour au jour choisi, pour le type de paiement, le niveau et l'échéance filtrés
            $eleves = $this->nonAJour($anneeId, \Illuminate\Support\Carbon::parse($valide['jour'] ?? now()),
                $valide['nature'] ?? null, $valide['niveau_id'] ?? null, $valide['libelle'] ?? null);
            if (empty($valide['tous'])) {
                $eleves = $eleves->whereIn('inscription_id', array_map('intval', $valide['inscriptions']));
            }
            foreach ($eleves as $e) {
                $cibles[$e['inscription_id']] = ['montant' => $e['reste'], 'echeances' => $libelleEcheances($e['echeances'])];
            }
        }

        return $this->runAjax(
            fn () => ['bilan' => $this->relances->relancerParMessage($cibles, $valide['canal'], $valide['message'], Auth::id())],
            'Relances enregistrées.'
        );
    }

    /** Envoi réel des relances sélectionnées dans l'historique (désactivé en développement). */
    public function envoyerRelances(Request $request)
    {
        $valide = $request->validate(['relances' => ['required', 'array', 'min:1'], 'relances.*' => ['integer']],
            ['relances.required' => 'Sélectionnez les relances à envoyer.']);
        $anneeId = app(\App\Support\AnneeScolaireContext::class)->id();
        $ids = \App\Domain\Finances\Models\Relance::where('annee_id', $anneeId)->whereIn('id', $valide['relances'])->pluck('id')->all();

        return $this->runAjax(fn () => ['bilan' => $this->relances->envoyer($ids)], 'Relances envoyées.');
    }

    /**
     * Planification des relances : le calendrier des échéances (par jour et par
     * type de paiement) et les élèves qui ne seront pas à jour à une date donnée,
     * pour une nature et, au besoin, une échéance (« T2 », « novembre »…).
     */
    /** Élèves non à jour au jour donné, filtrés par type de paiement, niveau et échéance. */
    private function nonAJour(?int $anneeId, \Illuminate\Support\Carbon $jour, ?string $nature, ?int $niveauId, ?string $libelle): \Illuminate\Support\Collection
    {
        $nature = array_key_exists((string) $nature, $this->natures()) ? $nature : null;

        return app(\App\Domain\Finances\Services\EcheancesEleveService::class)
            ->elevesNonAJour((int) $anneeId, $jour, $nature ? [$nature] : null, $libelle)
            ->when($niveauId, fn ($c) => $c->where('niveau_id', (int) $niveauId))
            ->values();
    }

    public function planification(Request $request)
    {
        $anneeId = app(\App\Support\AnneeScolaireContext::class)->id();
        $jour = \Illuminate\Support\Carbon::parse($request->input('jour') ?: today()->addDays(30));
        $nature = array_key_exists((string) $request->input('nature'), $this->natures()) ? $request->input('nature') : null;
        $niveauId = $request->integer('niveau_id') ?: null;
        $libelle = trim((string) $request->input('libelle')) ?: null;

        $eleves = $this->nonAJour($anneeId, $jour, $nature, $niveauId, $libelle);
        $page = max(1, (int) $request->input('page', 1));
        $liste = new \Illuminate\Pagination\LengthAwarePaginator(
            $eleves->forPage($page, 50)->values(), $eleves->count(), 50, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('domain.finances.recouvrement.planification', [
            'annee'      => Annee::find($anneeId),
            'jour'       => $jour, 'nature' => $nature, 'niveauId' => $niveauId, 'libelle' => $libelle,
            'natures'    => $this->natures(),
            'niveaux'    => \Illuminate\Support\Facades\DB::table('niveaux')->where('etat', 1)
                ->whereExists(fn ($q) => $q->from('inscriptions')->whereColumn('inscriptions.niveau_id', 'niveaux.id')
                    ->where('inscriptions.annee_id', $anneeId)->where('inscriptions.etat', 1))
                ->orderBy('libelle')->get(['id', 'libelle']),
            // Par type de paiement : élèves concernés et montant manquant au jour choisi
            'parNature'  => collect($eleves->flatMap(fn ($e) => $e['echeances']))->groupBy('nature')->map(fn ($l) => [
                'eleves' => $l->pluck('inscription_id')->unique()->count(), 'reste' => round($l->sum('reste'), 2),
            ]),
            // Relance ciblée : les élèves de la liste, à choisir dans un Select2
            'choix'      => $eleves->map(fn ($e) => ['id' => $e['inscription_id'],
                'text' => $e['nom'] . ' — ' . ($e['matricule'] ?? '') . ' — ' . ($e['niveau'] ?? '') . ($e['classe'] ? ' ' . $e['classe'] : '')
                    . ' (' . number_format($e['reste'], 0, ',', ' ') . ' F)'])->values(),
            'eleves'     => $liste,
            'totalEleves' => ['nb' => $eleves->count(), 'reste' => round($eleves->sum('reste'), 2)],
            'canaux'     => [TypeRelance::EMAIL->value => TypeRelance::EMAIL->label(), TypeRelance::SMS->value => TypeRelance::SMS->label()],
            'modeles'    => RelanceService::MODELES,
        ]);
    }

    /** Fiche détaillée d'un dossier + historique des relances. */
    public function show(int $dossier)
    {
        return view('domain.finances.recouvrement.show', [
            'dossier'        => $this->dossiers->findAvecDetails($dossier),
            'typesRelance'   => TypeRelance::options(),
            'resultats'      => ResultatRelance::options(),
            'statutsDossier' => DossierStatut::options(),
            'canaux'         => [TypeRelance::EMAIL->value => TypeRelance::EMAIL->label(), TypeRelance::SMS->value => TypeRelance::SMS->label()],
            'modeles'        => RelanceService::MODELES,
        ]);
    }

    /** Enregistre une relance. */
    public function storeRelance(StoreRelanceRequest $request, int $dossier)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($dossier, $data) {
            $relance = $this->relances->enregistrer(
                dossierId:       $dossier,
                typeRelance:     $data['type_relance'],
                dateRelance:     $data['date_relance'],
                contenu:         $data['contenu'] ?? null,
                resultat:        $data['resultat'] ?? null,
                dateProchaine:   $data['date_prochaine_relance'] ?? null,
                userId:          Auth::id(),
            );

            return ['relance' => $relance];
        }, 'Relance enregistrée.');
    }

    /** Change le statut d'un dossier. */
    public function changerStatut(ChangeStatutDossierRequest $request, int $dossier)
    {
        $data = $request->validated();

        return $this->runAjax(function () use ($dossier, $data) {
            return [
                'dossier' => $this->relances->changerStatut($dossier, $data['statut'], $data['motif'] ?? null),
            ];
        }, 'Statut du dossier mis à jour.');
    }

    /** Recalcul manuel (bouton d'urgence — la nuit c'est automatique). */
    public function recalculer(Request $request)
    {
        // Toutes les inscriptions de l'année sont recalculées : peut dépasser la durée par défaut
        @set_time_limit(300);

        $anneeId = app(\App\Support\AnneeScolaireContext::class)->id();

        return $this->runAjax(
            fn () => $this->recouvrement->recalculerTout($anneeId),
            'Dossiers de recouvrement recalculés d\'après les plans d\'échéancier.'
        );
    }

    /** Export CSV de la liste des dossiers. */
    public function export(Request $request): StreamedResponse
    {
        $filters  = ['annee_id' => app(\App\Support\AnneeScolaireContext::class)->id()] + $request->only(['onglet', 'statut', 'nature', 'search']);
        $dossiers = $this->dossiers->listePaginee($filters, perPage: 10000);

        $filename = 'recouvrement_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($dossiers) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Élève', 'Matricule', 'Niveau', 'Classe', 'Échéances réclamées', 'Montant échu',
                'Recouvré', 'Restant', 'Statut', 'Ouvert le', 'Clos le', 'Paiement de clôture',
            ], ';');

            foreach ($dossiers as $d) {
                fputcsv($out, [
                    trim(($d->inscription?->eleve?->nom ?? '') . ' ' . ($d->inscription?->eleve?->prenom ?? '')),
                    $d->inscription?->eleve?->matricule,
                    $d->inscription?->niveau?->libelle,
                    $d->inscription?->classe?->libelle,
                    $d->echeances->pluck('libelle')->implode(', '),
                    number_format((float) $d->montant_initial_du, 2, ',', ' '),
                    number_format((float) $d->montant_recouvre, 2, ',', ' '),
                    number_format($d->montant_restant, 2, ',', ' '),
                    $d->statut?->label(),
                    optional($d->date_ouverture)->format('d/m/Y'),
                    optional($d->date_cloture)->format('d/m/Y'),
                    $d->paiementCloture?->reference,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}