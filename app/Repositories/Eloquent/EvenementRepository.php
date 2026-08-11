<?php

namespace App\Repositories\Eloquent;

use App\Models\Evenement;
use App\Repositories\Interfaces\EvenementRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

class EvenementRepository extends BaseRepository implements EvenementRepositoryInterface
{
    protected bool $autoInjectAnneId = false;

    public function __construct(Evenement $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer tous les événements avec filtres
     */
    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query()->with('annee');

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('nom', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            if (isset($filters['type']) && $filters['type'] !== '') {
                $query->where('type', $filters['type']);
            }

            if (isset($filters['statut']) && $filters['statut'] !== '') {
                if ($filters['statut'] === 'upcoming') {
                    $query->upcoming();
                } elseif ($filters['statut'] === 'past') {
                    $query->past();
                }
            }

            if (isset($filters['annee_id']) && $filters['annee_id'] !== '') {
                $query->where('annee_id', (int)$filters['annee_id']);
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('date_evenement', 'asc')->get();
    }

    /**
     * Récupérer les événements actifs
     */
    public function getActiveEvenements(): Collection
    {
        return $this->activeQuery()
            ->with('annee')
            ->orderBy('date_evenement', 'asc')
            ->get();
    }

    /**
     * Récupérer les événements par type
     */
    public function getByType(string $type): Collection
    {
        return $this->activeQuery()
            ->where('type', $type)
            ->with('annee')
            ->orderBy('date_evenement', 'asc')
            ->get();
    }

    /**
     * Récupérer les événements à venir
     */
    public function getUpcoming(): Collection
    {
        return $this->activeQuery()
            ->upcoming()
            ->with('annee')
            ->orderBy('date_evenement', 'asc')
            ->get();
    }

    /**
     * Récupérer les événements passés
     */
    public function getPast(): Collection
    {
        return $this->activeQuery()
            ->past()
            ->with('annee')
            ->orderBy('date_evenement', 'desc')
            ->get();
    }

    /**
     * Récupérer les événements par année
     */
    public function getByAnnee(int $anneeId): Collection
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->with('annee')
            ->orderBy('date_evenement', 'asc')
            ->get();
    }

    /**
     * Trouver un événement par son nom
     */
    public function findByNom(string $nom): ?Evenement
    {
        return $this->query()->where('nom', $nom)->first();
    }

    /**
     * Vérifier si un événement peut être supprimé
     */
    public function canDelete(Evenement $evenement): bool
    {
        // Vérifier si l'événement a des participants
        // À adapter selon votre structure
        return true;
    }

    /**
     * Supprimer un événement avec vérification
     */
    public function deleteWithCheck(Evenement $evenement): bool
    {
        if (!$this->canDelete($evenement)) {
            Log::warning('Impossible de supprimer l\'événement car il a des participants', [
                'evenement_id' => $evenement->id
            ]);
            return false;
        }

        return $this->delete($evenement->id);
    }

    /**
     * Obtenir les statistiques des événements
     */
    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'actifs' => $this->activeQuery()->count(),
            'inactifs' => $this->query()->where('etat', self::SUPPRIME)->count(),
            'upcoming' => $this->query()->upcoming()->count(),
            'past' => $this->query()->past()->count(),
            'excursions' => $this->query()->where('type', Evenement::TYPE_EXCURSION)->count(),
            'voyages' => $this->query()->where('type', Evenement::TYPE_VOYAGE)->count(),
            'sorties' => $this->query()->where('type', Evenement::TYPE_SORTIE_PEDAGOGIQUE)->count(),
            'competitions' => $this->query()->where('type', Evenement::TYPE_COMPETITION)->count(),
        ];
    }

    /**
     * Créer un événement avec validation
     */
    public function createWithValidation(array $data): Evenement
    {
        return $this->create($data);
    }

    /**
     * Mettre à jour un événement avec validation
     */
    public function updateWithValidation(Evenement $evenement, array $data): Evenement
    {
        $this->update($evenement->id, $data);
        return $evenement->fresh();
    }


}
