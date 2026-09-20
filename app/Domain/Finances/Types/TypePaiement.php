<?php

namespace App\Domain\Finances\Types;

enum TypePaiement: int
{
    case Comptant   = 1;
    case Echelonne  = 2;

    public function label(): string
    {
        return match ($this) {
            self::Comptant  => 'Comptant',
            self::Echelonne => 'Échelonné',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}