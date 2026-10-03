<?php

namespace App\Domain\Logistique\Repositories;

use App\Domain\Logistique\Models\Achat;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class AchatRepository extends BaseRepository
{
    public function __construct(Achat $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'fournisseur:id,raison_social',
                'details',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['fournisseur_id'] ?? null, fn ($q, $v) => $q->where('fournisseur_id', $v))
            ->when(
                isset($filters['statut_livraison']) && $filters['statut_livraison'] !== '',
                fn ($q) => $q->where('statut_livraison', (int) $filters['statut_livraison'])
            )
            ->when(
                isset($filters['statut_paiement']) && $filters['statut_paiement'] !== '',
                fn ($q) => $q->where('statut_paiement', (int) $filters['statut_paiement'])
            )
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('reference', 'like', "%{$v}%")
                        ->orWhere('bon_commande', 'like', "%{$v}%")
                        ->orWhere('nom_acheteur', 'like', "%{$v}%");
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_achat', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_achat', '<=', $v))
            ->orderByDesc('date_achat')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAvecDetails(int $id): Achat
    {
        return $this->activeQuery()
            ->with([
                'fournisseur',
                'details',
                'details.produit:id,libelle,code',
            ])
            ->findOrFail($id);
    }

    /** Génère une référence unique : ACH-20260122-0001. */
    public function genererReference(): string
    {
        $prefix = 'ACH-' . now()->format('Ymd') . '-';

        $dernier = $this->activeQuery()
            ->where('reference', 'like', "{$prefix}%")
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function compterParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut_livraison, COUNT(*) as total')
            ->groupBy('statut_livraison')
            ->pluck('total', 'statut_livraison')
            ->all();

        return [
            'attente' => (int) ($rows[\App\Domain\Logistique\Types\AchatStatutLivraison::EN_ATTENTE->value] ?? 0),
            'livre'   => (int) ($rows[\App\Domain\Logistique\Types\AchatStatutLivraison::LIVRE->value]      ?? 0),
            'annule'  => (int) ($rows[\App\Domain\Logistique\Types\AchatStatutLivraison::ANNULE->value]     ?? 0),
        ];
    }

    public function montantsParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut_livraison, SUM(montant_total) as total')
            ->groupBy('statut_livraison')
            ->pluck('total', 'statut_livraison')
            ->all();

        return [
            'attente' => round((float) ($rows[\App\Domain\Logistique\Types\AchatStatutLivraison::EN_ATTENTE->value] ?? 0), 2),
            'livre'   => round((float) ($rows[\App\Domain\Logistique\Types\AchatStatutLivraison::LIVRE->value]      ?? 0), 2),
        ];
    }
}