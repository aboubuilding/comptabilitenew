<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\DTO\CompteDTO;
use App\Domain\Scolarite\Models\Compte;
use App\Domain\Scolarite\Models\ParentEleve;
use App\Domain\Scolarite\Repositories\CompteRepository;
use App\Mail\ComptePortailCreeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logique métier des comptes portail parent.
 */
class CompteService
{
    public function __construct(
        private readonly CompteRepository $repository
    ) {}

    /**
     * Crée un compte portail : mot de passe auto + envoi email.
     *
     * @return array{compte:Compte, mot_passe_clair:string}
     */
    public function creer(CompteDTO $dto): array
    {
        // Vérifier unicité email (actif)
        if ($this->repository->parEmail($dto->email)) {
            throw new RuntimeException("Un compte avec cet email existe déjà.");
        }

        $parent = ParentEleve::actif()->find($dto->parent_id);
        if (!$parent) {
            throw new RuntimeException("Parent introuvable ou inactif.");
        }

        if ((int) $parent->espace_id !== $dto->espace_id) {
            throw new RuntimeException("Le parent sélectionné n'appartient pas à cet espace.");
        }

        return DB::transaction(function () use ($dto, $parent) {
            // Génération du mot de passe clair (12 caractères)
            $motDePasseClair = Str::random(12);

            $compte = $this->repository->create([
                'email'         => $dto->email,
                'mot_passe'     => Hash::make($motDePasseClair),
                'statut_compte' => $dto->statut_compte,
                'espace_id'     => $dto->espace_id,
                'parent_id'     => $dto->parent_id,
            ]);

            // Envoi email avec identifiants
            Mail::to($dto->email)->send(new ComptePortailCreeMail(
                parentNom:     $parent->nom_complet,
                email:         $dto->email,
                motDePasse:    $motDePasseClair,
                nomFamille:    $parent->espace->nom_famille ?? '—',
            ));

            return [
                'compte'          => $compte,
                'mot_passe_clair' => $motDePasseClair,
            ];
        });
    }

    /**
     * Met à jour (email, statut, parent) — sans toucher au mot de passe.
     */
    public function mettreAJour(Compte $compte, CompteDTO $dto): Compte
    {
        // Vérifier unicité email si changé
        if ($compte->email !== $dto->email) {
            $existant = $this->repository->parEmail($dto->email);
            if ($existant && $existant->id !== $compte->id) {
                throw new RuntimeException("Un autre compte utilise déjà cet email.");
            }
        }

        $parent = ParentEleve::actif()->find($dto->parent_id);
        if (!$parent || (int) $parent->espace_id !== $dto->espace_id) {
            throw new RuntimeException("Le parent sélectionné n'appartient pas à cet espace.");
        }

        return DB::transaction(function () use ($compte, $dto) {
            $this->repository->update($compte, $dto->toArray());
            return $compte->fresh(['parent', 'espace']);
        });
    }

    /**
     * Régénère le mot de passe et renvoie le nouveau en clair.
     */
    public function regenererMotDePasse(Compte $compte): string
    {
        $motDePasseClair = Str::random(12);

        $this->repository->update($compte, [
            'mot_passe' => Hash::make($motDePasseClair),
        ]);

        // Envoi email
        if ($compte->parent) {
            Mail::to($compte->email)->send(new ComptePortailCreeMail(
                parentNom:  $compte->parent->nom_complet,
                email:      $compte->email,
                motDePasse: $motDePasseClair,
                nomFamille: $compte->espace->nom_famille ?? '—',
            ));
        }

        return $motDePasseClair;
    }

    public function supprimer(Compte $compte): void
    {
        $this->repository->delete($compte);
    }
}