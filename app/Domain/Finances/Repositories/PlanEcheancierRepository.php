<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\PlanEcheancier;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlanEcheancierRepository extends BaseRepository
{
    public function __construct(PlanEcheancier $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->withCount(['lignes', 'fraisEcoles'])
            // Échéances (montants, dates butoirs) et frais liés, affichés dans la liste
            ->with([
                'lignes',
                'fraisEcoles' => fn ($q) => $q->where('etat', 1)->orderBy('libelle')
                    ->select(['id', 'libelle', 'montant', 'niveau_id', 'type_paiement', 'plan_echeancier_id']),
                'fraisEcoles.niveau:id,libelle',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            // La recherche porte sur le nom du plan et sur le libellé des frais liés
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('nom', 'like', "%{$v}%")
                ->orWhereHas('fraisEcoles', fn ($f) => $f->where('etat', 1)->where('libelle', 'like', "%{$v}%"))))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Plans d'une année scolaire, pour la liste de choix du formulaire des frais. */
    public function pourAnnee(int $anneeId)
    {
        return $this->activeQuery()->where('annee_id', $anneeId)->orderBy('nom')->get(['id', 'nom']);
    }

    public function findAvecLignes(int $id): PlanEcheancier
    {
        return $this->activeQuery()
            ->with(['lignes'])
            ->findOrFail($id);
    }
}