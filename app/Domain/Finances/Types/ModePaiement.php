<?php

namespace App\Domain\Finances\Types;

enum ModePaiement: int
{
    case ESPECES      = 1;
    case CHEQUE       = 2;
    case VIREMENT     = 3;
    case MOBILE_MONEY = 4;

    public function label(): string
    {
        return match ($this) {
            self::ESPECES      => 'Espèces',
            self::CHEQUE       => 'Chèque',
            self::VIREMENT     => 'Virement',
            self::MOBILE_MONEY => 'Mobile Money',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::ESPECES      => 'fa-money-bill',
            self::CHEQUE       => 'fa-money-check',
            self::VIREMENT     => 'fa-university',
            self::MOBILE_MONEY => 'fa-mobile-alt',
        };
    }

    /**
     * Modes proposés à la saisie d'un paiement. Pour l'instant : espèces et
     * chèque. Les autres codes restent dans l'énumération pour l'historique.
     *
     * @return array<int, string> [valeur => libellé]
     */
    public static function disponibles(): array
    {
        return [
            self::ESPECES->value => self::ESPECES->label(),
            self::CHEQUE->value  => self::CHEQUE->label(),
        ];
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}