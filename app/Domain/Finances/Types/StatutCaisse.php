<?php

namespace App\Domain\Finances\Types;

/**
 * HYPOTHÈSE à confirmer : 'statut' (tinyInteger sur caisses) non
 * documenté dans le schéma.
 */
enum StatutCaisse: int
{
    case Ouverte  = 1;
    case Cloturee = 2;

    public function label(): string
    {
        return match ($this) {
            self::Ouverte  => 'Ouverte',
            self::Cloturee => 'Clôturée',
        };
    }
}