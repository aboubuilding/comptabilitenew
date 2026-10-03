<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\LivreRepository;
use App\Domain\ServicesEleves\Repositories\PretRepository;
use App\Domain\ServicesEleves\Types\StatutPret;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardBibliothequeController extends Controller
{
    public function __construct(
        private LivreRepository $livres,
        private PretRepository  $prets,
    ) {
        $this->middleware('permission:bibliotheque.voir');
    }

    public function index(Request $request)
    {
        $filters   = $request->only(['annee_id', 'date_debut', 'date_fin']);
        $anneeId   = app(AnneeScolaireContext::class)->id();
        $dateDebut = $filters['date_debut'] ?? now()->subMonths(6)->toDateString();
        $dateFin   = $filters['date_fin']   ?? now()->toDateString();

        /* ================================================================
           1) STATS GLOBALES CATALOGUE
           ================================================================ */
        $statsCatalogue = $this->livres->compterStatistiques();

        /* ================================================================
           2) STATS PRÊTS (sur la période)
           ================================================================ */
        $statsPrets = [
            'total'      => $this->prets->activeQuery()
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->whereDate('date_pret', '>=', $dateDebut)
                ->whereDate('date_pret', '<=', $dateFin)
                ->count(),
            'en_cours'   => $this->prets->activeQuery()
                ->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])
                ->count(),
            'en_retard'  => $this->prets->activeQuery()
                ->whereIn('statut_pret', [StatutPret::EN_COURS->value, StatutPret::RETARD->value])
                ->whereDate('date_retour_pret', '<', now())
                ->count(),
            'rendus'     => $this->prets->activeQuery()
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->whereDate('date_pret', '>=', $dateDebut)
                ->whereDate('date_pret', '<=', $dateFin)
                ->where('statut_pret', StatutPret::RENDU->value)
                ->count(),
            'perdus'     => $this->prets->activeQuery()
                ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                ->whereDate('date_pret', '>=', $dateDebut)
                ->whereDate('date_pret', '<=', $dateFin)
                ->where('statut_pret', StatutPret::PERDU->value)
                ->count(),
        ];

        /* ================================================================
           3) TOP 10 LIVRES LES PLUS EMPRUNTÉS
           ================================================================ */
        $topLivres = DB::table('prets as p')
            ->join('livres as l', 'l.id', '=', 'p.livre_id')
            ->leftJoin('auteurs as a', 'a.id', '=', 'l.auteur_id')
            ->when($anneeId, fn ($q, $v) => $q->where('p.annee_id', $v))
            ->whereDate('p.date_pret', '>=', $dateDebut)
            ->whereDate('p.date_pret', '<=', $dateFin)
            ->where('p.etat', 1)
            ->groupBy('l.id', 'l.titre', 'a.nom_prenom')
            ->selectRaw('l.id, l.titre, a.nom_prenom as auteur,
                COUNT(*) as nb_prets,
                SUM(CASE WHEN p.statut_pret = ' . StatutPret::RENDU->value . ' THEN 1 ELSE 0 END) as nb_rendus')
            ->orderByDesc('nb_prets')
            ->limit(10)
            ->get();

        /* ================================================================
           4) TAUX DE ROTATION PAR CATÉGORIE
              Rotation = nb de prêts / nb d'exemplaires de la catégorie
           ================================================================ */
        $rotationCategories = DB::table('categorie_livres as c')
            ->leftJoin('livres as l', 'l.categorie_livre_id', '=', 'c.id')
            ->leftJoin('prets as p', function ($join) use ($anneeId, $dateDebut, $dateFin) {
                $join->on('p.livre_id', '=', 'l.id')
                     ->where('p.etat', 1)
                     ->when($anneeId, fn ($q, $v) => $q->where('p.annee_id', $v))
                     ->whereDate('p.date_pret', '>=', $dateDebut)
                     ->whereDate('p.date_pret', '<=', $dateFin);
            })
            ->where('c.etat', 1)
            ->groupBy('c.id', 'c.libelle')
            ->selectRaw('
                c.id, c.libelle,
                COUNT(DISTINCT l.id) as nb_livres,
                COALESCE(SUM(l.nombre_exemplaires), 0) as nb_exemplaires,
                COUNT(p.id) as nb_prets,
                CASE
                    WHEN COALESCE(SUM(l.nombre_exemplaires), 0) > 0
                    THEN ROUND(COUNT(p.id) / SUM(l.nombre_exemplaires), 2)
                    ELSE 0
                END as taux_rotation
            ')
            ->orderByDesc('taux_rotation')
            ->get();

        /* ================================================================
           5) TOP EMPRUNTEURS (élèves)
           ================================================================ */
        $topEmprunteurs = DB::table('prets as p')
            ->join('inscriptions as i', 'i.id', '=', 'p.inscription_id')
            ->join('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->when($anneeId, fn ($q, $v) => $q->where('p.annee_id', $v))
            ->whereDate('p.date_pret', '>=', $dateDebut)
            ->whereDate('p.date_pret', '<=', $dateFin)
            ->where('p.etat', 1)
            ->groupBy('e.id', 'e.nom', 'e.prenom', 'e.matricule')
            ->selectRaw('e.id, e.nom, e.prenom, e.matricule, COUNT(*) as nb_prets')
            ->orderByDesc('nb_prets')
            ->limit(10)
            ->get();

        /* ================================================================
           6) ÉVOLUTION MENSUELLE DES PRÊTS (12 derniers mois)
           ================================================================ */
        $evolutionMensuelle = DB::table('prets')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_pret', '>=', now()->subMonths(12))
            ->selectRaw("
                DATE_FORMAT(date_pret, '%Y-%m') as mois,
                COUNT(*) as nb_prets,
                SUM(CASE WHEN statut_pret = " . StatutPret::RENDU->value . " THEN 1 ELSE 0 END) as nb_rendus,
                SUM(CASE WHEN statut_pret IN (" . StatutPret::EN_COURS->value . ", " . StatutPret::RETARD->value . ") THEN 1 ELSE 0 END) as nb_encours
            ")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        /* ================================================================
           7) RÉPARTITION PAR STATUT (pour donut)
           ================================================================ */
        $parStatut = DB::table('prets')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_pret', '>=', $dateDebut)
            ->whereDate('date_pret', '<=', $dateFin)
            ->selectRaw('statut_pret, COUNT(*) as nb')
            ->groupBy('statut_pret')
            ->pluck('nb', 'statut_pret')
            ->all();

        /* ================================================================
           8) PRÊTS EN RETARD (détail pour la liste)
           ================================================================ */
        $pretsEnRetard = $this->prets->enRetard()->take(15);

        /* ================================================================
           9) CHART DATA
           ================================================================ */
        $chartData = [
            'top_livres' => [
                'labels' => $topLivres->pluck('titre')->map(fn ($t) => mb_strlen($t) > 30 ? mb_substr($t, 0, 28) . '…' : $t)->all(),
                'values' => $topLivres->pluck('nb_prets')->map(fn ($v) => (int) $v)->all(),
                'auteurs'=> $topLivres->pluck('auteur')->all(),
            ],
            'rotation_categories' => [
                'labels' => $rotationCategories->pluck('libelle')->all(),
                'values' => $rotationCategories->pluck('taux_rotation')->map(fn ($v) => (float) $v)->all(),
            ],
            'par_statut' => [
                'labels' => collect($parStatut)->keys()->map(
                    fn ($k) => StatutPret::tryFrom((int) $k)?->label() ?? '—'
                )->values()->all(),
                'values' => collect($parStatut)->values()->all(),
            ],
            'evolution' => [
                'labels'   => $evolutionMensuelle->pluck('mois')->all(),
                'total'    => $evolutionMensuelle->pluck('nb_prets')->map(fn ($v) => (int) $v)->all(),
                'rendus'   => $evolutionMensuelle->pluck('nb_rendus')->map(fn ($v) => (int) $v)->all(),
                'en_cours' => $evolutionMensuelle->pluck('nb_encours')->map(fn ($v) => (int) $v)->all(),
            ],
        ];

        return view('domain.services-eleves.bibliotheque.dashboard', [
            'statsCatalogue'     => $statsCatalogue,
            'statsPrets'         => $statsPrets,
            'topLivres'          => $topLivres,
            'rotationCategories' => $rotationCategories,
            'topEmprunteurs'     => $topEmprunteurs,
            'evolutionMensuelle' => $evolutionMensuelle,
            'pretsEnRetard'      => $pretsEnRetard,
            'chartData'          => $chartData,
            'filters'            => array_merge($filters, ['date_debut' => $dateDebut, 'date_fin' => $dateFin]),
        ]);
    }
}