<?php

namespace App\Services;

use App\Repositories\Eloquent\AnneeRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AnneeService extends BaseService
{
    protected string $entityName = 'Année scolaire';

    protected array $defaultSelectFields = [
        'id', 'libelle', 'date_rentree', 'date_fin',
        'date_ouverture_inscription', 'date_fermeture_reinscription',
        'statut_annee', 'etat'
    ];

    public function __construct(AnneeRepository $repo)
    {
        parent::__construct($repo);
    }

    /**
     * 🔹 Récupère l'année en cours (active et statut_annee = 1 ou date courante dans intervalle)
     */
    public function getCurrentAnnee(): array
    {
        $now = Carbon::now();

        // Priorité : celle avec statut_annee = 1 (explicite)
        $current = $this->repo->activeQuery()
            ->where('statut_annee', 1)
            ->first();

        if (!$current) {
            // Fallback : année dont la date de rentrée <= now <= date_fin
            $current = $this->repo->activeQuery()
                ->where('date_rentree', '<=', $now)
                ->where('date_fin', '>=', $now)
                ->first();
        }

        if (!$current) {
            return $this->formatResponse(false, 'Aucune année scolaire active trouvée');
        }

        return $this->formatResponse(true, '', $current);
    }

    /**
     * 🔹 Liste simple pour les selects (id, libelle)
     * Surcharge la méthode du BaseService pour retourner exactement ce format.
     */
    public function getForSelect(array $filters = [], string $labelField = 'libelle', string $valueField = 'id'): array
    {
        $query = $this->repo->activeQuery()->select($valueField, $labelField);

        if (!empty($filters['statut_annee'])) {
            $query->where('statut_annee', $filters['statut_annee']);
        }
        if (!empty($filters['etat'])) {
            $query->where('etat', $filters['etat']);
        }

        $items = $query->orderBy($labelField)->get()
            ->map(fn($item) => ['value' => $item->$valueField, 'label' => $item->$labelField]);

        return $this->formatResponse(true, '', $items);
    }

    /**
     * 📦 Récupère toutes les années formatées pour l'affichage (utilisé par le contrôleur index)
     * C'est une surcharge de la méthode `all()` du parent, mais avec un formatage enrichi.
     */
    public function getAllFormatted(): Collection
    {
        $now = Carbon::now();

        return $this->repo->activeQuery()
            ->select($this->defaultSelectFields)
            ->orderByDesc('date_rentree')
            ->get()
            ->map(function ($annee) use ($now) {
                $rentree = optional($annee->date_rentree)->startOfDay();
                $fin     = optional($annee->date_fin)->startOfDay();

                $statusData = $this->determineStatus($annee->statut_annee, $rentree, $fin, $now);

                return [
                    'id'             => $annee->id,
                    'libelle'        => $annee->libelle,
                    'date_rentree'   => $rentree?->format('d/m/Y'),
                    'date_fin'       => $fin?->format('d/m/Y'),
                    'date_ouverture_inscription' => optional($annee->date_ouverture_inscription)->format('d/m/Y'),
                    'date_fermeture_reinscription' => optional($annee->date_fermeture_reinscription)->format('d/m/Y'),
                    'statut_label'   => $statusData['label'],
                    'is_active'      => $statusData['is_active'],
                    'duree_jours'    => $rentree && $fin ? $rentree->diffInDays($fin) : 0,
                    'etat'           => $annee->etat,
                ];
            });
    }

    /**
     * Vérifie si l'année est supprimable (pas de données liées)
     */
    public function hasRelatedData(int $id): bool
    {
        return \DB::table('inscriptions')->where('annee_id', $id)->exists()
            || \DB::table('frais')->where('annee_id', $id)->exists();
    }

    /**
     * Active une année (statut_annee = 1) et désactive toutes les autres
     */
    public function setAsCurrent(int $id): array
    {
        try {
            // Désactiver toutes les autres années
            $this->repo->activeQuery()->update(['statut_annee' => 0]);
            // Activer celle-ci
            $this->repo->update($id, ['statut_annee' => 1]);
            return $this->formatResponse(true, 'Année définie comme année en cours');
        } catch (ModelNotFoundException $e) {
            return $this->formatResponse(false, 'Année introuvable');
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Erreur lors de l’activation : ' . $e->getMessage());
        }
    }

    /**
     * Détermine le statut à partir de la DB et des dates
     */
    private function determineStatus(?int $statutDb, ?Carbon $rentree, ?Carbon $fin, Carbon $now): array
    {
        if ($statutDb !== null) {
            return match ($statutDb) {
                1 => ['label' => 'En cours', 'is_active' => true],
                2 => ['label' => 'Clôturée', 'is_active' => false],
                0 => ['label' => 'En préparation', 'is_active' => false],
                default => ['label' => 'Indéfini', 'is_active' => false],
            };
        }

        if (!$rentree || !$fin) {
            return ['label' => 'Incomplet', 'is_active' => false];
        }

        if ($now->between($rentree, $fin)) {
            return ['label' => 'En cours', 'is_active' => true];
        }
        if ($now->lt($rentree)) {
            return ['label' => 'À venir', 'is_active' => false];
        }
        return ['label' => 'Clôturée', 'is_active' => false];
    }
}
