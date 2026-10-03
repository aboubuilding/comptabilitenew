<?php

namespace App\Domain\ServicesEleves\Types;

enum TypeActivite: string
{
    case SPORTIVE      = 'sportive';
    case CULTURELLE    = 'culturelle';
    case ARTISTIQUE    = 'artistique';
    case SCIENTIFIQUE  = 'scientifique';
    case LINGUISTIQUE  = 'linguistique';
    case TECHNOLOGIQUE = 'technologique';
    case SOCIALE       = 'sociale';
    case AUTRE         = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::SPORTIVE      => 'Sportive',
            self::CULTURELLE    => 'Culturelle',
            self::ARTISTIQUE    => 'Artistique',
            self::SCIENTIFIQUE  => 'Scientifique',
            self::LINGUISTIQUE  => 'Linguistique',
            self::TECHNOLOGIQUE => 'Technologique',
            self::SOCIALE       => 'Sociale',
            self::AUTRE         => 'Autre',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::SPORTIVE      => 'sportive',
            self::CULTURELLE    => 'culturelle',
            self::ARTISTIQUE    => 'artistique',
            self::SCIENTIFIQUE  => 'scientifique',
            self::LINGUISTIQUE  => 'linguistique',
            self::TECHNOLOGIQUE => 'technologique',
            self::SOCIALE       => 'sociale',
            self::AUTRE         => 'autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}