<?php

namespace App\Domain\Logistique\Types;

enum TypeBon: int
{
    case ENTREE    = 1;
    case SORTIE    = 2;
    case TRANSFERT = 3;

    public function label(): string
    {
        return match ($this) {
            self::ENTREE    => 'Bon d\'entrée',
            self::SORTIE    => 'Bon de sortie',
            self::TRANSFERT => 'Bon de transfert',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::ENTREE    => 'entree',
            self::SORTIE    => 'sortie',
            self::TRANSFERT => 'transfert',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::ENTREE    => 'fa-arrow-down',
            self::SORTIE    => 'fa-arrow-up',
            self::TRANSFERT => 'fa-exchange-alt',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}