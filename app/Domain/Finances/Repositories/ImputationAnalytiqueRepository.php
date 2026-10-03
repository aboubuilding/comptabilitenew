<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\ImputationAnalytique;
use App\Domain\Finances\Types\SensAnalytique;
use App\Repositories\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ImputationAnalytiqueRepository extends BaseRepository
{
    public function __construct(ImputationAnalytique $model)
    {
        parent::__construct($model);
    }

    /**
     * Agrégat par centre et par sens sur une période — la requête
     * centrale du tableau de bord analytique.
     *
     * @return \Illuminate\Support\Collection<int, object{
     *     centre_id:int, code:string, libelle:string,
     *     total_recettes:float, total_depenses:float
     * }>
     */
    public function agregerParCentre(
        ?int $anneeId = null,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): Collection {
        // etat est qualifié par sa table : les deux tables de la jointure portent cette colonne
        return $this->query()
            ->where('imputations_analytiques.etat', self::ACTIF)
            ->join('centres_analytiques as c', 'c.id', '=', 'imputations_analytiques.centre_analytique_id')
            ->where('c.etat', self::ACTIF)
            ->when($anneeId, fn ($q, $v) => $q->where('imputations_analytiques.annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->whereDate('imputations_analytiques.created_at', '>=', $v))
            ->when($dateFin,   fn ($q, $v) => $q->whereDate('imputations_analytiques.created_at', '<=', $v))
            ->groupBy('c.id', 'c.code', 'c.libelle')
            ->select([
                'c.id as centre_id',
                'c.code',
                'c.libelle',
                DB::raw("SUM(CASE WHEN imputations_analytiques.sens = '" . SensAnalytique::RECETTE->value . "' THEN COALESCE(imputations_analytiques.montant, 0) ELSE 0 END) as total_recettes"),
                DB::raw("SUM(CASE WHEN imputations_analytiques.sens = '" . SensAnalytique::DEPENSE->value . "' THEN COALESCE(imputations_analytiques.montant, 0) ELSE 0 END) as total_depenses"),
            ])
            ->orderBy('c.code')
            ->get();
    }

    /** Toutes les imputations d'une source donnée (dépense, mouvement...). */
    public function pourSource(string $type, int $id): Collection
    {
        return $this->activeQuery()
            ->with('centre:id,code,libelle')
            ->where('imputable_type', $type)
            ->where('imputable_id', $id)
            ->get();
    }
}