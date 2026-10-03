<?php

namespace App\Domain\Administration\DTO;

/**
 * DTO d'un destinataire résolu (avant insertion).
 */
final readonly class DestinataireDTO
{
    public function __construct(
        public string  $email,
        public ?string $nom = null,
        public ?string $type = null,
        public ?int    $id = null,
    ) {}

    public function toArray(int $messageId): array
    {
        return [
            'message_id'        => $messageId,
            'destinataire_type' => $this->type,
            'destinataire_id'   => $this->id,
            'nom'               => $this->nom,
            'email'             => $this->email,
            'statut_envoi'      => 'en_attente',
        ];
    }
}