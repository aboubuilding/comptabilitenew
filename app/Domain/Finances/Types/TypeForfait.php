<?php

namespace App\Domain\Finances\Types;

/**
 * Correspond directement aux natures de frais listées au cahier des
 * charges §4 (2.1) : "Définir un frais (scolarité, inscription,
 * assurance, cantine, bus, livres, examen)".
 */
enum TypeForfait: int
{
    case Scolarite   = 1;
    case Inscription = 2;
    case Assurance   = 3;
    case Cantine     = 4;
    case Bus         = 5;
    case Livres      = 6;
    case Examen      = 7;

    public function label(): string
    {
        return match ($this) {
            self::Scolarite   => 'Scolarité',
            self::Inscription => 'Inscription',
            self::Assurance   => 'Assurance',
            self::Cantine     => 'Cantine',
            self::Bus         => 'Bus',
            self::Livres      => 'Livres',
            self::Examen      => 'Examen',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}