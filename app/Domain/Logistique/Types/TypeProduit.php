<?php

namespace App\Domain\Logistique\Types;

enum TypeProduit: int
{
    case CONSOMMABLE = 1;
    case VENDABLE    = 2;
    case MIXTE       = 3;

    public function label(): string
    {
        return match ($this) {
            self::CONSOMMABLE => 'Consommable interne',
            self::VENDABLE    => 'Vendable (boutique)',
            self::MIXTE       => 'Mixte',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::CONSOMMABLE => 'consommable',
            self::VENDABLE    => 'vendable',
            self::MIXTE       => 'mixte',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}