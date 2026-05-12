<?php
namespace App\Services;

use App\Repositories\Interfaces\VoitureRepositoryInterface;
use App\Repositories\Interfaces\ChauffeurRepositoryInterface;
use App\Repositories\Interfaces\AffectationVehiculeRepositoryInterface;
use App\Repositories\Interfaces\EntretienVehiculeRepositoryInterface;
use App\Repositories\Interfaces\CarburantVehiculeRepositoryInterface;
use App\Repositories\Interfaces\AssuranceVehiculeRepositoryInterface;
use Illuminate\Support\Collection;

class ParcAutomobileService extends BaseService
{
    protected string $entityName = 'Véhicule';
    protected array $defaultSelectFields = [
        'id', 'marque', 'modele', 'plaque', 'nombre_place', 'kilometrage_actuel', 'statut', 'couleur', 'date_achat', 'annee_id', 'etat'
    ];

    protected ChauffeurRepositoryInterface $chauffeurRepo;
    protected AffectationVehiculeRepositoryInterface $affectationRepo;
    protected EntretienVehiculeRepositoryInterface $entretienRepo;
    protected CarburantVehiculeRepositoryInterface $carburantRepo;
    protected AssuranceVehiculeRepositoryInterface $assuranceRepo;

    public function __construct(
        VoitureRepositoryInterface $voitureRepo,
        ChauffeurRepositoryInterface $chauffeurRepo,
        AffectationVehiculeRepositoryInterface $affectationRepo,
        EntretienVehiculeRepositoryInterface $entretienRepo,
        CarburantVehiculeRepositoryInterface $carburantRepo,
        AssuranceVehiculeRepositoryInterface $assuranceRepo
    ) {
        parent::__construct($voitureRepo);
        $this->chauffeurRepo = $chauffeurRepo;
        $this->affectationRepo = $affectationRepo;
        $this->entretienRepo = $entretienRepo;
        $this->carburantRepo = $carburantRepo;
        $this->assuranceRepo = $assuranceRepo;
    }

    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    // ======================= VOITURES =======================
    // Les méthodes CRUD de base (store, update, destroy, show) sont héritées de BaseService.
    // Mais nous avons des méthodes spécifiques : listeVoitures, getVoiture, etc.
    // Nous allons surcharger la méthode list? Non, gardons listeVoitures pour la compatibilité.

