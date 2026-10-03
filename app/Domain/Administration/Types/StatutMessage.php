<?php

namespace App\Domain\Administration\Types;

/**
 * Statut d'un message.
 */
enum StatutMessage: string
{
    case BROUILLON = 'brouillon';
    case EN_COURS  = 'en_cours';
    case ENVOYE    = 'envoye';
    case PARTIEL   = 'partiel';
    case ECHEC     = 'echec';

    public function libelle(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::EN_COURS  => 'En cours d\'envoi',
            self::ENVOYE    => 'Envoyé',
            self::PARTIEL   => 'Partiellement envoyé',
            self::ECHEC     => 'Échec',
        };
    }

    public function couleur(): string
    {
        return match($this) {
            self::BROUILLON => 'secondary',
            self::EN_COURS  => 'info',
            self::ENVOYE    => 'success',
            self::PARTIEL   => 'warning',
            self::ECHEC     => 'danger',
        };
    }
}