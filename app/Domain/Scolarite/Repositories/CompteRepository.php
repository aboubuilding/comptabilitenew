<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Compte;
use App\Repositories\BaseRepository;

class CompteRepository extends BaseRepository
{
    public function __construct(Compte $model)
    {
        parent::__construct($model);
    }

    public function parEspace(int $espaceId)
    {
        return $this->activeQuery()
            ->where('espace_id', $espaceId)
            ->get();
    }

    public function parEmail(string $email): ?Compte
    {
        return $this->activeQuery()->where('email', $email)->first();
    }

    public function compteActifEspace(int $espaceId): ?Compte
    {
        return $this->activeQuery()
            ->where('espace_id', $espaceId)
            ->where('etat', 1)
            ->first();
    }
}