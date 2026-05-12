<?php

namespace App\Services;

use App\Repositories\Eloquent\AssignationEleveBusRepository;
use App\Repositories\Eloquent\AbonnementBusRepository;
use App\Repositories\Eloquent\VoitureRepository;
use App\Repositories\Eloquent\ZoneRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusAssignmentService extends BaseService
{
    protected string $entityName = 'Assignation bus';
    protected AssignationEleveBusRepository $assignationRepo;
    protected AbonnementBusRepository $abonnementRepo;
    protected VoitureRepository $voitureRepo;
    protected ZoneRepository $zoneRepo;

    // Constantes
    const SENS_ALLER = 1;
    const SENS_RETOUR = 2;
    const SENS_ALLER_RETOUR = 3;
    const STATUT_ACTIF = 1;
    const STATUT_INACTIF = 0;

    public function __construct(
        AssignationEleveBusRepository $assignationRepo,
        AbonnementBusRepository $abonnementRepo,
        VoitureRepository $voitureRepo,
        ZoneRepository $zoneRepo
    ) {
        parent::__construct($assignationRepo);
        $this->assignationRepo = $assignationRepo;
        $this->abonnementRepo = $abonnementRepo;
        $this->voitureRepo = $voitureRepo;
        $this->zoneRepo = $zoneRepo;
    }

    /**
     * Liste des assignations avec filtres
     */
    public function listAssignations(array $filters = []): array
    {
        $query = $this->assignationRepo->activeQuery()
            ->with(['abonnementBus.inscription.eleve', 'voiture', 'zone']);

        if (!empty($filters['abonnement_bus_id'])) {
            $query->where('abonnement_bus_id', $filters['abonnement_bus_id']);
        }
        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['zone_id'])) {
            $query->where('zone_id', $filters['zone_id']);
        }
        if (isset($filters['sens']) && in_array($filters['sens'], [1,2,3])) {
            $query->where('sens', $filters['sens']);
        }
        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('abonnementBus.inscription.eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)->orWhere('prenom', 'like', $search);
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        $assignations = $query->orderBy('date_debut', 'desc')->paginate($perPage);

        $data = $assignations->map(fn($a) => [
            'id'                => $a->id,
            'eleve'             => $a->abonnementBus?->inscription?->eleve?->nom . ' ' . $a->abonnementBus?->inscription?->eleve?->prenom,
            'voiture'           => $a->voiture?->libelle,
            'zone'              => $a->zone?->libelle,
            'sens'              => $a->sens,
            'sens_label'        => $this->getSensLabel($a->sens),
            'date_debut'        => $a->date_debut->format('d/m/Y'),
            'date_fin'          => $a->date_fin?->format('d/m/Y'),
            'statut'            => $a->statut,
            'statut_label'      => $a->statut == self::STATUT_ACTIF ? 'Actif' : 'Inactif',
            'motif'             => $a->motif,
        ]);

        return $this->formatResponse(true, 'Liste des assignations', $data, [
            'pagination' => [
                'current_page' => $assignations->currentPage(),
                'last_page'    => $assignations->lastPage(),
                'per_page'     => $assignations->perPage(),
                'total'        => $assignations->total(),
            ]
        ]);
    }

    /**
     * Crée une assignation avec gestion des conflits
     */
    public function store(array $validatedData): array
    {
        // Vérifications préalables
        $abonnement = $this->abonnementRepo->find($validatedData['abonnement_bus_id']);
        if (!$abonnement || $abonnement->statut != 1) {
            return $this->formatResponse(false, 'Abonnement bus invalide ou inactif');
        }

        $voiture = $this->voitureRepo->find($validatedData['voiture_id']);
        if (!$voiture) {
            return $this->formatResponse(false, 'Véhicule introuvable');
        }

        $zone = $this->zoneRepo->find($validatedData['zone_id']);
        if (!$zone) {
            return $this->formatResponse(false, 'Zone introuvable');
        }

        // Vérifier qu'il n'y a pas déjà une assignation active pour ce sens et cet abonnement
        $exists = $this->assignationRepo->activeQuery()
            ->where('abonnement_bus_id', $validatedData['abonnement_bus_id'])
            ->where('statut', self::STATUT_ACTIF)
            ->where('sens', $validatedData['sens'])
            ->exists();

        if ($exists) {
            return $this->formatResponse(false, 'Cet élève a déjà une assignation active pour ce sens.');
        }

        try {
            $validatedData['statut'] = self::STATUT_ACTIF;
            $assignation = $this->assignationRepo->create($validatedData);
            return $this->formatResponse(true, 'Assignation créée avec succès', $assignation);
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Met fin à une assignation (date_fin = aujourd'hui, statut = inactif)
     */
    public function terminerAssignation(int $id): array
    {
        try {
            $assignation = $this->assignationRepo->findOrFail($id);
            $this->assignationRepo->update($id, [
                'date_fin' => now(),
                'statut'   => self::STATUT_INACTIF,
            ]);
            return $this->formatResponse(true, 'Assignation terminée');
        } catch (\Exception $e) {
            return $this->formatResponse(false, 'Assignation introuvable');
        }
    }

    /**
     * Récupère les assignations actives pour un élève (abonnement)
     */
    public function getByAbonnement(int $abonnementId): array
    {
        $assignations = $this->assignationRepo->activeQuery()
            ->with(['voiture', 'zone'])
            ->where('abonnement_bus_id', $abonnementId)
            ->where('statut', self::STATUT_ACTIF)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'voiture'    => $a->voiture?->libelle,
                'zone'       => $a->zone?->libelle,
                'sens_label' => $this->getSensLabel($a->sens),
                'date_debut' => $a->date_debut->format('d/m/Y'),
                'date_fin'   => $a->date_fin?->format('d/m/Y'),
            ]);
        return $this->formatResponse(true, '', $assignations);
    }

    /**
     * Récupère les assignations pour un bus (voiture)
     */
    public function getByVoiture(int $voitureId): array
    {
        $assignations = $this->assignationRepo->activeQuery()
            ->with(['abonnementBus.inscription.eleve', 'zone'])
            ->where('voiture_id', $voitureId)
            ->where('statut', self::STATUT_ACTIF)
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'eleve'      => $a->abonnementBus?->inscription?->eleve?->nom . ' ' . $a->abonnementBus?->inscription?->eleve?->prenom,
                'zone'       => $a->zone?->libelle,
                'sens_label' => $this->getSensLabel($a->sens),
            ]);
        return $this->formatResponse(true, '', $assignations);
    }

    /**
     * Libellé du sens
     */
    private function getSensLabel(int $sens): string
    {
        return match ($sens) {
            self::SENS_ALLER => 'Aller',
            self::SENS_RETOUR => 'Retour',
            self::SENS_ALLER_RETOUR => 'Aller-Retour',
            default => 'Inconnu',
        };
    }
}
