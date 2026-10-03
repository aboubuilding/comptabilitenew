<?php

namespace App\Domain\Logistique\Types;

enum AchatStatutPaiement: int
{
    // Codes de l'ancienne classe App\Types\StatutReglement
    case NON_PAYE     = 1;
    case PAYE         = 2;
    case PARTIEL      = 3;   // ancien EN_COURS

    public function label(): string
    {
        return match ($this) {
            self::NON_PAYE => 'Non payé',
            self::PARTIEL  => 'Partiellement payé',
            self::PAYE     => 'Payé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::NON_PAYE => 'impaye',
            self::PARTIEL  => 'partiel',
            self::PAYE     => 'paye',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}