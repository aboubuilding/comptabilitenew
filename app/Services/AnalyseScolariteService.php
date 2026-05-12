<?php

namespace App\Services;

use App\Repositories\Eloquent\InscriptionRepository;
use App\Repositories\Eloquent\DetailRepository;
use App\Repositories\Eloquent\PaiementRepository;
use Illuminate\Support\Facades\DB;

class AnalyseScolariteService
{
    protected ?int $anneeId;
    protected InscriptionRepository $inscriptionRepo;
    protected DetailRepository $detailPaiementRepo;
    protected PaiementRepository $paiementRepo;

    public function __construct(
        InscriptionRepository $inscriptionRepo,
        DetailRepository $detailPaiementRepo,
        PaiementRepository $paiementRepo
    ) {
        $this->inscriptionRepo = $inscriptionRepo;
        $this->detailPaiementRepo = $detailPaiementRepo;
        $this->paiementRepo = $paiementRepo;
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Récapitulatif global (tous niveaux)
     */
    public function getRecapitulatifGlobal(): array
    {
        $query = $this->inscriptionRepo->activeQuery()
            ->where('annee_id', $this->anneeId);

        $totalPrevu = $query->sum(DB::raw('frais_scolarite - COALESCE(remise_scolarite, 0)'));

        $totalPaye = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 1)
            ->where('statut_paiement', 1)
            ->sum('montant');

        return [
            'total_prevu'       => $totalPrevu,
            'total_paye'        => $totalPaye,
            'total_impaye'      => max($totalPrevu - $totalPaye, 0),
            'taux_recouvrement' => $totalPrevu > 0 ? round(($totalPaye / $totalPrevu) * 100, 2) : 0,
        ];
    }

    /**
     * Analyse par niveau (ou classe)
     */
    public function getAnalyseParNiveau(): array
    {
        $inscriptions = $this->inscriptionRepo->activeQuery()
            ->with('niveau')
            ->where('annee_id', $this->anneeId)
            ->get();

        $result = [];
        foreach ($inscriptions as $ins) {
            $niveauId = $ins->niveau_id;
            $niveauLib = $ins->niveau?->libelle ?? 'N/A';
            $prevu = $ins->frais_scolarite - ($ins->remise_scolarite ?? 0);
            $paye = $ins->detailsPaiement()
                ->where('type_paiement', 1)
                ->where('statut_paiement', 1)
                ->sum('montant');

            if (!isset($result[$niveauId])) {
                $result[$niveauId] = [
                    'niveau'        => $niveauLib,
                    'total_prevu'   => 0,
                    'total_paye'    => 0,
                    'nombre_eleves' => 0,
                ];
            }
            $result[$niveauId]['total_prevu'] += $prevu;
            $result[$niveauId]['total_paye'] += $paye;
            $result[$niveauId]['nombre_eleves']++;
        }

        foreach ($result as &$item) {
            $item['total_impaye'] = max($item['total_prevu'] - $item['total_paye'], 0);
            $item['taux_recouvrement'] = $item['total_prevu'] > 0
                ? round(($item['total_paye'] / $item['total_prevu']) * 100, 2)
                : 0;
        }
        return array_values($result);
    }

    /**
     * Liste des élèves en impayé (reste > 0)
     */
    public function getElevesImpayes(array $filters = []): array
    {
        $query = $this->inscriptionRepo->activeQuery()
            ->with(['eleve', 'classe', 'niveau'])
            ->where('annee_id', $this->anneeId);

        if (!empty($filters['classe_id'])) {
            $query->where('classe_id', $filters['classe_id']);
        }
        if (!empty($filters['niveau_id'])) {
            $query->where('niveau_id', $filters['niveau_id']);
        }
        if (!empty($filters['cycle_id'])) {
            $query->where('cycle_id', $filters['cycle_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('eleve', fn($q) => $q->where('nom', 'like', $search)->orWhere('prenom', 'like', $search));
        }

        $perPage = $filters['per_page'] ?? 15;
        $inscriptions = $query->paginate($perPage);

        $data = $inscriptions->getCollection()->map(function ($ins) {
            $prevu = $ins->frais_scolarite - ($ins->remise_scolarite ?? 0);
            $paye = $ins->detailsPaiement()
                ->where('type_paiement', 1)
                ->where('statut_paiement', 1)
                ->sum('montant');
            $reste = max($prevu - $paye, 0);

            return [
                'id'            => $ins->id,
                'eleve'         => $ins->eleve?->nom . ' ' . $ins->eleve?->prenom,
                'matricule'     => $ins->eleve?->matricule,
                'classe'        => $ins->classe?->libelle,
                'niveau'        => $ins->niveau?->libelle,
                'montant_prevu' => $prevu,
                'montant_paye'  => $paye,
                'reste_a_payer' => $reste,
                'remise'        => $ins->remise_scolarite ?? 0,
            ];
        })->filter(fn($item) => $item['reste_a_payer'] > 0);

        // Agrégats
        $totalPrevu = $data->sum('montant_prevu');
        $totalPaye = $data->sum('montant_paye');

        return [
            'data'        => $data->values(),
            'aggregates'  => [
                'total_prevu'    => $totalPrevu,
                'total_paye'     => $totalPaye,
                'total_impaye'   => $totalPrevu - $totalPaye,
                'nombre_impayes' => $data->count(),
            ],
            'pagination' => [
                'current_page' => $inscriptions->currentPage(),
                'last_page'    => $inscriptions->lastPage(),
                'per_page'     => $inscriptions->perPage(),
                'total'        => $inscriptions->total(),
            ],
        ];
    }

    /**
     * Évolution mensuelle des encaissements scolarité
     */
    public function getEvolutionMensuelle(): array
    {
        $encaissements = $this->detailPaiementRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->where('type_paiement', 1)
            ->where('statut_paiement', 1)
            ->select(DB::raw('DATE_FORMAT(date_encaissement, "%Y-%m") as mois'), DB::raw('SUM(montant) as total'))
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        return $encaissements->toArray();
    }

    /**
     * Répartition par mode de paiement (pour scolarité)
     */
    public function getRepartitionParMode(): array
    {
        $repartition = $this->detailPaiementRepo->activeQuery()
            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')
            ->where('details.annee_id', $this->anneeId)
            ->where('details.type_paiement', 1)
            ->where('details.statut_paiement', 1)
            ->select('paiements.mode_paiement', DB::raw('SUM(details.montant) as total'))
            ->groupBy('paiements.mode_paiement')
            ->get()
            ->map(fn($item) => [
                'mode'  => $this->getModeLabel($item->mode_paiement),
                'total' => $item->total,
            ]);

        return $repartition->toArray();
    }

    private function getModeLabel($mode): string
    {
        return match($mode) {
            1 => 'Espèces',
            2 => 'Chèque',
            3 => 'Carte bancaire',
            4 => 'Mobile money',
            default => 'Autre',
        };
    }
}
