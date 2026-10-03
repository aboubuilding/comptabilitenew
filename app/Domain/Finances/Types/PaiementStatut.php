<?php

namespace App\Domain\Finances\Types;

enum PaiementStatut: int
{
    // Codes de l'ancienne classe App\Types\StatutPaiement : la base historique les porte déjà
    case EN_ATTENTE            = 1;   // ancien NON_ENCAISSE
    case ENCAISSE              = 2;
    case EN_ATTENTE_ANNULATION = 3;
    case ANNULE                = 4;   // ancien ANNULES

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE            => "En attente d'encaissement",
            self::ENCAISSE              => 'Encaissé',
            self::EN_ATTENTE_ANNULATION => "En attente d'annulation",
            self::ANNULE                => 'Annulé',
        };
    }

    public function css(): string
    {
        return match ($this) {
            self::EN_ATTENTE            => 'attente',
            self::ENCAISSE              => 'encaisse',
            self::EN_ATTENTE_ANNULATION => 'attente',
            self::ANNULE                => 'annule',
        };
    }

    /** Un paiement encaissé ou annulé n'est plus modifiable. */
    public function isVerrouille(): bool
    {
        return in_array($this, [self::ENCAISSE, self::EN_ATTENTE_ANNULATION, self::ANNULE], true);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }

    // app/Domain/Finances/Types/PaiementStatut.php — ajout de helpers

public function isEncaisse(): bool
{
    return $this === self::ENCAISSE;
}

public function isAnnule(): bool
{
    return $this === self::ANNULE;
}
}