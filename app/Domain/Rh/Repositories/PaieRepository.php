<?php

namespace App\Domain\Rh\Repositories;

use App\Domain\Rh\Models\Paie;
use App\Domain\Rh\Types\StatutPaie;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaieRepository extends BaseRepository
{
    public function __construct(Paie $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'employe:id,nom_prenom,salaire,profession_id',
                'employe.profession:id,libelle',
                'periode:id,libelle,date_debut,date_fin',
                'payeur:id,name',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['periode_id'] ?? null, fn ($q, $v) => $q->where('periode_id', $v))
            ->when(
                isset($filters['statut']) && $filters['statut'] !== '',
                fn ($q) => $q->where('statut', $filters['statut'])
            )
            ->when($filters['employe_id'] ?? null, fn ($q, $v) => $q->where('personnel_id', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->whereHas('employe', fn ($sub) => $sub->where('nom_prenom', 'like', "%{$v}%"));
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAvecDetails(int $id): Paie
    {
        return $this->activeQuery()
            ->with([
                'employe.profession',
                'periode',
                'payeur:id,name',
            ])
            ->findOrFail($id);
    }

    /** Vérifie si un bulletin existe déjà pour cette (employé, période). */
    public function existePour(int $employeId, int $periodeId): bool
    {
        return $this->activeQuery()
            ->where('personnel_id', $employeId)
            ->where('periode_id', $periodeId)
            ->exists();
    }

    /** Compteurs et masses pour la période courante. */
    public function compterStatistiques(?int $periodeId = null): array
    {
        $q = $this->activeQuery()
            ->when($periodeId, fn ($q, $v) => $q->where('periode_id', $v));

        return [
            'total'      => (clone $q)->count(),
            'brouillons' => (clone $q)->where('statut', StatutPaie::BROUILLON->value)->count(),
            'valides'    => (clone $q)->where('statut', StatutPaie::VALIDE->value)->count(),
            'payes'      => (clone $q)->where('statut', StatutPaie::PAYE->value)->count(),
            'masse_brute'=> round((float) (clone $q)->sum('total_brut'), 2),
            'masse_nette'=> round((float) (clone $q)->sum('net_a_payer'), 2),
        ];
    }

   

/**
 * Récapitulatif complet d'une période pour le dashboard :
 * totaux bruts / nets, ventilation par mode de paiement, statuts.
 */
public function recapPeriode(int $periodeId): array
{
    $q = $this->activeQuery()->where('periode_id', $periodeId);

    $total       = (clone $q)->count();
    $totalBrut   = round((float) (clone $q)->sum('total_brut'), 2);
    $totalNet    = round((float) (clone $q)->sum('net_a_payer'), 2);
    $totalDed    = round((float) (clone $q)->sum('total_deductions'), 2);
    $totalRet    = round((float) (clone $q)->sum('total_retenues'), 2);

    $brouillons = (clone $q)->where('statut', StatutPaie::BROUILLON->value)->count();
    $valides    = (clone $q)->where('statut', StatutPaie::VALIDE->value)->count();
    $payes      = (clone $q)->where('statut', StatutPaie::PAYE->value)->count();

    // Ventilation par mode de paiement
    $billetteTotal = (clone $q)->where('mode_paiement', 'billette');
    $banqueTotal   = (clone $q)->where('mode_paiement', 'banque');

    $billette = [
        'nb'      => (clone $billetteTotal)->count(),
        'montant' => round((float) (clone $billetteTotal)->where('statut', StatutPaie::PAYE->value)->sum('net_a_payer'), 2),
        'prevu'   => round((float) (clone $billetteTotal)->sum('net_a_payer'), 2),
        'retires' => (clone $billetteTotal)->where('retire_a_la_billetterie', true)->count(),
        'en_attente_retrait' => (clone $billetteTotal)
            ->where('statut', StatutPaie::PAYE->value)
            ->where('retire_a_la_billetterie', false)
            ->count(),
        'montant_en_attente' => round((float) (clone $billetteTotal)
            ->where('statut', StatutPaie::PAYE->value)
            ->where('retire_a_la_billetterie', false)
            ->sum('net_a_payer'), 2),
    ];

    $banque = [
        'nb'      => (clone $banqueTotal)->count(),
        'montant' => round((float) (clone $banqueTotal)->where('statut', StatutPaie::PAYE->value)->sum('net_a_payer'), 2),
        'prevu'   => round((float) (clone $banqueTotal)->sum('net_a_payer'), 2),
    ];

    return [
        'total'         => $total,
        'total_brut'    => $totalBrut,
        'total_net'     => $totalNet,
        'total_deductions' => $totalDed,
        'total_retenues'   => $totalRet,
        'brouillons'    => $brouillons,
        'valides'       => $valides,
        'payes'         => $payes,
        'billette'      => $billette,
        'banque'        => $banque,
    ];
}

/**
 * Liste des bulletins à la billetterie dont les espèces n'ont pas encore
 * été retirées (état des retards en espèces).
 */
public function enAttenteRetrait(?int $periodeId = null): \Illuminate\Support\Collection
{
    return $this->activeQuery()
        ->with(['employe:id,nom_prenom', 'periode:id,libelle'])
        ->where('statut', StatutPaie::PAYE->value)
        ->where('mode_paiement', 'billette')
        ->where('retire_a_la_billetterie', false)
        ->when($periodeId, fn ($q, $v) => $q->where('periode_id', $v))
        ->orderBy('date_paiement')
        ->get();
}

/**
 * Évolution de la masse salariale sur les 12 derniers mois.
 */
public function evolutionMensuelle(int $mois = 12): array
{
    $rows = $this->activeQuery()
        ->whereDate('created_at', '>=', now()->subMonths($mois))
        ->selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as mois,
            COUNT(*) as nb,
            SUM(total_brut) as brut,
            SUM(net_a_payer) as net
        ")
        ->groupBy('mois')
        ->orderBy('mois')
        ->get();

    return $rows->map(fn ($r) => [
        'mois' => $r->mois,
        'nb'   => (int) $r->nb,
        'brut' => round((float) $r->brut, 2),
        'net'  => round((float) $r->net, 2),
    ])->all();
}
}