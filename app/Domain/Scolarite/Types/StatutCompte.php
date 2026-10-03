<?php

namespace App\Domain\Scolarite\Types;

/**
 * Statut métier du compte portail parent.
 * Codes de l'ancienne classe App\Types\StatutCompte, déjà portés par la
 * colonne `statut_compte` de la base historique.
 */
enum StatutCompte: int
{
    case EN_ATTENTE = 1;   // ancien EN_ATTENTE_VALIDATION
    case ACTIF      = 2;   // ancien VALIDE
    case INACTIF    = 3;   // ancien SUSPENDU
    case SUPPRIME   = 4;

    public function libelle(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACTIF      => 'Actif',
            self::INACTIF    => 'Inactif',
            self::SUPPRIME   => 'Supprimé',
        };
    }

    public function couleur(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'warning',
            self::ACTIF      => 'success',
            self::INACTIF    => 'secondary',
            self::SUPPRIME   => 'danger',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->libelle()],
            self::cases()
        );
    }
}