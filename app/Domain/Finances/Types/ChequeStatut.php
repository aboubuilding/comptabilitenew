<?php

namespace App\Domain\Finances\Types;

/**
 * Statut d'un chèque dans le portefeuille.
 * Mappe la colonne `statut` (tinyInteger nullable) de `cheques`.
 */
enum ChequeStatut: int
{
    case EMIS     = 1;
    case ENCAISSE = 2;
    case REJETE   = 3;

    public function label(): string
    {
        return match ($this) {
            self::EMIS     => 'Émis',
            self::ENCAISSE => 'Encaissé',
            self::REJETE   => 'Rejeté',
        };
    }

    /** Classe CSS utilisée dans les vues (badge-cheque-{css}). */
    public function css(): string
    {
        return match ($this) {
            self::EMIS     => 'emis',
            self::ENCAISSE => 'encaisse',
            self::REJETE   => 'rejete',
        };
    }

    /**
     * Un chèque sur ces statuts n'est plus modifiable (état figé),
     * sauf réouverture explicite par un administrateur.
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::ENCAISSE, self::REJETE], true);
    }

    /** @return array<int,string> [value => label] pour les <select>. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}