<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Mouvement;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class MouvementRepository extends BaseRepository
{
    public function __construct(Mouvement $model)
    {
        parent::__construct($model);
    }

    /** Journal ordonné d'une session de caisse. */
    public function journalDeCaisse(int $caisseId): Collection
    {
        return $this->activeQuery()
            ->with('utilisateur:id,nom,prenom')
            ->where('caisse_id', $caisseId)
            ->orderBy('date_mouvement')
            ->orderBy('id')
            ->get();
    }
}