<?php
namespace App\Services;

use App\Models\Caisse;
use App\Models\DetailPaiement;
use App\Models\Depense;
use App\Models\Paiement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CaisseJournalService
{
    protected ?int $anneeId;

    public function __construct()
    {
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des caisses avec leur état actuel (solde, date ouverture/clôture)
     */
    public function listeCaisses(array $filters = []): array
    {
        $query = Caisse::with('utilisateur', 'responsable')
            ->where('etat', 1);

        if (!empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
        if (!empty($filters['annee_id'])) {
            $query->where('annee_id', $filters['annee_id']);
        } else if ($this->anneeId) {
            $query->where('annee_id', $this->anneeId);
        }

        $perPage = $filters['per_page'] ?? 15;
        $caisses = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $caisses->map(function ($caisse) {
            return [
                'id'              => $caisse->id,
                'libelle'         => $caisse->libelle,
                'solde_initial'   => $caisse->solde_initial,
                'solde_final'     => $caisse->solde_final,
                'date_ouverture'  => $caisse->date_ouverture,
                'date_cloture'    => $caisse->date_cloture,
                'statut'          => $caisse->statut,
                'statut_label'    => $this->statutLabel($caisse->statut),
                'utilisateur'     => $caisse->utilisateur?->name,
                'responsable'     => $caisse->responsable?->name,
            ];
        });

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $caisses->currentPage(),
                'last_page'    => $caisses->lastPage(),
                'per_page'     => $caisses->perPage(),
                'total'        => $caisses->total(),
            ]
        ];
    }

    /**
     * Journal de caisse pour une période (encaissements + dépenses)
     *
     * @param int|null $caisseId - si null, toutes les caisses
     * @param string|null $dateDebut Y-m-d
     * @param string|null $dateFin Y-m-d
     * @param array $filters supplémentaires
     */
    public function journalCaisse(?int $caisseId, ?string $dateDebut, ?string $dateFin, array $filters = []): array
    {
        $anneeId = $this->anneeId;
        $queryEncaissements = DetailPaiement::query()
            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')
            ->join('inscriptions', 'details.inscription_id', '=', 'inscriptions.id')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->where('details.statut_paiement', 1) // encaissé
            ->where('details.annee_id', $anneeId)
            ->where('details.etat', 1)
            ->select(
                'details.*',
                'paiements.reference',
                'paiements.mode_paiement',
                'inscriptions.annee_id',
                DB::raw("CONCAT(eleves.nom, ' ', eleves.prenom) as eleve_nom")
            );

        if ($caisseId) {
            $queryEncaissements->where('details.caisse_id', $caisseId);
        }
        if ($dateDebut) {
            $queryEncaissements->whereDate('details.date_encaissement', '>=', $dateDebut);
        }
        if ($dateFin) {
            $queryEncaissements->whereDate('details.date_encaissement', '<=', $dateFin);
        }

        // Encaisse par type de paiement (agrégat)
        $encaissementsParType = (clone $queryEncaissements)
            ->select('details.type_paiement', DB::raw('SUM(details.montant) as total'))
            ->groupBy('details.type_paiement')
            ->get()
            ->map(fn($item) => [
                'type' => $item->type_paiement,
                'type_label' => $this->typePaiementLabel($item->type_paiement),
                'total' => $item->total,
            ]);

        // Liste détaillée des encaissements (paginer)
        $perPage = $filters['per_page'] ?? 15;
        $encaissementsList = $queryEncaissements
            ->orderBy('details.date_encaissement', 'desc')
            ->paginate($perPage)
            ->through(fn($detail) => [
                'id'            => $detail->id,
                'reference'     => $detail->reference,
                'libelle'       => $detail->libelle,
                'montant'       => $detail->montant,
                'type_paiement' => $detail->type_paiement,
                'type_label'    => $this->typePaiementLabel($detail->type_paiement),
                'mode_paiement' => $detail->mode_paiement,
                'eleve_nom'     => $detail->eleve_nom,
                'date_encaissement' => $detail->date_encaissement,
                'caisse_id'     => $detail->caisse_id,
            ]);

        // Dépenses
        $queryDepenses = Depense::where('etat', 1)
            ->where('annee_id', $anneeId);
        if ($caisseId) {
            $queryDepenses->where('caisse_id', $caisseId);
        }
        if ($dateDebut) {
            $queryDepenses->whereDate('date_depense', '>=', $dateDebut);
        }
        if ($dateFin) {
            $queryDepenses->whereDate('date_depense', '<=', $dateFin);
        }
        $totalDepenses = (clone $queryDepenses)->sum('montant');
        $depensesList = $queryDepenses
            ->orderBy('date_depense', 'desc')
            ->paginate($perPage)
            ->through(fn($d) => [
                'id'          => $d->id,
                'libelle'     => $d->libelle,
                'montant'     => $d->montant,
                'date_depense'=> $d->date_depense,
                'motif'       => $d->motif,
                'caisse_id'   => $d->caisse_id,
            ]);

        $totalEncaissements = (clone $queryEncaissements)->sum('details.montant');

        return [
            'encaissements_par_type' => $encaissementsParType,
            'encaissements_list'     => $encaissementsList,
            'depenses_list'          => $depensesList,
            'total_encaissements'    => $totalEncaissements,
            'total_depenses'         => $totalDepenses,
            'solde'                  => $totalEncaissements - $totalDepenses,
            'periode'                => compact('dateDebut', 'dateFin'),
            'filters'                => $filters,
        ];
    }

    private function statutLabel(?int $statut): string
    {
        return match($statut) {
            0 => 'Fermée',
            1 => 'Ouverte',
            default => 'Inconnu',
        };
    }

    private function typePaiementLabel(?int $type): string
    {
        return match($type) {
            1 => 'Scolarité',
            2 => 'Cantine',
            3 => 'Bus',
            4 => 'Inscription',
            5 => 'Examen',
            6 => 'Activité',
            7 => 'Produit',
            8 => 'Autre',
            default => 'Inconnu',
        };
    }
}