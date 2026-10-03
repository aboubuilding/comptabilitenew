<?php

namespace App\Domain\Scolarite\Types;

/**
 * Statut de validation d'une inscription.
 */
enum StatutValidationInscription: int
{
    // Codes de l'ancienne classe App\Types\StatutValidation
    case EN_ATTENTE = 1;
    case VALIDEE    = 2;
    case REJETEE    = 3;   // ancien REJETTE
    case EN_COURS   = 4;
    // 5 est présent dans la base historique sans constante connue : non réattribué
    case ABANDONNEE = 6;

    public function libelle(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE    => 'Validée',
            self::REJETEE    => 'Rejetée',
            self::EN_COURS   => 'En cours',
            self::ABANDONNEE => 'Abandonnée',
        };
    }

    public function couleur(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::VALIDEE    => 'success',
            self::REJETEE    => 'danger',
            self::EN_COURS   => 'info',
            self::ABANDONNEE => 'secondary',
        };
    }
}