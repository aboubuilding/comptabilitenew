<?php

namespace App\Repositories\Eloquent;

use App\Models\FraisEcole;
use App\Repositories\Interfaces\FraisEcoleRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class FraisEcoleRepository extends BaseRepository implements FraisEcoleRepositoryInterface
{
    protected bool $autoInjectAnneId = false;

    public function __construct(FraisEcole $model)
    {
        parent::__construct($model);
    }

    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query()->with(['niveau', 'annee', 'planEcheancier']);

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where('libelle', 'LIKE', "%{$search}%");
            }

            if (isset($filters['type_paiement']) && $filters['type_paiement'] !== '') {
                $query->where('type_paiement', (int)$filters['type_paiement']);
            }

            if (isset($filters['niveau_id']) && $filters['niveau_id'] !== '') {
                $query->where('niveau_id', (int)$filters['niveau_id']);
            }

            if (isset($filters['annee_id']) && $filters['annee_id'] !== '') {
                $query->where('annee_id', (int)$filters['annee_id']);
            }

            if (isset($filters['has_echeancier']) && $filters['has_echeancier'] !== '') {
                if ($filters['has_echeancier'] === '1') {
                    $query->whereNotNull('plan_echeancier_id');
                } else {
                    $query->whereNull('plan_echeancier_id');
                }
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('libelle')->get();
    }

    public function getActiveFrais(): Collection
    {
        return $this->activeQuery()
            ->with(['niveau', 'annee', 'planEcheancier'])
            ->orderBy('libelle')
            ->get();
    }

    public function getByNiveau(int $niveauId): Collection
    {
        return $this->activeQuery()
            ->where('niveau_id', $niveauId)
            ->with(['annee', 'planEcheancier'])
            ->orderBy('libelle')
            ->get();
    }

    public function getByAnnee(int $anneeId): Collection
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->with(['niveau', 'planEcheancier'])
            ->orderBy('libelle')
            ->get();
    }

    public function getByType(int $type): Collection
    {
        return $this->activeQuery()
            ->where('type_paiement', $type)
            ->with(['niveau', 'annee', 'planEcheancier'])
            ->orderBy('libelle')
            ->get();
    }

    public function getAvecEcheancier(): Collection
    {
        return $this->activeQuery()
            ->whereNotNull('plan_echeancier_id')
            ->with(['niveau', 'annee', 'planEcheancier.lignes'])
            ->orderBy('libelle')
            ->get();
    }

    public function getSansEcheancier(): Collection
    {
        return $this->activeQuery()
            ->whereNull('plan_echeancier_id')
            ->with(['niveau', 'annee'])
            ->orderBy('libelle')
            ->get();
    }

    public function findByLibelle(string $libelle): ?FraisEcole
    {
        return $this->query()->where('libelle', $libelle)->first();
    }

    public function canDelete(FraisEcole $frais): bool
    {
        // Vérifier si le frais est utilisé dans d'autres tables
        return true;
    }

    public function deleteWithCheck(FraisEcole $frais): bool
    {
        if (!$this->canDelete($frais)) {
            Log::warning('Impossible de supprimer le frais car il est utilisé', [
                'frais_id' => $frais->id
            ]);
            return false;
        }

        return $this->delete($frais->id);
    }

    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'actifs' => $this->activeQuery()->count(),
            'inactifs' => $this->query()->where('etat', self::SUPPRIME)->count(),
            'avec_echeancier' => $this->query()->whereNotNull('plan_echeancier_id')->count(),
            'sans_echeancier' => $this->query()->whereNull('plan_echeancier_id')->count(),
        ];
    }

    public function createWithValidation(array $data): FraisEcole
    {
        return $this->create($data);
    }

    public function updateWithValidation(FraisEcole $frais, array $data): FraisEcole
    {
        $this->update($frais->id, $data);
        return $frais->fresh();
    }


}
