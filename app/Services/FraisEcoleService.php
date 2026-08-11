<?php

namespace App\Services;

use App\Models\FraisEcole;
use App\Models\PlanEcheancier;
use App\Models\PlanEcheancierLigne;
use App\Repositories\Interfaces\FraisEcoleRepositoryInterface;
use App\Repositories\Interfaces\PlanEcheancierRepositoryInterface;
use App\Repositories\Interfaces\PlanEcheancierLigneRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FraisEcoleService
{
    protected FraisEcoleRepositoryInterface $fraisRepository;
    protected PlanEcheancierRepositoryInterface $planRepository;
    protected PlanEcheancierLigneRepositoryInterface $ligneRepository;

    public function __construct(
        FraisEcoleRepositoryInterface $fraisRepository,
        PlanEcheancierRepositoryInterface $planRepository,
        PlanEcheancierLigneRepositoryInterface $ligneRepository
    ) {
        $this->fraisRepository = $fraisRepository;
        $this->planRepository = $planRepository;
        $this->ligneRepository = $ligneRepository;
    }

    /**
     * Créer un frais avec son plan d'échéancier
     */
    public function createFraisWithEcheancier(array $fraisData, ?array $planData = null): FraisEcole
    {
        return DB::transaction(function () use ($fraisData, $planData) {
            // Créer le plan d'échéancier si fourni
            if ($planData && isset($planData['lignes']) && count($planData['lignes']) > 0) {
                $plan = $this->createPlanEcheancier($planData);
                $fraisData['plan_echeancier_id'] = $plan->id;
            }

            // Créer le frais
            $frais = $this->fraisRepository->createWithValidation($fraisData);

            Log::info('Frais créé avec succès', [
                'frais_id' => $frais->id,
                'has_echeancier' => isset($plan)
            ]);

            return $frais;
        });
    }

    /**
     * Mettre à jour un frais avec son plan d'échéancier
     */
    public function updateFraisWithEcheancier(FraisEcole $frais, array $fraisData, ?array $planData = null): FraisEcole
    {
        return DB::transaction(function () use ($frais, $fraisData, $planData) {
            // Gérer le plan d'échéancier
            if ($planData && isset($planData['lignes']) && count($planData['lignes']) > 0) {
                // Si le frais a déjà un plan, on le met à jour
                if ($frais->plan_echeancier_id) {
                    $plan = $this->updatePlanEcheancier($frais->planEcheancier, $planData);
                } else {
                    // Sinon on crée un nouveau plan
                    $plan = $this->createPlanEcheancier($planData);
                    $fraisData['plan_echeancier_id'] = $plan->id;
                }
            } else {
                // Si pas de plan fourni, on supprime l'association
                $fraisData['plan_echeancier_id'] = null;
            }

            // Mettre à jour le frais
            $frais = $this->fraisRepository->updateWithValidation($frais, $fraisData);

            Log::info('Frais mis à jour avec succès', [
                'frais_id' => $frais->id
            ]);

            return $frais;
        });
    }

    /**
     * Créer un plan d'échéancier avec ses lignes
     */
    public function createPlanEcheancier(array $data): PlanEcheancier
    {
        return DB::transaction(function () use ($data) {
            // Extraire les lignes
            $lignes = $data['lignes'] ?? [];
            unset($data['lignes']);

            // Créer le plan
            $plan = $this->planRepository->createWithValidation($data);

            // Créer les lignes
            foreach ($lignes as $ligne) {
                $this->ligneRepository->createForPlan($plan->id, $ligne);
            }

            Log::info('Plan d\'échéancier créé', [
                'plan_id' => $plan->id,
                'nombre_lignes' => count($lignes)
            ]);

            return $plan->load('lignes');
        });
    }

    /**
     * Mettre à jour un plan d'échéancier avec ses lignes
     */
    public function updatePlanEcheancier(PlanEcheancier $plan, array $data): PlanEcheancier
    {
        return DB::transaction(function () use ($plan, $data) {
            // Extraire les lignes
            $lignes = $data['lignes'] ?? [];
            unset($data['lignes']);

            // Mettre à jour le plan
            $plan = $this->planRepository->updateWithValidation($plan, $data);

            // Supprimer les anciennes lignes
            $this->ligneRepository->deleteByPlan($plan->id);

            // Créer les nouvelles lignes
            foreach ($lignes as $ligne) {
                $this->ligneRepository->createForPlan($plan->id, $ligne);
            }

            Log::info('Plan d\'échéancier mis à jour', [
                'plan_id' => $plan->id,
                'nombre_lignes' => count($lignes)
            ]);

            return $plan->load('lignes');
        });
    }

    /**
     * Supprimer un frais et son plan d'échéancier associé
     */
    public function deleteFraisWithEcheancier(FraisEcole $frais): bool
    {
        return DB::transaction(function () use ($frais) {
            $planId = $frais->plan_echeancier_id;

            // Supprimer le frais
            $this->fraisRepository->delete($frais->id);

            // Supprimer le plan associé si présent
            if ($planId) {
                $plan = $this->planRepository->find($planId);
                if ($plan) {
                    $this->ligneRepository->deleteByPlan($planId);
                    $this->planRepository->delete($planId);
                }
            }

            Log::info('Frais et plan d\'échéancier supprimés', [
                'frais_id' => $frais->id,
                'plan_id' => $planId
            ]);

            return true;
        });
    }

    /**
     * Récupérer un frais avec son plan d'échéancier
     */
    public function getFraisWithEcheancier(int $id): ?FraisEcole
    {
        return $this->fraisRepository->findOrFail($id)->load(['planEcheancier.lignes', 'niveau', 'annee']);
    }

    /**
     * Vérifier si un plan d'échéancier peut être supprimé
     */
    public function canDeletePlan(PlanEcheancier $plan): bool
    {
        // Vérifier si le plan est utilisé par des frais
        if ($plan->fraisEcoles()->exists()) {
            return false;
        }
        return true;
    }
}
