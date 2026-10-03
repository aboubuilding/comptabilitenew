<?php

namespace App\Domain\ServicesEleves\Types;

enum StatutVehicule: int
{
    case DISPONIBLE    = 1;
    case EN_MAINTENANCE = 2;
    case SORTI         = 3;
    case REFORME       = 4;

    public function label(): string
    {
        return match ($this) {
            self::DISPONIBLE    => 'Disponible',
            self::EN_MAINTENANCE => 'En maintenance',
            self::SORTI         => 'Sorti',
            self::REFORME       => 'Réformé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::DISPONIBLE    => 'disponible',
            self::EN_MAINTENANCE => 'maintenance',
            self::SORTI         => 'sorti',
            self::REFORME       => 'reforme',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}