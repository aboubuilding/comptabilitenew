<?php

namespace App\Domain\Scolarite\Types;

/**
 * HYPOTHÈSE à confirmer : le schéma ('sexe' tinyInteger sur eleves) ne
 * documente pas les valeurs. Alignée sur la convention 1-indexée déjà
 * observée partout ailleurs dans ce schéma (etat, role...).
 */
enum Sexe: int
{
    case Masculin = 1;
    case Feminin  = 2;

    public function label(): string
    {
        return match ($this) {
            self::Masculin => 'Masculin',
            self::Feminin  => 'Féminin',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}