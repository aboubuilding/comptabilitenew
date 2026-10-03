<?php

namespace App\Domain\ServicesEleves\Types;

enum StatutInscriptionActivite: int
{
    case EN_ATTENTE = 1;
    case CONFIRMEE  = 2;
    case ANNULEE    = 3;

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRMEE  => 'Confirmée',
            self::ANNULEE    => 'Annulée',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'attente',
            self::CONFIRMEE  => 'confirmee',
            self::ANNULEE    => 'annulee',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}