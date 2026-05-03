<?php

namespace App\Services;

use App\Repositories\Interfaces\ClasseRepositoryInterface;
use Illuminate\Support\Collection;

class ClasseService extends BaseService
{
    protected string $entityName = 'Classe';
    protected array $defaultSelectFields = [
        'id', 'libelle', 'emplacement', 'cycle_id', 'niveau_id', 'annee_id', 'etat'
    ];

    public function __construct(ClasseRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Récupère toutes les classes formatées pour l'affichage
     * (avec libellés du cycle, du niveau et de l'année)
     */
   public function getAllFormatted(?int $anneeId = null): Collection
{
    $query = $this->repo->activeQuery()
        ->with(['cycle:id,libelle', 'niveau:id,libelle', 'annee:id,libelle'])
        ->select($this->defaultSelectFields)
        ->orderBy('cycle_id')
        ->orderBy('niveau_id')
        ->orderBy('libelle');

    if ($anneeId) {
        $query->where('annee_id', $anneeId);
    }

    $classes = $query->get();

    return $classes->map(function ($classe) {
        return [
            'id'            => $classe->id,
            'libelle'       => $classe->libelle,
            'emplacement'   => $classe->emplacement,
            'cycle_id'      => $classe->cycle_id,
            'cycle_libelle' => $classe->cycle?->libelle,
            'niveau_id'     => $classe->niveau_id,
            'niveau_libelle'=> $classe->niveau?->libelle,
            'annee_id'      => $classe->annee_id,
            'annee_libelle' => $classe->annee?->libelle,
            'etat'          => $classe->etat,
            'etat_label'    => $classe->etat ? 'Actif' : 'Inactif',
        ];
    });
}

    /**
     * Récupère les classes d'une année donnée (utile pour les selects)
     */
    public function getByAnnee(int $anneeId): Collection
    {
        return $this->repo->activeQuery()
            ->where('annee_id', $anneeId)
            ->where('etat', 1)
            ->orderBy('libelle')
            ->get($this->defaultSelectFields);
    }

    /**
     * Récupère les classes d'un niveau et d'une année
     */
    public function getByNiveauAndAnnee(int $niveauId, int $anneeId): Collection
    {
        return $this->repo->activeQuery()
            ->where('niveau_id', $niveauId)
            ->where('annee_id', $anneeId)
            ->where('etat', 1)
            ->orderBy('libelle')
            ->get($this->defaultSelectFields);
    }

    /**
     * Liste simplifiée pour selects (dropdown) - filtres optionnels
     */
    public function getForSelect(?int $anneeId = null, ?int $cycleId = null, ?int $niveauId = null): Collection
    {
        $query = $this->repo->activeQuery()
            ->where('etat', 1)
            ->select('id', 'libelle', 'annee_id', 'niveau_id')
            ->orderBy('libelle');

        if ($anneeId) {
            $query->where('annee_id', $anneeId);
        }
        if ($cycleId) {
            $query->where('cycle_id', $cycleId);
        }
        if ($niveauId) {
            $query->where('niveau_id', $niveauId);
        }

        return $query->get();
    }

    /**
     * Vérifie si une classe a des données liées (élèves, emplois du temps, etc.)
     */
    public function hasRelatedData(int $id): bool
    {
        // À adapter selon vos tables
        return  \DB::table('inscriptions')->where('classe_id', $id)->exists();
    }
}