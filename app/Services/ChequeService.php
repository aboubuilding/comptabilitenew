<?php

namespace App\Services;

use App\Repositories\Eloquent\ChequeRepository;
use App\Repositories\Eloquent\PaiementRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChequeService
{
    protected $user;
    protected ChequeRepository $chequeRepo;
    protected PaiementRepository $paiementRepo;

    public function __construct(ChequeRepository $chequeRepo, PaiementRepository $paiementRepo)
    {
        $this->chequeRepo = $chequeRepo;
        $this->paiementRepo = $paiementRepo;
        $this->user = auth()->user();
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des chèques avec leurs relations (paiement, élève, banque)
     * Filtrée selon le rôle de l'utilisateur
     */
    public function listCheques(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return ['data' => collect(), 'pagination' => [], 'aggregates' => []];
        }

        // On commence par le modèle pour faire les jointures
        $query = $this->chequeRepo->getModel()->query()
            ->with(['banque', 'paiement.inscription.eleve', 'paiement.utilisateur'])
            ->where('cheques.annee_id', $anneeId)
            ->where('cheques.etat', 1)
            ->join('paiements', 'cheques.paiement_id', '=', 'paiements.id')
            ->join('inscriptions', 'paiements.inscription_id', '=', 'inscriptions.id')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->select('cheques.*',
                'paiements.reference as paiement_reference',
                'paiements.montant as paiement_montant',
                'paiements.utilisateur_id',
                'eleves.nom as eleve_nom',
                'eleves.prenom as eleve_prenom',
                'eleves.matricule as eleve_matricule',
                'inscriptions.type_inscription');

        // Restriction pour les non-admin
        if (!in_array($this->user->role, ['admin', 'directeur'])) {
            $query->where('paiements.utilisateur_id', $this->user->id);
        }

        // Filtres
        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $query->where('cheques.statut', $filters['statut']);
        }
        if (!empty($filters['banque_id'])) {
            $query->where('cheques.banque_id', $filters['banque_id']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('cheques.date_emission', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('cheques.date_emission', '<=', $filters['date_fin']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('cheques.numero', 'like', $search)
                    ->orWhere('cheques.emetteur', 'like', $search)
                    ->orWhere('eleves.nom', 'like', $search)
                    ->orWhere('eleves.prenom', 'like', $search)
                    ->orWhere('paiements.reference', 'like', $search);
            });
        }

        // Agrégats
        $aggregates = [
            'total_montant'     => (clone $query)->sum('paiements.montant'),
            'total_encaisse'    => (clone $query)->where('cheques.statut', 1)->sum('paiements.montant'),
            'total_en_attente'  => (clone $query)->where('cheques.statut', 0)->sum('paiements.montant'),
            'total_rejetes'     => (clone $query)->where('cheques.statut', 2)->sum('paiements.montant'),
            'nombre_total'      => (clone $query)->count(),
        ];

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $cheques = $query->orderBy('cheques.date_emission', 'desc')
            ->paginate($perPage);

        $data = $cheques->map(function ($cheque) {
            return [
                'id'                => $cheque->id,
                'numero'            => $cheque->numero,
                'emetteur'          => $cheque->emetteur,
                'date_emission'     => $cheque->date_emission,
                'statut'            => $cheque->statut,
                'statut_label'      => $this->getStatutLabel($cheque->statut),
                'date_encaissement' => $cheque->date_encaissement,
                'paiement_reference'=> $cheque->paiement_reference,
                'paiement_montant'  => $cheque->paiement_montant,
                'banque'            => $cheque->banque?->nom,
                'eleve' => [
                    'nom'       => $cheque->eleve_nom,
                    'prenom'    => $cheque->eleve_prenom,
                    'matricule' => $cheque->eleve_matricule,
                ],
                'utilisateur_id'    => $cheque->utilisateur_id,
            ];
        });

        return [
            'data'       => $data,
            'pagination' => [
                'current_page' => $cheques->currentPage(),
                'last_page'    => $cheques->lastPage(),
                'per_page'     => $cheques->perPage(),
                'total'        => $cheques->total(),
            ],
            'aggregates' => $aggregates,
        ];
    }

    /**
     * Mettre à jour le statut d'un chèque (ex: encaissement)
     */
    public function updateStatut(int $chequeId, array $data)
    {
        $cheque = $this->chequeRepo->with('paiement')->findOrFail($chequeId);

        if ($cheque->paiement->statut_paiement == 2) {
            throw ValidationException::withMessages(['statut' => 'Le paiement est annulé, impossible de modifier le chèque.']);
        }

        $oldStatut = $cheque->statut;
        $updateData = ['statut' => $data['statut']];
        if ($data['statut'] == 1 && isset($data['date_encaissement'])) {
            $updateData['date_encaissement'] = $data['date_encaissement'];
        }
        $this->chequeRepo->update($chequeId, $updateData);
        $cheque = $this->chequeRepo->find($chequeId); // recharger

        if ($data['statut'] == 1 && $oldStatut != 1) {
            $paiement = $cheque->paiement;
            if ($paiement && $paiement->statut_paiement == 0) {
                $this->paiementRepo->update($paiement->id, ['statut_paiement' => 1]);
            }
        }

        return $cheque;
    }

    private function getStatutLabel(?int $statut): string
    {
        return match($statut) {
            0 => 'Non encaissé',
            1 => 'Encaissé',
            2 => 'Rejeté',
            default => 'Inconnu',
        };
    }
}
