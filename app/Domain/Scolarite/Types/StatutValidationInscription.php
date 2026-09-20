<?php

namespace App\Domain\Scolarite\Types;

/**
 * HYPOTHÈSE à confirmer : le schéma ('statut_validation' tinyInteger sur
 * inscriptions) ne documente pas les valeurs. Alignée sur le cycle de vie
 * décrit au cahier des charges : "Saisie par le secrétariat, soumise à
 * validation de la Direction, puis susceptible d'un abandon en cours
 * d'année."
 */
enum StatutValidationInscription: int
{
    case EnAttente = 1;
    case Validee   = 2;
    case Rejetee   = 3;

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de validation',
            self::Validee   => 'Validée',
            self::Rejetee   => 'Rejetée',
        };
    }
}