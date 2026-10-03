<?php

namespace App\Domain\Logistique\Types;

enum TypeClient: string
{
    case PARTICULIER = 'particulier';
    case ENTREPRISE  = 'entreprise';
    case INSTITUTION = 'institution';

    public function label(): string
    {
        return match ($this) {
            self::PARTICULIER => 'Particulier',
            self::ENTREPRISE  => 'Entreprise',
            self::INSTITUTION => 'Institution',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}