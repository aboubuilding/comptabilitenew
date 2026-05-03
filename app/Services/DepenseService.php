<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\User;
use App\Repositories\Interfaces\DepenseRepositoryInterface;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Repositories\Interfaces\CaisseRepositoryInterface;
use App\Types\StatutDepense;
use App\Types\StatutCaisse;
use App\Types\TypeStatus;
use App\Types\Role;
use App\Types\TypeMouvement;
use App\Types\StatutMouvement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;
use Carbon\Carbon;

class DepenseService
{
    public const SEUIL_VALIDATION_AUTO = 150000;

    public function __construct(
        private DepenseRepositoryInterface $depenseRepo,
        private MouvementRepositoryInterface $mouvementRepo,
        private CaisseRepositoryInterface $caisseRepo
    ) {}

    // ─────────────────────────────────────────────────────────────
    // 🔐 Helpers : Session & Rôles
    // ─────────────────────────────────────────────────────────────

    /**
     * Récupère l'utilisateur connecté depuis la session
     */
    public function getCurrentUser(): ?User
    {
        $session = session()->get('LoginUser');
        if (!$session || empty($session['compte_id'])) {
            return null;
        }
        return User::rechercheUserById($session['compte_id']);
    }

    /**
     * Récupère l'année scolaire en cours depuis la session
     */
    public function getCurrentAnneeId(): ?int
    {
        $session = session()->get('LoginUser');
        return $session['annee_id'] ?? null;
    }

    /**
     * Vérifie si l'utilisateur est admin ou directeur (peut tout voir)
     */
    public function isAuthorizedViewer(?User $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();
        if (!$user) return false;
        
        return in_array($user->role, [Role::ADMIN, Role::DIRECTEUR], true);
    }

    /**
     * Vérifie si l'utilisateur peut valider des dépenses >= seuil
     */
    public function canValidateHighAmount(?User $user = null): bool
    {
        return $this->isAuthorizedViewer($user);
    }

    /**
     * Vérifie qu'une caisse existe ET est ouverte
     */
    private function assertCaisseOuverte(int $caisseId): void
    {
        $caisse = $this->caisseRepo->find($caisseId);
        
        throw_if(!$caisse, new LogicException('Caisse introuvable.'));
        throw_if(
            $caisse->statut !== StatutCaisse::OUVERT,
            new LogicException('La caisse sélectionnée n\'est pas ouverte.')
        );
    }

    // ─────────────────────────────────────────────────────────────
    // 📝 Création de dépense (avec caisse obligatoire)
    // ─────────────────────────────────────────────────────────────

    /**
     * Enregistre une nouvelle dépense avec règle de validation auto
     */
    public function enregistrerDepense(array $data, ?User $user = null): Depense
    {
        $user = $user ?? $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $anneeId = $data['annee_id'] ?? $this->getCurrentAnneeId();
        throw_if(!$anneeId, new LogicException('Année scolaire non définie.'));

        // 🔒 Validation : caisse_id obligatoire et ouverte
        throw_if(
            empty($data['caisse_id']),
            new LogicException('La sélection d\'une caisse ouverte est obligatoire.')
        );
        $this->assertCaisseOuverte((int) $data['caisse_id']);

        return DB::transaction(function () use ($data, $user, $anneeId) {
            $depense = $this->depenseRepo->create([
                'libelle'              => $data['libelle'],
                'beneficiaire'         => $data['beneficiaire'] ?? null,
                'motif_depense'        => $data['motif'],
                'montant'              => $data['montant'],
                'date_depense'         => $data['date_depense'] ?? now(),
                'statut_depense'       => StatutDepense::EN_ATTENTE,
                'utilisateur_id'       => $user->id,
                'annee_id'             => $anneeId,
                'caisse_id'            => (int) $data['caisse_id'],
                'justificatif_demande' => $data['justificatif'] ?? null,
                'etat'                 => TypeStatus::ACTIF,
            ]);

            // Validation auto si montant < seuil
            if ($depense->montant < self::SEUIL_VALIDATION_AUTO) {
                $this->validerEtGenererMouvement($depense, $user->id, auto: true);
            }

            return $depense;
        });
    }

    // ─────────────────────────────────────────────────────────────
    // ✅ Validation manuelle (avec vérification caisse toujours ouverte)
    // ─────────────────────────────────────────────────────────────

