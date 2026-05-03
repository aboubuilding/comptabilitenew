<?php

namespace App\Services;

use App\Repositories\Interfaces\TrancheRepositoryInterface;
use Illuminate\Support\Collection;

class TrancheService extends BaseService
{
    protected string $entityName = 'Tranche de paiement';
    protected array $defaultSelectFields = [
        'id', 'libelle', 'date_butoire', 'frais_ecole_id', 'type_frais', 'taux', 'etat'
    ];

    public function __construct(TrancheRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Récupère toutes les tranches, formatées pour l'affichage (avec libellé du frais)
     * Optionnellement filtrées par année (via la relation du frais)
     */
    public function getAllFormatted(?int $anneeId = null): Collection
    {
        $query = $this->repo->activeQuery()
            ->with(['fraisEcole:id,libelle,annee_id,niveau_id'])
            ->select($this->defaultSelectFields)
            ->orderBy('date_butoire')
            ->orderBy('libelle');

        if ($anneeId) {
            $query->whereHas('fraisEcole', function ($q) use ($anneeId) {
                $q->where('annee_id', $anneeId);
            });
        }

        $tranches = $query->get();

        return $tranches->map(function ($tranche) {
            return [
                'id'               => $tranche->id,
                'libelle'          => $tranche->libelle,
                'date_butoire'     => $tranche->date_butoire?->format('d/m/Y'),
                'date_butoire_raw' => $tranche->date_butoire?->toDateString(),
                'frais_ecole_id'   => $tranche->frais_ecole_id,
                'frais_libelle'    => $tranche->fraisEcole?->libelle,
                'annee_id'         => $tranche->fraisEcole?->annee_id,
                'niveau_id'        => $tranche->fraisEcole?->niveau_id,
                'type_frais'       => $tranche->type_frais,
                'type_frais_label' => $this->getTypeFraisLabel($tranche->type_frais),
                'taux'             => $tranche->taux,
                'taux_formatted'   => $tranche->taux . '%',
                'etat'             => $tranche->etat,
                'etat_label'       => $tranche->etat ? 'Actif' : 'Inactif',
            ];
        });
    }

    /**
     * Récupère les tranches d'un frais scolaire donné
     */
    public function getByFraisEcole(int $fraisEcoleId): Collection
    {
        return $this->repo->activeQuery()
            ->where('frais_ecole_id', $fraisEcoleId)
            ->where('etat', 1)
            ->orderBy('date_butoire')
            ->get($this->defaultSelectFields);
    }

    /**
     * Récupère les tranches pour une année donnée (via le frais associé)
     */
    public function getByAnnee(int $anneeId): Collection
    {
        return $this->repo->activeQuery()
            ->whereHas('fraisEcole', function ($q) use ($anneeId) {
                $q->where('annee_id', $anneeId);
            })
            ->where('etat', 1)
            ->orderBy('date_butoire')
            ->get($this->defaultSelectFields);
    }

    /**
     * Liste simplifiée pour selects (dropdown)
     */
    public function getForSelect(?int $fraisEcoleId = null): Collection
    {
        $query = $this->repo->activeQuery()
            ->where('etat', 1)
            ->select('id', 'libelle', 'date_butoire', 'taux')
            ->orderBy('date_butoire');

        if ($fraisEcoleId) {
            $query->where('frais_ecole_id', $fraisEcoleId);
        }

        return $query->get();
    }

    /**
     * Vérifie si une tranche a des données liées (factures, échéanciers, etc.)
     */
    public function hasRelatedData(int $id): bool
    {
        // À adapter selon vos tables
        return \DB::table('echeances')->where('tranche_id', $id)->exists()
            || \DB::table('factures_lignes')->where('tranche_id', $id)->exists();
    }

    /**
     * Helper pour le label du type de frais
     */
    protected function getTypeFraisLabel(?int $type): string
    {
        return match ($type) {
            1 => 'Inscription',
            2 => 'Scolarité',
            3 => 'Cantine',
            default => 'Autre',
        };
    }
}