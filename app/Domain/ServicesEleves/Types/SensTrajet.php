<?php

namespace App\Domain\ServicesEleves\Types;

enum SensTrajet: string
{
    case ALLER         = 'aller';
    case RETOUR        = 'retour';
    case ALLER_RETOUR  = 'aller_retour';

    public function label(): string
    {
        return match ($this) {
            self::ALLER        => 'Aller simple',
            self::RETOUR       => 'Retour simple',
            self::ALLER_RETOUR => 'Aller-retour',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::ALLER        => 'aller',
            self::RETOUR       => 'retour',
            self::ALLER_RETOUR => 'aller-retour',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}