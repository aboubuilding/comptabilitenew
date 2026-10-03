<?php

namespace App\Domain\ServicesEleves\Types;

enum TypeRepas: int
{
    case DEJEUNER = 1;
    case GOUTER   = 2;
    case PETIT_DEJEUNER = 3;

    public function label(): string
    {
        return match ($this) {
            self::DEJEUNER       => 'Déjeuner',
            self::GOUTER         => 'Goûter',
            self::PETIT_DEJEUNER => 'Petit-déjeuner',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::DEJEUNER       => 'dejeuner',
            self::GOUTER         => 'gouter',
            self::PETIT_DEJEUNER => 'petit-dejeuner',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}