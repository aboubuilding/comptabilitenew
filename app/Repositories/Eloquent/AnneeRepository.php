<?php
// app/Repositories/Eloquent/AnneeRepository.php

namespace App\Repositories\Eloquent;

use App\Models\Annee;
use App\Repositories\Interfaces\AnneeRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model; // Ajouter cette importation

class AnneeRepository extends BaseRepository implements AnneeRepositoryInterface
{
    // Désactiver l'injection automatique de annee_id
    protected bool $autoInjectAnneId = false;

    public function __construct(Annee $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupérer toutes les années avec filtres
     */
    public function getAllWithFilters(?array $filters = null): Collection
    {
        $query = $this->query();

        if (!empty($filters)) {
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('libelle', 'LIKE', "%{$search}%")
                        ->orWhere('code', 'LIKE', "%{$search}%");
                });
            }

            if (isset($filters['statut_annee']) && $filters['statut_annee'] !== '') {
                $query->where('statut_annee', (int)$filters['statut_annee']);
            }

            if (isset($filters['etat']) && $filters['etat'] !== '') {
                $query->where('etat', (int)$filters['etat']);
            }
        }

        return $query->orderBy('date_rentree', 'desc')->get();
    }

    /**
     * Récupérer les années actives
     */
    public function getActiveYears(): Collection
    {
        return $this->activeQuery()
            ->orderBy('date_rentree', 'desc')
            ->get();
    }

    /**
     * Récupérer les années par statut
     */
    public function getByStatus(int $statut): Collection
    {
        return $this->activeQuery()
            ->where('statut_annee', $statut)
            ->orderBy('date_rentree', 'desc')
            ->get();
    }

    /**
     * Récupérer l'année en cours (statut OUVERT)
     */
    public function getCurrentYear(): ?Annee
    {
        return $this->activeQuery()
            ->where('statut_annee', Annee::STATUT_OUVERT)
            ->first();
    }

    /**
     * Récupérer l'année par défaut
     */
    public function getDefaultYear(): ?Annee
    {
        return $this->activeQuery()
            ->orderBy('date_rentree', 'desc')
            ->first();
    }

    /**
     * Définir une année comme ouverte
     */
    public function setAsOpen(Annee $annee): Annee
    {
        DB::transaction(function () use ($annee) {
            // Fermer toutes les autres années ouvertes
            $this->activeQuery()
                ->where('id', '!=', $annee->id)
                ->where('statut_annee', Annee::STATUT_OUVERT)
                ->update(['statut_annee' => Annee::STATUT_CLOTURE]);

            // Ouvrir l'année sélectionnée
            $annee->statut_annee = Annee::STATUT_OUVERT;
            $annee->etat = self::ACTIF;
            $annee->save();
        });

        Log::info('Année définie comme ouverte', ['annee_id' => $annee->id]);

        return $annee->fresh();
    }

    /**
     * Changer le statut d'une année
     */
    public function changeStatus(Annee $annee, int $status): Annee
    {
        $annee->statut_annee = $status;
        $annee->save();

        Log::info('Statut de l\'année modifié', [
            'annee_id' => $annee->id,
            'nouveau_statut' => $status
        ]);

        return $annee->fresh();
    }

    /**
     * Vérifier si une année peut être supprimée
     */
    public function canDelete(Annee $annee): bool
    {
        // Vérifier si l'année est utilisée dans d'autres tables
        // À adapter selon votre structure
        return true;
    }

    /**
     * Supprimer une année avec vérification
     */
    public function deleteWithCheck(Annee $annee): bool
    {
        if (!$this->canDelete($annee)) {
            Log::warning('Impossible de supprimer l\'année car elle est utilisée', [
                'annee_id' => $annee->id
            ]);
            return false;
        }

        return $this->delete($annee->id);
    }

    /**
     * Obtenir les statistiques des années
     */
    public function getStats(): array
    {
        return [
            'total' => $this->query()->count(),
            'actives' => $this->activeQuery()->count(),
            'inactives' => $this->query()->where('etat', self::SUPPRIME)->count(),
            'ouvertes' => $this->getByStatus(Annee::STATUT_OUVERT)->count(),
            'cloturees' => $this->getByStatus(Annee::STATUT_CLOTURE)->count(),
            'non_ouvertes' => $this->getByStatus(Annee::STATUT_NON_OUVERT)->count(),
        ];
    }

    /**
     * Créer une année avec validation
     */
    public function createWithValidation(array $data): Annee
    {
        if (isset($data['date_ouverture_inscription']) && isset($data['date_fermeture_reinscription'])) {
            if ($data['date_fermeture_reinscription'] < $data['date_ouverture_inscription']) {
                throw new \InvalidArgumentException('La date de fermeture doit être après la date d\'ouverture.');
            }
        }

        return $this->create($data);
    }

    /**
     * Mettre à jour une année avec validation
     */
    public function updateWithValidation(Annee $annee, array $data): Annee
    {
        if (isset($data['date_ouverture_inscription']) && isset($data['date_fermeture_reinscription'])) {
            if ($data['date_fermeture_reinscription'] < $data['date_ouverture_inscription']) {
                throw new \InvalidArgumentException('La date de fermeture doit être après la date d\'ouverture.');
            }
        }

        $this->update($annee->id, $data);
        return $annee->fresh();
    }


}
