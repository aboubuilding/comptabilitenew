<?php

namespace App\Domain\ServicesEleves\Types;

enum StatutInscriptionCantine: int
{
    case ACTIF     = 1;
    case ABANDONNE = 0;

    public function label(): string
    {
        return match ($this) {
            self::ACTIF     => 'Actif',
            self::ABANDONNE => 'Abandonné',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::ACTIF     => 'actif',
            self::ABANDONNE => 'abandonne',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}