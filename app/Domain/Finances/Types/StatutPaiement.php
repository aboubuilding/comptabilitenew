<?php

namespace App\Domain\Finances\Types;

/**
 * Correspond exactement aux deux valeurs demandées par le cahier des
 * charges §4 (2.2) : "Statut (en attente d'encaissement / encaissé)".
 */
enum StatutPaiement: int
{
    case EnAttente = 1;
    case Encaisse  = 2;

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => "En attente d'encaissement",
            self::Encaisse  => 'Encaissé',
        };
    }
}