<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\DetailPaiement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecouvrementService
{
    protected ?int $anneeId;

    public function __construct()
    {
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des élèves en impayé pour un type de frais donné
     *
     * @param int $typeFrais 1=scolarité, 2=cantine, 3=bus, 5=examen
     * @param array $filters  (cycle_id, niveau_id, classe_id, search)
     * @return array ['data' => Collection, 'aggregates' => []]
     */
    public function getElevesImpayes(int $typeFrais, array $filters = []): array
    {
        if (!$this->anneeId) {
            return ['data' => collect(), 'aggregates' => []];
        }

        // Déterminer le champ montant dû dans inscriptions
        $fieldMontant = match ($typeFrais) {
            1 => 'frais_scolarite',
            2 => 'frais_cantine',
            3 => 'frais_bus',
            5 => 'frais_examen',
            default => throw new \InvalidArgumentException('Type de frais invalide'),
        };

        // Sous-requête pour obtenir le total payé par inscription pour ce type de frais
        $subPaye = DetailPaiement::selectRaw('inscription_id, SUM(montant) as total_paye')
            ->where('type_paiement', $typeFrais)
            ->where('statut_paiement', 1)
            ->where('annee_id', $this->anneeId)
            ->groupBy('inscription_id');

        $query = Inscription::with(['eleve', 'cycle', 'niveau', 'classe'])
            ->leftJoinSub($subPaye, 'paye', function ($join) {
                $join->on('inscriptions.id', '=', 'paye.inscription_id');
            })
            ->where('inscriptions.annee_id', $this->anneeId)
            ->where('inscriptions.etat', 1)
            ->where('inscriptions.statut_validation', 1) // inscrits validés
            ->select(
                'inscriptions.*',
                DB::raw("COALESCE(paye.total_paye, 0) as total_paye")
            )
            ->havingRaw("(inscriptions.{$fieldMontant} - (COALESCE(paye.total_paye, 0) * (1 - COALESCE(inscriptions.taux_remise, 0)/100)) > 0.01");

        // Remise éventuelle : le montant dû peut être réduit par un taux de remise
        // On applique la remise sur le montant prévu

        // Application des filtres
        if (!empty($filters['cycle_id'])) {
            $query->where('inscriptions.cycle_id', $filters['cycle_id']);
        }
        if (!empty($filters['niveau_id'])) {
            $query->where('inscriptions.niveau_id', $filters['niveau_id']);
        }
        if (!empty($filters['classe_id'])) {
            $query->where('inscriptions.classe_id', $filters['classe_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                  ->orWhere('prenom', 'like', $search)
                  ->orWhere('matricule', 'like', $search);
            });
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $inscriptions = $query->orderBy('inscriptions.created_at', 'desc')->paginate($perPage);

        // Agrégats : nombre total d'impayés, montant total dû impayé
        $aggregates = $this->getAggregatsImpayes($typeFrais, $filters);

        $data = $inscriptions->map(function ($ins) use ($fieldMontant) {
            $montantDu = $ins->$fieldMontant;
            $remise = $ins->taux_remise ?? 0;
            $montantApresRemise = $montantDu * (1 - $remise / 100);
            $reste = $montantApresRemise - $ins->total_paye;
            if ($reste < 0) $reste = 0;

            return [
                'id'                => $ins->id,
                'eleve'             => $ins->eleve ? $ins->eleve->nom . ' ' . $ins->eleve->prenom : '',
                'matricule'         => $ins->eleve?->matricule,
                'cycle'             => $ins->cycle?->libelle,
                'niveau'            => $ins->niveau?->libelle,
                'classe'            => $ins->classe?->libelle,
                'montant_du'        => $montantDu,
                'remise'            => $remise,
                'montant_apres_remise' => $montantApresRemise,
                'total_paye'        => $ins->total_paye,
                'reste_a_payer'     => $reste,
            ];
        });

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $inscriptions->currentPage(),
                'last_page'    => $inscriptions->lastPage(),
                'per_page'     => $inscriptions->perPage(),
                'total'        => $inscriptions->total(),
            ],
            'aggregates' => $aggregates,
        ];
    }

    /**
     * Agrégats des impayés (total élèves, montant global impayé)
     */
    private function getAggregatsImpayes(int $typeFrais, array $filters): array
    {
        $fieldMontant = match ($typeFrais) {
            1 => 'frais_scolarite',
            2 => 'frais_cantine',
            3 => 'frais_bus',
            5 => 'frais_examen',
        };

        $subPaye = DetailPaiement::selectRaw('inscription_id, SUM(montant) as total_paye')
            ->where('type_paiement', $typeFrais)
            ->where('statut_paiement', 1)
            ->where('annee_id', $this->anneeId)
            ->groupBy('inscription_id');

        $query = Inscription::leftJoinSub($subPaye, 'paye', function ($join) {
                $join->on('inscriptions.id', '=', 'paye.inscription_id');
            })
            ->where('inscriptions.annee_id', $this->anneeId)
            ->where('inscriptions.etat', 1)
            ->where('inscriptions.statut_validation', 1);

        // Appliquer les filtres (sans pagination)
        if (!empty($filters['cycle_id'])) {
            $query->where('inscriptions.cycle_id', $filters['cycle_id']);
        }
        if (!empty($filters['niveau_id'])) {
            $query->where('inscriptions.niveau_id', $filters['niveau_id']);
        }
        if (!empty($filters['classe_id'])) {
            $query->where('inscriptions.classe_id', $filters['classe_id']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)->orWhere('prenom', 'like', $search)->orWhere('matricule', 'like', $search);
            });
        }

        // Calcul du total dû réel après remise moins payé
        $totalImpaye = 0;
        $totalEleves = 0;
        foreach ($query->cursor() as $ins) {
            $montantDu = $ins->$fieldMontant;
            $remise = $ins->taux_remise ?? 0;
            $montantApresRemise = $montantDu * (1 - $remise / 100);
            $paye = $ins->total_paye ?? 0;
            $reste = max($montantApresRemise - $paye, 0);
            if ($reste > 0) {
                $totalImpaye += $reste;
                $totalEleves++;
            }
        }

        return [
            'total_eleves_impayes' => $totalEleves,
            'montant_global_impaye' => $totalImpaye,
        ];
    }
}