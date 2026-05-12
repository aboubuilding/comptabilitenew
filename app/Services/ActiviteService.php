<?php

namespace App\Services;

use App\Repositories\Eloquent\ActiviteRepository;
use App\Repositories\Eloquent\DetailRepository;
use Illuminate\Support\Facades\DB;

class ActiviteService extends BaseService
{
    protected ActiviteRepository $activiteRepo;
    protected DetailRepository $detailPaiementRepo;
    protected ?int $anneeId;
    protected bool $isAdminOrDirector;

    public function __construct(
        ActiviteRepository $activiteRepo,
        DetailRepository $detailPaiementRepo
    ) {
        parent::__construct($activiteRepo);
        $this->activiteRepo = $activiteRepo;
        $this->detailPaiementRepo = $detailPaiementRepo;

        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
        $user = auth()->user();
        $this->isAdminOrDirector = in_array($user->role ?? null, ['admin', 'directeur', 1, 3]);
    }

    /**
     * Liste toutes les activités avec statistiques (inscrits et montant global)
     */
    public function getAllWithStats(): array
    {
        if (!$this->anneeId) {
            return $this->formatResponse(true, 'Aucune année active', []);
        }

        $activites = $this->activiteRepo->activeQuery()
            ->where('annee_id', $this->anneeId)
            ->with('niveau:id,libelle')
            ->get();

        $stats = $this->detailPaiementRepo->activeQuery()
            ->select(
                'activite_id',
                DB::raw('COUNT(DISTINCT inscription_id) as nb_inscrits'),
                DB::raw('SUM(montant) as montant_total')
            )
            ->where('type_paiement', 6)
            ->where('statut_paiement', 1)
            ->where('annee_id', $this->anneeId)
            ->whereNotNull('activite_id')
            ->groupBy('activite_id')
            ->get()
            ->keyBy('activite_id');

        $data = $activites->map(function ($activite) use ($stats) {
            $stat = $stats->get($activite->id);
            return [
                'id'             => $activite->id,
                'libelle'        => $activite->libelle,
                'description'    => $activite->description,
                'montant'        => $activite->montant,
                'niveau'         => $activite->niveau?->libelle,
                'etat'           => $activite->etat,
                'nb_inscrits'    => $this->isAdminOrDirector ? ($stat->nb_inscrits ?? 0) : null,
                'montant_global' => $this->isAdminOrDirector ? ($stat->montant_total ?? 0) : null,
            ];
        });

        return $this->formatResponse(true, 'Liste des activités', $data);
    }

    /**
     * Récupère une activité par son ID (surcharge pour injection annee_id)
     */
    public function show(int $id): array
    {
        try {
            $activite = $this->activiteRepo->activeQuery()
                ->where('annee_id', $this->anneeId)
                ->findOrFail($id);
            return $this->formatResponse(true, '', $activite);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Activité introuvable');
        }
    }

    /**
     * Création avec année automatique
     */
    public function store(array $validatedData): array
    {
        if (!$this->anneeId) {
            return $this->formatResponse(false, 'Année scolaire non définie en session.');
        }
        $validatedData['annee_id'] = $this->anneeId;
        // Utiliser la logique du parent (mais on peut aussi réimplémenter)
        return parent::store($validatedData);
    }

    /**
     * Vérifie si l'activité a des paiements associés
     */
    public function hasRelatedPayments(int $id): bool
    {
        return $this->detailPaiementRepo->activeQuery()
            ->where('activite_id', $id)
            ->exists();
    }
}
