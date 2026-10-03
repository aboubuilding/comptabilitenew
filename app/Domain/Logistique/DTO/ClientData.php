<?php

namespace App\Domain\Logistique\DTO;

final readonly class ClientData
{
    public function __construct(
        public string  $nomRaisonSociale,
        public string  $typeClient,
        public ?string $telephone,
        public ?string $email,
        public ?string $adresse,
        public ?string $nif,
        public ?string $rccm,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nomRaisonSociale: trim($data['nom_raison_sociale']),
            typeClient:       $data['type_client'] ?? 'particulier',
            telephone:        $data['telephone'] ?? null,
            email:            $data['email'] ?? null,
            adresse:          $data['adresse'] ?? null,
            nif:              $data['nif'] ?? null,
            rccm:             $data['rccm'] ?? null,
        );
    }
}