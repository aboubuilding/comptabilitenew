<?php

namespace App\Types;

class TypePaiement
{
    const FRAIS_INSCRIPTION = 1;
    const FRAIS_SCOLARITE = 2;
    const SERVICES = 3;
    const PRODUIT = 4;
    const LIVRE = 5;
    const CAUTION = 6;
    const BUS = 7;
    const CANTINE = 8;
    const AUTRES = 9;
    const FRAIS_ASSURANCE = 10;
    const FRAIS_EXTRA_SCOLAIRE = 11;
    const FRAIS_EXAMEN = 12;

    public static function getLabel(int $type): string
    {
        return [
            self::FRAIS_INSCRIPTION => "Frais d'inscription",
            self::FRAIS_SCOLARITE => 'Frais de scolarité',
            self::SERVICES => 'Services',
            self::PRODUIT => 'Produit',
            self::LIVRE => 'Livre',
            self::CAUTION => 'Caution',
            self::BUS => 'Bus (Transport)',
            self::CANTINE => 'Cantine',
            self::AUTRES => 'Autres',
            self::FRAIS_ASSURANCE => "Frais d'assurance",
            self::FRAIS_EXTRA_SCOLAIRE => 'Frais extrascolaire',
            self::FRAIS_EXAMEN => "Frais d'examen",
        ][$type] ?? 'Type inconnu';
    }

    public static function getList(): array
    {
        return [
            self::FRAIS_INSCRIPTION => "Frais d'inscription",
            self::FRAIS_SCOLARITE => 'Frais de scolarité',
            self::SERVICES => 'Services',
            self::PRODUIT => 'Produit',
            self::LIVRE => 'Livre',
            self::CAUTION => 'Caution',
            self::BUS => 'Bus (Transport)',
            self::CANTINE => 'Cantine',
            self::AUTRES => 'Autres',
            self::FRAIS_ASSURANCE => "Frais d'assurance",
            self::FRAIS_EXTRA_SCOLAIRE => 'Frais extrascolaire',
            self::FRAIS_EXAMEN => "Frais d'examen",
        ];
    }

    /**
     * Méthode alias pour compatibilité ascendante
     */
    public static function getTypePaiement(int $type): string
    {
        return self::getLabel($type);
    }
}
