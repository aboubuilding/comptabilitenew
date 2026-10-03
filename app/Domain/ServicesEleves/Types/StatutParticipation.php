<?php

namespace App\Domain\ServicesEleves\Types;

enum StatutParticipation: string
{
    case PRE_INSCRIT = 'pre_inscrit';
    case CONFIRME    = 'confirme';
    case ANNULE      = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::PRE_INSCRIT => 'Pré-inscrit',
            self::CONFIRME    => 'Confirmé',
            self::ANNULE      => 'Annulé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::PRE_INSCRIT => 'pre-inscrit',
            self::CONFIRME    => 'confirme',
            self::ANNULE      => 'annule',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}