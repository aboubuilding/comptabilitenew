<?php

namespace App\Domain\Finances\Types;

enum SensAnalytique: string
{
    case RECETTE = 'recette';
    case DEPENSE = 'depense';

    public function label(): string
    {
        return match ($this) {
            self::RECETTE => 'Recette',
            self::DEPENSE => 'Dépense',
        };
    }

    public function signe(): int
    {
        return $this === self::RECETTE ? 1 : -1;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}