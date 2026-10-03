<?php

namespace App\Domain\Finances\Types;

enum TypeFrais: string
{
    case SCOLARITE   = 'scolarite';
    case INSCRIPTION = 'inscription';
    case ASSURANCE   = 'assurance';
    case CANTINE     = 'cantine';
    case BUS         = 'bus';
    case LIVRES      = 'livres';
    case EXAMEN      = 'examen';
    case AUTRE       = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::SCOLARITE   => 'Scolarité',
            self::INSCRIPTION => 'Inscription',
            self::ASSURANCE   => 'Assurance',
            self::CANTINE     => 'Cantine',
            self::BUS         => 'Transport scolaire',
            self::LIVRES      => 'Livres & fournitures',
            self::EXAMEN      => 'Frais d\'examen',
            self::AUTRE       => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}