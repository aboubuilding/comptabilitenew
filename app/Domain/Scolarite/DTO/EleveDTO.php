<?php

namespace App\Domain\Scolarite\DTO;

/**
 * DTO immuable pour la création / mise à jour d'un élève.
 */
final readonly class EleveDTO
{
    public function __construct(
        public string  $nom,
        public string  $prenom,
        public ?string $prenom_usuel = null,
        public ?string $ecole_provenance = null,
        public ?string $date_naissance = null,
        public ?string $lieu_naissance = null,
        public ?int    $sexe = null,
        public ?int    $nationalite_id = null,
        public ?string $nom_medecin = null,
        public ?string $numero_medecin = null,
        public ?string $personne_prevenir = null,
        public ?string $numero_personne_prevenir = null,
        public ?int    $lien_parente_personne = null,
        public ?int    $groupe_id = null,
        public ?string $vaccin_1 = null,
        public ?string $vaccin_2 = null,
        public ?string $vaccin_3 = null,
        public ?string $vaccin_4 = null,
        public ?string $vaccin_5 = null,
        public ?string $allergie = null,
        public ?string $certificat_medical = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nom:                      (string) $data['nom'],
            prenom:                   (string) $data['prenom'],
            prenom_usuel:             $data['prenom_usuel'] ?? null,
            ecole_provenance:         $data['ecole_provenance'] ?? null,
            date_naissance:           $data['date_naissance'] ?? null,
            lieu_naissance:           $data['lieu_naissance'] ?? null,
            sexe:                     isset($data['sexe']) ? (int) $data['sexe'] : null,
            nationalite_id:           isset($data['nationalite_id']) ? (int) $data['nationalite_id'] : null,
            nom_medecin:              $data['nom_medecin'] ?? null,
            numero_medecin:           $data['numero_medecin'] ?? null,
            personne_prevenir:        $data['personne_prevenir'] ?? null,
            numero_personne_prevenir: $data['numero_personne_prevenir'] ?? null,
            lien_parente_personne:    isset($data['lien_parente_personne']) ? (int) $data['lien_parente_personne'] : null,
            groupe_id:                isset($data['groupe_id']) ? (int) $data['groupe_id'] : null,
            vaccin_1:                 $data['vaccin_1'] ?? null,
            vaccin_2:                 $data['vaccin_2'] ?? null,
            vaccin_3:                 $data['vaccin_3'] ?? null,
            vaccin_4:                 $data['vaccin_4'] ?? null,
            vaccin_5:                 $data['vaccin_5'] ?? null,
            allergie:                 $data['allergie'] ?? null,
            certificat_medical:       $data['certificat_medical'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'nom'                      => $this->nom,
            'prenom'                   => $this->prenom,
            'prenom_usuel'             => $this->prenom_usuel,
            'ecole_provenance'         => $this->ecole_provenance,
            'date_naissance'           => $this->date_naissance,
            'lieu_naissance'           => $this->lieu_naissance,
            'sexe'                     => $this->sexe,
            'nationalite_id'           => $this->nationalite_id,
            'nom_medecin'              => $this->nom_medecin,
            'numero_medecin'           => $this->numero_medecin,
            'personne_prevenir'        => $this->personne_prevenir,
            'numero_personne_prevenir' => $this->numero_personne_prevenir,
            'lien_parente_personne'    => $this->lien_parente_personne,
            'groupe_id'                => $this->groupe_id,
            'vaccin_1'                 => $this->vaccin_1,
            'vaccin_2'                 => $this->vaccin_2,
            'vaccin_3'                 => $this->vaccin_3,
            'vaccin_4'                 => $this->vaccin_4,
            'vaccin_5'                 => $this->vaccin_5,
            'allergie'                 => $this->allergie,
            'certificat_medical'       => $this->certificat_medical,
        ], fn ($v) => $v !== null);
    }
}