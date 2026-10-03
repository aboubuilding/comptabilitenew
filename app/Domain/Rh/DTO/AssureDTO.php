<?php

namespace App\Domain\Rh\DTO;

/**
 * DTO immuable pour la création/mise à jour d'une souscription d'assurance.
 */
final readonly class AssureDTO
{
    public function __construct(
        public int     $employe_id,
        public int     $assurance_id,
        public string  $date_souscription,
        public float   $prelevement_mensuel,
        public string  $statut = 'actif',
        public ?int    $annee_id = null,
    ) {}

    /**
     * Construit le DTO depuis un tableau validé.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            employe_id:          (int) $data['employe_id'],
            assurance_id:        (int) $data['assurance_id'],
            date_souscription:   (string) $data['date_souscription'],
            prelevement_mensuel: (float) $data['prelevement_mensuel'],
            statut:              $data['statut'] ?? 'actif',
            annee_id:            isset($data['annee_id']) ? (int) $data['annee_id'] : null,
        );
    }

    /**
     * Retourne un tableau prêt pour le repository.
     */
    public function toArray(): array
    {
        return [
            'employe_id'          => $this->employe_id,
            'assurance_id'        => $this->assurance_id,
            'date_souscription'   => $this->date_souscription,
            'prelevement_mensuel' => $this->prelevement_mensuel,
            'statut'              => $this->statut,
            'annee_id'            => $this->annee_id,
        ];
    }
}