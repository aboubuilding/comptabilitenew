<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class PretData
{
    public function __construct(
        public int     $livreId,
        public int     $inscriptionId,
        public string  $datePret,
        public string  $dateRetourPret,
        public ?float  $caution,
        public ?string $observations,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            livreId:        (int) $data['livre_id'],
            inscriptionId:  (int) $data['inscription_id'],
            datePret:       $data['date_pret'] ?? now()->toDateString(),
            dateRetourPret: $data['date_retour_pret'],
            caution:        isset($data['caution']) ? (float) $data['caution'] : null,
            observations:   $data['observations'] ?? null,
        );
    }
}