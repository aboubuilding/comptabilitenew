<?php

namespace App\Http\Controllers\Domain\ServicesEleves;

use App\Domain\ServicesEleves\Repositories\AbonnementBusRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use App\Domain\ServicesEleves\Repositories\ZoneRepository;
use App\Http\Controllers\Controller;
use App\Support\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardTransportController extends Controller
{
    public function __construct(
        private VoitureRepository        $voitures,
        private ZoneRepository           $zones,
        private AbonnementBusRepository  $abonnements,
    ) {
        $this->middleware('permission:transport.voir');
    }

    public function index(Request $request)
    {
        $filters = $request->only(['annee_id', 'date_debut', 'date_fin']);
        $anneeId = app(AnneeScolaireContext::class)->id();
        $dateDebut = $filters['date_debut'] ?? now()->startOfYear()->toDateString();
        $dateFin   = $filters['date_fin']   ?? now()->toDateString();

        // 1) Stats parc
        $statsParc = $this->voitures->compterParStatut();

        // 2) Abonnements
        $statsAbonnements = $this->abonnements->compterActifs();
        $parZone = $this->abonnements->actifsParZone();

        // 3) Coûts sur la période
        $couts = DB::table('carburant_vehicules')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_plein', '>=', $dateDebut)
            ->whereDate('date_plein', '<=', $dateFin)
            ->selectRaw('SUM(montant_total) as carburant, SUM(quantite_litres) as litres')
            ->first();

        $entretiens = DB::table('entretiens_vehicules')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('date_entretien', '>=', $dateDebut)
            ->whereDate('date_entretien', '<=', $dateFin)
            ->selectRaw('SUM(cout) as total')
            ->first();

        $assurances = DB::table('assurances_vehicules')
            ->whereDate('date_debut', '>=', $dateDebut)
            ->whereDate('date_debut', '<=', $dateFin)
            ->selectRaw('SUM(prime) as total')
            ->first();

        $couts = [
            'carburant'  => round((float) ($couts->carburant ?? 0), 2),
            'litres'     => round((float) ($couts->litres ?? 0), 2),
            'entretien'  => round((float) ($entretiens->total ?? 0), 2),
            'assurance'  => round((float) ($assurances->total ?? 0), 2),
        ];
        $couts['total'] = $couts['carburant'] + $couts['entretien'] + $couts['assurance'];

        // 4) Coûts par véhicule (top 10)
        $coutsParVehicule = DB::table('voitures as v')
            ->leftJoin('carburant_vehicules as c', 'c.voiture_id', '=', 'v.id')
            ->leftJoin('entretiens_vehicules as e', 'e.voiture_id', '=', 'v.id')
            ->where('v.etat', 1)
            ->when($anneeId, fn ($q, $val) => $q->where('v.annee_id', $val))
            ->groupBy('v.id', 'v.plaque', 'v.marque')
            ->selectRaw('v.id, v.plaque, v.marque,
                COALESCE(SUM(DISTINCT c.montant_total), 0) as cout_carburant,
                COALESCE(SUM(DISTINCT e.cout), 0) as cout_entretien')
            ->limit(10)
            ->get();

        // 5) Abonnements par mois (12 derniers mois)
        $aboParMois = DB::table('abonnements_bus')
            ->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->whereDate('created_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, COUNT(*) as nb")
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // 6) Zones avec nombre d'abonnements actifs
        $zonesActives = DB::table('zones as z')
            ->leftJoin('abonnements_bus as a', function ($join) {
                $join->on('a.zone_id', '=', 'z.id')->where('a.statut', 1);
            })
            ->where('z.etat', 1)
            ->groupBy('z.id', 'z.libelle')
            ->selectRaw('z.id, z.libelle, COUNT(a.id) as nb_abonnes')
            ->orderByDesc('nb_abonnes')
            ->get();

        // Chart data
        $chartData = [
            'couts_repartition' => [
                'labels' => ['Carburant', 'Entretien', 'Assurance'],
                'values' => [$couts['carburant'], $couts['entretien'], $couts['assurance']],
            ],
            'couts_vehicules' => [
                'labels' => $coutsParVehicule->pluck('plaque')->all(),
                'carburant' => $coutsParVehicule->pluck('cout_carburant')->map(fn ($v) => round((float) $v, 2))->all(),
                'entretien' => $coutsParVehicule->pluck('cout_entretien')->map(fn ($v) => round((float) $v, 2))->all(),
            ],
            'abo_par_mois' => [
                'labels' => $aboParMois->pluck('mois')->all(),
                'values' => $aboParMois->pluck('nb')->all(),
            ],
            'zones' => [
                'labels' => $zonesActives->pluck('libelle')->all(),
                'values' => $zonesActives->pluck('nb_abonnes')->all(),
            ],
        ];

        return view('domain.services-eleves.transport.dashboard', [
            'statsParc'        => $statsParc,
            'statsAbonnements' => $statsAbonnements,
            'couts'            => $couts,
            'coutsParVehicule' => $coutsParVehicule,
            'zonesActives'     => $zonesActives,
            'chartData'        => $chartData,
            'filters'          => array_merge($filters, ['date_debut' => $dateDebut, 'date_fin' => $dateFin]),
        ]);
    }
}