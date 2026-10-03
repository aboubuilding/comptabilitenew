<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\DTO\EspaceDTO;
use App\Domain\Scolarite\Models\Eleve;
use App\Domain\Scolarite\Models\Espace;
use App\Domain\Scolarite\Repositories\EspaceRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Logique métier des espaces familiaux.
 */
class EspaceService
{
    public function __construct(
        private readonly EspaceRepository $repository,
        private readonly AnneeScolaireContext $anneeContext
    ) {}

    /**
     * Crée un espace familial.
     */
    public function creer(EspaceDTO $dto): Espace
    {
        return DB::transaction(function () use ($dto) {
            $data = $dto->toArray();
            if (!$data['annee_id']) {
                $data['annee_id'] = $this->anneeContext->id();
            }
            return $this->repository->create($data);
        });
    }

    public function mettreAJour(Espace $espace, EspaceDTO $dto): Espace
    {
        return DB::transaction(function () use ($espace, $dto) {
            $this->repository->update($espace, $dto->toArray());
            return $espace->fresh(['parents', 'eleves']);
        });
    }

    public function supprimer(Espace $espace): void
    {
        $this->repository->delete($espace);
    }

    /**
     * Regroupe une liste d'élèves dans un espace (existant ou nouveau).
     *
     * @return array{espace_id:int, eleves_affectes:int, deja_affectes:array}
     */
    public function regrouper(array $eleveIds, ?int $espaceIdExistant, ?string $nomFamille): array
    {
        if (empty($eleveIds)) {
            throw new RuntimeException("Aucun élève sélectionné.");
        }

        return DB::transaction(function () use ($eleveIds, $espaceIdExistant, $nomFamille) {
            // 1. Créer ou récupérer l'espace cible
            if ($espaceIdExistant) {
                $espace = Espace::actif()->find($espaceIdExistant);
                if (!$espace) {
                    throw new RuntimeException("Espace familial introuvable.");
                }
            } else {
                if (!$nomFamille) {
                    throw new RuntimeException("Le nom de la famille est obligatoire pour créer un nouvel espace.");
                }
                $espace = $this->repository->create([
                    'nom_famille' => $nomFamille,
                    'annee_id'    => $this->anneeContext->id(),
                ]);
            }

            // 2. Identifier les élèves déjà affectés à un autre espace
            $eleves = Eleve::whereIn('id', $eleveIds)->get(['id', 'nom', 'prenom', 'espace_id']);

            $dejaAffectes = [];
            $aAffecter = [];

            foreach ($eleves as $e) {
                if ($e->espace_id && (int) $e->espace_id !== (int) $espace->id) {
                    $dejaAffectes[] = [
                        'id'   => $e->id,
                        'nom'  => $e->nom . ' ' . $e->prenom,
                        'espace_actuel_id' => $e->espace_id,
                    ];
                } else {
                    $aAffecter[] = $e->id;
                }
            }

            // 3. Affecter les élèves éligibles
            $nbAffectes = 0;
            if (!empty($aAffecter)) {
                $nbAffectes = Eleve::whereIn('id', $aAffecter)
                    ->update(['espace_id' => $espace->id]);
            }

            return [
                'espace_id'       => $espace->id,
                'espace_nom'      => $espace->nom_famille,
                'eleves_affectes' => $nbAffectes,
                'deja_affectes'   => $dejaAffectes,
            ];
        });
    }
}