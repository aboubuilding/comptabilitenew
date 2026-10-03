<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class LivreData
{
    public function __construct(
        public string  $titre,
        public ?string $numero,
        public ?string $numeroIsbn,
        public ?int    $auteurId,
        public ?int    $maisonEditionId,
        public ?int    $anneeEditionId,
        public ?int    $categorieLivreId,
        public int     $nombreExemplaires,
        public ?string $description,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            titre:              trim($data['titre']),
            numero:             $data['numero'] ?? null,
            numeroIsbn:         $data['numero_isbn'] ?? null,
            auteurId:           isset($data['auteur_id']) ? (int) $data['auteur_id'] : null,
            maisonEditionId:    isset($data['maison_edition_id']) ? (int) $data['maison_edition_id'] : null,
            anneeEditionId:     isset($data['annee_edition_id']) ? (int) $data['annee_edition_id'] : null,
            categorieLivreId:   isset($data['categorie_livre_id']) ? (int) $data['categorie_livre_id'] : null,
            nombreExemplaires:  (int) ($data['nombre_exemplaires'] ?? 1),
            description:        $data['description'] ?? null,
        );
    }
}