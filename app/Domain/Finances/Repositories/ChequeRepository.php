<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Cheque;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChequeRepository extends BaseRepository
{
    public function __construct(Cheque $model)
    {
        parent::__construct($model);
    }

    /**
     * Paiement qui a abouti au chèque : porté par le chèque (paiement_id) ou,
     * à défaut, par le paiement (cheque_id).
     */
    public function paiementLie(Cheque $cheque): ?int
    {
        return $cheque->paiement_id
            ? (int) $cheque->paiement_id
            : (\App\Domain\Finances\Models\Paiement::where('cheque_id', $cheque->id)->where('etat', 1)->value('id') ?: null);
    }

    /**
     * Données de la fenêtre « contenu du paiement » d'un chèque : le paiement,
     * ses lignes, ses totaux et le chèque adossé.
     */
    public function contenuPaiement(Cheque $cheque, int $paiementId): array
    {
        $paiement = \App\Domain\Finances\Models\Paiement::query()
            ->with([
                'details' => fn ($q) => $q->where('etat', 1)->orderBy('id'),
                'details.caissier:id,nom,prenom',
                'comptable:id,nom,prenom',
                'inscription:id,eleve_id,classe_id,niveau_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
            ])
            ->findOrFail($paiementId);
        $details = $paiement->details;
        $statut = fn (\App\Domain\Finances\Types\PaiementStatut $s) => round((float) $details->filter(fn ($d) => $d->statut_paiement === $s)->sum('montant'), 2);

        return [
            'paiement' => $paiement,
            'cheque'   => $cheque->loadMissing('banque:id,nom'),
            'totaux'   => [
                'total'      => round((float) $details->sum('montant'), 2),
                'encaisse'   => $statut(\App\Domain\Finances\Types\PaiementStatut::ENCAISSE),
                'en_attente' => $statut(\App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE),
            ],
        ];
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'banque:id,nom',
                'rapprocheur:id,name',
            ])
            // Paiement qui a abouti au chèque (action « Voir le paiement »)
            ->addSelect(['paiement_lie' => \App\Domain\Finances\Models\Paiement::query()->select('id')
                ->whereColumn('paiements.cheque_id', 'cheques.id')->where('paiements.etat', 1)->limit(1)])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', (int) $filters['statut'])
            )
            ->when($filters['banque_id'] ?? null, fn ($q, $v) => $q->where('banque_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('numero', 'like', "%{$v}%")
                        ->orWhere('emetteur', 'like', "%{$v}%");
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_emission', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_emission', '<=', $v))
            ->orderByDesc('date_emission')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /* ===== Compteurs pour les cartes-statistiques de l'index ===== */

    public function compterParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return [
            'emis'     => (int) ($rows[\App\Domain\Finances\Types\ChequeStatut::EMIS->value]     ?? 0),
            'encaisse' => (int) ($rows[\App\Domain\Finances\Types\ChequeStatut::ENCAISSE->value] ?? 0),
            'rejete'   => (int) ($rows[\App\Domain\Finances\Types\ChequeStatut::REJETE->value]   ?? 0),
        ];
    }

    public function montantEnAttente(): float
    {
        return round(
            (float) $this->activeQuery()
                ->where('statut', \App\Domain\Finances\Types\ChequeStatut::EMIS->value)
                ->sum('montant'),
            2
        );
    }

    public function findAvecRelations(int $id): Cheque
    {
        return $this->activeQuery()
            ->with(['banque:id,nom', 'rapprocheur:id,name'])
            ->findOrFail($id);
    }
}