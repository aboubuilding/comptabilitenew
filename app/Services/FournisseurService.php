<?php
namespace App\Services;

use App\Repositories\Interfaces\FournisseurRepositoryInterface;
use App\Repositories\Interfaces\AchatRepositoryInterface;
use Illuminate\Support\Collection;

class FournisseurService extends BaseService
{
    protected string $entityName = 'Fournisseur';
    protected array $defaultSelectFields = ['id', 'raison_social', 'nom_contact', 'telephone_contact', 'adresse', 'etat'];
    protected AchatRepositoryInterface $achatRepo;

    public function __construct(
        FournisseurRepositoryInterface $repo,
        AchatRepositoryInterface $achatRepo
    ) {
        parent::__construct($repo);
        $this->achatRepo = $achatRepo;
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes spécifiques (non héritées)
    // ─────────────────────────────────────────────────────────────

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste personnalisée avec format spécifique (remplace la méthode list() si appelée directement)
     */
    public function listFournisseurs(array $filters = []): array
    {
        $query = $this->repo->activeQuery();

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('raison_social', 'like', $search)
                    ->orWhere('nom_contact', 'like', $search)
                    ->orWhere('telephone_contact', 'like', $search);
            });
        }
        if (isset($filters['etat']) && in_array($filters['etat'], [0, 1])) {
            $query->where('etat', $filters['etat']);
        }

        $perPage = $filters['per_page'] ?? 15;
        $fournisseurs = $query->orderBy('raison_social')->paginate($perPage);

        $data = $fournisseurs->map(fn($f) => [
            'id'                => $f->id,
            'raison_social'     => $f->raison_social,
            'nom_contact'       => $f->nom_contact,
            'telephone_contact' => $f->telephone_contact,
            'adresse'           => $f->adresse,
            'etat'              => $f->etat,
            'etat_label'        => $f->etat ? 'Actif' : 'Inactif',
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $fournisseurs->currentPage(),
                'last_page'    => $fournisseurs->lastPage(),
                'per_page'     => $fournisseurs->perPage(),
                'total'        => $fournisseurs->total(),
            ]
        ];
    }

    /**
     * Surcharge de destroy : on désactive (etat=0) au lieu de suppression logique
     */
    public function destroy(int $id): array
    {
        try {
            $this->repo->update($id, ['etat' => 0]);
            return $this->formatResponse(true, "{$this->entityName} désactivé.");
        } catch (\Exception $e) {
            return $this->formatResponse(false, "{$this->entityName} introuvable.");
        }
    }

    /**
     * Liste pour selects (dropdown) au format Collection brute
     * (La méthode parent getForSelect retourne un tableau formaté, donc on garde celle-ci si nécessaire)
     */
    public function getForSelect(): Collection
    {
        return $this->repo->activeQuery()
            ->where('etat', 1)
            ->orderBy('raison_social')
            ->get(['id', 'raison_social']);
    }

    /**
     * Statistiques des achats par fournisseur
     */
    public function getStatsAchats(int $fournisseurId, ?string $dateDebut, ?string $dateFin): array
    {
        $anneeId = $this->getCurrentAnneeId();

        $query = $this->achatRepo->activeQuery()
            ->where('fournisseur_id', $fournisseurId)
            ->where('annee_id', $anneeId);

        if ($dateDebut) {
            $query->whereDate('date_achat', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->whereDate('date_achat', '<=', $dateFin);
        }

        $totalDepense = $query->sum('montant_total');
        $nombreAchats = $query->count();

        $achats = $query->orderBy('date_achat', 'desc')
            ->get(['id', 'date_achat', 'reference', 'montant_total'])
            ->map(fn($a) => [
                'id' => $a->id,
                'date' => $a->date_achat->format('Y-m-d'),
                'reference' => $a->reference,
                'montant' => $a->montant_total,
            ]);

        return [
            'total_depense' => $totalDepense,
            'nombre_achats' => $nombreAchats,
            'achats'        => $achats,
        ];
    }
}
