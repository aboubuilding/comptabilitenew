<?php

namespace App\Domain\Logistique\Types;

enum AchatType: int
{
    case STOCK       = 1;   // achat destiné à entrer en stock
    case CONSOMMABLE = 2;   // achat de service ou consommable immédiat
    case IMMOBILIER  = 3;   // achat d'équipement durable

    public function label(): string
    {
        return match ($this) {
            self::STOCK       => 'Achat de stock',
            self::CONSOMMABLE => 'Consommable / Service',
            self::IMMOBILIER  => 'Équipement / Immobilisation',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}