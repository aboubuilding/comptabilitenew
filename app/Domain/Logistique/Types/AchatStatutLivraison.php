<?php

namespace App\Domain\Logistique\Types;

enum AchatStatutLivraison: int
{
    // Codes de l'ancienne classe App\Types\StatutLivraison ; ANNULE est ajouté
    case EN_ATTENTE = 1;   // ancien NON_LIVRE
    case LIVRE      = 2;
    case ANNULE     = 3;

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::LIVRE      => 'Livré',
            self::ANNULE     => 'Annulé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'attente',
            self::LIVRE      => 'livre',
            self::ANNULE     => 'annule',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
