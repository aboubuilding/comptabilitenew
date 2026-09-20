<?php

namespace App\Domain\Scolarite\Types;

/**
 * HYPOTHÈSE à confirmer : lien de la "personne à prévenir" avec l'élève
 * (eleves.lien_parente_personne, tinyInteger non documenté).
 */
enum LienParentePersonne: int
{
    case Pere   = 1;
    case Mere   = 2;
    case Tuteur = 3;
    case Autre  = 4;

    public function label(): string
    {
        return match ($this) {
            self::Pere   => 'Père',
            self::Mere   => 'Mère',
            self::Tuteur => 'Tuteur légal',
            self::Autre  => 'Autre',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}