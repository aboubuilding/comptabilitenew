<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\ParentEleve;
use App\Repositories\BaseRepository;

class ParentEleveRepository extends BaseRepository
{
    public function __construct(ParentEleve $model)
    {
        parent::__construct($model);
    }

    /**
     * Parents d'un espace, triés par principal d'abord.
     */
    public function parEspace(int $espaceId)
    {
        return $this->activeQuery()
            ->where('espace_id', $espaceId)
            ->orderByDesc('is_principal')
            ->orderBy('nom_parent')
            ->get();
    }

    /**
     * Désigne un parent comme principal et retire le flag aux autres
     * du même espace (unicité).
     */
    public function designerPrincipal(int $espaceId, int $parentId): void
    {
        \DB::transaction(function () use ($espaceId, $parentId) {
            $this->activeQuery()
                ->where('espace_id', $espaceId)
                ->where('id', '!=', $parentId)
                ->update(['is_principal' => 0]);

            $this->activeQuery()
                ->where('id', $parentId)
                ->where('espace_id', $espaceId)
                ->update(['is_principal' => 1]);
        });
    }

    /**
     * Parent principal d'un espace.
     */
    public function principal(int $espaceId): ?ParentEleve
    {
        return $this->activeQuery()
            ->where('espace_id', $espaceId)
            ->where('is_principal', 1)
            ->first();
    }
}