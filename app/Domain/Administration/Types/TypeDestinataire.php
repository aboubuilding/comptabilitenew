<?php

namespace App\Domain\Administration\Types;

/**
 * Nature du destinataire d'un message.
 */
enum TypeDestinataire: string
{
    case PARENT = 'parent';
    case ELEVE  = 'eleve';
    case TIERS  = 'tiers';
    case LIBRE  = 'libre';
    case GROUPE = 'groupe';

    public function libelle(): string
    {
        return match($this) {
            self::PARENT => 'Parent',
            self::ELEVE  => 'Élève',
            self::TIERS  => 'Tiers externe',
            self::LIBRE  => 'Adresse libre',
            self::GROUPE => 'Groupe (classe/niveau)',
        };
    }
}