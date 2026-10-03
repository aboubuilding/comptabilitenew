<?php

namespace App\Http\Controllers\Domain\Logistique;

use App\Domain\Logistique\Repositories\MagasinRepository;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardBoutiqueController extends Controller
{
    public function __construct(
        private MagasinRepository $magasins,
    ) {
        $this->middleware('permission:ventes.voir');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['magasin_id', 'date_debut', 'date_fin']);
        $anneeId = app(AnneeScolaireContext::class)->id();
        $dateDebut = $filters['date_debut'] ?? now()->startOfMonth()->toDateString();
        $dateFin   = $filters['date_fin']   ?? now()->toDateString();

        // 1) Stats globales de la période
        $stats = $this->statsPeriode($anneeId, $dateDebut, $dateFin, $filters['magasin_id'] ?? null);

        // 2) Chiffre d'affaires par jour (30 derniers jours)
        $caParJour = DB::table('ventes')
            ->where('etat', 1)
            ->where('statut_paiement', 1) // payé
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->whereDate('date_vente', '>=', now()->subDays(30))
            ->selectRaw('DATE(date_vente) as jour, SUM(total_ttc) as ca, COUNT(*) as nb')
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

        // 3) Top 10 produits vendus (par quantité)
        $topProduits = DB::table('vente_details as vd')
            ->join('ventes as v', 'v.id', '=', 'vd.vente_id')
            ->join('produits as p', 'p.id', '=', 'vd.produit_id')
            ->where('v.etat', 1)
            ->where('v.statut_paiement', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('v.annee_id', $v))
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('v.magasin_id', $v))
            ->whereDate('v.date_vente', '>=', $dateDebut)
            ->whereDate('v.date_vente', '<=', $dateFin)
            ->groupBy('p.id', 'p.libelle', 'p.unite_stock')
            ->selectRaw('p.id, p.libelle, p.unite_stock,
                SUM(vd.quantite) as quantite_vendue,
                SUM(vd.montant_ht) as ca')
            ->orderByDesc('quantite_vendue')
            ->limit(10)
            ->get();

        // 4) CA par magasin (boutique)
        $caParMagasin = DB::table('ventes as v')
            ->join('magasins as m', 'm.id', '=', 'v.magasin_id')
            ->where('v.etat', 1)
            ->where('v.statut_paiement', 1)
            ->when($anneeId, fn ($q, $val) => $q->where('v.annee_id', $val))
            ->whereDate('v.date_vente', '>=', $dateDebut)
            ->whereDate('v.date_vente', '<=', $dateFin)
            ->groupBy('m.id', 'm.libelle')
            ->selectRaw('m.id, m.libelle, SUM(v.total_ttc) as ca, COUNT(*) as nb_ventes')
            ->orderByDesc('ca')
            ->get();

        // 5) Répartition par mode de paiement (payé / impayé / annulé)
        $statuts = DB::table('ventes')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->whereDate('date_vente', '>=', $dateDebut)
            ->whereDate('date_vente', '<=', $dateFin)
            ->selectRaw('statut_paiement, COUNT(*) as nb, SUM(total_ttc) as montant')
            ->groupBy('statut_paiement')
            ->get()
            ->keyBy('statut_paiement');

        // 6) Ventes du jour (aperçu rapide)
        $ventesDuJour = DB::table('ventes as v')
            ->leftJoin('clients as c', 'c.id', '=', 'v.client_id')
            ->leftJoin('inscriptions as i', 'i.id', '=', 'v.inscription_id')
            ->leftJoin('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->where('v.etat', 1)
            ->when($filters['magasin_id'] ?? null, fn ($q, $val) => $q->where('v.magasin_id', $val))
            ->whereDate('v.date_vente', now()->toDateString())
            ->orderByDesc('v.id')
            ->selectRaw('v.id, v.reference, v.total_ttc, v.statut_paiement,
                COALESCE(c.nom_raison_sociale, CONCAT(COALESCE(e.nom, ""), " ", COALESCE(e.prenom, ""))) as acheteur')
            ->limit(10)
            ->get();

        // Préparation Chart.js
        $chartData = [
            'ca_par_jour' => [
                'labels' => $caParJour->pluck('jour')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all(),
                'values' => $caParJour->pluck('ca')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'top_produits' => [
                'labels'   => $topProduits->pluck('libelle')->all(),
                'quantite' => $topProduits->pluck('quantite_vendue')->map(fn ($v) => round((float) $v, 2))->all(),
                'ca'       => $topProduits->pluck('ca')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'ca_par_magasin' => [
                'labels' => $caParMagasin->pluck('libelle')->all(),
                'values' => $caParMagasin->pluck('ca')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
        ];

        return view('domain.logistique.ventes.dashboard', [
            'stats'         => $stats,
            'caParJour'     => $caParJour,
            'topProduits'   => $topProduits,
            'caParMagasin'  => $caParMagasin,
            'statuts'       => $statuts,
            'ventesDuJour'  => $ventesDuJour,
            'magasins'      => $this->magasins->listePourSelect(),
            'filters'       => array_merge($filters, ['date_debut' => $dateDebut, 'date_fin' => $dateFin]),
            'chartData'     => $chartData,
        ]);
    }

    private function statsPeriode(?int $anneeId, string $dateDebut, string $dateFin, ?int $magasinId): array
    {
        $base = DB::table('ventes')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($magasinId, fn ($q, $v) => $q->where('magasin_id', $v))
            ->whereDate('date_vente', '>=', $dateDebut)
            ->whereDate('date_vente', '<=', $dateFin);

        $jour = DB::table('ventes')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($magasinId, fn ($q, $v) => $q->where('magasin_id', $v))
            ->whereDate('date_vente', now()->toDateString());

        return [
            'nb_ventes'       => (clone $base)->count(),
            'ca_periode'      => round((float) (clone $base)->where('statut_paiement', 1)->sum('total_ttc'), 2),
            'nb_jour'         => (clone $jour)->count(),
            'ca_jour'         => round((float) (clone $jour)->where('statut_paiement', 1)->sum('total_ttc'), 2),
            'panier_moyen'    => (function () use ($base) {
                $ca = (clone $base)->where('statut_paiement', 1)->sum('total_ttc');
                $nb = (clone $base)->where('statut_paiement', 1)->count();
                return $nb > 0 ? round((float) $ca / $nb, 2) : 0;
            })(),
        ];
    }
}