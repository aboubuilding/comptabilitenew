<?php

namespace App\Repositories\Eloquent;

use App\Models\Depense;
use App\Repositories\Contracts\DepenseRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Exception;

class DepenseRepository extends BaseRepository implements DepenseRepositoryInterface
{
    public function __construct(Depense $model)
    {
        parent::__construct($model);
    }

    public function valider(int $id, int $validateurId): bool
    {
        $depense = $this->findOrFail($id);

        if ($depense->statut_depense !== 'en_attente') {
            throw new Exception('Seules les dépenses en attente peuvent être validées.');
        }

        return $depense->update([
            'statut_depense'    => 'validee',
            'validateur_id'     => $validateurId,
            'date_validation'   => now(),
        ]);
    }

    public function getMontantPaye(int $id): float
    {
        $depense = $this->findOrFail($id);
        
        return (float) $depense->mouvements()
            ->where('etat', self::ACTIF)
            ->where('type_mouvement', \App\Constants\TypeMouvement::DECAISSEMENT)
            ->whereIn('statut_mouvement', [\App\Constants\StatutMouvement::VALIDER, \App\Constants\StatutMouvement::DECAISSER])
            ->sum('montant');
    }

    public function syncStatutPaiement(int $id): bool
    {
        $depense = $this->findOrFail($id);
        $paye    = $this->getMontantPaye($id);
        $total   = (float) $depense->montant_prevu;

        $nouveauStatut = match(true) {
            $paye >= $total  => 'payee',
            $paye > 0        => 'partiellement_payee',
            default          => 'validee'
        };

        return $depense->statut_depense !== $nouveauStatut
            ? $depense->update(['statut_depense' => $nouveauStatut])
            : true;
    }

    public function getDepensesByStatut(string $statut, ?int $anneeId = null): Collection
    {
        $query = $this->activeQuery()->where('statut_depense', $statut);

        if ($anneeId) {
            $query->where('annee_scolaire_id', $anneeId);
        }

        return $query->orderByDesc('date_demande')->get();
    }
}