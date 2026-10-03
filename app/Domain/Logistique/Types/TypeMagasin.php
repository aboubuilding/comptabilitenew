<?php

namespace App\Domain\Logistique\Types;

enum TypeMagasin: int
{
    case ENTREPOT = 1;
    case BOUTIQUE = 2;

    public function label(): string
    {
        return match ($this) {
            self::ENTREPOT => 'Entrepôt',
            self::BOUTIQUE => 'Boutique / Point de vente',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::ENTREPOT => 'entrepot',
            self::BOUTIQUE => 'boutique',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}