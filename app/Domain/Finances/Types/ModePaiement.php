<?php

namespace App\Domain\Finances\Types;

/**
 * HYPOTHÈSE à confirmer : 'mode_paiement' (tinyInteger sur paiements)
 * non documenté dans le schéma.
 */
enum ModePaiement: int
{
    case Especes        = 1;
    case Cheque          = 2;
    case Virement         = 3;
    case MobileMoney       = 4;

    public function label(): string
    {
        return match ($this) {
            self::Especes      => 'Espèces',
            self::Cheque       => 'Chèque',
            self::Virement     => 'Virement',
            self::MobileMoney  => 'Mobile Money',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}