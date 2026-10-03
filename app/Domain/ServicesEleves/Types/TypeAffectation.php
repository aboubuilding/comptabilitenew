<?php

namespace App\Domain\ServicesEleves\Types;

enum TypeAffectation: int
{
    case PERMANENTE  = 1;
    case TEMPORAIRE  = 2;

    public function label(): string
    {
        return match ($this) {
            self::PERMANENTE => 'Permanente',
            self::TEMPORAIRE => 'Temporaire',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::PERMANENTE => 'permanente',
            self::TEMPORAIRE => 'temporaire',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}