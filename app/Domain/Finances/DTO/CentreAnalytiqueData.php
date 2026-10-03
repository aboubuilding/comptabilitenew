<?php

namespace App\Domain\Finances\DTO;

final readonly class CentreAnalytiqueData
{
    public function __construct(
        public string  $code,
        public string  $libelle,
        public ?string $description,
        public string  $type,
        public ?int    $responsableId,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            code:          strtoupper(trim($data['code'])),
            libelle:       trim($data['libelle']),
            description:   $data['description'] ?? null,
            type:          $data['type'] ?? 'service',
            responsableId: isset($data['responsable_id']) ? (int) $data['responsable_id'] : null,
        );
    }
}