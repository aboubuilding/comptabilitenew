<?php

namespace App\Domain\Administration\DTO;

/**
 * DTO immuable pour la création/mise à jour d'un utilisateur.
 */
final readonly class UserDTO
{
    public function __construct(
        public string  $nom,
        public string  $prenom,
        public string  $login,
        public ?string $email,
        public ?string $mot_passe,   // null = ne pas modifier
        public int     $role_id,
        public ?string $photo = null,
        public int     $etat = 1,
        /** @var int[] Profils complémentaires (identifiants de roles) */
        public array   $profils_complementaires = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            nom:       (string) $data['nom'],
            prenom:    (string) $data['prenom'],
            login:     (string) $data['login'],
            email:     $data['email'] ?? null,
            mot_passe: $data['mot_passe'] ?? null,
            role_id:   (int) $data['role_id'],
            photo:     $data['photo'] ?? null,
            etat:      isset($data['etat']) ? (int) $data['etat'] : 1,
            profils_complementaires: array_values(array_unique(array_map('intval', $data['profils_complementaires'] ?? []))),
        );
    }

    /**
     * Tableau pour création (mot de passe hashé).
     */
    public function toArrayForCreate(): array
    {
        return [
            'nom'       => $this->nom,
            'prenom'    => $this->prenom,
            'login'     => $this->login,
            'email'     => $this->email,
            'mot_passe' => $this->mot_passe ? bcrypt($this->mot_passe) : null,
            'role_id'   => $this->role_id,
            // Le profil pilote les droits (config/permissions.php) : roles.id reprend le code du profil
            'role'      => $this->role_id,
            'photo'     => $this->photo,
            'etat'      => $this->etat,
        ];
    }

    /**
     * Tableau pour mise à jour (mot de passe optionnel).
     */
    public function toArrayForUpdate(): array
    {
        $data = [
            'nom'     => $this->nom,
            'prenom'  => $this->prenom,
            'login'   => $this->login,
            'email'   => $this->email,
            'role_id' => $this->role_id,
            'role'    => $this->role_id,
            'photo'   => $this->photo,
            'etat'    => $this->etat,
        ];

        if (!empty($this->mot_passe)) {
            $data['mot_passe'] = bcrypt($this->mot_passe);
        }

        return $data;
    }
}