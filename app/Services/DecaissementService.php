<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Types\StatutMouvement;
use App\Types\TypeMouvement;
use App\Types\TypeStatus;
use App\Types\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Carbon\Carbon;

class DecaissementService
{
    public function __construct(
        private MouvementRepositoryInterface $mouvementRepo
    ) {}

    // ─────────────────────────────────────────────────────────────
    // 🔐 Helpers : Session & Rôles
    // ─────────────────────────────────────────────────────────────

    public function getCurrentUser(): ?User
    {
        $session = session()->get('LoginUser');
        if (!$session || empty($session['compte_id'])) {
            return null;
        }
        return User::rechercheUserById($session['compte_id']);
    }

    public function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    public function isAuthorizedViewer(?User $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();
        if (!$user) return false;

        return in_array($user->role, [Role::ADMIN, Role::DIRECTEUR], true);
    }

    // ─────────────────────────────────────────────────────────────
    // 📊 Liste avec agrégats (pour la vue index)
    // ─────────────────────────────────────────────────────────────

    public function getDecaissementsWithAggregates(array $filters = []): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $isViewer = $this->isAuthorizedViewer($user);

        // ─── Requête de base via le repository ───
        $query = $this->mouvementRepo->activeQuery()
            ->with(['operateur:id,name', 'caisse:id,libelle', 'depense:id,libelle'])
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER);

        // 🔐 Contrôle d'accès
        if (!$isViewer) {
            $query->where('utilisateur_id', $user->id);
        }

        // 🎛️ Filtres
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_mouvement', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_mouvement', '<=', $filters['date_fin']);
        }
        if (!empty($filters['caisse_id']) && is_numeric($filters['caisse_id'])) {
            $query->where('caisse_id', (int) $filters['caisse_id']);
        }
        if ($anneeId) {
            $query->where('annee_id', $anneeId);
        }

        // ─── Récupération et formatage ───
        $decaissements = $query
            ->orderByDesc('date_mouvement')
            ->get()
            ->map(fn($mvt) => $this->formatDecaissement($mvt, $isViewer));

        // ─── Agrégats (déjà calculés sur la collection filtrée) ───
        $aggregates = $this->calculateTimeAggregates($decaissements, $anneeId);

        return [
            'data'       => $decaissements,
            'aggregates' => $aggregates,
            'meta'       => [
                'total'         => $decaissements->count(),
                'montant_total' => $decaissements->sum('montant'),
                'user_role'     => $user->role,
            ],
        ];
    }

    /**
     * Détail d'un décaissement
     */
    public function getDecaissementById(int $id): ?Mouvement
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $isViewer = $this->isAuthorizedViewer();

        $query = $this->mouvementRepo->activeQuery()
            ->with(['operateur:id,name', 'caisse:id,libelle', 'depense:id,libelle'])
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->where('id', $id);

        if (!$isViewer) {
            $query->where('utilisateur_id', $user->id);
        }

        return $query->first();
    }

    /**
     * Formate un décaissement
     */
    private function formatDecaissement(Mouvement $mvt, bool $isViewer): array
    {
        return [
            'id'            => $mvt->id,
            'reference'     => $mvt->reference,
            'libelle'       => $mvt->motif,
            'motif'         => $mvt->motif,
            'beneficiaire'  => $mvt->beneficiaire,
            'montant'       => (float) $mvt->montant,
            'date'          => $mvt->date_mouvement?->format('Y-m-d'),
            'caisse_id'     => $mvt->caisse_id,
            'caisse_libelle'=> $mvt->caisse?->libelle,
            'effectue_par'  => $mvt->operateur?->name,
            'lie_a_depense' => $mvt->depense?->libelle,
            'depense_id'    => $mvt->depense_id,
            'justificatif'  => $mvt->file ? asset('storage/' . $mvt->file) : null,
        ];
    }

    /**
     * Calcul des agrégats temporels
     */
    private function calculateTimeAggregates(Collection $items, ?int $anneeId): array
    {
        $now = Carbon::now();
        $filterByDate = fn($dateStr, $start, $end) => Carbon::parse($dateStr)->between($start, $end);

        return [
            'aujourd_hui' => [
                'date'   => $now->format('Y-m-d'),
                'count'  => $items->filter(fn($d) => $d['date'] === $now->format('Y-m-d'))->count(),
                'total'  => (float) $items->filter(fn($d) => $d['date'] === $now->format('Y-m-d'))->sum('montant'),
            ],
            'semaine_en_cours' => [
                'debut'  => $now->copy()->startOfWeek()->format('Y-m-d'),
                'fin'    => $now->copy()->endOfWeek()->format('Y-m-d'),
                'count'  => $items->filter(fn($d) => $filterByDate($d['date'], $now->copy()->startOfWeek(), $now->copy()->endOfWeek()))->count(),
                'total'  => (float) $items->filter(fn($d) => $filterByDate($d['date'], $now->copy()->startOfWeek(), $now->copy()->endOfWeek()))->sum('montant'),
            ],
            'mois_en_cours' => [
                'debut'  => $now->copy()->startOfMonth()->format('Y-m-d'),
                'fin'    => $now->copy()->endOfMonth()->format('Y-m-d'),
                'count'  => $items->filter(fn($d) => $filterByDate($d['date'], $now->copy()->startOfMonth(), $now->copy()->endOfMonth()))->count(),
                'total'  => (float) $items->filter(fn($d) => $filterByDate($d['date'], $now->copy()->startOfMonth(), $now->copy()->endOfMonth()))->sum('montant'),
            ],
            'annee_en_cours' => [
                'debut'  => $now->copy()->startOfYear()->format('Y-m-d'),
                'fin'    => $now->copy()->endOfYear()->format('Y-m-d'),
                'count'  => $items->filter(fn($d) => Carbon::parse($d['date'])->year === $now->year)->count(),
                'total'  => (float) $items->filter(fn($d) => Carbon::parse($d['date'])->year === $now->year)->sum('montant'),
            ],
            'par_jour'   => $this->getAggregatesByPeriod('day', $anneeId),
            'par_semaine'=> $this->getAggregatesByPeriod('week', $anneeId),
            'par_mois'   => $this->getAggregatesByPeriod('month', $anneeId),
        ];
    }

    /**
     * Agrégats SQL natifs par période
     */
    private function getAggregatesByPeriod(string $period, ?int $anneeId): array
    {
        $format = match($period) {
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };

        $user = $this->getCurrentUser();
        $isViewer = $this->isAuthorizedViewer();

        $query = $this->mouvementRepo->activeQuery()
            ->selectRaw("DATE_FORMAT(date_mouvement, '{$format}') as period, COUNT(*) as count, SUM(montant) as total")
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!$isViewer, fn($q) => $q->where('utilisateur_id', $user?->id))
            ->groupBy('period')
            ->orderBy('period');

        return $query->get()->mapWithKeys(fn($row) => [
            $row->period => ['count' => (int) $row->count, 'total' => (float) $row->total]
        ])->toArray();
    }

    /**
     * Liste paginée simple
     */
    public function getDecaissementsList(array $filters = [], int $perPage = 15): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $isViewer = $this->isAuthorizedViewer();
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = $this->mouvementRepo->activeQuery()
            ->with(['operateur:id,name', 'caisse:id,libelle'])
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!$isViewer, fn($q) => $q->where('utilisateur_id', $user->id));

        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_mouvement', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_mouvement', '<=', $filters['date_fin']);
        }
        if (!empty($filters['caisse_id']) && is_numeric($filters['caisse_id'])) {
            $query->where('caisse_id', (int) $filters['caisse_id']);
        }

        $paginated = $query->orderByDesc('date_mouvement')->paginate($perPage);

        return [
            'data'  => $paginated->items(),
            'meta'  => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
            ],
        ];
    }
}
