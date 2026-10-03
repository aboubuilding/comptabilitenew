<?php

namespace App\Domain\Scolarite\DTO;

use App\Domain\Scolarite\Types\StatutCompte;

/**
 * DTO immuable pour la création / mise à jour d'un compte portail parent.
 */
final readonly class CompteDTO
{
    public function __construct(
        public string $email,
        public int    $espace_id,
        public int    $parent_id,
        public int    $statut_compte = StatutCompte::ACTIF->value,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email:         (string) $data['email'],
            espace_id:     (int) $data['espace_id'],
            parent_id:     (int) $data['parent_id'],
            statut_compte: (int) ($data['statut_compte'] ?? StatutCompte::ACTIF->value),
        );
    }

    public function toArray(): array
    {
        return [
            'email'         => $this->email,
            'espace_id'     => $this->espace_id,
            'parent_id'     => $this->parent_id,
            'statut_compte' => $this->statut_compte,
        ];
    }
}