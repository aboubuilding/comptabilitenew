<?php

namespace App\Domain\Logistique\DTO;

final readonly class FournisseurData
{
    public function __construct(
        public string  $raisonSociale,
        public ?string $nomContact,
        public ?string $telephoneContact,
        public ?string $emailContact,
        public ?string $adresse,
        public ?string $nif,
        public ?string $rccm,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            raisonSociale:    trim($data['raison_social']),
            nomContact:       $data['nom_contact'] ?? null,
            telephoneContact: $data['telephone_contact'] ?? null,
            emailContact:     $data['email_contact'] ?? null,
            adresse:          $data['adresse'] ?? null,
            nif:              $data['nif'] ?? null,
            rccm:             $data['rccm'] ?? null,
        );
    }
}