    /**
     * Validation manuelle (admin/directeur requis si >= seuil)
     */
    public function validerDepense(int $id, ?User $validator = null): bool
    {
        $validator = $validator ?? $this->getCurrentUser();
        throw_if(!$validator, new LogicException('Utilisateur non authentifié.'));

        $depense = $this->depenseRepo->findOrFail($id);

        // 🔐 Rôle pour montants élevés
        if ($depense->montant >= self::SEUIL_VALIDATION_AUTO) {
            throw_unless(
                $this->canValidateHighAmount($validator),
                new LogicException('Seuls les administrateurs ou directeurs peuvent valider cette dépense.')
            );
        }

        throw_if(
            $depense->statut_depense !== StatutDepense::EN_ATTENTE,
            new LogicException('Cette dépense n\'est plus en attente de validation.')
        );

        // 🔒 VÉRIFICATION CRITIQUE : La caisse d'origine doit être TOUJOURS ouverte
        throw_if(
            !$depense->caisse_id,
            new LogicException('Cette dépense n\'est liée à aucune caisse.')
        );
        $this->assertCaisseOuverte($depense->caisse_id);

        return DB::transaction(function () use ($depense, $validator) {
            $this->depenseRepo->valider($depense->id, $validator->id);
            // 👇 Le mouvement utilisera la caisse de la dépense, PAS la caisse active de l'utilisateur
            return $this->validerEtGenererMouvement($depense, $validator->id, auto: false);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // ❌ Rejet d'une dépense
    // ─────────────────────────────────────────────────────────────

    /**
     * Rejet d'une dépense avec motif
     */
    public function rejeterDepense(int $id, string $motif, ?User $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $depense = $this->depenseRepo->findOrFail($id);

        throw_if(
            $depense->statut_depense !== StatutDepense::EN_ATTENTE,
            new LogicException('Seules les dépenses en attente peuvent être rejetées.')
        );

        return $depense->update([
            'statut_depense'         => StatutDepense::REFUSEE,
            'validateur_id'          => $user->id,
            'date_validation'        => now(),
            'motif_rejet'        =>  $motif,
           
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 🔄 Génération du mouvement (utilise la caisse de la dépense)
    // ─────────────────────────────────────────────────────────────

    /**
     * Valide la dépense ET crée le mouvement de décaissement associé
     */
    private function validerEtGenererMouvement(Depense $depense, int $userId, bool $auto): bool
    {
        // ✅ Utilisation de $depense->caisse_id (et non getActiveCaisseId)
        $this->mouvementRepo->enregistrer([
            'caisse_id'         => $depense->caisse_id,
            'type_mouvement'    => TypeMouvement::DECAISSEMENT,
            'montant'           => $depense->montant,
            'motif'             => $depense->motif_depense,
            'beneficiaire'      => $depense->beneficiaire,
            'depense_id'        => $depense->id,
            'annee_id'          => $depense->annee_id,
            'utilisateur_id'    => $userId,
            'statut_mouvement'  => StatutMouvement::VALIDER,
            'reference'         => 'DEP-' . $depense->id . '-' . now()->format('Ymd'),
        ]);

        return $depense->update(['statut_depense' => StatutDepense::PAYEE]);
    }

    // ─────────────────────────────────────────────────────────────
    // 📊 Liste avec agrégats & filtres (CŒUR DE LA DEMANDE)
    // ─────────────────────────────────────────────────────────────

    /**
     * Retourne la liste des dépenses avec agrégats jour/semaine/mois/année
     * + contrôle d'accès par rôle
     */
    public function getDepensesWithAggregates(array $filters = []): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $isViewer = $this->isAuthorizedViewer($user);

        // ─── Construction de la requête de base ───
        $query = Depense::query()
            ->with(['demandeur:id,name', 'validateur:id,name', 'caisse:id,libelle'])
            ->where('etat', TypeStatus::ACTIF)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId));

        // 🔐 Contrôle d'accès : non-admin voit seulement ses dépenses
        if (!$isViewer) {
            $query->where('utilisateur_id', $user->id);
        }

        // 🎛️ Filtres optionnels
        if (!empty($filters['statut']) && is_numeric($filters['statut'])) {
            $query->where('statut_depense', (int) $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_depense', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_depense', '<=', $filters['date_fin']);
        }
        if (!empty($filters['caisse_id']) && is_numeric($filters['caisse_id'])) {
            $query->where('caisse_id', (int) $filters['caisse_id']);
        }

        // ─── Récupération des dépenses formatées ───
        $depenses = $query
            ->orderByDesc('date_depense')
            ->get()
            ->map(fn($d) => $this->formatDepense($d, $isViewer));

        // ─── Calcul des agrégats temporels ───
        $aggregates = $this->calculateTimeAggregates($depenses, $anneeId);

        return [
            'data'       => $depenses,
            'aggregates' => $aggregates,
            'meta'       => [
                'total'         => $depenses->count(),
                'montant_total' => $depenses->sum('montant'),
                'user_role'     => $user->role,
                'can_see_validators' => $isViewer,
            ],
        ];
    }

    /**
     * Formate une dépense pour l'affichage (contrôle d'accès sur validateur)
     */
    private function formatDepense(Depense $depense, bool $isViewer): array
    {
        return [
            // Champs de base (tous sauf 'etat')
            'id'                     => $depense->id,
            'libelle'                => $depense->libelle,
            'beneficiaire'           => $depense->beneficiaire,
            'motif_depense'          => $depense->motif_depense,
            'montant'                => (float) $depense->montant,
            'date_depense'           => $depense->date_depense?->format('Y-m-d'),
            'statut_depense'         => $depense->statut_depense,
            'statut_label'           => \App\Types\StatutDepense::label($depense->statut_depense),
            'justificatif_demande'   => $depense->justificatif_demande,
            'annee_id'               => $depense->annee_id,
            'utilisateur_id'         => $depense->utilisateur_id,
            'caisse_id'              => $depense->caisse_id,
            
            // Noms des utilisateurs
            'createur_nom'           => $depense->demandeur?->name,
            
            // 🔐 Masquage du validateur si non autorisé
            'validateur_nom'         => $isViewer && $depense->validateur ? $depense->validateur->name : null,
            'date_validation'        => $isViewer ? $depense->date_validation?->format('Y-m-d') : null,
            'commentaire_validation' => $isViewer ? $depense->commentaire_validation : null,
            
            // Infos caisse
            'caisse_libelle'         => $depense->caisse?->libelle,
        ];
    }

    /**
     * Calcule les totaux par jour / semaine / mois / année
     */
    private function calculateTimeAggregates(Collection $depenses, ?int $anneeId): array
    {
        $now = Carbon::now();
        $paid = $depenses->where('statut_depense', StatutDepense::PAYEE);

        return [
            'aujourd_hui' => [
                'date'   => $now->format('Y-m-d'),
                'count'  => $paid->where('date_depense', $now->format('Y-m-d'))->count(),
                'total'  => (float) $paid->where('date_depense', $now->format('Y-m-d'))->sum('montant'),
            ],
            'semaine_en_cours' => [
                'debut'  => $now->copy()->startOfWeek()->format('Y-m-d'),
                'fin'    => $now->copy()->endOfWeek()->format('Y-m-d'),
                'count'  => $paid->filter(fn($d) => 
                    Carbon::parse($d['date_depense'])->between(
                        $now->copy()->startOfWeek(), 
                        $now->copy()->endOfWeek()
                    )
                )->count(),
                'total'  => (float) $paid->filter(fn($d) => 
                    Carbon::parse($d['date_depense'])->between(
                        $now->copy()->startOfWeek(), 
                        $now->copy()->endOfWeek()
                    )
                )->sum('montant'),
            ],
            'mois_en_cours' => [
                'debut'  => $now->copy()->startOfMonth()->format('Y-m-d'),
                'fin'    => $now->copy()->endOfMonth()->format('Y-m-d'),
                'count'  => $paid->filter(fn($d) => 
                    Carbon::parse($d['date_depense'])->month === $now->month && 
                    Carbon::parse($d['date_depense'])->year === $now->year
                )->count(),
                'total'  => (float) $paid->filter(fn($d) => 
                    Carbon::parse($d['date_depense'])->month === $now->month && 
                    Carbon::parse($d['date_depense'])->year === $now->year
                )->sum('montant'),
            ],
            'annee_en_cours' => [
                'debut'  => $now->copy()->startOfYear()->format('Y-m-d'),
                'fin'    => $now->copy()->endOfYear()->format('Y-m-d'),
                'count'  => $paid->filter(fn($d) => 
                    Carbon::parse($d['date_depense'])->year === $now->year
                )->count(),
                'total'  => (float) $paid->filter(fn($d) => 
                    Carbon::parse($d['date_depense'])->year === $now->year
                )->sum('montant'),
            ],
            // Agrégats SQL natifs pour performance sur gros volumes
            'par_jour'   => $this->getAggregatesByPeriod('day', $anneeId),
            'par_semaine'=> $this->getAggregatesByPeriod('week', $anneeId),
            'par_mois'   => $this->getAggregatesByPeriod('month', $anneeId),
        ];
    }

    /**
     * Agrégats SQL natifs par période (performant)
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
        $isViewer = $this->isAuthorizedViewer($user);

        $query = Depense::query()
            ->selectRaw("DATE_FORMAT(date_depense, '{$format}') as period, COUNT(*) as count, SUM(montant) as total")
            ->where('statut_depense', StatutDepense::PAYEE)
            ->where('etat', TypeStatus::ACTIF)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!$isViewer, fn($q) => $q->where('utilisateur_id', $user?->id))
            ->groupBy('period')
            ->orderBy('period');

        return $query->get()->mapWithKeys(fn($row) => [
            $row->period => ['count' => (int) $row->count, 'total' => (float) $row->total]
        ])->toArray();
    }

    // ─────────────────────────────────────────────────────────────
    // 📋 Liste paginée simple (pour frontend avec pagination)
    // ─────────────────────────────────────────────────────────────

    public function getDepensesList(array $filters = [], int $perPage = 15): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $isViewer = $this->isAuthorizedViewer($user);
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = Depense::query()
            ->with(['demandeur:id,name', 'validateur:id,name', 'caisse:id,libelle'])
            ->where('etat', TypeStatus::ACTIF)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!$isViewer, fn($q) => $q->where('utilisateur_id', $user->id));

        // Filtres
        if (!empty($filters['statut']) && is_numeric($filters['statut'])) {
            $query->where('statut_depense', (int) $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_depense', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_depense', '<=', $filters['date_fin']);
        }
        if (!empty($filters['caisse_id']) && is_numeric($filters['caisse_id'])) {
            $query->where('caisse_id', (int) $filters['caisse_id']);
        }

        $paginated = $query->orderByDesc('date_depense')->paginate($perPage);

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