<?php

namespace App\Domain\ServicesEleves\DTO;

final readonly class AbonnementBusData
{
    public function __construct(
        public int     $inscriptionId,
        public string  $dateDebut,
        public float   $montantMensuel,
        public int     $nombreMois,
        public ?int    $zoneId,
        public ?int    $voitureId,
        public ?int    $ligneId,
        public string  $sens,
        public ?string $pointArret,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            inscriptionId:  (int) $data['inscription_id'],
            dateDebut:      $data['date_debut'],
            montantMensuel: (float) $data['montant_mensuel'],
            nombreMois:     (int) $data['nombre_mois'],
            zoneId:         isset($data['zone_id']) ? (int) $data['zone_id'] : null,
            voitureId:      isset($data['voiture_id']) ? (int) $data['voiture_id'] : null,
            ligneId:        isset($data['ligne_id']) ? (int) $data['ligne_id'] : null,
            sens:           $data['sens'] ?? 'aller_retour',
            pointArret:     $data['point_arret'] ?? null,
        );
    }

    public function montantTotalDu(): float
    {
        return round($this->montantMensuel * $this->nombreMois, 2);
    }
}