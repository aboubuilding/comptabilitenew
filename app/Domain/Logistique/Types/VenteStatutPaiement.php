<?php

namespace App\Domain\Logistique\Types;

enum VenteStatutPaiement: int
{
    case IMPAYE  = 0;
    case PAYE    = 1;
    case ANNULE  = 2;

    public function label(): string
    {
        return match ($this) {
            self::IMPAYE => 'Impayé',
            self::PAYE   => 'Payé',
            self::ANNULE => 'Annulé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::IMPAYE => 'impaye',
            self::PAYE   => 'paye',
            self::ANNULE => 'annule',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}