<?php

namespace App\Domain\Finances\Types;

enum TypePrevision: string
{
    case RECETTE = 'recette';
    case DEPENSE = 'depense';

    public function label(): string
    {
        return match ($this) {
            self::RECETTE => 'Recette',
            self::DEPENSE => 'Dépense',
        };
    }

    public function css(): string
    {
        return $this->value;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
