<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\DTO\ParentDTO;
use App\Domain\Scolarite\Models\ParentEleve;
use App\Domain\Scolarite\Repositories\ParentEleveRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ParentEleveService
{
    public function __construct(
        private readonly ParentEleveRepository $repository
    ) {}

    public function creer(ParentDTO $dto): ParentEleve
    {
        if (!$dto->espace_id) {
            throw new RuntimeException("L'espace familial est obligatoire.");
        }

        return DB::transaction(function () use ($dto) {
            // Si c'est le premier parent de l'espace OU si is_principal=1,
            // on retire le flag aux autres
            if ($dto->is_principal) {
                $this->repository->activeQuery()
                    ->where('espace_id', $dto->espace_id)
                    ->update(['is_principal' => 0]);
            } else {
                // Si c'est le premier parent de l'espace, il devient principal d'office
                $existeDeja = $this->repository->activeQuery()
                    ->where('espace_id', $dto->espace_id)
                    ->exists();

                if (!$existeDeja) {
                    $data = $dto->toArray();
                    $data['is_principal'] = 1;
                    return $this->repository->create($data);
                }
            }

            return $this->repository->create($dto->toArray());
        });
    }

    public function mettreAJour(ParentEleve $parent, ParentDTO $dto): ParentEleve
    {
        return DB::transaction(function () use ($parent, $dto) {
            if ($dto->is_principal) {
                $this->repository->activeQuery()
                    ->where('espace_id', $parent->espace_id)
                    ->where('id', '!=', $parent->id)
                    ->update(['is_principal' => 0]);
            }

            $this->repository->update($parent, $dto->toArray());
            return $parent->fresh();
        });
    }

    public function designerPrincipal(ParentEleve $parent): void
    {
        if (!$parent->espace_id) {
            throw new RuntimeException("Ce parent n'est rattaché à aucun espace.");
        }

        $this->repository->designerPrincipal((int) $parent->espace_id, $parent->id);
    }

    public function supprimer(ParentEleve $parent): void
    {
        $this->repository->delete($parent);
    }
}