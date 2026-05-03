<?php

namespace App\Services;

use App\Repositories\Interfaces\NiveauRepositoryInterface;
use Illuminate\Support\Collection;

class NiveauService extends BaseService
{
    protected string $entityName = 'Niveau';
    protected array $defaultSelectFields = ['id', 'libelle', 'description', 'numero_ordre', 'cycle_id', 'etat'];

    public function __construct(NiveauRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Récupère tous les niveaux formatés pour l'affichage (avec nom du cycle associé)
     */
    public function getAllFormatted(): Collection
    {
        $niveaux = $this->repo->activeQuery()
            ->with('cycle:id,libelle') // suppose une relation belongsTo
            ->select($this->defaultSelectFields)
            ->orderBy('cycle_id')
            ->orderBy('numero_ordre')
            ->orderBy('libelle')
            ->get();

        return $niveaux->map(function ($niveau) {
            return [
                'id'            => $niveau->id,
                'libelle'       => $niveau->libelle,
                'description'   => $niveau->description,
                'numero_ordre'  => $niveau->numero_ordre,
                'cycle_id'      => $niveau->cycle_id,
                'cycle_libelle' => $niveau->cycle?->libelle,
                'etat'          => $niveau->etat,
                'etat_label'    => $niveau->etat ? 'Actif' : 'Inactif',
            ];
        });
    }

    /**
     * Récupère les niveaux d'un cycle donné, formatés pour select
     */
    public function getByCycle(int $cycleId): Collection
    {
        return $this->repo->activeQuery()
            ->where('cycle_id', $cycleId)
            ->where('etat', 1)
            ->orderBy('numero_ordre')
            ->orderBy('libelle')
            ->get($this->defaultSelectFields);
    }

    /**
     * Liste simplifiée pour les selects (dropdown) - filtre optionnel par cycle
     */
    public function getForSelect(?int $cycleId = null): Collection
    {
        $query = $this->repo->activeQuery()
            ->where('etat', 1)
            ->select('id', 'libelle', 'cycle_id')
            ->orderBy('numero_ordre')
            ->orderBy('libelle');

        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }

        return $query->get();
    }

    /**
     * Vérifie si un niveau a des données liées (classes, inscriptions, etc.)
     */
    public function hasRelatedData(int $id): bool
    {
        // À adapter selon vos tables
        return \DB::table('classes')->where('niveau_id', $id)->exists()
            || \DB::table('inscriptions')->where('niveau_id', $id)->exists();
    }
}