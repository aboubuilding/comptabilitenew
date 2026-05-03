<?php

namespace App\Services;

use App\Repositories\Interfaces\FraisEcoleRepositoryInterface;
use Illuminate\Support\Collection;

class FraisEcoleService extends BaseService
{
    protected string $entityName = 'Frais scolaire';
    protected array $defaultSelectFields = [
        'id', 'libelle', 'montant', 'type_paiement', 'type_forfait',
        'niveau_id', 'annee_id', 'etat'
    ];

    public function __construct(FraisEcoleRepositoryInterface $repo)
    {
        parent::__construct($repo);
    }

    /**
     * Récupère tous les frais, formatés pour l'affichage (avec libellé du niveau)
     */
    public function getAllFormatted(?int $anneeId = null): Collection
    {
        $query = $this->repo->activeQuery()
            ->with('niveau:id,libelle')
            ->select($this->defaultSelectFields)
            ->orderBy('niveau_id')
            ->orderBy('libelle');

        if ($anneeId) {
            $query->where('annee_id', $anneeId);
        }

        $frais = $query->get();

        return $frais->map(function ($frais) {
            return [
                'id'               => $frais->id,
                'libelle'          => $frais->libelle,
                'montant'          => number_format($frais->montant, 0, ',', ' '),
                'montant_raw'      => $frais->montant,
                'type_paiement'    => $frais->type_paiement,
                'type_paiement_label' => $this->getTypePaiementLabel($frais->type_paiement),
                'type_forfait'     => $frais->type_forfait,
                'type_forfait_label'  => $this->getTypeForfaitLabel($frais->type_forfait),
                'niveau_id'        => $frais->niveau_id,
                'niveau_libelle'   => $frais->niveau?->libelle,
                'annee_id'         => $frais->annee_id,
                'etat'             => $frais->etat,
                'etat_label'       => $frais->etat ? 'Actif' : 'Inactif',
            ];
        });
    }

    /**
     * Récupère les frais d'une année donnée (pour selects)
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
     * Récupère les frais d'un niveau et d'une année
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
     * Liste simplifiée pour selects (dropdown)
     */
    public function getForSelect(?int $anneeId = null, ?int $niveauId = null): Collection
    {
        $query = $this->repo->activeQuery()
            ->where('etat', 1)
            ->select('id', 'libelle', 'montant', 'niveau_id')
            ->orderBy('libelle');

        if ($anneeId) {
            $query->where('annee_id', $anneeId);
        }
        if ($niveauId) {
            $query->where('niveau_id', $niveauId);
        }

        return $query->get();
    }

    /**
     * Vérifie si un frais a des données liées (factures, paiements, etc.)
     */
    public function hasRelatedData(int $id): bool
    {
        // À adapter selon vos tables
        return \DB::table('factures')->where('frais_ecole_id', $id)->exists()
            || \DB::table('paiements')->where('frais_ecole_id', $id)->exists();
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers pour les labels
    // ─────────────────────────────────────────────────────────────
    protected function getTypePaiementLabel(?int $type): string
    {
        return match ($type) {
            1 => 'Unique',
            2 => 'Trimestriel',
            3 => 'Mensuel',
            default => 'Inconnu',
        };
    }

    protected function getTypeForfaitLabel(?int $type): string
    {
        return match ($type) {
            1 => 'Obligatoire',
            2 => 'Optionnel',
            default => 'Inconnu',
        };
    }
}