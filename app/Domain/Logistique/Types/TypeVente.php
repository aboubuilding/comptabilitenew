<?php

namespace App\Domain\Logistique\Types;

enum TypeVente: int
{
    case COMPTOIR   = 1;
    case ABONNEMENT = 2;

    public function label(): string
    {
        return match ($this) {
            self::COMPTOIR   => 'Vente au comptoir',
            self::ABONNEMENT => 'Abonnement',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::COMPTOIR   => 'comptoir',
            self::ABONNEMENT => 'abonnement',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}