<?php

namespace App\Domain\Scolarite\Types;

enum StatutAnnee: int
{
    case Ouvert  = 1;
    case Cloture = 2;

    public function label(): string
    {
        // "Active" / "Clôturée" : terminologie exacte du cahier des
        // charges (§4, 1.4), pas "Ouverte" comme dans la version d'origine.
        return match ($this) {
            self::Ouvert  => 'Active',
            self::Cloture => 'Clôturée',
        };
    }
}