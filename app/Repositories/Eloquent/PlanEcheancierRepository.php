<?php

namespace App\Repositories\Eloquent;

use App\Models\PlanEcheancier;
use App\Repositories\Interfaces\PlanEcheancierRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class PlanEcheancierRepository extends BaseRepository implements PlanEcheancierRepositoryInterface
{
    protected bool $autoInjectAnneId = false;

    public function __construct(PlanEcheancier $model)
    {
        parent::__construct($model);
    }

    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query()->with(['annee', 'lignes']);

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where('nom', 'LIKE', "%{$search}%");
            }

            if (isset($filters['annee_id']) && $filters['annee_id'] !== '') {
                $query->where('annee_id', (int)$filters['annee_id']);
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('nom')->get();
    }

    public function getActivePlans(): Collection
    {
        return $this->activeQuery()
            ->with(['annee', 'lignes'])
            ->orderBy('nom')
            ->get();
    }

    public function getByAnnee(int $anneeId): Collection
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->with('lignes')
            ->orderBy('nom')
            ->get();
    }

    public function findWithLignes(int $id): ?PlanEcheancier
    {
        return $this->query()
            ->with(['annee', 'lignes' => function($query) {
                $query->orderBy('ordre');
            }])
            ->find($id);
    }

    public function findByNom(string $nom): ?PlanEcheancier
    {
        return $this->query()->where('nom', $nom)->first();
    }

    public function canDelete(PlanEcheancier $plan): bool
    {
        // Vérifier si le plan est utilisé par des frais
        if ($plan->fraisEcoles()->exists()) {
            return false;
        }
        return true;
    }

    public function deleteWithCheck(PlanEcheancier $plan): bool
    {
        if (!$this->canDelete($plan)) {
            Log::warning('Impossible de supprimer le plan car il est utilisé par des frais', [
                'plan_id' => $plan->id
            ]);
            return false;
        }

        return $this->delete($plan->id);
    }

    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'actifs' => $this->activeQuery()->count(),
            'inactifs' => $this->query()->where('etat', self::SUPPRIME)->count(),
        ];
    }

    public function createWithValidation(array $data): PlanEcheancier
    {
        return $this->create($data);
    }

    public function updateWithValidation(PlanEcheancier $plan, array $data): PlanEcheancier
    {
        $this->update($plan->id, $data);
        return $plan->fresh();
    }


}
