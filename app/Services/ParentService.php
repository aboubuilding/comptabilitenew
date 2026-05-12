<?php

namespace App\Services;

use App\Repositories\Interfaces\ParentEleveRepositoryInterface;
use App\Repositories\Interfaces\InscriptionRepositoryInterface;
use App\Repositories\Interfaces\PaiementRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ParentService extends BaseService
{
    protected string $entityName = 'Parent';
    protected array $defaultSelectFields = [
        'id', 'nom_parent', 'prenom_parent', 'telephone', 'whatsapp', 'email', 'quartier', 'adresse', 'profession', 'is_principal', 'role', 'annee_id', 'etat'
    ];

    protected InscriptionRepositoryInterface $inscriptionRepo;
    protected PaiementRepositoryInterface $paiementRepo;

    public function __construct(
        ParentEleveRepositoryInterface $parentRepo,
        InscriptionRepositoryInterface $inscriptionRepo,
        PaiementRepositoryInterface $paiementRepo
    ) {
        parent::__construct($parentRepo);
        $this->inscriptionRepo = $inscriptionRepo;
        $this->paiementRepo = $paiementRepo;
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des parents avec agrégats pour l'année courante
     */
    public function listWithAggregates(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return ['data' => collect(), 'aggregates' => $this->emptyAggregates()];
        }

        // Sous-requête : nombre d'enfants par parent
        $enfantsCountSub = $this->inscriptionRepo->getModel()->newQuery()
            ->select('parent_id', DB::raw('COUNT(*) as enfants_count'))
            ->where('annee_id', $anneeId)
            ->where('etat', 1)
            ->groupBy('parent_id');

        // Sous-requête : CA prévu total par parent
        $caPrevuSub = $this->inscriptionRepo->getModel()->newQuery()
            ->select('parent_id', DB::raw('
                COALESCE(SUM(frais_scolarite),0)
                + COALESCE(SUM(frais_inscription),0)
                + COALESCE(SUM(frais_assurance),0)
                + COALESCE(SUM(frais_cantine),0)
                + COALESCE(SUM(frais_bus),0)
                + COALESCE(SUM(frais_livre),0)
                + COALESCE(SUM(frais_examen),0)
                - COALESCE(SUM(remise_scolarite),0) as ca_prevu
            '))
            ->where('annee_id', $anneeId)
            ->where('etat', 1)
            ->groupBy('parent_id');

        // Sous-requête : total payé par parent via les paiements
        $payeSub = $this->paiementRepo->getModel()->newQuery()
            ->select('inscriptions.parent_id', DB::raw('SUM(paiements.montant) as total_paye'))
            ->join('inscriptions', 'paiements.inscription_id', '=', 'inscriptions.id')
            ->where('paiements.annee_id', $anneeId)
            ->where('paiements.etat', 1)
            ->where('inscriptions.etat', 1)
            ->groupBy('inscriptions.parent_id');

        $query = $this->repo->getModel()->newQuery()
            ->leftJoinSub($enfantsCountSub, 'enfants', 'parent_eleves.id', '=', 'enfants.parent_id')
            ->leftJoinSub($caPrevuSub, 'ca', 'parent_eleves.id', '=', 'ca.parent_id')
            ->leftJoinSub($payeSub, 'paye', 'parent_eleves.id', '=', 'paye.parent_id')
            ->select(
                'parent_eleves.*',
                DB::raw('COALESCE(enfants.enfants_count, 0) as enfants_count'),
                DB::raw('COALESCE(ca.ca_prevu, 0) as ca_prevu'),
                DB::raw('COALESCE(paye.total_paye, 0) as total_paye')
            )
            ->where('parent_eleves.etat', 1)
            ->orderBy('parent_eleves.nom_parent')
            ->orderBy('parent_eleves.prenom_parent');

        // Filtres
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nom_parent', 'like', $search)
                    ->orWhere('prenom_parent', 'like', $search)
                    ->orWhere('telephone', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }
        if (!empty($filters['has_whatsapp'])) {
            $query->whereNotNull('whatsapp')->where('whatsapp', '!=', '');
        }

        $perPage = $filters['per_page'] ?? 15;
        $parents = $query->paginate($perPage);

        $aggregates = $this->computeAggregates($anneeId, $filters);

        $data = $parents->map(fn($parent) => [
            'id'            => $parent->id,
            'nom'           => $parent->nom_parent,
            'prenom'        => $parent->prenom_parent,
            'telephone'     => $parent->telephone,
            'whatsapp'      => $parent->whatsapp,
            'email'         => $parent->email,
            'enfants_count' => (int) $parent->enfants_count,
            'ca_prevu'      => (float) $parent->ca_prevu,
            'total_paye'    => (float) $parent->total_paye,
            'reste_a_payer' => (float) $parent->ca_prevu - (float) $parent->total_paye,
        ]);

        return [
            'data'       => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $parents->currentPage(),
                'last_page'    => $parents->lastPage(),
                'per_page'     => $parents->perPage(),
                'total'        => $parents->total(),
            ]
        ];
    }

    /**
     * Fiche complète d'un parent : enfants, CA, paiements, etc.
     */
    public function getFicheParent(int $parentId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $parent = $this->repo->findOrFail($parentId);

        $inscriptions = $this->inscriptionRepo->activeQuery()
            ->with(['eleve', 'cycle', 'niveau', 'classe'])
            ->where('parent_id', $parentId)
            ->where('annee_id', $anneeId)
            ->get();

        $caPrevuTotal = 0;
        $totalPaye = 0;
        $enfantsDetails = [];

        foreach ($inscriptions as $ins) {
            $caEnfant = ($ins->frais_scolarite ?? 0)
                + ($ins->frais_inscription ?? 0)
                + ($ins->frais_assurance ?? 0)
                + ($ins->frais_cantine ?? 0)
                + ($ins->frais_bus ?? 0)
                + ($ins->frais_livre ?? 0)
                + ($ins->frais_examen ?? 0)
                - ($ins->remise_scolarite ?? 0);
            $caPrevuTotal += $caEnfant;

            $paye = $this->paiementRepo->activeQuery()
                ->where('inscription_id', $ins->id)
                ->where('annee_id', $anneeId)
                ->sum('montant');
            $totalPaye += $paye;

            $enfantsDetails[] = [
                'eleve_id'    => $ins->eleve->id,
                'nom'         => $ins->eleve->nom,
                'prenom'      => $ins->eleve->prenom,
                'matricule'   => $ins->eleve->matricule,
                'cycle'       => $ins->cycle?->libelle,
                'niveau'      => $ins->niveau?->libelle,
                'classe'      => $ins->classe?->libelle,
                'ca_prevu'    => $caEnfant,
                'paye'        => $paye,
                'reste'       => $caEnfant - $paye,
            ];
        }

        $resteAPayer = $caPrevuTotal - $totalPaye;

        return [
            'parent'            => $parent,
            'enfants'           => $enfantsDetails,
            'ca_prevu_total'    => $caPrevuTotal,
            'total_paye'        => $totalPaye,
            'reste_a_payer'     => $resteAPayer,
        ];
    }

    /**
     * Mise à jour des informations du parent (surcharge pour compatibilité)
     */
    public function updateParent(int $parentId, array $data): array
    {
        return $this->update($parentId, $data);
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function emptyAggregates(): array
    {
        return [
            'total_parents'       => 0,
            'nouveaux_parents'    => 0,
            'parents_plus_3_enfants' => 0,
        ];
    }

    private function computeAggregates(int $anneeId, array $filters): array
    {
        // 1. Total parents ayant au moins un enfant inscrit cette année
        $totalParentsQuery = $this->repo->getModel()->newQuery()
            ->whereExists(function ($query) use ($anneeId) {
                $query->select(DB::raw(1))
                    ->from('inscriptions')
                    ->whereColumn('inscriptions.parent_id', 'parent_eleves.id')
                    ->where('inscriptions.annee_id', $anneeId)
                    ->where('inscriptions.etat', 1);
            });

        // 2. Nouveaux parents : tous leurs enfants sont nouveaux (type_inscription = 1)
        $nouveauxParentsQuery = $this->repo->getModel()->newQuery()
            ->whereNotExists(function ($query) use ($anneeId) {
                $query->select(DB::raw(1))
                    ->from('inscriptions')
                    ->whereColumn('inscriptions.parent_id', 'parent_eleves.id')
                    ->where('inscriptions.annee_id', $anneeId)
                    ->where('inscriptions.etat', 1)
                    ->where('inscriptions.type_inscription', '!=', 1);
            })
            ->whereExists(function ($query) use ($anneeId) {
                $query->select(DB::raw(1))
                    ->from('inscriptions')
                    ->whereColumn('inscriptions.parent_id', 'parent_eleves.id')
                    ->where('inscriptions.annee_id', $anneeId)
                    ->where('inscriptions.etat', 1);
            });

        // 3. Parents avec plus de 3 enfants
        $plus3Query = $this->repo->getModel()->newQuery()
            ->whereHas('inscriptions', function ($q) use ($anneeId) {
                $q->where('annee_id', $anneeId)->where('etat', 1);
            })
            ->withCount(['inscriptions as enfants_count' => function ($q) use ($anneeId) {
                $q->where('annee_id', $anneeId)->where('etat', 1);
            }])
            ->having('enfants_count', '>', 3);

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $totalParentsQuery->where(function ($q) use ($search) {
                $q->where('nom_parent', 'like', $search)->orWhere('prenom_parent', 'like', $search);
            });
            $nouveauxParentsQuery->where(function ($q) use ($search) {
                $q->where('nom_parent', 'like', $search)->orWhere('prenom_parent', 'like', $search);
            });
            $plus3Query->where(function ($q) use ($search) {
                $q->where('nom_parent', 'like', $search)->orWhere('prenom_parent', 'like', $search);
            });
        }

        return [
            'total_parents'           => $totalParentsQuery->count(),
            'nouveaux_parents'        => $nouveauxParentsQuery->count(),
            'parents_plus_3_enfants'  => $plus3Query->count(),
        ];
    }
}
