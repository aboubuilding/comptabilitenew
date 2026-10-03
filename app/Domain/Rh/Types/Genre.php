<?php

namespace App\Domain\Rh\Types;

enum Genre: string
{
    case MASCULIN = 'M';
    case FEMININ  = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MASCULIN => 'Masculin',
            self::FEMININ  => 'Féminin',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}