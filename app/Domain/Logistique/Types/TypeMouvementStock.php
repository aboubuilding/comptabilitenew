<?php

namespace App\Domain\Logistique\Types;

enum TypeMouvementStock: int
{
    case ENTREE            = 1;
    case SORTIE            = 2;
    case TRANSFERT_SORTIE  = 3;
    case TRANSFERT_ENTREE  = 4;
    case AJUSTEMENT        = 5;

    public function label(): string
    {
        return match ($this) {
            self::ENTREE           => 'Entrée',
            self::SORTIE           => 'Sortie',
            self::TRANSFERT_SORTIE => 'Transfert (sortie)',
            self::TRANSFERT_ENTREE => 'Transfert (entrée)',
            self::AJUSTEMENT       => 'Ajustement',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::ENTREE, self::TRANSFERT_ENTREE => 'entree',
            self::SORTIE, self::TRANSFERT_SORTIE => 'sortie',
            self::AJUSTEMENT                     => 'ajustement',
        };
    }

    /** Signe : +1 augmente le stock, -1 le diminue. */
    public function signe(): int
    {
        return in_array($this, [self::ENTREE, self::TRANSFERT_ENTREE], true) ? 1 : -1;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}