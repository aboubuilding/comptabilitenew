<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class ActiviteData
{
    public function __construct(
        public string  $libelle,
        public ?string $description,
        public float   $montant,
        public ?string $type,
        public ?string $encadreur,
        public ?string $contactEncadreur,
        public ?int    $niveauId,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:          trim($data['libelle']),
            description:      $data['description'] ?? null,
            montant:          (float) $data['montant'],
            type:             $data['type'] ?? null,
            encadreur:        $data['encadreur'] ?? null,
            contactEncadreur: $data['contact_encadreur'] ?? null,
            niveauId:         isset($data['niveau_id']) ? (int) $data['niveau_id'] : null,
        );
    }
}