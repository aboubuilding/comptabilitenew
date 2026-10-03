<?php

namespace App\Domain\Finances\Types;

enum DossierStatut: string
{
    case EN_COURS      = 'en_cours';
    case ECHEANCIER    = 'echeancier';
    case SOLDE         = 'solde';
    case IRRECOUVRABLE = 'irrecouvrable';
    case CONTENTIEUX   = 'contentieux';

    public function label(): string
    {
        return match ($this) {
            self::EN_COURS      => 'En cours',
            self::ECHEANCIER    => 'Échéancier accepté',
            self::SOLDE         => 'Soldé',
            self::IRRECOUVRABLE => 'Irrécouvrable',
            self::CONTENTIEUX   => 'Contentieux',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::EN_COURS      => 'encours',
            self::ECHEANCIER    => 'echeancier',
            self::SOLDE         => 'solde',
            self::IRRECOUVRABLE => 'irrecouvrable',
            self::CONTENTIEUX   => 'contentieux',
        };
    }

    /** Un dossier dans ces statuts est fermé — plus de relance automatique. */
    public function isClos(): bool
    {
        return in_array($this, [self::SOLDE, self::IRRECOUVRABLE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}