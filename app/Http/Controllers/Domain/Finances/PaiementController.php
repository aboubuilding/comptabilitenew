<?php

namespace App\Http\Controllers\Domain\Finances;

use App\Domain\Finances\DTO\PaiementData;
use App\Domain\Finances\Repositories\PaiementRepository;
use App\Domain\Finances\Services\PaiementService;
use App\Domain\Finances\Services\EngagementService;
use App\Domain\Finances\Services\PanierRechercheService;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Support\AnneeScolaireContext;
use App\Domain\Finances\Types\ModePaiement;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Scolarite\Models\Inscription;
use App\Http\Controllers\Concerns\HandlesControllerErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Finances\AnnulerPaiementRequest;
use App\Http\Requests\Domain\Finances\StorePaiementRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaiementController extends Controller
{
    use HandlesControllerErrors;

    public function __construct(
        private PaiementRepository       $paiements,
        private PaiementService          $service,
        private PanierRechercheService   $panier,
        private EngagementService        $engagements,
    ) {
        $this->middleware('permission:paiements.voir')->only(['index', 'detail', 'export']);
        $this->middleware('permission:paiements.creer')->only(['rechercheEleve', 'eleves', 'show', 'rechercherPanier', 'store', 'update']);
        $this->middleware('permission:paiements.annuler')->only(['annuler']);
    }

    /** Liste globale des paiements. */
    public function index(Request $request)
    {
        $filters = $request->only([
            'annee_id', 'statut_paiement', 'mode_paiement',
            'search', 'date_debut', 'date_fin',
        ]);

        // Cloisonnement par année scolaire : la liste est celle de l'année de travail
        $annee = $this->anneeDeTravail();
        $filters['annee_id'] ??= $annee?->id;

        return view('domain.finances.paiements.index', [
            'paiements' => $this->paiements->listePaginee($filters),
            'filters'   => $filters,
            'statuts'   => PaiementStatut::options(),
            'modes'     => ModePaiement::options(),
            'compteurs' => $this->paiements->compterParStatut($annee?->id),
            'annee'     => $annee,
            // Totaux encaissés : réservés à l'Administrateur et à la Direction
            'totaux'    => $request->user()->can('totaux-encaisses.consulter')
                ? $this->paiements->totauxEncaisses($annee?->id)
                : null,
        ]);
    }

    /** Nouveau paiement : l'écran de saisie, avant le choix de l'élève. */
    public function rechercheEleve()
    {
        return view('domain.finances.paiements.saisie', [
            'inscription' => null,
            'annee'       => $this->anneeDeTravail(),
        ]);
    }

    /**
     * AJAX : élèves inscrits sur l'année de travail, pour la liste de
     * recherche de l'écran de saisie (nom, prénom ou matricule).
     */
    public function eleves(Request $request)
    {
        $terme = trim((string) $request->query('q', ''));
        if (mb_strlen($terme) < 2) {
            return response()->json(['results' => []]);
        }

        $inscriptions = Inscription::query()
            ->with(['eleve:id,nom,prenom,matricule', 'classe:id,libelle', 'niveau:id,libelle'])
            ->where('annee_id', app(AnneeScolaireContext::class)->id())
            ->where('etat', 1)
            ->where('statut_validation', '!=', StatutValidationInscription::REJETEE->value)
            ->whereHas('eleve', function ($q) use ($terme) {
                $q->where('etat', 1)->where(function ($q) use ($terme) {
                    $q->where('nom', 'like', "%{$terme}%")
                      ->orWhere('prenom', 'like', "%{$terme}%")
                      ->orWhere('matricule', 'like', "%{$terme}%")
                      ->orWhereRaw("CONCAT(nom, ' ', prenom) LIKE ?", ["%{$terme}%"]);
                });
            })
            ->limit(25)
            ->get();

        return response()->json([
            'results' => $inscriptions
                ->sortBy(fn (Inscription $i) => mb_strtolower($i->eleve->nom . ' ' . $i->eleve->prenom))
                ->values()
                ->map(fn (Inscription $i) => [
                    'id'   => $i->eleve_id,
                    'text' => $this->libelleEleve($i),
                ]),
        ]);
    }

    /**
     * Écran de saisie pour un élève : sa situation frais par frais, ses
     * paiements passés, puis le nouveau paiement.
     */
    public function show(int $eleve)
    {
        $annee = $this->anneeDeTravail();

        $inscription = Inscription::query()
            ->with(['eleve:id,nom,prenom,matricule', 'classe:id,libelle', 'niveau:id,libelle'])
            ->where('eleve_id', $eleve)
            ->where('annee_id', $annee?->id)
            ->where('etat', 1)
            ->latest('id')
            ->first();

        if (! $inscription) {
            return redirect()
                ->route('finances.paiements.recherche')
                ->with('error', "Cet élève n'a pas d'inscription sur l'année scolaire " . ($annee?->libelle ?? 'de travail') . '.');
        }

        $lignes = collect($this->engagements->lignes($inscription->id));

        return view('domain.finances.paiements.saisie', [
            'inscription'  => $inscription,
            'eleve'        => $inscription->eleve,
            'libelleEleve' => $this->libelleEleve($inscription),
            'annee'        => $annee,
            'lignes'       => $lignes,
            'totaux'       => [
                'du'         => round($lignes->sum('du'), 2),
                'paye'       => round($lignes->sum('paye'), 2),
                'en_attente' => round($lignes->sum('en_attente'), 2),
                'reste'      => round($lignes->sum('reste'), 2),
            ],
            'manquants'    => $this->engagementsManquants($lignes),
            'historique'   => $this->paiements->historiqueEleve((int) $inscription->eleve_id, $annee?->id),
            'modes'        => ModePaiement::disponibles(),
            'banques'      => \App\Domain\Finances\Models\Banque::where('etat', 1)->orderBy('nom')->get(['id', 'nom']),
            'souscrire'    => $this->catalogueSouscriptions($inscription),
        ]);
    }

    /**
     * Résumé d'un paiement : qui a payé quoi, pour quel élève, comment, et où
     * en est chaque ligne (saisie, encaissement, annulation).
     */
    public function detail(int $paiement)
    {
        $paiement = \App\Domain\Finances\Models\Paiement::query()
            ->with([
                'details' => fn ($q) => $q->where('etat', 1)->orderBy('id'),
                'details.caissier:id,nom,prenom',
                'details.caisse:id,libelle',
                'comptable:id,nom,prenom',
                'cheque.banque:id,nom',
                'inscription:id,eleve_id,classe_id,niveau_id,annee_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
            ])
            ->where('etat', 1)
            ->findOrFail($paiement);

        $details = $paiement->details;
        $encaisse = PaiementStatut::ENCAISSE;

        return view('domain.finances.paiements.detail', [
            'paiement' => $paiement,
            'eleve'    => $paiement->inscription?->eleve,
            'annee'    => $paiement->inscription ? Annee::find($paiement->inscription->annee_id) : null,
            'totaux'   => [
                'total'      => round((float) $details->sum('montant'), 2),
                'encaisse'   => round((float) $details->filter(fn ($d) => $d->statut_paiement === $encaisse)->sum('montant'), 2),
                'en_attente' => round((float) $details->filter(fn ($d) => $d->statut_paiement === PaiementStatut::EN_ATTENTE)->sum('montant'), 2),
            ],
        ]);
    }

    /**
     * Ce que l'on peut souscrire depuis l'écran de saisie (fenêtres Cantine,
     * Transport, Activité, Événement) : tarifs, zones, activités et
     * événements ouverts de l'année.
     */
    private function catalogueSouscriptions(Inscription $inscription): array
    {
        $anneeId = (int) $inscription->annee_id;

        $dejaInscritAux = \Illuminate\Support\Facades\DB::table('inscriptions_activites')
            ->where('eleve_id', $inscription->eleve_id)
            ->where('annee_id', $anneeId)
            ->where('etat', 1)
            ->where('statut', '!=', \App\Domain\ServicesEleves\Types\StatutInscriptionActivite::ANNULEE->value)
            ->pluck('activite_id')
            ->all();

        $preInscritAux = \Illuminate\Support\Facades\DB::table('participation_evenements')
            ->where('inscription_id', $inscription->id)
            ->whereIn('statut', [
                \App\Domain\ServicesEleves\Types\StatutParticipation::PRE_INSCRIT->value,
                \App\Domain\ServicesEleves\Types\StatutParticipation::CONFIRME->value,
            ])
            ->pluck('evenement_scolaire_id')
            ->all();

        // Tarifs des frais de l'année pour une nature (cantine, bus), au niveau de l'élève ou commun
        $tarifs = fn (\App\Domain\Finances\Types\TypeDetail $type) => \App\Domain\Finances\Models\FraisEcole::query()
            ->where('etat', 1)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', $type->value)
            ->where(fn ($q) => $q->whereNull('niveau_id')->orWhere('niveau_id', $inscription->niveau_id))
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'montant']);

        // Durée proposée par défaut pour un abonnement : de la rentrée à la fin de l'année
        $annee = Annee::find($anneeId);
        $mois = $annee && $annee->date_rentree && $annee->date_fin
            ? (int) round(\Illuminate\Support\Carbon::parse($annee->date_rentree)->floatDiffInMonths(\Illuminate\Support\Carbon::parse($annee->date_fin)))
            : 9;

        return [
            'mois'          => max(1, min(12, $mois)),
            'frais_cantine' => $tarifs(\App\Domain\Finances\Types\TypeDetail::CANTINE),
            'zones'         => \App\Domain\ServicesEleves\Models\Zone::where('etat', 1)->orderBy('libelle')->get(['id', 'libelle', 'tarif_base']),
            'lignes'        => \App\Domain\ServicesEleves\Models\Ligne::where('etat', 1)->orderBy('libelle')->get(['id', 'libelle', 'prix_minimal', 'prix_plafond']),
            'sens'          => \App\Domain\ServicesEleves\Types\SensTrajet::options(),
            'activites'     => \App\Domain\ServicesEleves\Models\Activite::query()
                ->where('etat', 1)
                ->where('annee_id', $anneeId)
                ->where(fn ($q) => $q->whereNull('niveau_id')->orWhere('niveau_id', $inscription->niveau_id))
                ->whereNotIn('id', $dejaInscritAux)
                ->orderBy('libelle')
                ->get(['id', 'libelle', 'montant', 'encadreur']),
            'evenements'    => \App\Domain\ServicesEleves\Models\Evenement::query()
                ->where('etat', 1)
                ->where('annee_id', $anneeId)
                ->where(fn ($q) => $q->whereNull('date_evenement')->orWhere('date_evenement', '>=', now()->toDateString()))
                ->whereNotIn('id', $preInscritAux)
                ->orderBy('date_evenement')
                ->get(),
        ];
    }

    /** AJAX : catalogue du panier (articles vendus comme frais, produits en stock, services, événements). */
    public function rechercherPanier(Request $request)
    {
        $request->validate([
            'q'              => ['nullable', 'string', 'max:100'],
            'type'           => ['nullable', 'in:frais,produit,service,evenement'],
            'inscription_id' => ['nullable', 'integer'],
        ]);

        return response()->json(
            $this->panier->rechercher(
                trim((string) $request->input('q', '')),
                $request->input('type'),
                20,
                $request->integer('inscription_id') ?: null,
            )
        );
    }

    /** Enregistre un paiement complet. */
    public function store(StorePaiementRequest $request)
    {
        $valide = $request->validated();
        $data   = PaiementData::fromRequest($valide, Auth::id());

        // Montants saisis par engagement, pour vérifier qu'aucun ne dépasse le reste à payer
        $parEngagement = [];
        foreach ($valide['lignes'] as $ligne) {
            if (! empty($ligne['engagement'])) {
                $parEngagement[$ligne['engagement']] = ($parEngagement[$ligne['engagement']] ?? 0) + (float) $ligne['montant'];
            }
        }

        return $this->runAjax(function () use ($data, $valide, $parEngagement) {
            $this->engagements->verifierPlafonds((int) $valide['inscription_id'], $parEngagement);

            return ['paiement' => $this->service->enregistrer($data)];
        }, 'Paiement enregistré. En attente d\'encaissement.');
    }

    private function anneeDeTravail(): ?Annee
    {
        $id = app(AnneeScolaireContext::class)->id();

        return $id ? Annee::find($id) : null;
    }

    private function libelleEleve(Inscription $inscription): string
    {
        $eleve  = $inscription->eleve;
        $classe = $inscription->classe?->libelle ?? $inscription->niveau?->libelle ?? 'sans classe';

        return trim("{$eleve->nom} {$eleve->prenom}")
            . ($eleve->matricule ? " ({$eleve->matricule})" : '')
            . " — {$classe}";
    }

    /**
     * Natures pour lesquelles l'élève n'a aucun engagement : l'écran renvoie
     * vers la souscription correspondante au lieu d'en créer une implicitement.
     */
    private function engagementsManquants(\Illuminate\Support\Collection $lignes): array
    {
        $liens = [
            NatureCreance::SCOLARITE->value => ['Écolage', route('finances.souscriptions.index')],
            NatureCreance::CANTINE->value   => ['Cantine', url('/services-eleves/cantine/inscriptions')],
            NatureCreance::BUS->value       => ['Transport scolaire', url('/services-eleves/transport/abonnements')],
            NatureCreance::ACTIVITE->value  => ['Activités', url('/services-eleves/activites-evenements/activites')],
        ];
        // Natures souscrites sans quitter l'écran (fenêtre de souscription), si le profil en a le droit
        $fenetres = [
            NatureCreance::CANTINE->value  => ['cantine', 'cantine.inscrire'],
            NatureCreance::BUS->value      => ['transport', 'transport.abonner'],
            NatureCreance::ACTIVITE->value => ['activite', 'activites.inscrire'],
        ];

        $presentes = $lignes->pluck('nature')->unique()->all();

        return collect($liens)
            ->reject(fn ($v, string $nature) => in_array($nature, $presentes, true))
            ->map(fn (array $v, string $nature) => [
                'libelle' => $v[0],
                'lien'    => $v[1],
                'fenetre' => isset($fenetres[$nature]) && auth()->user()?->can($fenetres[$nature][1])
                    ? $fenetres[$nature][0]
                    : null,
            ])
            ->values()
            ->all();
    }

    /** Modifie un paiement en attente. */
    public function update(StorePaiementRequest $request, int $paiement)
    {
        $data = PaiementData::fromRequest($request->validated(), Auth::id());

        return $this->runAjax(
            fn () => ['paiement' => $this->service->modifier($paiement, $data)],
            'Paiement mis à jour.'
        );
    }

    /** Annule un paiement en attente. */
    public function annuler(AnnulerPaiementRequest $request, int $paiement)
    {
        return $this->runAjax(
            fn () => ['paiement' => $this->service->annuler(
                $paiement,
                $request->validated()['motif_suppression'],
                Auth::id(),
            )],
            'Paiement annulé.'
        );
    }

    /** Export CSV de la liste des paiements. */
    public function export(Request $request): StreamedResponse
    {
        $filters  = $request->only([
            'annee_id', 'statut_paiement', 'mode_paiement',
            'search', 'date_debut', 'date_fin',
        ]);
        $paiements = $this->paiements->listePaginee($filters, perPage: 10000);

        $filename = 'paiements_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($paiements) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Référence', 'Payeur', 'Téléphone', 'Montant',
                'Statut', 'Mode', 'Date', 'Comptable',
            ], ';');

            foreach ($paiements as $p) {
                fputcsv($out, [
                    $p->reference,
                    $p->payeur,
                    $p->telephone_payeur,
                    number_format((float) $p->montant, 2, ',', ' '),
                    $p->statut_paiement?->label(),
                    $p->mode_paiement?->label(),
                    optional($p->date_paiement)->format('d/m/Y'),
                    $p->comptable?->name,
                ], ';');
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}