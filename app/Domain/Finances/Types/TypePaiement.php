<?php

namespace App\Domain\Finances\Types;

enum TypePaiement: int
{
    case COMPTANT   = 1;
    case ECHELONNE  = 2;

    public function label(): string
    {
        return match ($this) {
            self::COMPTANT  => 'Comptant',
            self::ECHELONNE => 'Échelonné',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::COMPTANT  => 'comptant',
            self::ECHELONNE => 'echelonne',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}