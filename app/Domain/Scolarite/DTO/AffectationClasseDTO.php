<?php

namespace App\Domain\Scolarite\DTO;

/**
 * DTO immuable pour l'affectation / désaffectation de classe.
 * classe_id = null  →  désaffectation (retirer la classe).
 */
final readonly class AffectationClasseDTO
{
    public function __construct(
        public ?int  $classe_id,
        public array $eleve_ids = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            classe_id: isset($data['classe_id']) && $data['classe_id'] !== ''
                ? (int) $data['classe_id']
                : null,
            eleve_ids: array_map('intval', $data['eleve_ids'] ?? []),
        );
    }

    public function estGroupee(): bool
    {
        return count($this->eleve_ids) > 1;
    }

    public function estDesaffectation(): bool
    {
        return $this->classe_id === null;
    }
}