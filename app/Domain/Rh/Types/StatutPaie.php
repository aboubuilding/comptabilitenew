<?php

namespace App\Domain\Rh\Types;

enum StatutPaie: string
{
    case BROUILLON = 'brouillon';
    case VALIDE    = 'valide';
    case PAYE      = 'paye';
    case ANNULE    = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::VALIDE    => 'Validé',
            self::PAYE      => 'Payé',
            self::ANNULE    => 'Annulé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::BROUILLON => 'brouillon',
            self::VALIDE    => 'valide',
            self::PAYE      => 'paye',
            self::ANNULE    => 'annule',
        };
    }

    /** Un bulletin payé ou annulé est verrouillé. */
    public function isVerrouille(): bool
    {
        return in_array($this, [self::PAYE, self::ANNULE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}