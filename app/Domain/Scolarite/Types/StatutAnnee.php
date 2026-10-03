<?php

namespace App\Domain\Scolarite\Types;

enum StatutAnnee: int
{
    // Codes de l'ancienne classe App\Types\StatutAnnee
    case NonOuvert = 1;
    case Ouvert    = 2;
    case Cloture   = 3;

    public function label(): string
    {
        // "Active" / "Clôturée" : terminologie exacte du cahier des
        // charges (§4, 1.4), pas "Ouverte" comme dans la version d'origine.
        return match ($this) {
            self::NonOuvert => 'Non ouverte',
            self::Ouvert    => 'Active',
            self::Cloture   => 'Clôturée',
        };
    }
}