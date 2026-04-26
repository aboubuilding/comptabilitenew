<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\Mouvement;
use App\Repositories\Contracts\DepenseRepositoryInterface;
use App\Repositories\Contracts\MouvementRepositoryInterface;
use App\Constants\TypeMouvement;
use App\Constants\StatutMouvement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class DepenseService
{
    public const SEUIL_VALIDATION_AUTO = 150000;

    public function __construct(
        private DepenseRepositoryInterface $depenseRepo,
        private MouvementRepositoryInterface $mouvementRepo
    ) {}

    /**
     * Enregistre une nouvelle dépense et applique la règle de validation auto
     */
    public function enregistrerDepense(array $data, int $userId): Depense
    {
        return DB::transaction(function () use ($data, $userId) {
            $depense = $this->depenseRepo->create([
                'libelle'           => $data['libelle'],
                'beneficiaire'      => $data['beneficiaire'] ?? null,
                'motif'             => $data['motif'],
                'montant_prevu'     => $data['montant'],
                'date_demande'      => now(),
                'date_prevue_paiement' => $data['date_prevue'] ?? null,
                'statut_depense'    => 'en_attente',
                'demandeur_id'      => $userId,
                'annee_scolaire_id' => $data['annee_id'],
                'justificatif_demande' => $data['justificatif'] ?? null,
            ]);

            // Règle métier : validation auto si < seuil
            if ($depense->montant_prevu < self::SEUIL_VALIDATION_AUTO) {
                $this->validerEtGenererMouvement($depense, $userId, auto: true);
            }

            return $depense;
        });
    }

    /**
     * Validation manuelle par un admin/directeur (montant >= seuil)
     */
    public function validerDepense(int $id, int $validatorId): bool
    {
        $depense = $this->depenseRepo->findOrFail($id);

        if ($depense->montant_prevu >= self::SEUIL_VALIDATION_AUTO) {
            $validator = \App\Models\User::findOrFail($validatorId);
            if (!$validator->hasRole(['admin', 'directeur'])) {
                throw new LogicException('Seuls les administrateurs ou directeurs peuvent valider cette dépense.');
            }
        }

        if ($depense->statut_depense !== 'en_attente') {
            throw new LogicException('Cette dépense n\'est plus en attente de validation.');
        }

        return DB::transaction(function () use ($depense, $validatorId) {
            $this->depenseRepo->valider($depense->id, $validatorId);
            return $this->validerEtGenererMouvement($depense, $validatorId, auto: false);
        });
    }

    /**
     * Rejet d'une dépense avec motif
     */
    public function rejeterDepense(int $id, string $motif, int $userId): bool
    {
        $depense = $this->depenseRepo->findOrFail($id);
        
        if ($depense->statut_depense !== 'en_attente') {
            throw new LogicException('Seules les dépenses en attente peuvent être rejetées.');
        }

        return $depense->update([
            'statut_depense' => 'refusee',
            'validateur_id'  => $userId,
            'date_validation' => now(),
            'commentaire_validation' => $motif,
        ]);
    }

    /**
     * Valide la dépense ET crée le mouvement de décaissement associé
     */
    private function validerEtGenererMouvement(Depense $depense, int $userId, bool $auto): bool
    {
        // Création du mouvement de type DECAISSEMENT, statut VALIDER (impacte le solde)
        $this->mouvementRepo->enregistrer([
            'caisse_id'         => $this->getActiveCaisseId($userId), // À adapter selon ta logique
            'type_mouvement'    => TypeMouvement::DECAISSEMENT,
            'montant'           => $depense->montant_prevu,
            'motif'             => $depense->motif,
            'beneficiaire'      => $depense->beneficiaire,
            'depense_id'        => $depense->id,
            'annee_id'          => $depense->annee_scolaire_id,
            'utilisateur_id'    => $userId,
            'statut_mouvement'  => StatutMouvement::VALIDER, // ✅ Impacte immédiatement le solde
            'reference'         => 'DEP-' . $depense->id . '-' . now()->format('Ymd'),
        ]);

        // Mise à jour du statut de la dépense
        return $depense->update(['statut_depense' => 'payee']);
    }

    /**
     * Récupère la caisse active de l'utilisateur (à adapter à ton contexte)
     */
    private function getActiveCaisseId(int $userId): int
    {
        // Exemple : via CaisseRepository
        $caisse = app(\App\Repositories\Contracts\CaisseRepositoryInterface::class)
            ->getActiveOuverte($userId);
            
        if (!$caisse) {
            throw new LogicException('Aucune caisse ouverte pour cet utilisateur.');
        }
        return $caisse->id;
    }

    /**
     * Liste des dépenses avec stats et informations de l'enregistreur
     */
    public function getDepensesWithStats(array $filters = []): Collection
    {
        $query = $this->depenseRepo->getModel()
            ->with(['demandeur:id,name', 'mouvements:id,depense_id,montant,statut_mouvement'])
            ->where('etat', \App\Repositories\Eloquent\BaseRepository::ACTIF);

        // Filtres optionnels
        if (!empty($filters['statut'])) {
            $query->where('statut_depense', $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_demande', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_demande', '<=', $filters['date_fin']);
        }
        if (!empty($filters['annee_id'])) {
            $query->where('annee_scolaire_id', $filters['annee_id']);
        }

        return $query->orderByDesc('date_demande')->get()->map(function ($depense) {
            return [
                'id'                => $depense->id,
                'libelle'           => $depense->libelle,
                'beneficiaire'      => $depense->beneficiaire,
                'montant'           => (float) $depense->montant_prevu,
                'statut'            => $depense->statut_depense,
                'motif_rejet'       => $depense->commentaire_validation,
                'enregistree_par'   => $depense->demandeur?->name,
                'date'              => $depense->date_demande,
                'validee_par'       => $depense->validateur_id ? \App\Models\User::find($depense->validateur_id)?->name : null,
            ];
        });
    }
}