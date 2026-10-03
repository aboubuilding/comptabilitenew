<?php

namespace App\Domain\ServicesEleves\Types;

enum TypeEvenement: string
{
    case EXCURSION           = 'excursion';
    case VOYAGE              = 'voyage';
    case SORTIE_PEDAGOGIQUE  = 'sortie_pedagogique';
    case COMPETITION         = 'competition';
    case AUTRE               = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::EXCURSION          => 'Excursion',
            self::VOYAGE             => 'Voyage',
            self::SORTIE_PEDAGOGIQUE => 'Sortie pédagogique',
            self::COMPETITION        => 'Compétition',
            self::AUTRE              => 'Autre',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::EXCURSION          => 'excursion',
            self::VOYAGE             => 'voyage',
            self::SORTIE_PEDAGOGIQUE => 'sortie',
            self::COMPETITION        => 'competition',
            self::AUTRE              => 'autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}