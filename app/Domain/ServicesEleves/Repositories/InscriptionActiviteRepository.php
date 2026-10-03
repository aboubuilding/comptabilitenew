<?php

namespace App\Domain\ServicesEleves\Repositories;

use App\Domain\ServicesEleves\Models\InscriptionActivite;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InscriptionActiviteRepository extends BaseRepository
{
    public function __construct(InscriptionActivite $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'eleve:id,nom,prenom,matricule',
                'activite:id,libelle,type,montant',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['activite_id'] ?? null, fn ($q, $v) => $q->where('activite_id', $v))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->whereHas('eleve', function ($sub) use ($v) {
                    $sub->where('nom', 'like', "%{$v}%")
                        ->orWhere('prenom', 'like', "%{$v}%")
                        ->orWhere('matricule', 'like', "%{$v}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Compteurs de l'écran des inscriptions. Le montant dû ignore les
     * inscriptions annulées : un abandon éteint l'engagement.
     *
     * @return array{total:int, en_attente:int, confirmees:int, montant_du:float}
     */
    public function compterStatistiques(?int $anneeId = null): array
    {
        $q = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v));

        return [
            'total'      => (clone $q)->count(),
            'en_attente' => (clone $q)->where('statut', StatutInscriptionActivite::EN_ATTENTE->value)->count(),
            'confirmees' => (clone $q)->where('statut', StatutInscriptionActivite::CONFIRMEE->value)->count(),
            'montant_du' => round((float) (clone $q)
                ->where('statut', '!=', StatutInscriptionActivite::ANNULEE->value)
                ->sum('montant_du'), 2),
        ];
    }
}