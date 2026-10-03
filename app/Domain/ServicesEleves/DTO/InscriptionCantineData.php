<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class InscriptionCantineData
{
    public function __construct(
        public int    $inscriptionId,
        public string $dateDebut,
        public float  $montantMensuel,
        public int    $nombreMois,
        public ?int   $fraisEcoleId = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            inscriptionId: (int) $data['inscription_id'],
            dateDebut:     $data['date_debut'],
            montantMensuel:(float) $data['montant_mensuel'],
            nombreMois:    (int) $data['nombre_mois'],
            fraisEcoleId:  isset($data['frais_ecole_id']) ? (int) $data['frais_ecole_id'] : null,
        );
    }

    public function montantTotalDu(): float
    {
        return round($this->montantMensuel * $this->nombreMois, 2);
    }
}