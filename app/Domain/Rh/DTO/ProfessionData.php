<?php

namespace App\Domain\Rh\DTO;

final readonly class ProfessionData
{
    public function __construct(
        public string $libelle,
        public float  $salaireBase,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:      trim($data['libelle']),
            salaireBase:  (float) $data['salaire_base'],
        );
    }
}