<?php

namespace App\Repositories\Eloquent;

use App\Models\Caisse;
use App\Repositories\Contracts\CaisseRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Facades\DB;
use Exception;

class CaisseRepository extends BaseRepository implements CaisseRepositoryInterface
{
    public function __construct(Caisse $model)
    {
        parent::__construct($model);
    }

    public function getActiveOuverte(?int $userId = null, ?int $anneeId = null): ?Caisse
    {
        $query = $this->activeQuery()->where('statut', 'ouvert');

        if ($userId) {
            $query->where('utilisateur_id', $userId);
        }
        if ($anneeId) {
            $query->where('annee_scolaire_id', $anneeId);
        }

        return $query->first();
    }

    public function hasUserOpenCaisse(int $userId): bool
    {
        return $this->activeQuery()
            ->where('utilisateur_id', $userId)
            ->where('statut', 'ouvert')
            ->exists();
    }

    public function ouvrir(int $caisseId, float $soldeInitial, int $userId): bool
    {
        $caisse = $this->findOrFail($caisseId);

        if ($caisse->statut === 'ouvert') {
            throw new Exception('Cette caisse est déjà ouverte.');
        }

        return $caisse->update([
            'statut'          => 'ouvert',
            'solde_initial'   => $soldeInitial,
            'date_ouverture'  => now(),
            'utilisateur_id'  => $userId,
        ]);
    }

    public function cloturer(int $caisseId, float $soldePhysique, int $userId, ?string $observation = null): bool
    {
        $caisse = $this->findOrFail($caisseId);

        if ($caisse->statut !== 'ouvert') {
            throw new Exception('Cette caisse n\'est pas ouverte.');
        }

        return $caisse->update([
            'statut'          => 'cloture',
            'solde_final'     => $soldePhysique,
            'date_cloture'    => now(),
            'responsable_id'  => $userId,
            'observation'     => $observation,
        ]);
    }

    public function calculerSoldeTheorique(int $caisseId): float
    {
        $caisse = $this->findOrFail($caisseId);
        
        // Uniquement les mouvements VALIDÉS ou DÉCAISSÉS impactent le solde
        $mouvements = $this->model->newQuery()
            ->where('caisse_id', $caisse->id)
            ->where('etat', self::ACTIF)
            ->whereIn('statut_mouvement', [\App\Constants\StatutMouvement::VALIDER, \App\Constants\StatutMouvement::DECAISSER])
            ->get();

        $entrees = $mouvements->where('type_mouvement', \App\Constants\TypeMouvement::ENCAISSEMENT)->sum('montant');
        $sorties = $mouvements->where('type_mouvement', \App\Constants\TypeMouvement::DECAISSEMENT)->sum('montant');

        return (float) ($caisse->solde_initial + $entrees - $sorties);
    }
}