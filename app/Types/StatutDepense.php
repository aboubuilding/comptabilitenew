<?php

namespace App\Types;

/**
 * Constantes de statut pour la table `depenses`
 * Valeurs entières pour cohérence avec StatutMouvement
 */
class StatutDepense
{
    public const EN_ATTENTE = 1;
    public const REFUSEE = 2;
    public const VALIDEE = 3;
    public const PAYEE = 4;
    public const PARTIELLEMENT_PAYEE = 5;

    /**
     * Retourne le libellé lisible d'un statut
     */
    public static function label(int $statut): string
    {
        return match($statut) {
            self::EN_ATTENTE => 'En attente',
            self::REFUSEE => 'Refusée',
            self::VALIDEE => 'Validée',
            self::PAYEE => 'Payée',
            self::PARTIELLEMENT_PAYEE => 'Partiellement payée',
            default => 'Inconnu'
        };
    }

    /**
     * Liste des statuts "actifs" (non supprimés logiquement)
     */
    public static function actifs(): array
    {
        return [
            self::EN_ATTENTE,
            self::VALIDEE,
            self::PAYEE,
            self::PARTIELLEMENT_PAYEE,
        ];
    }
}