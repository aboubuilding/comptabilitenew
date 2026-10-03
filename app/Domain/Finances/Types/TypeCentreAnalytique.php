<?php

namespace App\Domain\Finances\Types;

enum TypeCentreAnalytique: string
{
    case SERVICE = 'service';   // Scolarité, Cantine, Transport...
    case SECTION = 'section';   // Administration, Direction...
    case PROJET  = 'projet';    // Projet ponctuel à durée limitée

    public function label(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::SECTION => 'Section',
            self::PROJET  => 'Projet',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}