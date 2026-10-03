<?php

namespace App\Domain\Logistique\Types;

enum TypeFournisseur: string
{
    case FOURNITURES   = 'fournitures';   // fournitures scolaires, papeterie
    case ALIMENTAIRE   = 'alimentaire';   // cantine, denrées
    case MAINTENANCE   = 'maintenance';   // entretien, réparations
    case TRANSPORT     = 'transport';     // carburant, pièces véhicules
    case IMMOBILIER    = 'immobilier';    // loyers, charges
    case SERVICE       = 'service';       // prestations diverses
    case AUTRE         = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::FOURNITURES => 'Fournitures scolaires',
            self::ALIMENTAIRE => 'Alimentaire / Cantine',
            self::MAINTENANCE => 'Maintenance / Entretien',
            self::TRANSPORT   => 'Transport / Carburant',
            self::IMMOBILIER  => 'Immobilier',
            self::SERVICE     => 'Prestations de service',
            self::AUTRE       => 'Autre',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::FOURNITURES => 'fournitures',
            self::ALIMENTAIRE => 'alimentaire',
            self::MAINTENANCE => 'maintenance',
            self::TRANSPORT   => 'transport',
            self::IMMOBILIER  => 'immobilier',
            self::SERVICE     => 'service',
            self::AUTRE       => 'autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}