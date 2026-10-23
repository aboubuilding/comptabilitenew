<?php

namespace App\Domain\ServicesEleves\DTO;

/** Parcours desservi par un chauffeur et un véhicule pendant une période. */
final readonly class LigneData
{
    public function __construct(
        public string  $libelle,
        public ?int    $chauffeurId,
        public ?int    $voitureId,
        public ?string $dateDebut,
        public ?string $dateFin,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            libelle:     trim($data['libelle']),
            chauffeurId: ! empty($data['chauffeur_id']) ? (int) $data['chauffeur_id'] : null,
            voitureId:   ! empty($data['voiture_id']) ? (int) $data['voiture_id'] : null,
            dateDebut:   $data['date_debut'] ?? null,
            dateFin:     $data['date_fin'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'libelle'      => $this->libelle,
            'chauffeur_id' => $this->chauffeurId,
            'voiture_id'   => $this->voitureId,
            'date_debut'   => $this->dateDebut,
            'date_fin'     => $this->dateFin,
        ];
    }
}
