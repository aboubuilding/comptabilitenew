<?php

namespace App\Services;

use App\Models\Activite;
use App\Models\DetailPaiement;
use App\Models\Inscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActiviteService
{
    protected ?int $anneeId;
    protected bool $isAdminOrDirector;

    public function __construct()
    {
        $this->anneeId = session()->get('LoginUser')['annee_id'] ?? null;
        $user = auth()->user();
        $this->isAdminOrDirector = in_array($user->role ?? null, ['admin', 'directeur', 1, 3]); // selon votre config
    }

    /**
     * Liste toutes les activités avec statistiques (inscrits et montant global pour l'année en cours)
     */
    public function getAllWithStats(): Collection
    {
        $activites = Activite::where('annee_id', $this->anneeId)
            ->where('etat', 1)
            ->with('niveau:id,libelle')
            ->get();

        if (!$this->anneeId) {
            return $activites->map(fn($a) => [
                'id' => $a->id,
                'libelle' => $a->libelle,
                'description' => $a->description,
                'montant' => $a->montant,
                'niveau' => $a->niveau?->libelle,
                'etat' => $a->etat,
                'nb_inscrits' => 0,
                'montant_global' => 0,
            ]);
        }

        // Sous-requête pour compter les inscriptions (élèves uniques) ayant payé cette activité
        // On compte les détails de paiement de type 6 avec activite_id = a.id, groupe par inscription_id
        // Et on compte le nombre d'inscriptions distinctes (élèves)
        $stats = DetailPaiement::select(
                'activite_id',
                DB::raw('COUNT(DISTINCT inscription_id) as nb_inscrits'),
                DB::raw('SUM(montant) as montant_total')
            )
            ->where('type_paiement', 6) // activité
            ->where('statut_paiement', 1) // encaissé
            ->where('annee_id', $this->anneeId)
            ->whereNotNull('activite_id')
            ->groupBy('activite_id')
            ->get()
            ->keyBy('activite_id');

        return $activites->map(function ($activite) use ($stats) {
            $stat = $stats->get($activite->id);
            return [
                'id' => $activite->id,
                'libelle' => $activite->libelle,
                'description' => $activite->description,
                'montant' => $activite->montant,
                'niveau' => $activite->niveau?->libelle,
                'etat' => $activite->etat,
                'nb_inscrits' => $this->isAdminOrDirector ? ($stat->nb_inscrits ?? 0) : null,
                'montant_global' => $this->isAdminOrDirector ? ($stat->montant_total ?? 0) : null,
            ];
        });
    }

    /**
     * Liste simplifiée pour selects (dropdown)
     */
    public function getForSelect(): Collection
    {
        return Activite::where('annee_id', $this->anneeId)
            ->where('etat', 1)
            ->select('id', 'libelle', 'montant', 'niveau_id')
            ->orderBy('libelle')
            ->get();
    }

    /**
     * Récupère une activité par son ID
     */
    public function find(int $id): Activite
    {
        return Activite::where('annee_id', $this->anneeId)->findOrFail($id);
    }

    /**
     * Crée une nouvelle activité (année_id automatique depuis session)
     */
    public function store(array $data): Activite
    {
        if (!$this->anneeId) {
            throw new \Exception('Année scolaire non définie en session.');
        }
        $data['annee_id'] = $this->anneeId;
        return Activite::create($data);
    }

    /**
     * Met à jour une activité
     */
    public function update(int $id, array $data): Activite
    {
        $activite = $this->find($id);
        $activite->update($data);
        return $activite;
    }

    /**
     * Supprime une activité (soft delete possible)
     */
    public function delete(int $id): void
    {
        $activite = $this->find($id);
        $activite->delete();
    }

    /**
     * Vérifie si l'activité a des paiements associés
     */
    public function hasRelatedPayments(int $id): bool
    {
        return DetailPaiement::where('activite_id', $id)->where('etat', 1)->exists();
    }
}