<?php

namespace App\Domain\Finances\Types;

enum TypeRelance: string
{
    case APPEL           = 'appel';
    case SMS             = 'sms';
    case EMAIL           = 'email';
    case COURRIER        = 'courrier';
    case MISE_EN_DEMEURE = 'mise_en_demeure';

    public function label(): string
    {
        return match ($this) {
            self::APPEL           => 'Appel téléphonique',
            self::SMS             => 'SMS',
            self::EMAIL           => 'Email',
            self::COURRIER        => 'Courrier',
            self::MISE_EN_DEMEURE => 'Mise en demeure',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::APPEL           => 'fa-phone',
            self::SMS             => 'fa-sms',
            self::EMAIL           => 'fa-envelope',
            self::COURRIER        => 'fa-envelope-open-text',
            self::MISE_EN_DEMEURE => 'fa-gavel',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}