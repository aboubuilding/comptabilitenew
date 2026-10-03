<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Souscription;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SouscriptionRepository extends BaseRepository
{
    public function __construct(Souscription $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'fraisEcole:id,libelle,montant',
                'inscription:id,eleve_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'auteur:id,name',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['frais_ecole_id'] ?? null, fn ($q, $v) => $q->where('frais_ecole_id', $v))
            ->when($filters['inscription_id'] ?? null, fn ($q, $v) => $q->where('inscription_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->whereHas('inscription.eleve', function ($sub) use ($v) {
                    $sub->where('nom', 'like', "%{$v}%")
                        ->orWhere('prenom', 'like', "%{$v}%")
                        ->orWhere('matricule', 'like', "%{$v}%");
                });
            })
            ->orderByDesc('date_souscription')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Somme totale des souscriptions d'une inscription (pour info). */
    public function totalPourInscription(int $inscriptionId): float
    {
        return round(
            (float) $this->activeQuery()
                ->where('inscription_id', $inscriptionId)
                ->sum('montant_annuel_prevu'),
            2
        );
    }
}