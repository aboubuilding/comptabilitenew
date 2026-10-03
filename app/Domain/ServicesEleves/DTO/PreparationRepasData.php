<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class PreparationRepasData
{
    public function __construct(
        public int     $menuId,
        public int     $magasinId,
        public string  $datePreparation,
        public int     $nombreParts,
        public ?float  $coutReel,
        public ?string $observations,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            menuId:          (int) $data['menu_id'],
            magasinId:       (int) $data['magasin_id'],
            datePreparation: $data['date_preparation'],
            nombreParts:     (int) $data['nombre_parts'],
            coutReel:        isset($data['cout_reel']) && $data['cout_reel'] !== ''
                ? (float) $data['cout_reel'] : null,
            observations:    $data['observations'] ?? null,
        );
    }
}