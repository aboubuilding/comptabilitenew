<?php

namespace App\Domain\ServicesEleves\DTO;

/**
 * Souscription au bus : l'élève, la zone de ramassage et le montant convenu.
 * La ligne, le chauffeur et le véhicule découlent de la zone ; la position GPS
 * du domicile se renseigne après la souscription.
 */
final readonly class AbonnementBusData
{
    public function __construct(
        public int     $inscriptionId,
        public string  $dateDebut,
        public float   $montantMensuel,
        public int     $nombreMois,
        public ?int    $zoneId,
        public string  $sens = 'aller_retour',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            inscriptionId:  (int) $data['inscription_id'],
            dateDebut:      $data['date_debut'],
            montantMensuel: (float) $data['montant_mensuel'],
            nombreMois:     (int) $data['nombre_mois'],
            zoneId:         ! empty($data['zone_id']) ? (int) $data['zone_id'] : null,
            sens:           $data['sens'] ?? 'aller_retour',
        );
    }

    public function montantTotalDu(): float
    {
        return round($this->montantMensuel * $this->nombreMois, 2);
    }
}
