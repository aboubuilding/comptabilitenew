<?php

namespace App\Services;

use App\Repositories\Eloquent\AbonnementBusRepository;
use App\Repositories\Eloquent\DetailRepository;
use App\Repositories\Eloquent\InscriptionRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyseBusService
{
    protected ?int $anneeId;
    protected AbonnementBusRepository $abonnementRepo;
    protected DetailRepository $detailPaiementRepo;
    protected InscriptionRepository $inscriptionRepo;

    public function __construct(
        AbonnementBusRepository $abonnementRepo,
        DetailRepository $detailPaiementRepo,
        InscriptionRepository $inscriptionRepo
    ) {
        $this->abonnementRepo = $abonnementRepo;
        $this->detailPaiementRepo = $detailPaiementRepo;
        $this->inscriptionRepo = $inscriptionRepo;
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Indicateurs globaux (inscrits, montants, taux)
     */
    public function getIndicateursGlobaux(): array
    {
        $query = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1); // actifs

        $totalInscrits = $query->count();
        $totalDu = $query->sum('montant_total_du');
        // Total payé via somme des montants_paye (attribut calculé)
        $totalPaye = $query->get()->sum(fn($a) => $a->montant_paye);
        $reste = $totalDu - $totalPaye;

        // Nombre d’abonnements par zone (si zone_id existe)
        $parZone = [];
        if (Schema::hasColumn('abonnements_bus', 'zone_id')) {
            $parZone = $this->abonnementRepo->activeQuery()
                ->where('annee_id', $this->anneeId)
                ->with('zone')
                ->select('zone_id', DB::raw('count(*) as total'))
                ->groupBy('zone_id')
                ->get()
                ->map(fn($z) => ['zone' => $z->zone?->libelle, 'total' => $z->total])
                ->toArray();
        }

        return [
            'total_inscrits'   => $totalInscrits,
            'total_du'         => $totalDu,
            'total_paye'       => $totalPaye,
            'reste_a_payer'    => $reste,
            'taux_recouvrement'=> $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 2) : 0,
            'par_zone'         => $parZone,
        ];
    }

    /**
     * Évolution mensuelle des inscriptions et paiements
     */
    public function getEvolutionMensuelle(): array
    {
        // Inscriptions par mois
        $inscriptionsMensuelles = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as mois'), DB::raw('count(*) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        // Paiements encaissés (type_paiement = 3 pour bus, statut_paiement=1)
        $paiementsMensuels = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 3)
            ->where('statut_paiement', 1)
            ->select(DB::raw('DATE_FORMAT(date_encaissement, "%Y-%m") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return [
            'inscriptions' => $inscriptionsMensuelles,
            'paiements'    => $paiementsMensuels,
        ];
    }

    /**
     * Performance par niveau (via inscription.niveau)
     */
    public function getPerformanceParNiveau(): array
    {
        $data = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->with('inscription.niveau')
            ->get()
            ->groupBy(fn($a) => $a->inscription?->niveau?->libelle ?? 'Sans niveau')
            ->map(function ($group) {
                $totalDu = $group->sum('montant_total_du');
                $totalPaye = $group->sum(fn($a) => $a->montant_paye);
                return [
                    'total_inscrits' => $group->count(),
                    'total_du'       => $totalDu,
                    'total_paye'     => $totalPaye,
                    'reste'          => $totalDu - $totalPaye,
                    'taux'           => $totalDu > 0 ? round(($totalPaye / $totalDu) * 100, 2) : 0,
                ];
            });

        return $data->toArray();
    }

    /**
     * Top classes les plus souscrites
     */
    public function getTopClasses(): array
    {
        $classeIds = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->with('inscription.classe')
            ->select('inscription_id', DB::raw('count(*) as total'))
            ->groupBy('inscription_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'classe' => $a->inscription->classe?->libelle ?? 'Inconnue',
                'total'  => $a->total,
            ]);

        return $classeIds->toArray();
    }

    /**
     * Recouvrement : liste des abonnements en souffrance (reste > 0)
     */
    public function getImpayes(): array
    {
        $abonnements = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 1)
            ->with('inscription.eleve', 'inscription.classe')
            ->get()
            ->filter(fn($a) => ($a->montant_reste ?? 0) > 0)
            ->map(fn($a) => [
                'eleve'            => $a->inscription->eleve->nom . ' ' . $a->inscription->eleve->prenom,
                'classe'           => $a->inscription->classe?->libelle,
                'montant_total_du' => $a->montant_total_du,
                'montant_paye'     => $a->montant_paye,
                'reste'            => $a->montant_reste,
            ]);

        return [
            'total_impayes' => $abonnements->count(),
            'liste'         => $abonnements->values(),
        ];
    }

    /**
     * Tendance des abandons
     */
    public function getAbandons(): array
    {
        $totalAbandons = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 0)
            ->count();

        $parMois = $this->abonnementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('statut', 0)
            ->select(DB::raw('DATE_FORMAT(date_abandon, "%Y-%m") as mois'), DB::raw('count(*) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return [
            'total_abandons' => $totalAbandons,
            'par_mois'       => $parMois,
        ];
    }

    /**
     * Récupération de tous les indicateurs pour l'API
     */
    public function getAll(): array
    {
        return [
            'indicateurs_globaux'      => $this->getIndicateursGlobaux(),
            'evolution_mensuelle'      => $this->getEvolutionMensuelle(),
            'performance_par_niveau'   => $this->getPerformanceParNiveau(),
            'top_classes'              => $this->getTopClasses(),
            'impayes'                  => $this->getImpayes(),
            'abandons'                 => $this->getAbandons(),
        ];
    }
}
