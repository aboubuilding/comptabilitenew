<?php

namespace App\Domain\Finances\Types;

/**
 * Statut d'une session de caisse (une ligne de la table `caisses`).
 * Mappe la colonne `statut` (tinyInteger).
 */
enum CaisseStatut: int
{
    case OUVERTE  = 1;
    case CLOTUREE = 0;

    public function label(): string
    {
        return match ($this) {
            self::OUVERTE  => 'Ouverte',
            self::CLOTUREE => 'Clôturée',
        };
    }

    /** Classe CSS utilisée dans les vues (badge-etat-{css}). */
    public function css(): string
    {
        return match ($this) {
            self::OUVERTE  => 'ouverte',
            self::CLOTUREE => 'cloturee',
        };
    }

    /** Pour un <select> : [value => label]. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}