<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InscriptionRepository extends BaseRepository
{
    public function __construct(Inscription $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $anneeId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->with(['eleve', 'cycle', 'niveau', 'classe'])
            ->when($filters['cycle_id'] ?? null, fn ($q, $v) => $q->where('cycle_id', $v))
            ->when($filters['niveau_id'] ?? null, fn ($q, $v) => $q->where('niveau_id', $v))
            ->when($filters['classe_id'] ?? null, fn ($q, $v) => $q->where('classe_id', $v))
            ->when(($filters['statut'] ?? '') !== '', function ($q) use ($filters) {
                if ($filters['statut'] === 'abandonnee') {
                    $q->where('statut_abandon', 1);
                    return;
                }

                $map = ['en_attente' => 1, 'validee' => 2, 'rejetee' => 3];
                $q->where('statut_abandon', 0)
                    ->where('statut_validation', $map[$filters['statut']] ?? 0);
            })
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->whereHas('eleve', function ($q) use ($s) {
                    $q->where('nom', 'like', "%{$s}%")
                        ->orWhere('prenom', 'like', "%{$s}%")
                        ->orWhere('matricule', 'like', "%{$s}%");
                });
            })
            ->latest('date_inscription')
            ->paginate($perPage);
    }

    /**
     * Inscriptions de l'année sur lesquelles un engagement peut être créé
     * (ni rejetées, ni abandonnées), pour alimenter un sélecteur d'élèves.
     */
    public function listePourSelect(int $anneeId): Collection
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->where('statut_abandon', 0)
            ->where('statut_validation', '!=', StatutValidationInscription::REJETEE->value)
            ->whereHas('eleve')
            ->with(['eleve:id,nom,prenom,matricule', 'classe:id,libelle', 'niveau:id,libelle'])
            ->get(['id', 'eleve_id', 'classe_id', 'niveau_id'])
            ->sortBy(fn (Inscription $i) => mb_strtolower($i->eleve->nom . ' ' . $i->eleve->prenom))
            ->values();
    }
}