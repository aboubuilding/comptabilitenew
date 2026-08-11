<?php

namespace App\Repositories\Eloquent;

use App\Models\Cycle;
use App\Repositories\Interfaces\CycleRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class CycleRepository extends BaseRepository implements CycleRepositoryInterface
{
    // Désactiver l'injection automatique de annee_id
    protected bool $autoInjectAnneId = false;

    public function __construct(Cycle $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les cycles avec filtres
     */
    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query();

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where('libelle', 'LIKE', "%{$search}%");
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('libelle', 'asc')->get();
    }

    /**
     * Récupérer les cycles actifs
     */
    public function getActiveCycles(): Collection
    {
        return $this->activeQuery()
            ->orderBy('libelle', 'asc')
            ->get();
    }

    /**
     * Trouver un cycle par son libellé
     */
    public function findByLibelle(string $libelle): ?Cycle
    {
        return $this->query()->where('libelle', $libelle)->first();
    }

    /**
     * Vérifier si un cycle peut être supprimé
     */
    public function canDelete(Cycle $cycle): bool
    {
        // Vérifier si le cycle est utilisé dans d'autres tables
        // Exemple: $cycle->classes()->exists()
        // À adapter selon votre structure
        return true;
    }

    /**
     * Supprimer un cycle avec vérification
     */
    public function deleteWithCheck(Cycle $cycle): bool
    {
        if (!$this->canDelete($cycle)) {
            Log::warning('Impossible de supprimer le cycle car il est utilisé', [
                'cycle_id' => $cycle->id
            ]);
            return false;
        }

        return $this->delete($cycle->id);
    }

    /**
     * Obtenir les statistiques des cycles
     */
    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'actifs' => $this->activeQuery()->count(),
            'inactifs' => $this->query()->where('etat', self::SUPPRIME)->count(),
        ];
    }

    /**
     * Créer un cycle avec validation
     */
    public function createWithValidation(array $data): Cycle
    {
        return $this->create($data);
    }

    /**
     * Mettre à jour un cycle avec validation
     */
    public function updateWithValidation(Cycle $cycle, array $data): Cycle
    {
        $this->update($cycle->id, $data);
        return $cycle->fresh();
    }


}
