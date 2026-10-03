<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class ZoneData
{
    public function __construct(
        public string  $libelle,
        public ?string $description,
        public ?int    $chauffeurId,
        public ?int    $voitureId,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:     trim($data['libelle']),
            description: $data['description'] ?? null,
            chauffeurId: isset($data['chauffeur_id']) ? (int) $data['chauffeur_id'] : null,
            voitureId:   isset($data['voiture_id']) ? (int) $data['voiture_id'] : null,
        );
    }
}