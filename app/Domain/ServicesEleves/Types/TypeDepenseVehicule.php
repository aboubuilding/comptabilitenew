<?php

namespace App\Domain\ServicesEleves\Types;

enum TypeDepenseVehicule: int
{
    case CARBURANT     = 1;
    case ENTRETIEN     = 2;
    case ASSURANCE     = 3;
    case REPARATION    = 4;
    case AMENDE        = 5;
    case AUTRE         = 6;

    public function label(): string
    {
        return match ($this) {
            self::CARBURANT  => 'Carburant',
            self::ENTRETIEN  => 'Entretien',
            self::ASSURANCE  => 'Assurance',
            self::REPARATION => 'Réparation',
            self::AMENDE     => 'Amende',
            self::AUTRE      => 'Autre',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::CARBURANT  => 'carburant',
            self::ENTRETIEN  => 'entretien',
            self::ASSURANCE  => 'assurance',
            self::REPARATION => 'reparation',
            self::AMENDE     => 'amende',
            self::AUTRE      => 'autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}