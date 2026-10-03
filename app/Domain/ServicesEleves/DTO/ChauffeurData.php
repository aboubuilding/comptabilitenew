<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class ChauffeurData
{
    public function __construct(
        public string  $nom,
        public string  $prenom,
        public ?string $permisConduire,
        public ?string $dateValiditePermis,
        public ?string $telephone,
        public ?string $email,
        public ?string $adresse,
        public int     $statut,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nom:               trim($data['nom']),
            prenom:            trim($data['prenom']),
            permisConduire:    $data['permis_conduire'] ?? null,
            dateValiditePermis: $data['date_validite_permis'] ?? null,
            telephone:         $data['telephone'] ?? null,
            email:             $data['email'] ?? null,
            adresse:           $data['adresse'] ?? null,
            statut:            (int) ($data['statut'] ?? 1),
        );
    }
}