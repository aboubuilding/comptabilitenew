<?php

namespace App\Types;

class Role
{
    const ADMIN = 1;
    const DIRECTEUR = 2;
    const COMPTABLE = 3;
    const ADMIN_ADJOINT = 4;
    const CAISSIER = 5;
    const SECRETAIRE = 6;
    const ENSEIGNANT = 7;
    const PARENT = 8;

    public static function getLabel(int $role): string
    {
        return [
            self::ADMIN => 'Administrateur',
            self::DIRECTEUR => 'Directeur',
            self::COMPTABLE => 'Comptable',
            self::ADMIN_ADJOINT => 'Admin Adjoint',
            self::CAISSIER => 'Caissier',
            self::SECRETAIRE => 'Secrétaire',
            self::ENSEIGNANT => 'Enseignant',
            self::PARENT => 'Parent',
        ][$role] ?? 'Inconnu';
    }

    public static function getList(): array
    {
        return [
            self::ADMIN => 'Administrateur',
            self::DIRECTEUR => 'Directeur',
            self::COMPTABLE => 'Comptable',
            self::ADMIN_ADJOINT => 'Admin Adjoint',
            self::CAISSIER => 'Caissier',
            self::SECRETAIRE => 'Secrétaire',
            self::ENSEIGNANT => 'Enseignant',
            self::PARENT => 'Parent',
        ];
    }
}
