<?php

namespace App\Domain\Scolarite\Types;

/**
 * HYPOTHÈSE à confirmer : 'type_inscription' (tinyInteger) non documenté
 * dans le schéma.
 */
enum TypeInscription: int
{
    case Nouvelle     = 1;
    case Reinscription = 2;

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle      => 'Nouvelle inscription',
            self::Reinscription => 'Réinscription',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}