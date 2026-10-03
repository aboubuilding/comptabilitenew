<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class LigneData
{
    public function __construct(
        public string $libelle,
        public ?float $prixMinimal,
        public ?float $prixPlafond,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:     trim($data['libelle']),
            prixMinimal: isset($data['prix_minimal']) ? (float) $data['prix_minimal'] : null,
            prixPlafond: isset($data['prix_plafond']) ? (float) $data['prix_plafond'] : null,
        );
    }
}