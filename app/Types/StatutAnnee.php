<?php
// app/Enums/StatutAnnee.php

namespace App\Types;

class StatutAnnee
{
    const NON_OUVERT = 1;
    const OUVERT = 2;
    const CLOTURE = 3;

    /**
     * Obtenir le libellé du statut
     */
    public static function getLabel(int $statut): string
    {
        return match ($statut) {
            self::NON_OUVERT => 'Non ouvert',
            self::OUVERT => 'Ouvert',
            self::CLOTURE => 'Clôturé',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la liste des statuts pour les selects
     */
    public static function getList(): array
    {
        return [
            self::NON_OUVERT => 'Non ouvert',
            self::OUVERT => 'Ouvert',
            self::CLOTURE => 'Clôturé',
        ];
    }

    /**
     * Vérifier si un statut est valide
     */
    public static function isValid(int $statut): bool
    {
        return in_array($statut, [self::NON_OUVERT, self::OUVERT, self::CLOTURE]);
    }
}
