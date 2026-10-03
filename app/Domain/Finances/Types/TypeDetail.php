<?php

namespace App\Domain\Finances\Types;

/**
 * Nature d'une ligne de paiement ou d'un frais (colonne `type_paiement` de
 * `details`, `frais_ecoles` et `souscriptions`).
 *
 * Codes 1 à 12 : ceux de l'ancienne classe App\Types\TypePaiement, déjà
 * portés par la base historique. 13 y était utilisé sans constante. Les
 * codes suivants sont les natures ajoutées depuis.
 */
enum TypeDetail: int
{
    case FRAIS_INSCRIPTION = 1;
    case FRAIS_SCOLAIRE    = 2;    // ancien FRAIS_SCOLARITE
    case SERVICE           = 3;    // ancien SERVICES
    case PRODUIT           = 4;
    case LIVRE             = 5;
    case CAUTION           = 6;
    case BUS               = 7;
    case CANTINE           = 8;
    case AUTRES            = 9;
    case FRAIS_ASSURANCE   = 10;
    case ACTIVITE          = 11;   // ancien FRAIS_EXTRA_SCOLAIRE
    case FRAIS_EXAMEN      = 12;
    case SANCTION_TABLETTE = 13;
    case EVENEMENT         = 14;

    public function label(): string
    {
        return match ($this) {
            self::FRAIS_INSCRIPTION => "Frais d'inscription",
            self::FRAIS_SCOLAIRE    => 'Frais de scolarité',
            self::SERVICE           => 'Service',
            self::PRODUIT           => 'Produit',
            self::LIVRE             => 'Livre',
            self::CAUTION           => 'Caution',
            self::BUS               => 'Transport',
            self::CANTINE           => 'Cantine',
            self::AUTRES            => 'Autres',
            self::FRAIS_ASSURANCE   => "Frais d'assurance",
            self::ACTIVITE          => 'Activité',
            self::FRAIS_EXAMEN      => "Frais d'examen",
            self::SANCTION_TABLETTE => 'Sanction tablette',
            self::EVENEMENT         => 'Événement',
        };
    }

    public function css(): string
    {
        return strtolower($this->name);
    }

    /** Nature de créance correspondante pour le recouvrement. */
    public function natureRecouvrement(): ?NatureCreance
    {
        return match ($this) {
            self::FRAIS_INSCRIPTION, self::FRAIS_SCOLAIRE,
            self::FRAIS_ASSURANCE, self::FRAIS_EXAMEN => NatureCreance::SCOLARITE,
            self::CANTINE                             => NatureCreance::CANTINE,
            self::BUS                                 => NatureCreance::BUS,
            self::ACTIVITE                            => NatureCreance::ACTIVITE,
            default                                   => null, // produits, services, événements → pas de créance
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
