<?php

namespace App\Domain\Scolarite\Types;

/**
 * Rôle du parent / tuteur dans le foyer.
 */
enum RoleParent: int
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
            fn (self $r) => ['value' => $r->value, 'label' => $r->libelle()],
            self::cases()
        );
    }
}