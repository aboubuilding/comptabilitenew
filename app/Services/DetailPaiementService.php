<?php

namespace App\Services;

use App\Models\DetailPaiement;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class DetailPaiementService
{
    /**
     * Récupère l'année en session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des détails de paiement avec filtres et pagination
     *
     * @param array $filters
     * @return array ['data' => Collection, 'pagination' => array, 'aggregates' => array]
     */
    public function listDetails(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return [
                'data' => collect(),
                'pagination' => [],
                'aggregates' => ['total_montant' => 0, 'total_details' => 0]
            ];
        }

        $query = DetailPaiement::with([
            'paiement', 
            'inscription.eleve', 
            'inscription.cycle', 
            'inscription.niveau', 
            'inscription.classe'
        ])
            ->where('details.annee_id', $anneeId)
            ->where('details.etat', 1)
            ->join('paiements', 'details.paiement_id', '=', 'paiements.id')
            ->join('inscriptions', 'details.inscription_id', '=', 'inscriptions.id')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id');

        // Sélection des colonnes pour éviter les conflits
        $query->select([
            'details.*',
            'paiements.reference as paiement_reference',
            'paiements.date_paiement as paiement_date',
            'paiements.mode_paiement as paiement_mode',
            'eleves.nom as eleve_nom',
            'eleves.prenom as eleve_prenom',
            'eleves.matricule as eleve_matricule',
            'inscriptions.type_inscription',
        ]);

        // 🔍 Filtres
        // Intervalle de dates (date_paiement)
        if (!empty($filters['date_debut'])) {
            $query->whereDate('details.date_paiement', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('details.date_paiement', '<=', $filters['date_fin']);
        }
        // Type de paiement
        if (isset($filters['type_paiement']) && $filters['type_paiement'] !== '') {
            $query->where('details.type_paiement', $filters['type_paiement']);
        }
        // Élève (recherche nom ou prénom)
        if (!empty($filters['eleve_search'])) {
            $search = '%' . $filters['eleve_search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('eleves.nom', 'like', $search)
                  ->orWhere('eleves.prenom', 'like', $search)
                  ->orWhere('eleves.matricule', 'like', $search);
            });
        }
        // Niveau
        if (!empty($filters['niveau_id'])) {
            $query->where('inscriptions.niveau_id', $filters['niveau_id']);
        }
        // Classe
        if (!empty($filters['classe_id'])) {
            $query->where('inscriptions.classe_id', $filters['classe_id']);
        }
        // Cycle
        if (!empty($filters['cycle_id'])) {
            $query->where('inscriptions.cycle_id', $filters['cycle_id']);
        }
        // Type inscription (ancien/nouveau)
        if (isset($filters['type_inscription']) && $filters['type_inscription'] !== '') {
            $query->where('inscriptions.type_inscription', $filters['type_inscription']);
        }
        // Statut paiement (encaissé ou non)
        if (isset($filters['statut_paiement']) && $filters['statut_paiement'] !== '') {
            $query->where('details.statut_paiement', $filters['statut_paiement']);
        }
        // Mode de paiement (espèce, chèque, etc.)
        if (!empty($filters['mode_paiement'])) {
            $query->where('paiements.mode_paiement', $filters['mode_paiement']);
        }

        // Agrégats avant pagination (total montant, nombre de détails)
        $aggregates = [
            'total_montant' => (clone $query)->sum('details.montant'),
            'total_details' => (clone $query)->count(),
        ];

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $details = $query->orderBy('details.date_paiement', 'desc')
                         ->paginate($perPage);

        // Formatage des données
        $data = $details->map(function ($detail) {
            return [
                'id'                 => $detail->id,
                'libelle'            => $detail->libelle,
                'montant'            => $detail->montant,
                'type_paiement'      => $detail->type_paiement,
                'type_paiement_label'=> $this->getTypePaiementLabel($detail->type_paiement),
                'statut_paiement'    => $detail->statut_paiement,
                'statut_label'       => $this->getStatutLabel($detail->statut_paiement),
                'date_paiement'      => $detail->date_paiement,
                'date_encaissement'  => $detail->date_encaissement,
                'paiement_reference' => $detail->paiement_reference,
                'paiement_mode'      => $detail->paiement_mode,
                'eleve' => [
                    'id'         => $detail->inscription->eleve->id ?? null,
                    'nom'        => $detail->eleve_nom,
                    'prenom'     => $detail->eleve_prenom,
                    'matricule'  => $detail->eleve_matricule,
                ],
                'inscription' => [
                    'id'               => $detail->inscription_id,
                    'type_inscription' => $detail->type_inscription,
                    'cycle'            => $detail->inscription->cycle->libelle ?? null,
                    'niveau'           => $detail->inscription->niveau->libelle ?? null,
                    'classe'           => $detail->inscription->classe->libelle ?? null,
                ]
            ];
        });

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $details->currentPage(),
                'last_page'    => $details->lastPage(),
                'per_page'     => $details->perPage(),
                'total'        => $details->total(),
            ],
            'aggregates' => $aggregates,
        ];
    }

    // Helpers
    private function getTypePaiementLabel(?int $type): string
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

    private function getStatutLabel(?int $statut): string
    {
        return match($statut) {
            0 => 'En attente',
            1 => 'Encaissé',
            2 => 'Annulé',
            default => 'Indéfini',
        };
    }
}