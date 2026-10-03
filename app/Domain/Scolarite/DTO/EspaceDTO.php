<?php

namespace App\Domain\Scolarite\DTO;

/**
 * DTO immuable pour la création / mise à jour d'un espace familial.
 */
final readonly class EspaceDTO
{
    public function __construct(
        public string $nom_famille,
        public ?int   $annee_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nom_famille: (string) $data['nom_famille'],
            annee_id:    isset($data['annee_id']) ? (int) $data['annee_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'nom_famille' => $this->nom_famille,
            'annee_id'    => $this->annee_id,
        ];
    }
}