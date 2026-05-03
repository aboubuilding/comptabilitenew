<?php

namespace App\Services;

use App\Repositories\Interfaces\DepenseRepositoryInterface;
use App\Repositories\Interfaces\MouvementRepositoryInterface;
use App\Constants\TypeMouvement;
use App\Constants\StatutMouvement;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function __construct(
        private DepenseRepositoryInterface $depenseRepo,
        private MouvementRepositoryInterface $mouvementRepo
    ) {}

    /**
     * Totaux des dépenses VALIDÉES par période
     */
    public function getDepensesTotalsByPeriod(string $period = 'day', array $filters = []): array
    {
        $dateField = $this->getDateField($period);
        $format = $this->getDateFormat($period);

        $query = $this->depenseRepo->getModel()
            ->selectRaw("DATE_FORMAT(date_demande, '{$format}') as period, 
                        COUNT(*) as count, 
                        SUM(montant_prevu) as total")
            ->where('statut_depense', 'payee') // Uniquement les dépenses payées (validées + mouvement créé)
            ->where('etat', \App\Repositories\Eloquent\BaseRepository::ACTIF)
            ->groupBy('period')
            ->orderBy('period');

        $this->applyDateFilters($query, $filters);

        return $query->get()->mapWithKeys(fn($row) => [$row->period => [
            'count' => (int) $row->count,
            'total' => (float) $row->total,
        ]])->toArray();
    }

    /**
     * Totaux des DÉCAISSEMENTS EFFECTIFS par période
     */
    public function getDecaissementsTotalsByPeriod(string $period = 'day', array $filters = []): array
    {
        $dateField = $this->getDateField($period);
        $format = $this->getDateFormat($period);

        $query = $this->mouvementRepo->getModel()
            ->selectRaw("DATE_FORMAT(date_mouvement, '{$format}') as period, 
                        COUNT(*) as count, 
                        SUM(montant) as total")
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->where('etat', \App\Repositories\Eloquent\BaseRepository::ACTIF)
            ->groupBy('period')
            ->orderBy('period');

        if (!empty($filters['caisse_id'])) {
            $query->where('caisse_id', $filters['caisse_id']);
        }
        $this->applyDateFilters($query, $filters, 'date_mouvement');

        return $query->get()->mapWithKeys(fn($row) => [$row->period => [
            'count' => (int) $row->count,
            'total' => (float) $row->total,
        ]])->toArray();
    }

    /**
     * Résumé des dépenses REJETÉES avec motifs et totaux
     */
    public function getRejectedExpensesSummary(array $filters = []): array
    {
        $query = $this->depenseRepo->getModel()
            ->selectRaw('commentaire_validation as motif, COUNT(*) as count, SUM(montant_prevu) as total')
            ->where('statut_depense', 'refusee')
            ->where('etat', \App\Repositories\Eloquent\BaseRepository::ACTIF)
            ->groupBy('motif')
            ->orderByDesc('total');

        $this->applyDateFilters($query, $filters);

        return $query->get()->map(fn($row) => [
            'motif' => $row->motif ?? 'Sans motif',
            'count' => (int) $row->count,
            'total' => (float) $row->total,
        ])->toArray();
    }

    /**
     * Helper : format de date selon la période
     */
    private function getDateFormat(string $period): string
    {
        return match($period) {
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u', // Semaine ISO
            'month' => '%Y-%m',
            default => '%Y-%m-%d'
        };
    }

    /**
     * Helper : application des filtres de date
     */
    private function applyDateFilters($query, array $filters, string $field = 'date_demande'): void
    {
        if (!empty($filters['date_debut'])) {
            $query->whereDate($field, '>=', $filters['date_debut']);
        }
        if (!empty($filters['date_fin'])) {
            $query->whereDate($field, '<=', $filters['date_fin']);
        }
        if (!empty($filters['annee_id'])) {
            $query->where('annee_scolaire_id', $filters['annee_id']);
        }
    }

    private function getDateField(string $period): string
    {
        return $period === 'week' ? 'YEARWEEK(date_demande)' : 'date_demande';
    }
}