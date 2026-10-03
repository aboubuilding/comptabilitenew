<?php

namespace App\Domain\Finances\Types;

enum TypeForfait: int
{
    case OBLIGATOIRE = 1;   // scolarité, inscription, assurance
    case OPTIONNEL   = 2;   // cantine, bus, activités

    public function label(): string
    {
        return match ($this) {
            self::OBLIGATOIRE => 'Obligatoire',
            self::OPTIONNEL   => 'Optionnel',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::OBLIGATOIRE => 'obligatoire',
            self::OPTIONNEL   => 'optionnel',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}