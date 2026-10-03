<?php

namespace App\Domain\Scolarite\Types;

/**
 * Lien de parenté de la personne à prévenir en cas d'urgence.
 */
enum LienParente: int
{
    case PERE   = 1;
    case MERE   = 2;
    case TUTEUR = 3;
    case AUTRE  = 4;

    public function libelle(): string
    {
        return match($this) {
            self::PERE   => 'Père',
            self::MERE   => 'Mère',
            self::TUTEUR => 'Tuteur',
            self::AUTRE  => 'Autre',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $c) => ['value' => $c->value, 'label' => $c->libelle()],
            self::cases()
        );
    }
}