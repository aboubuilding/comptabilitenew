<?php
namespace App\Services;

use App\Models\Voiture;
use App\Models\Chauffeur;
use App\Models\AffectationVehicule;
use App\Models\EntretienVehicule;
use App\Models\CarburantVehicule;
use App\Models\AssuranceVehicule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ParcAutomobileService
{
    protected ?int $anneeId;

    public function __construct()
    {
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
    }

    // ======================= VOITURES =======================
    public function listeVoitures(array $filters = []): array
    {
        $query = Voiture::query();

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
        if ($this->anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $this->anneeId);
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

        return ['data' => $data, 'pagination' => [
            'current_page' => $voitures->currentPage(),
            'last_page' => $voitures->lastPage(),
            'per_page' => $voitures->perPage(),
            'total' => $voitures->total(),
        ]];
    }

    public function getVoiture(int $id): Voiture
    {
        return Voiture::findOrFail($id);
    }

    public function createVoiture(array $data): Voiture
    {
        if (empty($data['annee_id']) && $this->anneeId) {
            $data['annee_id'] = $this->anneeId;
        }
        return Voiture::create($data);
    }

    public function updateVoiture(int $id, array $data): Voiture
    {
        $voiture = $this->getVoiture($id);
        $voiture->update($data);
        return $voiture;
    }

    public function deleteVoiture(int $id): void
    {
        $voiture = $this->getVoiture($id);
        if ($voiture->affectations()->exists() || $voiture->entretiens()->exists() || $voiture->carburants()->exists()) {
            throw new \Exception("Impossible de supprimer un véhicule avec des historiques.");
        }
        $voiture->delete();
    }

    // ======================= CHAUFFEURS =======================
    public function listeChauffeurs(array $filters = []): array
    {
        $query = Chauffeur::query();

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
        if ($this->anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $this->anneeId);
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

    public function getChauffeur(int $id): Chauffeur
    {
        return Chauffeur::findOrFail($id);
    }

    public function createChauffeur(array $data): Chauffeur
    {
        if (empty($data['annee_id']) && $this->anneeId) {
            $data['annee_id'] = $this->anneeId;
        }
        return Chauffeur::create($data);
    }

    public function updateChauffeur(int $id, array $data): Chauffeur
    {
        $chauffeur = $this->getChauffeur($id);
        $chauffeur->update($data);
        return $chauffeur;
    }

    public function deleteChauffeur(int $id): void
    {
        $chauffeur = $this->getChauffeur($id);
        if ($chauffeur->affectations()->exists()) {
            throw new \Exception("Ce chauffeur a des affectations, vous ne pouvez pas le supprimer.");
        }
        $chauffeur->delete();
    }

    // ======================= AFFECTATIONS =======================
    public function listeAffectations(array $filters = []): array
    {
        $query = AffectationVehicule::with(['voiture', 'chauffeur']);

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['chauffeur_id'])) {
            $query->where('chauffeur_id', $filters['chauffeur_id']);
        }
        if (!empty($filters['en_cours']) && $filters['en_cours'] == 1) {
            $query->whereNull('date_fin');
        }
        if ($this->anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $this->anneeId);
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

    public function createAffectation(array $data): AffectationVehicule
    {
        // Vérifier que la voiture n'est pas déjà affectée sans date de fin
        $existing = AffectationVehicule::where('voiture_id', $data['voiture_id'])
            ->whereNull('date_fin')
            ->first();
        if ($existing) {
            throw new \Exception("Ce véhicule est déjà affecté (depuis le {$existing->date_debut}).");
        }

        if (empty($data['annee_id']) && $this->anneeId) {
            $data['annee_id'] = $this->anneeId;
        }
        return AffectationVehicule::create($data);
    }

    public function terminerAffectation(int $id, ?string $motif = null): AffectationVehicule
    {
        $affectation = AffectationVehicule::findOrFail($id);
        if ($affectation->date_fin) {
            throw new \Exception("Cette affectation est déjà terminée.");
        }
        $affectation->date_fin = now();
        if ($motif) {
            $affectation->motif = $motif;
        }
        $affectation->save();
        return $affectation;
    }

    // ======================= ENTRETIENS =======================
    public function listeEntretiens(array $filters = []): array
    {
        $query = EntretienVehicule::with(['voiture', 'chauffeur']);

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_entretien', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_entretien', '<=', $filters['date_fin']);
        }
        if ($this->anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $this->anneeId);
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

    public function createEntretien(array $data): EntretienVehicule
    {
        if (empty($data['annee_id']) && $this->anneeId) {
            $data['annee_id'] = $this->anneeId;
        }
        return EntretienVehicule::create($data);
    }

    // ======================= CARBURANT =======================
    public function listeCarburants(array $filters = []): array
    {
        $query = CarburantVehicule::with('voiture');

        if (!empty($filters['voiture_id'])) {
            $query->where('voiture_id', $filters['voiture_id']);
        }
        if (!empty($filters['date_debut'])) {
            $query->whereDate('date_plein', '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate('date_plein', '<=', $filters['date_fin']);
        }
        if ($this->anneeId && empty($filters['ignore_annee'])) {
            $query->where('annee_id', $this->anneeId);
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

    public function createCarburant(array $data): CarburantVehicule
    {
        // Mettre à jour le kilométrage de la voiture
        $voiture = Voiture::find($data['voiture_id']);
        if ($voiture && $data['kilometrage'] > $voiture->kilometrage_actuel) {
            $voiture->kilometrage_actuel = $data['kilometrage'];
            $voiture->save();
        }

        if (empty($data['annee_id']) && $this->anneeId) {
            $data['annee_id'] = $this->anneeId;
        }
        return CarburantVehicule::create($data);
    }

    // ======================= ASSURANCES =======================
    public function listeAssurances(array $filters = []): array
    {
        $query = AssuranceVehicule::with('voiture');

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

    public function createAssurance(array $data): AssuranceVehicule
    {
        return AssuranceVehicule::create($data);
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