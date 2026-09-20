<?php

namespace App\Domain\Finances\Types;

/**
 * Nature d'un mouvement de caisse.
 * Mappe la colonne `type_mouvement` (tinyInteger).
 *   1 = encaissement d'un paiement (via details.caisse_id)
 *   2 = règlement d'une dépense en espèces (via depenses.caisse_id)
 *   3 = entrée manuelle (fonds ajoutés, régularisation...)
 *   4 = sortie manuelle (prélèvement, régularisation...)
 */
enum MouvementType: int
{
    case ENCAISSEMENT     = 1;
    case DEPENSE          = 2;
    case ENTREE_MANUELLE  = 3;
    case SORTIE_MANUELLE  = 4;

    public function label(): string
    {
        return match ($this) {
            self::ENCAISSEMENT    => 'Encaissement',
            self::DEPENSE         => 'Dépense en espèces',
            self::ENTREE_MANUELLE => 'Entrée manuelle',
            self::SORTIE_MANUELLE => 'Sortie manuelle',
        };
    }

    /** true si ce type alimente la caisse (crédit), false sinon (débit). */
    public function isEntree(): bool
    {
        return in_array($this, [self::ENCAISSEMENT, self::ENTREE_MANUELLE], true);
    }

    /**
     * Types autorisés lors d'une saisie manuelle depuis l'écran Caisses.
     * Les encaissements et dépenses sont, eux, créés automatiquement par
     * les observers sur `details` et `depenses` — jamais à la main.
     *
     * @return array<int, self>
     */
    public static function manuels(): array
    {
        return [self::ENTREE_MANUELLE, self::SORTIE_MANUELLE];
    }
}