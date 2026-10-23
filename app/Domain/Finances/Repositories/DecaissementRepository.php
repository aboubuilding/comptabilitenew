<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Types\DepenseStatut;
use App\Domain\Finances\Types\MouvementType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Décaissements : les sorties d'argent enregistrées dans une caisse (règlement
 * d'une dépense, sortie manuelle). Ce sont les mouvements de sortie du journal
 * de caisse.
 */
class DecaissementRepository
{
    /** Types de mouvement qui sortent de l'argent de la caisse. */
    public const SORTIES = [MouvementType::DEPENSE, MouvementType::SORTIE_MANUELLE];

    public function query(array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('mouvements as m')
            ->join('caisses as c', 'c.id', '=', 'm.caisse_id')
            ->leftJoin('users as u', 'u.id', '=', 'm.utilisateur_id')
            ->leftJoin('depenses as d', 'd.id', '=', 'm.depense_id')
            ->where('m.etat', 1)
            ->whereIn('m.type_mouvement', array_map(fn (MouvementType $t) => $t->value, self::SORTIES))
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('m.annee_id', $v))
            ->when($filters['utilisateur_id'] ?? null, fn ($q, $v) => $q->where('m.utilisateur_id', $v))
            ->when($filters['caisse_id'] ?? null, fn ($q, $v) => $q->where('m.caisse_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('m.type_mouvement', $v))
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('m.date_mouvement', '>=', $v))
            ->when($filters['date_fin'] ?? null, fn ($q, $v) => $q->whereDate('m.date_mouvement', '<=', $v))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('m.libelle', 'like', "%{$search}%")
                ->orWhere('m.beneficiaire', 'like', "%{$search}%")
                ->orWhere('m.reference', 'like', "%{$search}%")
                ->orWhere('d.libelle', 'like', "%{$search}%")))
            ->orderByDesc('m.date_mouvement')->orderByDesc('m.id')
            ->select([
                'm.id', 'm.reference', 'm.libelle', 'm.beneficiaire', 'm.motif', 'm.date_mouvement', 'm.montant',
                'm.type_mouvement', 'm.caisse_id', 'm.depense_id', 'm.created_at',
                'c.libelle as caisse', 'c.statut as statut_caisse',
                'd.libelle as depense_libelle', 'd.motif_depense', 'd.date_depense',
                'd.justificatif_path', 'd.justificatif_nom_original',
                DB::raw("TRIM(CONCAT(COALESCE(u.nom,''), ' ', COALESCE(u.prenom,''))) as caissier"),
            ]);
    }

    public function pagines(array $filters = [], int $perPage = 20, string $page = 'page'): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage, ['*'], $page)->withQueryString();
    }

    /** @return array{nb:int, montant:float} */
    public function total(array $filters = []): array
    {
        $ligne = DB::query()->fromSub($this->query($filters), 'x')
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(x.montant), 0) as montant')->first();

        return ['nb' => (int) $ligne->nb, 'montant' => round((float) $ligne->montant, 2)];
    }

    /** Ceux qui ont réellement décaissé sur l'année (filtre « Caissier »). */
    public function caissiers(?int $anneeId): Collection
    {
        return DB::table('users as u')
            ->whereExists(fn ($q) => $q->from('mouvements as m')->whereColumn('m.utilisateur_id', 'u.id')
                ->where('m.etat', 1)
                ->whereIn('m.type_mouvement', array_map(fn (MouvementType $t) => $t->value, self::SORTIES))
                ->when($anneeId, fn ($q, $v) => $q->where('m.annee_id', $v)))
            ->orderBy('u.nom')->orderBy('u.prenom')
            ->get(['u.id', DB::raw("TRIM(CONCAT(COALESCE(u.nom,''), ' ', COALESCE(u.prenom,''))) as nom")]);
    }

    /**
     * Onglet « Dépenses en attente » : les dépenses de l'année pas encore
     * décaissées, validées ou non (les rejetées en sont exclues). Seules les
     * validées peuvent être décaissées.
     */
    public function depensesEnAttente(?int $anneeId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Depense::query()
            ->with(['auteur:id,nom,prenom', 'valideur:id,nom,prenom'])
            ->where('etat', 1)
            ->whereIn('statut_depense', [DepenseStatut::EN_ATTENTE->value, DepenseStatut::VALIDEE->value])
            ->whereNull('date_reglement')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(($filters['statut'] ?? '') !== '', fn ($q) => $q->where('statut_depense', (int) $filters['statut']))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('beneficiaire', 'like', "%{$search}%")
                ->orWhere('motif_depense', 'like', "%{$search}%")))
            // Les validées (décaissables) d'abord, puis les plus anciennes
            ->orderByRaw('CASE WHEN statut_depense = ? THEN 0 ELSE 1 END', [DepenseStatut::VALIDEE->value])
            ->orderBy('date_depense')->orderBy('id')
            ->paginate($perPage, ['*'], 'page_attente')
            ->withQueryString();
    }

    /** @return array{nb:int, montant:float, validees:int, montant_validees:float} */
    public function compterEnAttente(?int $anneeId): array
    {
        $ligne = Depense::query()
            ->where('etat', 1)
            ->whereIn('statut_depense', [DepenseStatut::EN_ATTENTE->value, DepenseStatut::VALIDEE->value])
            ->whereNull('date_reglement')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(montant), 0) as montant,
                         SUM(statut_depense = ?) as validees, COALESCE(SUM(CASE WHEN statut_depense = ? THEN montant END), 0) as montant_validees',
                [DepenseStatut::VALIDEE->value, DepenseStatut::VALIDEE->value])
            ->first();

        return [
            'nb' => (int) $ligne->nb, 'montant' => round((float) $ligne->montant, 2),
            'validees' => (int) $ligne->validees, 'montant_validees' => round((float) $ligne->montant_validees, 2),
        ];
    }

    /**
     * Onglet « Sans justificatif » : les dépenses de l'année (hors rejetées) qui
     * n'ont pas de pièce justificative, décaissées ou non.
     */
    public function depensesSansJustificatif(?int $anneeId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->sansJustificatif($anneeId)
            ->with(['auteur:id,nom,prenom', 'caisse:id,libelle'])
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$search}%")
                ->orWhere('beneficiaire', 'like', "%{$search}%")))
            // Les décaissées d'abord (l'argent est sorti sans pièce), puis les plus anciennes
            ->orderByRaw('CASE WHEN date_reglement IS NULL THEN 1 ELSE 0 END')
            ->orderBy('date_depense')->orderBy('id')
            ->paginate($perPage, ['*'], 'page_justif')
            ->withQueryString();
    }

    /** @return array{nb:int, montant:float, decaissees:int} */
    public function compterSansJustificatif(?int $anneeId): array
    {
        $ligne = $this->sansJustificatif($anneeId)
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(montant), 0) as montant, SUM(date_reglement IS NOT NULL) as decaissees')
            ->first();

        return ['nb' => (int) $ligne->nb, 'montant' => round((float) $ligne->montant, 2), 'decaissees' => (int) $ligne->decaissees];
    }

    private function sansJustificatif(?int $anneeId): \Illuminate\Database\Eloquent\Builder
    {
        return Depense::query()
            ->where('etat', 1)
            ->where('statut_depense', '!=', DepenseStatut::REJETEE->value)
            ->where(fn ($q) => $q->whereNull('justificatif_path')->orWhere('justificatif_path', ''))
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v));
    }

    /** Dépenses validées de l'année, pas encore décaissées. */
    public function depensesADecaisser(?int $anneeId): Collection
    {
        return Depense::query()
            ->with('auteur:id,nom,prenom')
            ->where('etat', 1)
            ->where('statut_depense', DepenseStatut::VALIDEE->value)
            ->whereNull('date_reglement')
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderBy('date_depense')->orderBy('id')
            ->get();
    }
}
