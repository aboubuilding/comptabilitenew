<?php

namespace App\Domain\Finances\Types;

/**
 * Traitement d'un écart de caisse constaté à la clôture.
 * Mappe la colonne `caisses.statut_ecart` (enum déjà présent dans le schéma).
 *
 * Cahier des charges, « Gestion des caisses et des écarts » : tout écart
 * est motivé par le caissier puis soumis à validation de la Direction.
 */
enum StatutEcartCaisse: string
{
    case AUCUN      = 'aucun';       // clôture sans écart
    case EN_ATTENTE = 'en_attente';  // écart déclaré, la Direction n'a pas statué
    case ENQUETE    = 'enquete';     // la Direction demande des vérifications
    case VALIDE     = 'valide';      // justification acceptée
    case REFUSE     = 'refuse';      // justification refusée

    public function label(): string
    {
        return match ($this) {
            self::AUCUN      => 'Aucun écart',
            self::EN_ATTENTE => 'En attente de validation',
            self::ENQUETE    => 'En cours de vérification',
            self::VALIDE     => 'Écart validé',
            self::REFUSE     => 'Écart refusé',
        };
    }

    /** Classe CSS utilisée dans les vues (badge-ecart-{css}). */
    public function css(): string
    {
        return match ($this) {
            self::AUCUN      => 'aucun',
            self::EN_ATTENTE => 'attente',
            self::ENQUETE    => 'enquete',
            self::VALIDE     => 'valide',
            self::REFUSE     => 'refuse',
        };
    }

    /** La Direction doit encore se prononcer. */
    public function isEnInstance(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::ENQUETE], true);
    }

    /**
     * Décisions que la Direction peut prendre sur un écart en instance.
     *
     * @return self[]
     */
    public static function decisions(): array
    {
        return [self::VALIDE, self::REFUSE, self::ENQUETE];
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
