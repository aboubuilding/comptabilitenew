<?php

namespace App\Domain\Finances\Types;

enum ResultatRelance: string
{
    case SANS_REPONSE      = 'sans_reponse';
    case PROMESSE_PAIEMENT = 'promesse_paiement';
    case PAIEMENT_PARTIEL  = 'paiement_partiel';
    case PAIEMENT_TOTAL    = 'paiement_total';
    case REFUS             = 'refus';

    public function label(): string
    {
        return match ($this) {
            self::SANS_REPONSE      => 'Sans réponse',
            self::PROMESSE_PAIEMENT => 'Promesse de paiement',
            self::PAIEMENT_PARTIEL  => 'Paiement partiel',
            self::PAIEMENT_TOTAL    => 'Paiement total',
            self::REFUS             => 'Refus',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::SANS_REPONSE      => 'neutre',
            self::PROMESSE_PAIEMENT => 'attente',
            self::PAIEMENT_PARTIEL  => 'partiel',
            self::PAIEMENT_TOTAL    => 'positif',
            self::REFUS             => 'negatif',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}