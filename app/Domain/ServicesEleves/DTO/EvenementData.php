<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class EvenementData
{
    public function __construct(
        public string  $nom,
        public string  $type,
        public string  $dateEvenement,
        public float   $participation,
        public ?int    $capacite,
        public bool    $capaciteLimitee,
        public ?string $dateLimiteConfirmation,
        public ?string $description,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nom:                     trim($data['nom']),
            type:                    $data['type'],
            dateEvenement:           $data['date_evenement'],
            participation:           (float) $data['participation'],
            capacite:                isset($data['capacite']) && $data['capacite'] !== '' ? (int) $data['capacite'] : null,
            capaciteLimitee:         (bool) ($data['capacite_limitee'] ?? false),
            dateLimiteConfirmation:  $data['date_limite_confirmation'] ?? null,
            description:             $data['description'] ?? null,
        );
    }
}