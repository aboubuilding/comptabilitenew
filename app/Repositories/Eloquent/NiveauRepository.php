<?php

namespace App\Repositories\Eloquent;

use App\Models\Niveau;
use App\Repositories\Interfaces\NiveauRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class NiveauRepository extends BaseRepository implements NiveauRepositoryInterface
{
    // Désactiver l'injection automatique de annee_id
    protected bool $autoInjectAnneId = false;

    public function __construct(Niveau $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les niveaux avec filtres
     */
    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query()->with('cycle');

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('libelle', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if (isset($filters['cycle_id']) && $filters['cycle_id'] !== '') {
                $query->where('cycle_id', (int)$filters['cycle_id']);
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('numero_ordre', 'asc')->get();
    }

    /**
     * Récupérer les niveaux actifs
     */
    public function getActiveNiveaux(): Collection
    {
        return $this->activeQuery()
            ->with('cycle')
            ->orderBy('numero_ordre', 'asc')
            ->get();
    }

    /**
     * Récupérer les niveaux par cycle
     */
    public function getByCycle(int $cycleId): Collection
    {
        return $this->activeQuery()
            ->where('cycle_id', $cycleId)
            ->orderBy('numero_ordre', 'asc')
            ->get();
    }

    /**
     * Récupérer les niveaux triés par ordre
     */
    public function getOrderedNiveaux(): Collection
    {
        return $this->activeQuery()
            ->with('cycle')
            ->orderBy('numero_ordre', 'asc')
            ->get();
    }

    /**
     * Trouver un niveau par son libellé
     */
    public function findByLibelle(string $libelle): ?Niveau
    {
        return $this->query()->where('libelle', $libelle)->first();
    }

    /**
     * Vérifier si un niveau peut être supprimé
     */
    public function canDelete(Niveau $niveau): bool
    {
        // Vérifier si le niveau est utilisé dans d'autres tables
        // Exemple: $niveau->classes()->exists()
        // À adapter selon votre structure
        return true;
    }

    /**
     * Supprimer un niveau avec vérification
     */
    public function deleteWithCheck(Niveau $niveau): bool
    {
        if (!$this->canDelete($niveau)) {
            Log::warning('Impossible de supprimer le niveau car il est utilisé', [
                'niveau_id' => $niveau->id
            ]);
            return false;
        }

        return $this->delete($niveau->id);
    }

    /**
     * Obtenir les statistiques des niveaux
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
     * Créer un niveau avec validation
     */
    public function createWithValidation(array $data): Niveau
    {
        return $this->create($data);
    }

    /**
     * Mettre à jour un niveau avec validation
     */
    public function updateWithValidation(Niveau $niveau, array $data): Niveau
    {
        $this->update($niveau->id, $data);
        return $niveau->fresh();
    }


}
