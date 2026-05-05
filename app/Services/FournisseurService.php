<?php
namespace App\Services;

use App\Models\Fournisseur;
use App\Models\Achat;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FournisseurService
{
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des fournisseurs (paginate)
     */
    public function listFournisseurs(array $filters = []): array
    {
        $query = Fournisseur::query();

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($search) {
                $q->where('raison_social', 'like', $search)
                  ->orWhere('nom_contact', 'like', $search)
                  ->orWhere('telephone_contact', 'like', $search);
            });
        }
        if (isset($filters['etat']) && in_array($filters['etat'], [0,1])) {
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
     * Récupérer un fournisseur
     */
    public function getFournisseur(int $id): Fournisseur
    {
        return Fournisseur::findOrFail($id);
    }

    /**
     * Créer un fournisseur
     */
    public function createFournisseur(array $data): Fournisseur
    {
        $data['etat'] = $data['etat'] ?? 1;
        return Fournisseur::create($data);
    }

    /**
     * Mettre à jour un fournisseur
     */
    public function updateFournisseur(int $id, array $data): Fournisseur
    {
        $fournisseur = $this->getFournisseur($id);
        $fournisseur->update($data);
        return $fournisseur;
    }

    /**
     * Supprimer (désactiver) un fournisseur
     */
    public function deleteFournisseur(int $id): void
    {
        $fournisseur = $this->getFournisseur($id);
        $fournisseur->etat = 0;
        $fournisseur->save();
    }

    /**
     * Liste pour selects (dropdown)
     */
    public function getForSelect(): Collection
    {
        return Fournisseur::where('etat', 1)
            ->orderBy('raison_social')
            ->get(['id', 'raison_social']);
    }

    /**
     * Statistiques des achats par fournisseur sur une période
     */
    public function getStatsAchats(int $fournisseurId, ?string $dateDebut, ?string $dateFin): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $query = Achat::where('fournisseur_id', $fournisseurId)
            ->where('etat', 1)
            ->where('annee_id', $anneeId);

        if ($dateDebut) {
            $query->whereDate('date_achat', '>=', $dateDebut);
        }
        if ($dateFin) {
            $query->whereDate('date_achat', '<=', $dateFin);
        }

        $totalDepense = $query->sum('montant_total');
        $nombreAchats = $query->count();

        // Détail des achats (juste pour infos)
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