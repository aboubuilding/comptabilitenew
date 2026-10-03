<?php

namespace App\Domain\Finances\Types;

enum NatureCreance: string
{
    case SCOLARITE = 'scolarite';
    case CANTINE   = 'cantine';
    case BUS       = 'bus';
    case ACTIVITE  = 'activite';
    case AUTRE     = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::SCOLARITE => 'Écolage',
            self::CANTINE   => 'Cantine',
            self::BUS       => 'Transport',
            self::ACTIVITE  => 'Activité extrascolaire',
            self::AUTRE     => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}