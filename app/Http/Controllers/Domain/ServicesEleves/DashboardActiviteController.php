<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\ActiviteRepository;
use App\Domain\ServicesEleves\Repositories\EvenementRepository;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Domain\ServicesEleves\Types\TypeActivite;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardActiviteController extends Controller
{
    public function __construct(
        private ActiviteRepository $activites,
        private EvenementRepository $evenements,
    ) {
        $this->middleware('permission:activites.voir');
    }

    public function index(Request $request)
    {
        $filters   = $request->only(['annee_id', 'date_debut', 'date_fin']);
        $anneeId   = app(AnneeScolaireContext::class)->id();
        $dateDebut = $filters['date_debut'] ?? now()->startOfYear()->toDateString();
        $dateFin   = $filters['date_fin']   ?? now()->toDateString();

        /* ================================================================
           1) STATS GLOBALES ACTIVITÉS
           ================================================================ */
        $statsActivites = [
            'total'        => $this->activites->activeQuery()
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->count(),
            'inscrits'     => DB::table('inscriptions_activites')
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->whereIn('statut', [
                    StatutInscriptionActivite::EN_ATTENTE->value,
                    StatutInscriptionActivite::CONFIRMEE->value,
                ])
                ->count(),
            'montant_du'   => round((float) DB::table('inscriptions_activites')
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->whereIn('statut', [
                    StatutInscriptionActivite::EN_ATTENTE->value,
                    StatutInscriptionActivite::CONFIRMEE->value,
                ])
                ->sum('montant_du'), 2),
        ];

        // Revenus encaissés sur les activités
        $revenusActivites = round((float) DB::table('details')
            ->where('etat', 1)
            ->whereNotNull('date_encaissement')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_encaissement', '>=', $dateDebut)
            ->whereDate('date_encaissement', '<=', $dateFin)
            ->where('type_paiement', \App\Domain\Finances\Types\TypeDetail::ACTIVITE->value)
            ->sum('montant'), 2);

        /* ================================================================
           2) STATS GLOBALES ÉVÉNEMENTS
           ================================================================ */
        $statsEvenements = [
            'total'       => $this->evenements->activeQuery()
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->count(),
            'a_venir'     => $this->evenements->activeQuery()
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->whereDate('date_evenement', '>=', now())
                ->count(),
            'pre_inscrits'=> DB::table('participation_evenements')
                ->where('etat', 1)
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->where('statut', StatutParticipation::PRE_INSCRIT->value)
                ->count(),
            'confirmes'   => DB::table('participation_evenements')
                ->where('etat', 1)
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->where('statut', StatutParticipation::CONFIRME->value)
                ->count(),
        ];

        // Revenus encaissés sur les événements
        $revenusEvenements = round((float) DB::table('details')
            ->where('etat', 1)
            ->whereNotNull('date_encaissement')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_encaissement', '>=', $dateDebut)
            ->whereDate('date_encaissement', '<=', $dateFin)
            ->where('type_paiement', \App\Domain\Finances\Types\TypeDetail::EVENEMENT->value)
            ->sum('montant'), 2);

        /* ================================================================
           3) TOP ACTIVITÉS (par nombre d'inscrits)
           ================================================================ */
        $topActivites = DB::table('activites as a')
            ->leftJoin('inscriptions_activites as ia', function ($join) use ($anneeId) {
                $join->on('ia.activite_id', '=', 'a.id')
                     ->where('ia.etat', 1)
                     ->whereIn('ia.statut', [
                         StatutInscriptionActivite::EN_ATTENTE->value,
                         StatutInscriptionActivite::CONFIRMEE->value,
                     ])
                     ->when($anneeId, fn ($q, $v) => $q->where('ia.annee_id', $v));
            })
            ->where('a.etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('a.annee_id', $v))
            ->groupBy('a.id', 'a.libelle', 'a.montant', 'a.type')
            ->selectRaw('
                a.id, a.libelle, a.montant, a.type,
                COUNT(ia.id) as nb_inscrits,
                COALESCE(SUM(ia.montant_du), 0) as montant_du
            ')
            ->orderByDesc('nb_inscrits')
            ->limit(10)
            ->get();

        /* ================================================================
           4) RÉPARTITION PAR TYPE D'ACTIVITÉ
           ================================================================ */
        $parType = DB::table('activites as a')
            ->leftJoin('inscriptions_activites as ia', 'ia.activite_id', '=', 'a.id')
            ->where('a.etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('a.annee_id', $v))
            ->where(function ($q) {
                $q->where('ia.etat', 1)->orWhereNull('ia.id');
            })
            ->groupBy('a.type')
            ->selectRaw('a.type, COUNT(DISTINCT a.id) as nb_activites, COUNT(ia.id) as nb_inscrits')
            ->get();

        /* ================================================================
           5) ÉVÉNEMENTS À VENIR AVEC TAUX DE REMPLISSAGE
           ================================================================ */
        $prochainsEvenements = DB::table('evenements as e')
            ->leftJoin('participation_evenements as pe', function ($join) {
                $join->on('pe.evenement_scolaire_id', '=', 'e.id')
                     ->where('pe.etat', 1)
                     ->where('pe.statut', StatutParticipation::CONFIRME->value);
            })
            ->where('e.etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('e.annee_id', $v))
            ->whereDate('e.date_evenement', '>=', now())
            ->groupBy('e.id', 'e.nom', 'e.type', 'e.date_evenement', 'e.participation', 'e.capacite', 'e.capacite_limitee')
            ->selectRaw('
                e.id, e.nom, e.type, e.date_evenement, e.participation,
                e.capacite, e.capacite_limitee,
                COUNT(pe.id) as nb_confirmes
            ')
            ->orderBy('e.date_evenement')
            ->limit(10)
            ->get();

        /* ================================================================
           6) ÉVOLUTION MENSUELLE (12 derniers mois)
              - inscriptions activités
              - pré-inscriptions événements
           ================================================================ */
        $evolutionInscriptions = DB::table('inscriptions_activites')
            ->where('etat', 1)
            ->whereDate('created_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as nb")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->pluck('nb', 'mois')
            ->all();

        $evolutionParticipations = DB::table('participation_evenements')
            ->where('etat', 1)
            ->whereDate('created_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as nb")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->pluck('nb', 'mois')
            ->all();

        // Fusion des deux séries sur les 12 derniers mois
        $moisLabels = [];
        $dataInscriptions = [];
        $dataParticipations = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = now()->subMonths($i)->format('Y-m');
            $moisLabels[] = $mois;
            $dataInscriptions[] = $evolutionInscriptions[$mois] ?? 0;
            $dataParticipations[] = $evolutionParticipations[$mois] ?? 0;
        }

        /* ================================================================
           7) CHART DATA
           ================================================================ */
        $chartData = [
            'top_activites' => [
                'labels' => $topActivites->pluck('libelle')->map(
                    fn ($l) => mb_strlen($l) > 28 ? mb_substr($l, 0, 26) . '…' : $l
                )->all(),
                'inscrits' => $topActivites->pluck('nb_inscrits')->map(fn ($v) => (int) $v)->all(),
                'montant'  => $topActivites->pluck('montant_du')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'par_type' => [
                'labels' => $parType->map(
                    fn ($t) => TypeActivite::tryFrom((string) $t->type)?->label() ?? 'Autre'
                )->values()->all(),
                'activites' => $parType->pluck('nb_activites')->map(fn ($v) => (int) $v)->values()->all(),
                'inscrits'  => $parType->pluck('nb_inscrits')->map(fn ($v) => (int) $v)->values()->all(),
            ],
            'evolution' => [
                'labels'         => $moisLabels,
                'inscriptions'   => $dataInscriptions,
                'participations' => $dataParticipations,
            ],
            'revenus' => [
                'labels' => ['Activités', 'Événements'],
                'values' => [$revenusActivites, $revenusEvenements],
            ],
        ];

        return view('domain.services-eleves.activites.dashboard', [
            'statsActivites'    => $statsActivites,
            'statsEvenements'   => $statsEvenements,
            'revenusActivites'  => $revenusActivites,
            'revenusEvenements' => $revenusEvenements,
            'topActivites'      => $topActivites,
            'parType'           => $parType,
            'prochainsEvenements'=> $prochainsEvenements,
            'chartData'         => $chartData,
            'filters'           => array_merge($filters, [
                'date_debut' => $dateDebut,
                'date_fin'   => $dateFin,
            ]),
        ]);
    }
}