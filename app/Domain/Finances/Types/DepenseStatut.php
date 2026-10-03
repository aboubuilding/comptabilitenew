<?php

namespace App\Domain\Finances\Types;

/**
 * Statut du cycle de vie d'une dépense.
 * Mappe la colonne `statut_depense` (tinyInteger nullable).
 * Codes de l'ancienne classe App\Types\StatutDepense :
 *   1 = en attente de validation
 *   2 = rejetée (motif obligatoire)
 *   3 = validée (peut être rattachée à une caisse pour règlement)
 *   4 = payée
 *   5 = partiellement payée
 */
enum DepenseStatut: int
{
    case EN_ATTENTE          = 1;
    case REJETEE             = 2;   // ancien REFUSEE
    case VALIDEE             = 3;
    case PAYEE               = 4;
    case PARTIELLEMENT_PAYEE = 5;

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE          => 'En attente',
            self::REJETEE             => 'Rejetée',
            self::VALIDEE             => 'Validée',
            self::PAYEE               => 'Payée',
            self::PARTIELLEMENT_PAYEE => 'Partiellement payée',
        };
    }

    /** Classe CSS pour les badges (badge-depense-{css}). */
    public function css(): string
    {
        return match ($this) {
            self::EN_ATTENTE          => 'attente',
            self::REJETEE             => 'rejetee',
            self::VALIDEE, self::PAYEE, self::PARTIELLEMENT_PAYEE => 'validee',
        };
    }

    /** Seule une dépense validée peut être réglée via une caisse. */
    public function peutEtreReglee(): bool
    {
        return $this === self::VALIDEE;
    }

    /** Un état final (validé/rejeté) ne peut plus être modifié librement. */
    public function isFinal(): bool
    {
        return $this !== self::EN_ATTENTE;
    }

    /** @return array<int,string> [value => label] pour un <select>. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}