    public function listeVoitures(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $query = $this->repo->activeQuery();

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('marque', 'like', $search)
                    ->orWhere('modele', 'like', $search)
                    ->orWhere('plaque', 'like', $search)
                    ->orWhere('numero_chassis', 'like', $search);
            });
        }
        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $query->where('statut', $filters['statut']);
        }
        if ($anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $anneeId);
        }

        $perPage = $filters['per_page'] ?? 15;
        $voitures = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = $voitures->map(function ($v) {
            return [
                'id' => $v->id,
                'marque' => $v->marque,
                'modele' => $v->modele,
                'plaque' => $v->plaque,
                'nombre_place' => $v->nombre_place,
                'kilometrage_actuel' => $v->kilometrage_actuel,
                'statut' => $v->statut,
                'statut_label' => $v->statut_label,
                'couleur' => $v->couleur,
                'date_achat' => $v->date_achat?->format('d/m/Y'),
            ];
        });

        return ['data' => $data, 'pagination' => $this->paginationData($voitures)];
    }

    public function getVoiture(int $id)
    {
        return $this->repo->findOrFail($id);
    }

    public function createVoiture(array $data): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (empty($data['annee_id']) && $anneeId) {
            $data['annee_id'] = $anneeId;
        }
        return $this->store($data); // Utilise la méthode store() de BaseService
    }

    public function updateVoiture(int $id, array $data): array
    {
        return $this->update($id, $data);
    }

    public function deleteVoiture(int $id): array
    {
        $voiture = $this->repo->find($id);
        // Vérifier les relations
        if ($this->affectationRepo->activeQuery()->where('voiture_id', $id)->exists() ||
            $this->entretienRepo->activeQuery()->where('voiture_id', $id)->exists() ||
            $this->carburantRepo->activeQuery()->where('voiture_id', $id)->exists()) {
            return $this->formatResponse(false, 'Impossible de supprimer un véhicule avec des historiques.');
        }
        return $this->destroy($id);
    }

    // ======================= CHAUFFEURS =======================
    public function listeChauffeurs(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $query = $this->chauffeurRepo->activeQuery();

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', $search)
                    ->orWhere('prenom', 'like', $search)
                    ->orWhere('permis_conduire', 'like', $search);
            });
        }
        if (isset($filters['statut']) && $filters['statut'] !== '') {
            $query->where('statut', $filters['statut']);
        }
        if ($anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $anneeId);
        }

        $perPage = $filters['per_page'] ?? 15;
        $chauffeurs = $query->orderBy('nom')->paginate($perPage);

        $data = $chauffeurs->map(fn($c) => [
            'id' => $c->id,
            'nom' => $c->nom,
            'prenom' => $c->prenom,
            'permis_conduire' => $c->permis_conduire,
            'telephone' => $c->telephone,
            'statut' => $c->statut,
            'statut_label' => $c->statut ? 'Actif' : 'Inactif',
        ]);

        return ['data' => $data, 'pagination' => $this->paginationData($chauffeurs)];
    }

    public function getChauffeur(int $id)
    {
        return $this->chauffeurRepo->findOrFail($id);
    }

    public function createChauffeur(array $data): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (empty($data['annee_id']) && $anneeId) {
            $data['annee_id'] = $anneeId;
        }
        $data['etat'] = 1;
        $chauffeur = $this->chauffeurRepo->create($data);
        return $this->formatResponse(true, 'Chauffeur créé', $chauffeur);
    }

    public function updateChauffeur(int $id, array $data): array
    {
        $this->chauffeurRepo->update($id, $data);
        $chauffeur = $this->chauffeurRepo->find($id);
        return $this->formatResponse(true, 'Chauffeur mis à jour', $chauffeur);
    }

    public function deleteChauffeur(int $id): array
    {
        if ($this->affectationRepo->activeQuery()->where('chauffeur_id', $id)->exists()) {
            return $this->formatResponse(false, 'Ce chauffeur a des affectations, vous ne pouvez pas le supprimer.');
        }
        $this->chauffeurRepo->delete($id);
        return $this->formatResponse(true, 'Chauffeur supprimé');
    }

    // ======================= AFFECTATIONS =======================
    public function listeAffectations(array $filters = []): array
    {
        $query = $this->affectationRepo->activeQuery()
            ->with(['voiture', 'chauffeur']);

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['chauffeur_id'])) {
            $query->where('chauffeur_id', $filters['chauffeur_id']);
        }
        if (!empty($filters['en_cours']) && $filters['en_cours'] == 1) {
            $query->whereNull('date_fin');
        }
        $anneeId = $this->getCurrentAnneeId();
        if ($anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $anneeId);
        }

        $perPage = $filters['per_page'] ?? 15;
        $affectations = $query->orderBy('date_debut', 'desc')->paginate($perPage);

        $data = $affectations->map(fn($a) => [
            'id' => $a->id,
            'voiture' => $a->voiture->marque . ' ' . ($a->voiture->modele ?? '') . ' - ' . $a->voiture->plaque,
            'chauffeur' => $a->chauffeur ? $a->chauffeur->nom . ' ' . $a->chauffeur->prenom : null,
            'date_debut' => $a->date_debut->format('d/m/Y'),
            'date_fin' => $a->date_fin?->format('d/m/Y'),
            'type_affectation' => $a->type_affectation == 1 ? 'Permanente' : 'Temporaire',
            'est_active' => is_null($a->date_fin),
        ]);

        return ['data' => $data, 'pagination' => $this->paginationData($affectations)];
    }

    public function createAffectation(array $data): array
    {
        // Vérifier que la voiture n'est pas déjà affectée sans date de fin
        $existing = $this->affectationRepo->activeQuery()
            ->where('voiture_id', $data['voiture_id'])
            ->whereNull('date_fin')
            ->first();
        if ($existing) {
            return $this->formatResponse(false, "Ce véhicule est déjà affecté (depuis le {$existing->date_debut}).");
        }

        $anneeId = $this->getCurrentAnneeId();
        if (empty($data['annee_id']) && $anneeId) {
            $data['annee_id'] = $anneeId;
        }
        $data['etat'] = 1;
        $affectation = $this->affectationRepo->create($data);
        return $this->formatResponse(true, 'Affectation créée', $affectation);
    }

    public function terminerAffectation(int $id, ?string $motif = null): array
    {
        $affectation = $this->affectationRepo->find($id);
        if (!$affectation || $affectation->date_fin) {
            return $this->formatResponse(false, 'Affectation introuvable ou déjà terminée.');
        }
        $this->affectationRepo->update($id, [
            'date_fin' => now(),
            'motif' => $motif,
        ]);
        return $this->formatResponse(true, 'Affectation terminée', $affectation);
    }

    // ======================= ENTRETIENS =======================
    public function listeEntretiens(array $filters = []): array
    {
        $query = $this->entretienRepo->activeQuery()
            ->with(['voiture', 'chauffeur']);

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_entretien', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_entretien', '<=', $filters['date_fin']);
        }
        $anneeId = $this->getCurrentAnneeId();
        if ($anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $anneeId);
        }

        $perPage = $filters['per_page'] ?? 15;
        $entretiens = $query->orderBy('date_entretien', 'desc')->paginate($perPage);

        $data = $entretiens->map(fn($e) => [
            'id' => $e->id,
            'voiture' => $e->voiture->plaque,
            'date_entretien' => $e->date_entretien->format('d/m/Y'),
            'type_entretien' => $e->type_entretien,
            'cout' => $e->cout,
            'kilometrage' => $e->kilometrage,
            'chauffeur' => $e->chauffeur?->nom . ' ' . $e->chauffeur?->prenom,
        ]);

        return ['data' => $data, 'pagination' => $this->paginationData($entretiens)];
    }

    public function createEntretien(array $data): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (empty($data['annee_id']) && $anneeId) {
            $data['annee_id'] = $anneeId;
        }
        $data['etat'] = 1;
        $entretien = $this->entretienRepo->create($data);
        return $this->formatResponse(true, 'Entretien enregistré', $entretien);
    }

    // ======================= CARBURANT =======================
    public function listeCarburants(array $filters = []): array
    {
        $query = $this->carburantRepo->activeQuery()
            ->with('voiture');

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_plein', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_plein', '<=', $filters['date_fin']);
        }
        $anneeId = $this->getCurrentAnneeId();
        if ($anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $anneeId);
        }

        $perPage = $filters['per_page'] ?? 15;
        $carburants = $query->orderBy('date_plein', 'desc')->paginate($perPage);

        $data = $carburants->map(fn($c) => [
            'id' => $c->id,
            'voiture' => $c->voiture->plaque,
            'date_plein' => $c->date_plein->format('d/m/Y'),
            'quantite' => $c->quantite_litres,
            'prix_unitaire' => $c->prix_unitaire,
            'montant_total' => $c->montant_total,
            'kilometrage' => $c->kilometrage,
            'station' => $c->station_service,
        ]);

        return ['data' => $data, 'pagination' => $this->paginationData($carburants)];
    }

    public function createCarburant(array $data): array
    {
        // Mettre à jour le kilométrage de la voiture
        $voiture = $this->repo->find($data['voiture_id']);
        if ($voiture && $data['kilometrage'] > $voiture->kilometrage_actuel) {
            $this->repo->update($voiture->id, ['kilometrage_actuel' => $data['kilometrage']]);
        }

        $anneeId = $this->getCurrentAnneeId();
        if (empty($data['annee_id']) && $anneeId) {
            $data['annee_id'] = $anneeId;
        }
        $data['etat'] = 1;
        $carburant = $this->carburantRepo->create($data);
        return $this->formatResponse(true, 'Carburant enregistré', $carburant);
    }

    // ======================= ASSURANCES =======================
    public function listeAssurances(array $filters = []): array
    {
        $query = $this->assuranceRepo->activeQuery()
            ->with('voiture');

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['a_expirer'])) {
            $query->whereDate('date_fin', '<=', now()->addDays(30))
                ->whereDate('date_fin', '>=', now());
        }

        $perPage = $filters['per_page'] ?? 15;
        $assurances = $query->orderBy('date_fin')->paginate($perPage);

        $data = $assurances->map(fn($a) => [
            'id' => $a->id,
            'voiture' => $a->voiture->plaque,
            'compagnie' => $a->compagnie_assurance,
            'numero_contrat' => $a->numero_contrat,
            'date_debut' => $a->date_debut->format('d/m/Y'),
            'date_fin' => $a->date_fin->format('d/m/Y'),
            'prime' => $a->prime,
            'type' => $a->type_assurance,
        ]);

        return ['data' => $data, 'pagination' => $this->paginationData($assurances)];
    }

    public function createAssurance(array $data): array
    {
        $data['etat'] = 1;
        $assurance = $this->assuranceRepo->create($data);
        return $this->formatResponse(true, 'Assurance enregistrée', $assurance);
    }

    private function paginationData($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
