<?php

namespace App\Domain\Finances\DTO;

final readonly class BanqueData
{
    public function __construct(
        public string $nom,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(nom: trim($data['nom']));
    }
}