<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Prevision;
use App\Domain\Finances\Types\TypePrevision;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PrevisionRepository extends BaseRepository
{
    public function __construct(Prevision $model)
    {
        parent::__construct($model);
    }

    /**
     * Une dépense n'est imputée à sa ligne que lorsqu'elle a réellement été
     * décaissée, pour le montant décaissé (à défaut, son montant).
     */
    private const SQL_MONTANT_DEPENSE = 'COALESCE(depenses.montant_decaisse, depenses.montant)';

    private function depensesDeLaLigne(): \Illuminate\Database\Query\Builder
    {
        return \Illuminate\Support\Facades\DB::table('depenses')
            ->whereColumn('depenses.ligne_budgetaire_id', 'previsions.id')
            ->where('depenses.etat', 1)
            ->where('depenses.statut_depense', '!=', \App\Domain\Finances\Types\DepenseStatut::REJETEE->value);
    }

    private function depensesImputees(): \Illuminate\Database\Query\Builder
    {
        return $this->depensesDeLaLigne()->whereNotNull('depenses.date_reglement');
    }

    /**
     * Lignes budgétaires de l'année avec, pour chacune : le montant des dépenses
     * imputées (décaissées) et leur nombre, et ce qui attend d'être décaissé.
     */
    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with('centre:id,code,libelle')
            ->select('previsions.*')
            ->selectSub($this->depensesImputees()->selectRaw('COALESCE(SUM(' . self::SQL_MONTANT_DEPENSE . '), 0)'), 'montant_depenses')
            ->selectSub($this->depensesImputees()->selectRaw('COUNT(*)'), 'nb_depenses')
            // Rattachées à la ligne mais pas encore décaissées : à titre indicatif, non imputées
            ->selectSub($this->depensesDeLaLigne()->whereNull('depenses.date_reglement')
                ->selectRaw('COALESCE(SUM(depenses.montant), 0)'), 'montant_en_attente')
            ->selectSub($this->depensesDeLaLigne()->whereNull('depenses.date_reglement')->selectRaw('COUNT(*)'), 'nb_en_attente')
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['search'] ?? null,   fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Lignes de l'année proposées à la saisie d'une dépense. */
    public function listePourSelect(?int $anneeId = null): Collection
    {
        return $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'montant_previsionnel']);
    }

    /**
     * Dépenses imputées à une ligne, avec la caisse qui a servi à les décaisser
     * et le caissier.
     */
    public function depensesDe(int $ligneId): Collection
    {
        return \App\Domain\Finances\Models\Depense::query()
            ->with(['caisse:id,libelle', 'regleur:id,nom,prenom', 'auteur:id,nom,prenom'])
            ->where('ligne_budgetaire_id', $ligneId)
            ->where('etat', 1)
            ->orderByDesc('date_depense')->orderByDesc('id')
            ->get();
    }

    /**
     * Agrégat par centre analytique sur une période — utilisé par le
     * rapport analytique (module 2.7).
     *
     * @return Collection<int, object{centre_id:int, total_prevu:float}>
     */
    public function agregerParCentre(
        ?int $anneeId = null,
        ?string $dateDebut = null,
        ?string $dateFin = null
    ): Collection {
        return $this->activeQuery()
            ->whereNotNull('centre_analytique_id')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($dateDebut, fn ($q, $v) => $q->where(fn ($w) => $w->whereNull('date_debut')->orWhereDate('date_debut', '>=', $v)))
            ->when($dateFin,   fn ($q, $v) => $q->where(fn ($w) => $w->whereNull('date_fin')->orWhereDate('date_fin', '<=', $v)))
            ->groupBy('centre_analytique_id')
            ->selectRaw(
                "centre_analytique_id as centre_id, SUM(CASE WHEN type = ? THEN montant_previsionnel ELSE -montant_previsionnel END) as total_prevu",
                [\App\Domain\Finances\Types\TypePrevision::RECETTE->value]
            )
            ->get()
            ->keyBy('centre_id');
    }
}