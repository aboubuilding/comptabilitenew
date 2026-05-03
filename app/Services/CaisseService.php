<?php

namespace App\Services;

use App\Models\Caisse;
use App\Models\User;
use App\Repositories\Interfaces\CaisseRepositoryInterface;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Types\StatutCaisse;
use App\Types\StatutMouvement;
use App\Types\TypeMouvement;
use App\Types\TypeStatus;
use App\Types\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Carbon\Carbon;

class CaisseService
{
    /**
     * Seuils de gestion des écarts (configurables)
     */
    private const SEUIL_ECARD_NEGLIGEABLE = 100;      // FCFA
    private const SEUIL_ECARD_SIGNIFICATIF = 5000;     // FCFA

    public function __construct(
        private CaisseRepositoryInterface $caisseRepo,
        private MouvementRepositoryInterface $mouvementRepo
    ) {}

    // ─────────────────────────────────────────────────────────────
    // 🔐 Helpers : Session & Rôles
    // ─────────────────────────────────────────────────────────────

    public function getCurrentUser(): ?User
    {
        $session = session()->get('LoginUser');
        $compteId = $session['compte_id'] ?? null;
        return $compteId ? User::rechercheUserById($compteId) : null;
    }

    public function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    public function isAuthorizedViewer(?User $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();
        if (!$user) return false;
        return in_array((int) $user->role, [Role::ADMIN, Role::DIRECTEUR], true);
    }

    // ─────────────────────────────────────────────────────────────
    // 👥 Gestion des Caissiers (Users avec rôle CAISSIER)
    // ─────────────────────────────────────────────────────────────

    /**
     * Crée un nouvel utilisateur avec le rôle CAISSIER
     */
    public function createCaissier(array $data, ?User $creator = null): User
    {
        $creator = $creator ?? $this->getCurrentUser();
        throw_if(!$creator || !in_array((int) $creator->role, [Role::ADMIN, Role::DIRECTEUR]), 
            new LogicException('Seuls les administrateurs ou directeurs peuvent créer des caissiers.'));

        // Validation des données requises
        throw_if(empty($data['login']), new LogicException('Le login est obligatoire.'));
        throw_if(empty($data['mot_passe']), new LogicException('Le mot de passe est obligatoire.'));

        // Vérification unicité login
        if (User::where('login', $data['login'])->where('etat', TypeStatus::ACTIF)->exists()) {
            throw new LogicException('Ce login est déjà utilisé.');
        }

        return DB::transaction(function () use ($data, $creator) {
            return User::create([
                'nom'         => $data['nom'] ?? null,
                'prenom'      => $data['prenom'] ?? null,
                'login'       => $data['login'],
                'email'       => $data['email'] ?? null,
                'mot_passe'   => Hash::make($data['mot_passe']),
                'photo'       => $data['photo'] ?? null,
                'role'        => Role::CAISSIER,  // ✅ Rôle CAISSIER
                'etat'        => TypeStatus::ACTIF,
                'created_by'  => $creator->id ?? null,
            ]);
        });
    }

    /**
     * Liste des caissiers actifs avec filtres
     */
    public function getCaissiersList(array $filters = []): array
    {
        $query = User::query()
            ->where('role', Role::CAISSIER)
            ->where('etat', TypeStatus::ACTIF)
            ->select(['id', 'nom', 'prenom', 'login', 'email', 'created_at']);

        if (!empty($filters['search'])) {
            $search = "%{$filters['search']}%";
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', $search)
                  ->orWhere('prenom', 'LIKE', $search)
                  ->orWhere('login', 'LIKE', $search);
            });
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('created_at', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('created_at', '<=', $filters['date_fin']);
        }

        $caissiers = $query->orderBy('nom')->orderBy('prenom')->get();

