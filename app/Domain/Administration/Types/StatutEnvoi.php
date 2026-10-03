<?php

namespace App\Domain\Administration\Types;

/**
 * Statut de livraison d'un destinataire.
 */
enum StatutEnvoi: string
{
    case EN_ATTENTE = 'en_attente';
    case ENVOYE     = 'envoye';
    case ECHEC      = 'echec';
    case OUVERT     = 'ouvert';

    public function libelle(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::ENVOYE     => 'Envoyé',
            self::ECHEC      => 'Échec',
            self::OUVERT     => 'Ouvert',
        };
    }

    public function couleur(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'secondary',
            self::ENVOYE     => 'success',
            self::ECHEC      => 'danger',
            self::OUVERT     => 'info',
        };
    }
}