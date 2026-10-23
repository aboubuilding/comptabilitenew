<?php

namespace App\Domain\ServicesEleves\DTO;

/** Zone de ramassage : sa ligne et son tarif mensuel indicatif. */
final readonly class ZoneData
{
    public function __construct(
        public string  $libelle,
        public ?string $description,
        public ?int    $ligneId,
        public float   $tarifBase,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:     trim($data['libelle']),
            description: $data['description'] ?? null,
            ligneId:     ! empty($data['ligne_id']) ? (int) $data['ligne_id'] : null,
            tarifBase:   (float) ($data['tarif_base'] ?? 0),
        );
    }
}