        return [
            'data' => $caissiers->map(fn($c) => [
                'id'        => $c->id,
                'nom_complet'=> trim("{$c->nom} {$c->prenom}"),
                'login'     => $c->login,
                'email'     => $c->email,
                'created_at'=> $c->created_at?->format('Y-m-d'),
            ]),
            'meta' => ['total' => $caissiers->count()],
        ];
    }

    /**
     * Désactive un caissier (suppression logique)
     */
    public function deactivateCaissier(int $userId, ?User $admin = null): bool
    {
        $admin = $admin ?? $this->getCurrentUser();
        throw_if(!$admin || !in_array((int) $admin->role, [Role::ADMIN, Role::DIRECTEUR]),
            new LogicException('Action réservée aux administrateurs.'));

        $caissier = User::findOrFail($userId);
        throw_if((int) $caissier->role !== Role::CAISSIER, new LogicException('Cet utilisateur n\'est pas un caissier.'));

        return $caissier->update(['etat' => TypeStatus::SUPPRIME]);
    }

    // ─────────────────────────────────────────────────────────────
    // 📦 Cycle de vie : Création → Ouverture → Clôture
    // ─────────────────────────────────────────────────────────────

    public function creerCaisse(array $data, ?User $user = null): Caisse
    {
        $user = $user ?? $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $anneeId = $data['annee_id'] ?? $this->getCurrentAnneeId();
        throw_if(!$anneeId, new LogicException('Année scolaire non définie.'));

        return $this->caisseRepo->create([
            'libelle'            => $data['libelle'],
            'solde_initial'      => 0,
            'statut'             => StatutCaisse::FERME,
            'utilisateur_id'     => $user->id,
            'responsable_id'     => $data['responsable_id'] ?? $user->id,
            'annee_id'           => $anneeId,
            'etat'               => TypeStatus::ACTIF,
        ]);
    }

    public function ouvrirCaisse(int $caisseId, float $soldeInitial, ?User $user = null): Caisse
    {
        $user = $user ?? $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $caisse = $this->caisseRepo->findOrFail($caisseId);
        throw_if((int) $caisse->statut === StatutCaisse::OUVERT, new LogicException('Cette caisse est déjà ouverte.'));
        throw_if((int) $caisse->statut === StatutCaisse::CLOTURE, new LogicException('Une caisse clôturée ne peut pas être rouverte.'));
        
        throw_if(
            $this->caisseRepo->hasUserOpenCaisse($user->id),
            new LogicException('Vous avez déjà une caisse ouverte. Clôturez-la avant d\'en ouvrir une nouvelle.')
        );

        return DB::transaction(function () use ($caisse, $soldeInitial, $user) {
            $this->caisseRepo->ouvrir($caisse->id, $soldeInitial, $user->id);
            return $this->caisseRepo->findOrFail($caisse->id);
        });
    }

    public function cloturerCaisse(int $caisseId, float $soldePhysique, ?string $motifEcarts = null, ?User $user = null): array
    {
        $user = $user ?? $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $caisse = $this->caisseRepo->findOrFail($caisseId);
        throw_if((int) $caisse->statut !== StatutCaisse::OUVERT, new LogicException('La caisse n\'est pas ouverte.'));

        return DB::transaction(function () use ($caisse, $soldePhysique, $motifEcarts, $user) {
            $soldeTheorique = $this->caisseRepo->calculerSoldeTheorique($caisse->id);
            $ecart = $soldeTheorique - $soldePhysique;
            $ecartAbs = abs($ecart);

            $niveau = $this->getEcartLevel($ecartAbs);

            match($niveau) {
                'negligeable' => $this->handleNegligeableEcart($caisse, $ecart, $user),
                'significatif' => $this->handleSignificatifEcart($caisse, $ecart, $motifEcarts, $user),
                'critique' => $this->handleCritiqueEcart($caisse, $ecart, $motifEcarts, $user),
            };

            $this->caisseRepo->cloturer(
                caisseId: $caisse->id,
                soldePhysique: $soldePhysique,
                userId: $user->id,
                observation: $motifEcarts,
                ecartConst: $ecart,
                statutEcart: $niveau === 'critique' ? 'enquete' : 'valide'
            );

            return [
                'caisse'          => $caisse,
                'solde_theorique' => $soldeTheorique,
                'solde_physique'  => $soldePhysique,
                'ecart'           => $ecart,
                'niveau_ecart'    => $niveau,
                'message'         => $this->getEcartMessage($niveau, $ecartAbs),
            ];
        });
    }

    private function getEcartLevel(float $ecartAbs): string
    {
        if ($ecartAbs <= self::SEUIL_ECARD_NEGLIGEABLE) return 'negligeable';
        if ($ecartAbs <= self::SEUIL_ECARD_SIGNIFICATIF) return 'significatif';
        return 'critique';
    }

    private function handleNegligeableEcart(Caisse $caisse, float $ecart, User $user): void
    {
        if (abs($ecart) > 0.01) {
            $this->createEcartMouvement($caisse, abs($ecart), $ecart > 0 ? 'sortie' : 'entree', $user->id);
        }
        $caisse->update([
            'ecart_constate' => $ecart,
            'statut_ecart' => 'valide',
            'validateur_ecart_id' => $user->id,
            'date_validation_ecart' => now(),
            'motif_ecart' => 'Écart négligeable - régularisation automatique',
        ]);
    }

    private function handleSignificatifEcart(Caisse $caisse, float $ecart, ?string $motif, User $user): void
    {
        if (!$this->isAuthorizedViewer($user)) {
            throw new LogicException("Écart de " . number_format(abs($ecart), 2, ',', ' ') . " FCFA : validation d'un responsable requise.");
        }
        if (empty($motif) || strlen($motif) < 10) {
            throw new LogicException('Veuillez fournir une explication détaillée pour cet écart (min. 10 caractères).');
        }
        if (abs($ecart) > 0.01) {
            $this->createEcartMouvement($caisse, abs($ecart), $ecart > 0 ? 'sortie' : 'entree', $user->id);
        }
        $caisse->update([
            'ecart_constate' => $ecart,
            'motif_ecart' => $motif,
            'statut_ecart' => 'valide',
            'validateur_ecart_id' => $user->id,
            'date_validation_ecart' => now(),
        ]);
    }

    private function handleCritiqueEcart(Caisse $caisse, float $ecart, ?string $motif, User $user): void
    {
        if ((int) $user->role !== Role::ADMIN && (int) $user->role !== Role::DIRECTEUR) {
            throw new LogicException("Écart CRITIQUE de " . number_format(abs($ecart), 2, ',', ' ') . " FCFA : clôture bloquée. Contactez la direction.");
        }
        if (empty($motif) || strlen($motif) < 50) {
            throw new LogicException('Écart critique : un rapport détaillé (min. 50 caractères) est obligatoire.');
        }
        if (abs($ecart) > 0.01) {
            $this->createEcartMouvement($caisse, abs($ecart), $ecart > 0 ? 'sortie' : 'entree', $user->id, isCritique: true);
        }
        $caisse->update([
            'ecart_constate' => $ecart,
            'motif_ecart' => $motif,
            'statut_ecart' => 'enquete',
            'validateur_ecart_id' => $user->id,
            'date_validation_ecart' => now(),
        ]);
    }

    private function createEcartMouvement(Caisse $caisse, float $montant, string $direction, int $userId, bool $isCritique = false): void
    {
        $typeMvt = $direction === 'sortie' ? TypeMouvement::DECAISSEMENT : TypeMouvement::ENCAISSEMENT;
        $this->mouvementRepo->enregistrer([
            'caisse_id' => $caisse->id,
            'type_mouvement' => $typeMvt,
            'montant' => $montant,
            'motif' => $isCritique ? 'Régularisation écart CRITIQUE (clôture) - Voir rapport' : 'Régularisation écart de caisse (clôture)',
            'utilisateur_id' => $userId,
            'annee_id' => $caisse->annee_id,
            'statut_mouvement' => StatutMouvement::VALIDER,
            'observation' => $isCritique ? 'Écart validé par direction' : null,
        ]);
    }

    private function getEcartMessage(string $niveau, float $ecartAbs): string
    {
        return match($niveau) {
            'negligeable' => "Caisse clôturée. Écart de " . number_format($ecartAbs, 2, ',', ' ') . " FCFA régularisé automatiquement.",
            'significatif' => "Caisse clôturée. Écart de " . number_format($ecartAbs, 2, ',', ' ') . " FCFA validé par responsable.",
            'critique' => "Caisse clôturée. Écart CRITIQUE de " . number_format($ecartAbs, 2, ',', ' ') . " FCFA : rapport enregistré pour audit.",
        };
    }

    // ─────────────────────────────────────────────────────────────
    // 📊 Liste & Détail
    // ─────────────────────────────────────────────────────────────

    public function getCaissesWithAggregates(array $filters = []): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));

        $isViewer = $this->isAuthorizedViewer($user);
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = Caisse::query()
            ->with(['responsable:id,nom,prenom,login', 'utilisateur:id,nom,prenom,login'])
            ->where('etat', TypeStatus::ACTIF)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId));

        if (!$isViewer) {
            $query->where('utilisateur_id', $user->id);
        }
        if (!empty($filters['statut']) && is_numeric($filters['statut'])) {
            $query->where('statut', (int) $filters['statut']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_ouverture', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_ouverture', '<=', $filters['date_fin']);
        }

        $caisses = $query->orderByDesc('date_ouverture')->get()
            ->map(fn($c) => $this->formatCaisse($c, $isViewer));

        $aggregates = [
            'total_caisses' => $caisses->count(),
            'ouvertes' => $caisses->filter(fn($c) => (int) $c->statut === StatutCaisse::OUVERT)->count(),
            'cloturees' => $caisses->filter(fn($c) => (int) $c->statut === StatutCaisse::CLOTURE)->count(),
            'fonds_initial_total' => (float) $caisses->sum('solde_initial'),
            'fonds_final_total' => (float) $caisses->filter(fn($c) => (int) $c->statut === StatutCaisse::CLOTURE)->sum('solde_final'),
        ];

        return ['data' => $caisses, 'aggregates' => $aggregates, 'meta' => ['user_role' => $user->role]];
    }

    public function getCaisseDetail(int $id): ?array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));
        $isViewer = $this->isAuthorizedViewer();

        $caisse = Caisse::query()
            ->with(['responsable:id,nom,prenom,login', 'utilisateur:id,nom,prenom,login'])
            ->where('etat', TypeStatus::ACTIF)
            ->where('id', $id)
            ->when(!$isViewer, fn($q) => $q->where('utilisateur_id', $user->id))
            ->first();

        if (!$caisse) return null;

        $encaissements = $this->getEncaissementsWithPaymentRef($caisse->id, $isViewer);
        $decaissements = $this->getDecaissementsWithExpenseRef($caisse->id, $isViewer);
        $soldeActuel = (int) $caisse->statut === StatutCaisse::OUVERT ? $this->caisseRepo->calculerSoldeTheorique($caisse->id) : null;

        return [
            'caisse' => $this->formatCaisse($caisse, $isViewer),
            'solde_actuel' => $soldeActuel,
            'encaissements' => $encaissements,
            'decaissements' => $decaissements,
            'stats' => [
                'total_encaisse' => (float) collect($encaissements)->sum('montant'),
                'total_decaisse' => (float) collect($decaissements)->sum('montant'),
                'count_encaisse' => count($encaissements),
                'count_decaisse' => count($decaissements),
            ],
        ];
    }

    private function getEncaissementsWithPaymentRef(int $caisseId, bool $isViewer): array
    {
        return $this->mouvementRepo->getModel()
            ->with(['paiement:id,reference,libelle', 'operateur:id,nom,prenom,login'])
            ->where('caisse_id', $caisseId)
            ->where('type_mouvement', TypeMouvement::ENCAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->where('etat', TypeStatus::ACTIF)
            ->orderByDesc('date_mouvement')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id, 'reference' => $m->reference, 'montant' => (float) $m->montant,
                'motif' => $m->motif, 'date' => $this->formatDateTime($m->date_mouvement),
                'effectue_par' => $m->operateur?->nom . ' ' . $m->operateur?->prenom,
                'paiement_reference' => $m->paiement?->reference, 'paiement_libelle' => $m->paiement?->libelle, 'paiement_id' => $m->paiement_id,
            ])->toArray();
    }

    private function getDecaissementsWithExpenseRef(int $caisseId, bool $isViewer): array
    {
        return $this->mouvementRepo->getModel()
            ->with(['depense:id,reference,libelle', 'operateur:id,nom,prenom,login'])
            ->where('caisse_id', $caisseId)
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->where('etat', TypeStatus::ACTIF)
            ->orderByDesc('date_mouvement')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id, 'reference' => $m->reference, 'montant' => (float) $m->montant,
                'motif' => $m->motif, 'beneficiaire' => $m->beneficiaire, 'date' => $this->formatDateTime($m->date_mouvement),
                'effectue_par' => $m->operateur?->nom . ' ' . $m->operateur?->prenom,
                'depense_reference' => $m->depense?->reference, 'depense_libelle' => $m->depense?->libelle, 'depense_id' => $m->depense_id,
            ])->toArray();
    }

    public function formatCaisse(Caisse $c, bool $isViewer): array
    {
        return [
            'id' => $c->id, 'libelle' => $c->libelle,
            'solde_initial' => $c->solde_initial !== null ? (float) $c->solde_initial : null,
            'solde_final' => $c->solde_final !== null ? (float) $c->solde_final : null,
            'date_ouverture' => $this->formatDateTime($c->date_ouverture),
            'date_cloture' => $this->formatDateTime($c->date_cloture),
            'statut' => (int) $c->statut, 'statut_label' => $this->getStatutLabel((int) $c->statut),
            'annee_id' => $c->annee_id,
            'responsable_nom' => $isViewer ? ($c->responsable?->nom . ' ' . $c->responsable?->prenom) : null,
            'createur_nom' => $isViewer ? ($c->utilisateur?->nom . ' ' . $c->utilisateur?->prenom) : null,
        ];
    }

    private function getStatutLabel(int $statut): string
    {
        return match($statut) {
            StatutCaisse::FERME => 'Fermée', StatutCaisse::OUVERT => 'Ouverte', StatutCaisse::CLOTURE => 'Clôturée', default => 'Inconnu'
        };
    }

    private function formatDateTime(?string $datetime): ?string
    {
        return $datetime ? Carbon::parse($datetime)->format('Y-m-d H:i') : null;
    }

    public function getSoldeActuel(int $caisseId): float
    {
        $caisse = $this->caisseRepo->find($caisseId);
        throw_if(!$caisse, new LogicException('Caisse introuvable.'));
        throw_if((int) $caisse->statut !== StatutCaisse::OUVERT, new LogicException('La caisse doit être ouverte pour consulter le solde.'));
        return $this->caisseRepo->calculerSoldeTheorique($caisse->id);
    }

    // ─────────────────────────────────────────────────────────────
    // 📈 Reporting : Écarts par période & caissier
    // ─────────────────────────────────────────────────────────────

    /**
     * Rapport des écarts de caisse par période et caissier
     */
    public function getEcartReport(array $filters = []): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));
        throw_unless($this->isAuthorizedViewer($user), new LogicException('Accès réservé aux administrateurs ou directeurs.'));

        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $period = $filters['period'] ?? 'month'; // day, week, month, year
        $groupByCaissier = $filters['by_caissier'] ?? true;

        $query = Caisse::query()
            ->selectRaw('
                caisses.responsable_id,
                users.nom, users.prenom, users.login,
                COUNT(caisses.id) as total_clotures,
                SUM(CASE WHEN caisses.ecart_constate IS NOT NULL THEN 1 ELSE 0 END) as avec_ecart,
                SUM(CASE WHEN caisses.ecart_constate > 0 THEN caisses.ecart_constate ELSE 0 END) as total_excedents,
                SUM(CASE WHEN caisses.ecart_constate < 0 THEN ABS(caisses.ecart_constate) ELSE 0 END) as total_manquants,
                AVG(ABS(caisses.ecart_constate)) as ecart_moyen
            ')
            ->join('users', 'users.id', '=', 'caisses.responsable_id')
            ->where('caisses.etat', TypeStatus::ACTIF)
            ->where('caisses.statut', StatutCaisse::CLOTURE)
            ->whereNotNull('caisses.date_cloture')
            ->when($anneeId, fn($q) => $q->where('caisses.annee_id', $anneeId))
            ->when(!empty($filters['date_debut']), fn($q) => $q->whereDate('caisses.date_cloture', '>=', $filters['date_debut']))
            ->when(!empty($filters['date_fin']), fn($q) => $q->whereDate('caisses.date_cloture', '<=', $filters['date_fin']))
            ->when(!empty($filters['caissier_id']), fn($q) => $q->where('caisses.responsable_id', (int) $filters['caissier_id']))
            ->when(!empty($filters['statut_ecart']), fn($q) => $q->where('caisses.statut_ecart', $filters['statut_ecart']));

        // Groupement temporel
        $dateGroup = match($period) {
            'day' => "DATE_FORMAT(caisses.date_cloture, '%Y-%m-%d')",
            'week' => "CONCAT(YEAR(caisses.date_cloture), '-S', WEEK(caisses.date_cloture))",
            'month' => "DATE_FORMAT(caisses.date_cloture, '%Y-%m')",
            'year' => "YEAR(caisses.date_cloture)",
            default => "DATE_FORMAT(caisses.date_cloture, '%Y-%m')"
        };

        $query->addSelect(DB::raw("$dateGroup as periode"));

        if ($groupByCaissier) {
            $query->groupBy('caisses.responsable_id', 'users.nom', 'users.prenom', 'users.login', 'periode');
        } else {
            $query->groupBy('periode');
        }

        $query->orderBy('periode')->orderBy('users.nom');

        $results = $query->get()->map(fn($row) => [
            'periode' => $row->periode,
            'caissier_id' => $groupByCaissier ? $row->responsable_id : null,
            'caissier_nom' => $groupByCaissier ? trim("{$row->nom} {$row->prenom}") : null,
            'caissier_login' => $groupByCaissier ? $row->login : null,
            'total_clotures' => (int) $row->total_clotures,
            'avec_ecart' => (int) $row->avec_ecart,
            'taux_ecart' => $row->total_clotures > 0 ? round(($row->avec_ecart / $row->total_clotures) * 100, 2) : 0,
            'total_excedents' => (float) ($row->total_excedents ?? 0),
            'total_manquants' => (float) ($row->total_manquants ?? 0),
            'ecart_net' => (float) (($row->total_excedents ?? 0) - ($row->total_manquants ?? 0)),
            'ecart_moyen' => (float) ($row->ecart_moyen ?? 0),
        ]);

        // Totaux globaux
        $global = [
            'total_clotures' => $results->sum('total_clotures'),
            'total_avec_ecart' => $results->sum('avec_ecart'),
            'total_excedents' => (float) $results->sum('total_excedents'),
            'total_manquants' => (float) $results->sum('total_manquants'),
            'ecart_net_global' => (float) ($results->sum('total_excedents') - $results->sum('total_manquants')),
        ];

        return ['data' => $results->toArray(), 'global' => $global, 'meta' => ['period' => $period, 'by_caissier' => $groupByCaissier]];
    }

    // ─────────────────────────────────────────────────────────────
    // 📋 Mouvements par caissier & période
    // ─────────────────────────────────────────────────────────────

    /**
     * Liste des mouvements effectués par un caissier sur une période
     */
    public function getMouvementsByCaissier(int $caissierId, array $filters = []): array
    {
        $user = $this->getCurrentUser();
        throw_if(!$user, new LogicException('Utilisateur non authentifié.'));
        
        // 🔐 Contrôle d'accès : seul le caissier lui-même ou un admin peut voir ses mouvements
        $isSelf = $caissierId === $user->id;
        $isAdmin = $this->isAuthorizedViewer($user);
        throw_unless($isSelf || $isAdmin, new LogicException('Accès non autorisé aux mouvements de ce caissier.'));

        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();
        $type = $filters['type'] ?? 'all'; // all, entree, sortie

        $query = $this->mouvementRepo->getModel()
            ->with(['caisse:id,libelle', 'depense:id,libelle,reference', 'paiement:id,libelle,reference'])
            ->where('utilisateur_id', $caissierId)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->where('etat', TypeStatus::ACTIF)
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(!empty($filters['date_debut']), fn($q) => $q->whereDate('date_mouvement', '>=', $filters['date_debut']))
            ->when(!empty($filters['date_fin']), fn($q) => $q->whereDate('date_mouvement', '<=', $filters['date_fin']))
            ->when($type === 'entree', fn($q) => $q->where('type_mouvement', TypeMouvement::ENCAISSEMENT))
            ->when($type === 'sortie', fn($q) => $q->where('type_mouvement', TypeMouvement::DECAISSEMENT))
            ->orderByDesc('date_mouvement');

        $mouvements = $query->get()->map(fn($m) => [
            'id' => $m->id, 'reference' => $m->reference,
            'type' => (int) $m->type_mouvement === TypeMouvement::ENCAISSEMENT ? 'entree' : 'sortie',
            'montant' => (float) $m->montant, 'motif' => $m->motif,
            'beneficiaire' => $m->beneficiaire, 'date' => $this->formatDateTime($m->date_mouvement),
            'caisse_libelle' => $m->caisse?->libelle, 'caisse_id' => $m->caisse_id,
            'lie_depense' => $m->depense ? ['id' => $m->depense_id, 'libelle' => $m->depense->libelle, 'reference' => $m->depense->reference] : null,
            'lie_paiement' => $m->paiement ? ['id' => $m->paiement_id, 'libelle' => $m->paiement->libelle, 'reference' => $m->paiement->reference] : null,
        ]);

        $stats = [
            'total_mouvements' => $mouvements->count(),
            'total_entrees' => (float) $mouvements->where('type', 'entree')->sum('montant'),
            'total_sorties' => (float) $mouvements->where('type', 'sortie')->sum('montant'),
            'solde_net' => (float) ($mouvements->where('type', 'entree')->sum('montant') - $mouvements->where('type', 'sortie')->sum('montant')),
        ];

        return ['data' => $mouvements->toArray(), 'stats' => $stats, 'meta' => ['caissier_id' => $caissierId, 'period' => [$filters['date_debut'] ?? null, $filters['date_fin'] ?? null]]];
    }
}