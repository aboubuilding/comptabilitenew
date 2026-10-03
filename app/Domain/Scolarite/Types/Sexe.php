<?php

namespace App\Domain\Scolarite\Types;

/**
 * Sexe de l'élève (tinyInteger en base).
 * 1 = Masculin, 2 = Féminin
 */
enum Sexe: int
{
    case MASCULIN = 1;
    case FEMININ  = 2;

    public function libelle(): string
    {
        return match($this) {
            self::MASCULIN => 'Masculin',
            self::FEMININ  => 'Féminin',
        };
    }

    public function initiale(): string
    {
        return match($this) {
            self::MASCULIN => 'M',
            self::FEMININ  => 'F',
        };
    }

    /**
     * Retourne les options pour les selects.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->libelle()],
            self::cases()
        );
    }
}