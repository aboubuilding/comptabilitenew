<?php

namespace App\Domain\Rh\DTO;

final readonly class EmployeData
{
    public function __construct(
        public string  $nomPrenom,
        public ?string $numeroCnss,
        public ?string $quartier,
        public ?string $ville,
        public ?string $telephone,
        public ?string $dateEmbauche,
        public ?string $dateNaissance,
        public ?string $lieuNaissance,
        public ?string $email,
        public ?string $genre,
        public ?string $situationFamiliale,
        public ?string $dateFinContrat,
        public bool    $isCnss,
        public bool    $isAmu,
        public int     $nbreEnfant,
        public bool    $isPensionValidite,
        public ?int    $tauxPensionValidite,
        public ?float  $naturePensionValidite,
        public float   $salaire,
        public ?int    $professionId,

        // === Mode de paiement & fiscalité ===
        public string  $modePaiement,
        public ?string $banque,
        public ?string $iban,
        public ?string $titulaireCompte,
        public string  $irppMode,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            nomPrenom:              trim($data['nom_prenom']),
            numeroCnss:             $data['numero_cnss'] ?? null,
            quartier:               $data['quartier'] ?? null,
            ville:                  $data['ville'] ?? null,
            telephone:              $data['telephone'] ?? null,
            dateEmbauche:           $data['date_embauche'] ?? null,
            dateNaissance:          $data['date_naissance'] ?? null,
            lieuNaissance:          $data['lieu_naissance'] ?? null,
            email:                  $data['email'] ?? null,
            genre:                  $data['genre'] ?? null,
            situationFamiliale:     $data['situation_familiale'] ?? null,
            dateFinContrat:         $data['date_fin_contrat'] ?? null,
            isCnss:                 (bool) ($data['is_cnss'] ?? false),
            isAmu:                  (bool) ($data['is_amu'] ?? false),
            nbreEnfant:             (int) ($data['nbre_enfant'] ?? 0),
            isPensionValidite:      (bool) ($data['is_pension_validite'] ?? false),
            tauxPensionValidite:    isset($data['taux_pension_validite']) && $data['taux_pension_validite'] !== ''
                ? (int) $data['taux_pension_validite']
                : null,
            naturePensionValidite:  isset($data['nature_pension_validite']) && $data['nature_pension_validite'] !== ''
                ? (float) $data['nature_pension_validite']
                : null,
            salaire:                (float) $data['salaire'],
            professionId:           isset($data['profession_id']) && $data['profession_id'] !== ''
                ? (int) $data['profession_id']
                : null,

            // Mode de paiement & fiscalité
            modePaiement:           $data['mode_paiement'] ?? 'billette',
            banque:                 $data['banque'] ?? null,
            iban:                   $data['iban'] ?? null,
            titulaireCompte:        $data['titulaire_compte'] ?? null,
            irppMode:               $data['irpp_mode'] ?? 'auto',
        );
    }
}