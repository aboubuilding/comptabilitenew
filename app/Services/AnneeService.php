<?php

namespace App\Services;

use App\Repositories\Eloquent\AnneeRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * 🔹 Récupère l'année en cours
     */
    public function getAnneeEnCours(): ?object
    {
        return $this->repo->activeQuery()
            ->where('date_rentree', '<=', now())
            ->where('date_fin', '>=', now())
            ->where('statut_annee', 1)
            ->first($this->defaultSelectFields);
    }

    /**
     * 🔹 Liste simple pour les selects
     */
    public function getForSelect(): Collection
    {
        return $this->repo->activeQuery()
            ->select('id', 'libelle')
            ->orderByDesc('date_rentree')
            ->get();
    }

    /**
     * 📦 Récupère toutes les années formatées pour l'affichage (utilisé par le contrôleur index)
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
        // À adapter selon vos tables réelles
        return \DB::table('inscriptions')->where('annee_id', $id)->exists()
            || \DB::table('frais')->where('annee_id', $id)->exists();
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