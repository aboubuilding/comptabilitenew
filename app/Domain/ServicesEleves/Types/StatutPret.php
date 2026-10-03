<?php

namespace App\Domain\ServicesEleves\Types;

enum StatutPret: int
{
    case EN_COURS = 1;
    case RENDU    = 2;
    case RETARD   = 3;
    case PERDU    = 4;

    public function label(): string
    {
        return match ($this) {
            self::EN_COURS => 'En cours',
            self::RENDU    => 'Rendu',
            self::RETARD   => 'En retard',
            self::PERDU    => 'Perdu',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::EN_COURS => 'encours',
            self::RENDU    => 'rendu',
            self::RETARD   => 'retard',
            self::PERDU    => 'perdu',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}