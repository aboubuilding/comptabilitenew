<?php
namespace App\Services;

use App\Models\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ZoneService
{
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste paginée des zones
     */
    public function listZones(array $filters = []): array
    {
        $anneeId = $filters['annee_id'] ?? $this->getCurrentAnneeId();

        $query = Zone::with(['chauffeur', 'voiture'])
            ->when($anneeId, fn($q) => $q->where('annee_id', $anneeId))
            ->when(isset($filters['etat']) && $filters['etat'] !== '', fn($q) => $q->where('etat', $filters['etat']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $search = '%' . $filters['search'] . '%';
                $q->where(function ($sub) use ($search) {
                    $sub->where('code', 'like', $search)
                        ->orWhere('libelle', 'like', $search);
                });
            });

        $perPage = $filters['per_page'] ?? 15;
        $zones = $query->orderBy('ordre')->orderBy('libelle')->paginate($perPage);

        $data = $zones->map(fn($zone) => [
            'id'           => $zone->id,
            'code'         => $zone->code,
            'libelle'      => $zone->libelle,
            'description'  => $zone->description,
            'tarif_base'   => $zone->tarif_base,
            'ordre'        => $zone->ordre,
            'couleur'      => $zone->couleur,
            'points_arret' => $zone->points_arret,
            'chauffeur'    => $zone->chauffeur?->nom . ' ' . $zone->chauffeur?->prenom,
            'voiture'      => $zone->voiture?->plaque,
            'etat'         => $zone->etat,
            'etat_label'   => $zone->etat ? 'Active' : 'Inactive',
        ]);

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $zones->currentPage(),
                'last_page'    => $zones->lastPage(),
                'per_page'     => $zones->perPage(),
                'total'        => $zones->total(),
            ]
        ];
    }

    /**
     * Récupère une zone
     */
    public function getZone(int $id): Zone
    {
        return Zone::with(['chauffeur', 'voiture'])->findOrFail($id);
    }

    /**
     * Crée une zone
     */
    public function createZone(array $data): Zone
    {
        $anneeId = $data['annee_id'] ?? $this->getCurrentAnneeId();
        if (!$anneeId) {
            throw new \Exception('Année scolaire non définie');
        }
        $data['annee_id'] = $anneeId;
        $data['etat'] = $data['etat'] ?? 1;

        return Zone::create($data);
    }

    /**
     * Met à jour une zone
     */
    public function updateZone(int $id, array $data): Zone
    {
        $zone = $this->getZone($id);
        $zone->update($data);
        return $zone;
    }

    /**
     * Supprime une zone (soft delete ? on peut juste passer etat=0)
     */
    public function deleteZone(int $id): void
    {
        $zone = $this->getZone($id);
        $zone->etat = 0;
        $zone->save();
    }

    /**
     * Liste des zones pour select (dropdown)
     */
    public function getForSelect(): Collection
    {
        return Zone::actif()
            ->orderBy('ordre')
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle', 'tarif_base']);
    }
}