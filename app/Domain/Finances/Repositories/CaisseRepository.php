<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Types\CaisseStatut;
use App\Domain\Finances\Types\StatutEcartCaisse;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CaisseRepository extends BaseRepository
{
    public function __construct(Caisse $model)
    {
        parent::__construct($model);
    }

    /** Liste paginée pour l'écran index (filtres + eager load caissier). */
    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with(['caissier:id,nom,prenom'])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', (int) $filters['statut'])
            )
            ->when($filters['responsable_id'] ?? null, fn ($q, $v) => $q->where('responsable_id', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"))
            ->orderByDesc('date_ouverture')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Écarts de caisse déclarés à la clôture, pour l'écran de validation de
     * la Direction. Sans filtre de statut : ceux qui attendent une décision.
     */
    public function ecartsPagines(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $statut = $filters['statut_ecart'] ?? null;

        return $this->activeQuery()
            ->with(['caissier:id,nom,prenom', 'valideur:id,nom,prenom'])
            ->where('statut', CaisseStatut::CLOTUREE->value)
            ->where('statut_ecart', '!=', StatutEcartCaisse::AUCUN->value)
            ->when(
                $statut === null || $statut === '',
                fn ($q) => $q->whereIn('statut_ecart', [StatutEcartCaisse::EN_ATTENTE->value, StatutEcartCaisse::ENQUETE->value]),
                fn ($q) => $statut === 'tous' ? $q : $q->where('statut_ecart', $statut)
            )
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['responsable_id'] ?? null, fn ($q, $v) => $q->where('responsable_id', $v))
            ->orderByDesc('date_cloture')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return array<string, int> nombre d'écarts par statut (hors « aucun ») */
    public function compterEcartsParStatut(?int $anneeId = null): array
    {
        $rows = $this->activeQuery()
            ->where('statut_ecart', '!=', StatutEcartCaisse::AUCUN->value)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->selectRaw('statut_ecart, COUNT(*) as total')
            ->groupBy('statut_ecart')
            ->pluck('total', 'statut_ecart');

        return [
            'en_attente' => (int) ($rows[StatutEcartCaisse::EN_ATTENTE->value] ?? 0),
            'enquete'    => (int) ($rows[StatutEcartCaisse::ENQUETE->value] ?? 0),
            'valide'     => (int) ($rows[StatutEcartCaisse::VALIDE->value] ?? 0),
            'refuse'     => (int) ($rows[StatutEcartCaisse::REFUSE->value] ?? 0),
        ];
    }

    /** Charge la caisse + tout ce qu'il faut pour la vue show (journal). */
    public function findAvecJournal(int $id): Caisse
    {
        return $this->activeQuery()
            ->with([
                'caissier:id,nom,prenom',
                'valideur:id,nom,prenom',
                'mouvements' => fn ($q) => $q->where('etat', 1)->orderBy('date_mouvement')->orderBy('id'),
                'mouvements.utilisateur:id,nom,prenom',
            ])
            ->findOrFail($id);
    }

    /** Règle métier : un caissier ne peut avoir qu'une seule caisse ouverte. */
    public function findOuverteParCaissier(int $caissierId): ?Caisse
    {
        return $this->activeQuery()
            ->where('responsable_id', $caissierId)
            ->where('statut', CaisseStatut::OUVERTE->value)
            ->latest('date_ouverture')
            ->first();
    }

    /** @return array{total:int, ouvertes:int, fermees:int} caisses créées sur une année scolaire */
    public function compterParStatutAnnee(?int $anneeId): array
    {
        $rows = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        $ouvertes = (int) ($rows[CaisseStatut::OUVERTE->value] ?? 0);
        $fermees  = (int) ($rows[CaisseStatut::CLOTUREE->value] ?? 0);

        return ['total' => array_sum(array_map('intval', $rows)), 'ouvertes' => $ouvertes, 'fermees' => $fermees];
    }

    /** Nombre de sessions actuellement ouvertes (cartes stats du index). */
    public function compterOuvertes(): int
    {
        return $this->activeQuery()
            ->where('statut', CaisseStatut::OUVERTE->value)
            ->count();
    }

    /**
     * Caissiers qui ont réellement encaissé sur l'année scolaire (filtre
     * « Caissier » de la liste des caisses), avec leur nombre de caisses de
     * cette année.
     */
    public function caissiersAyantEncaisse(?int $anneeId): Collection
    {
        return \Illuminate\Support\Facades\DB::table('users as u')
            ->whereExists(fn ($q) => $q->from('details as d')->whereColumn('d.caissier_id', 'u.id')
                ->where('d.etat', 1)->where('d.statut_paiement', \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value)
                ->when($anneeId, fn ($q, $v) => $q->where('d.annee_id', $v)))
            ->orderBy('u.nom')->orderBy('u.prenom')
            ->select('u.id', 'u.nom', 'u.prenom')
            ->selectSub(\Illuminate\Support\Facades\DB::table('caisses as c')->selectRaw('COUNT(*)')
                ->whereColumn('c.responsable_id', 'u.id')->where('c.etat', 1)
                ->when($anneeId, fn ($q, $v) => $q->where('c.annee_id', $v)), 'nb_caisses')
            ->get();
    }

    /**
     * Tableau de bord des caisses d'une année (Administrateur et Direction) :
     * nombre de caisses de l'année (encore ouvertes, clôturées), et nombre de
     * caisses ouvertes et clôturées ce mois-ci, cette semaine et aujourd'hui.
     *
     * @return array{total:int, ouvertes:int, fermees:int, periodes:array<string, array{ouvertes:int, cloturees:int}>}
     */
    public function tableauDeBord(?int $anneeId): array
    {
        $tableau = $this->compterParStatutAnnee($anneeId);
        $maintenant = now();
        $periodes = [
            'mois'    => [$maintenant->copy()->startOfMonth(), $maintenant->copy()->endOfMonth()],
            'semaine' => [$maintenant->copy()->startOfWeek(), $maintenant->copy()->endOfWeek()],
            'jour'    => [$maintenant->copy()->startOfDay(), $maintenant->copy()->endOfDay()],
        ];

        foreach ($periodes as $cle => [$debut, $fin]) {
            $caisses = fn () => $this->activeQuery()->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v));

            $tableau['periodes'][$cle] = [
                'ouvertes'  => $caisses()->whereBetween('date_ouverture', [$debut, $fin])->count(),
                'cloturees' => $caisses()->where('statut', CaisseStatut::CLOTUREE->value)->whereBetween('date_cloture', [$debut, $fin])->count(),
                'debut'     => $debut,
                'fin'       => $fin,
            ];
        }

        return $tableau;
    }

    /** Liste légère pour peupler les <select>. */
    public function listePourSelect(?int $anneeId = null): Collection
    {
        return $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderByDesc('id')
            ->get(['id', 'libelle', 'statut']);
    }
}