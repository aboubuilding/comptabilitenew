<?php

namespace App\Domain\Administration\DTO;

/**
 * DTO immuable pour la création/mise à jour d'un message.
 */
final readonly class MessageDTO
{
    public function __construct(
        public string  $sujet,
        public string  $corps,
        public ?string $type = null,
        public ?int    $modele_id = null,
        public ?int    $annee_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sujet:     (string) $data['sujet'],
            corps:     (string) $data['corps'],
            type:      $data['type'] ?? null,
            modele_id: isset($data['modele_id']) ? (int) $data['modele_id'] : null,
            annee_id:  isset($data['annee_id']) ? (int) $data['annee_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'sujet'     => $this->sujet,
            'corps'     => $this->corps,
            'type'      => $this->type,
            'modele_id' => $this->modele_id,
            'annee_id'  => $this->annee_id,
        ];
    }
}