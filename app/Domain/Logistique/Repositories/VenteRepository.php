<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Vente;
use App\Domain\Logistique\Types\VenteStatutPaiement;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VenteRepository extends BaseRepository
{
    public function __construct(Vente $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'client:id,nom_raison_sociale',
                'magasin:id,libelle',
                'inscription.eleve:id,nom,prenom,matricule',
                'utilisateur:id,name',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->when($filters['type_vente'] ?? null, fn ($q, $v) => $q->where('type_vente', $v))
            ->when(
                isset($filters['statut_paiement']) && $filters['statut_paiement'] !== '',
                fn ($q) => $q->where('statut_paiement', (int) $filters['statut_paiement'])
            )
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('reference', 'like', "%{$v}%")
                        ->orWhereHas('client', fn ($c) => $c->where('nom_raison_sociale', 'like', "%{$v}%"))
                        ->orWhereHas('inscription.eleve', fn ($e) => $e
                            ->where('nom', 'like', "%{$v}%")
                            ->orWhere('prenom', 'like', "%{$v}%")
                            ->orWhere('matricule', 'like', "%{$v}%"));
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_vente', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_vente', '<=', $v))
            ->when(($filters['jour'] ?? false) === 'today', fn ($q) => $q->whereDate('date_vente', now()->toDateString()))
            ->orderByDesc('date_vente')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAvecDetails(int $id): Vente
    {
        return $this->activeQuery()
            ->with([
                'client',
                'magasin',
                'utilisateur:id,name',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'details.produit:id,libelle,unite_stock',
            ])
            ->findOrFail($id);
    }

    /** Génère une référence unique : VTE-20260510-0001. */
    public function genererReference(): string
    {
        $prefix = 'VTE-' . now()->format('Ymd') . '-';

        $dernier = $this->activeQuery()
            ->where('reference', 'like', "{$prefix}%")
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /** Stats pour la page index. */
    public function compterStatistiques(?int $anneeId = null): array
    {
        $q = $this->activeQuery()->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v));

        $aujourdhui = (clone $q)->whereDate('date_vente', now()->toDateString());
        $duMois     = (clone $q)->whereMonth('date_vente', now()->month)
                                ->whereYear('date_vente', now()->year);

        return [
            'jour_nb'      => (clone $aujourdhui)->count(),
            'jour_montant' => round((float) (clone $aujourdhui)->sum('total_ttc'), 2),
            'mois_nb'      => (clone $duMois)->count(),
            'mois_montant' => round((float) (clone $duMois)->sum('total_ttc'), 2),
        ];
    }

    /** Ventes du jour pour le point de vente. */
    public function ventesDuJour(int $magasinId, ?int $anneeId = null)
    {
        return $this->activeQuery()
            ->with([
                'client:id,nom_raison_sociale',
                'inscription.eleve:id,nom,prenom,matricule',
            ])
            ->whereDate('date_vente', now()->toDateString())
            ->where('magasin_id', $magasinId)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderByDesc('id')
            ->get();
    }
}