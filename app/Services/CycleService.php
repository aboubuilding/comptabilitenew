<?php

namespace App\Services;

use App\Repositories\Interfaces\CycleRepositoryInterface;
use Illuminate\Support\Collection;

class CycleService extends BaseService
{
    protected string $entityName = 'Cycle';
    protected array $defaultSelectFields = ['id', 'libelle', 'etat'];

    public function __construct(CycleRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Récupère tous les cycles formatés pour l'affichage
     */
    public function getAllFormatted(): Collection
    {
        $cycles = $this->repo->activeQuery()
            ->select($this->defaultSelectFields)
            ->orderBy('libelle')
            ->get();

        return $cycles->map(function ($cycle) {
            return [
                'id'         => $cycle->id,
                'libelle'    => $cycle->libelle,
                'etat'       => $cycle->etat,
                'etat_label' => $cycle->etat ? 'Actif' : 'Inactif',
            ];
        });
    }

    /**
     * Liste simplifiée pour les selects (dropdown)
     */
    public function getForSelect(): Collection
    {
        return $this->repo->activeQuery()
            ->where('etat', 1)
            ->select('id', 'libelle')
            ->orderBy('libelle')
            ->get();
    }

    /**
     * Vérifie si un cycle a des données liées (niveaux, classes, etc.)
     */
    public function hasRelatedData(int $id): bool
    {
        // À adapter selon vos tables (ex: niveaux, classes, matières)
        return \DB::table('niveaux')->where('cycle_id', $id)->exists()
            || \DB::table('classes')->where('cycle_id', $id)->exists();
    }
}