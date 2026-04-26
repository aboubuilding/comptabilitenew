<?php
namespace App\Repositories\Interfaces;

use App\Models\Caisse;

interface CaisseRepositoryInterface extends BaseRepositoryInterface
{
    /** Retourne la caisse ouverte d'un utilisateur (optionnel: filtrée par année) */
    public function getActiveOuverte(?int $userId = null, ?int $anneeId = null): ?Caisse;

    /** Vérifie si un utilisateur a déjà une caisse ouverte */
    public function hasUserOpenCaisse(int $userId): bool;

    /** Marque la caisse comme ouverte et définit le fond de caisse */
    public function ouvrir(int $caisseId, float $soldeInitial, int $userId): bool;

    /** Clôture la caisse avec le solde physique constaté */
    public function cloturer(int $caisseId, float $soldePhysique, int $userId, ?string $observation = null): bool;

    /** Calcule le solde théorique : solde_initial + entrées_validées - sorties_validées */
    public function calculerSoldeTheorique(int $caisseId): float;
}