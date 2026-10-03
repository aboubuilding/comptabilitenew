<?php

namespace App\Domain\Scolarite\DTO;

/**
 * DTO immuable pour la création / mise à jour d'un parent / tuteur.
 */
final readonly class ParentDTO
{
    public function __construct(
        public string  $nom_parent,
        public string  $prenom_parent,
        public ?string $telephone = null,
        public ?string $whatsapp = null,
        public ?string $email = null,
        public ?string $profession = null,
        public ?string $quartier = null,
        public ?string $adresse = null,
        public ?int    $nationalite_id = null,
        public ?int    $role = null,
        public bool    $is_principal = false,
        public ?int    $espace_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nom_parent:    (string) $data['nom_parent'],
            prenom_parent: (string) $data['prenom_parent'],
            telephone:     $data['telephone'] ?? null,
            whatsapp:      $data['whatsapp'] ?? null,
            email:         $data['email'] ?? null,
            profession:    $data['profession'] ?? null,
            quartier:      $data['quartier'] ?? null,
            adresse:       $data['adresse'] ?? null,
            nationalite_id: isset($data['nationalite_id']) ? (int) $data['nationalite_id'] : null,
            role:          isset($data['role']) ? (int) $data['role'] : null,
            is_principal:  !empty($data['is_principal']),
            espace_id:     isset($data['espace_id']) ? (int) $data['espace_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'nom_parent'     => $this->nom_parent,
            'prenom_parent'  => $this->prenom_parent,
            'telephone'      => $this->telephone,
            'whatsapp'       => $this->whatsapp,
            'email'          => $this->email,
            'profession'     => $this->profession,
            'quartier'       => $this->quartier,
            'adresse'        => $this->adresse,
            'nationalite_id' => $this->nationalite_id,
            'role'           => $this->role,
            'is_principal'   => $this->is_principal ? 1 : 0,
            'espace_id'      => $this->espace_id,
        ];
    }
}