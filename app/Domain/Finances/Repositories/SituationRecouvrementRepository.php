<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\SituationRecouvrement;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;

class SituationRecouvrementRepository extends BaseRepository
{
    public function __construct(SituationRecouvrement $model)
    {
        parent::__construct($model);
    }

    /** Dernière photo pour une inscription donnée (toutes natures). */
    public function dernieresParInscription(int $inscriptionId): Collection
    {
        $date = $this->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->max('date_calcul');

        if (! $date) return collect();

        return $this->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->where('date_calcul', $date)
            ->get();
    }

    /** Situation courante (dernière photo) pour une inscription + nature. */
    public function situationCourante(int $inscriptionId, string $nature): ?SituationRecouvrement
    {
        return $this->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->where('nature', $nature)
            ->orderByDesc('date_calcul')
            ->first();
    }
}