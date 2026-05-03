<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;
use App\Repositories\Interfaces\BaseRepositoryInterface;

interface FraisEcoleRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Récupère les frais actifs pour un niveau et une année donnée
     */
    public function getByNiveauEtAnnee(int $niveauId, int $anneeId): Collection;

    /**
     * Vérifie l'unicité d'un frais pour éviter les doublons (niveau + année + type)
     */
    public function existsForNiveauAnnee(int $niveauId, int $anneeId, int $typePaiement, ?int $excludeId = null): bool;

    /**
     * Calcule le montant total des frais pour un niveau/année
     */
    public function getMontantTotalByNiveauEtAnnee(int $niveauId, int $anneeId): float;

    /**
     * Clone les frais d'une année source vers une année destination (report annuel)
     */
    public function clonerPourAnnee(int $anneeSource, int $anneeDestination): int;

    /**
     * Liste des frais groupés par type de paiement (mensuel, trimestriel, annuel, etc.)
     */
    public function getListeGroupedByType(int $anneeId, ?int $niveauId = null): Collection;
}