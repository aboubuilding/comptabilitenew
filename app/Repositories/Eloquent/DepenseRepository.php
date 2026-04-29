<?php

namespace App\Repositories;

use App\Models\Depense;
use App\Repositories\Contracts\DepenseRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Types\StatutDepense;
use App\Types\StatutMouvement;
use App\Types\TypeMouvement;
use Illuminate\Support\Collection;
use Exception;

class DepenseRepository extends BaseRepository implements DepenseRepositoryInterface
{
    public function __construct(Depense $model)
    {
        parent::__construct($model);
    }

    /**
     * Valide la dépense et la marque directement comme PAYÉE
     * (Paiement unique et immédiat)
     */
    public function valider(int $id, int $validateurId): bool
    {
        $depense = $this->findOrFail($id);

        if ((int) $depense->statut_depense !== StatutDepense::EN_ATTENTE) {
            throw new Exception('Seules les dépenses en attente peuvent être traitées.');
        }

        // ✅ Passage direct à PAYEE : plus besoin de sync ou d'étape intermédiaire
        return $depense->update([
            'statut_depense'  => StatutDepense::PAYEE,
            'validateur_id'   => $validateurId,
            'date_validation' => now(),
        ]);
    }

    /**
     * Retourne le montant déjà sorti de caisse pour cette dépense
     * (Utile pour l'audit ou l'affichage, mais ne sert plus au workflow)
     */
    public function getMontantPaye(int $id): float
    {
        $depense = $this->findOrFail($id);
        
        return (float) $depense->mouvements()
            ->where('etat', self::ACTIF)
            ->where('type_mouvement', TypeMouvement::DECAISSEMENT)
            ->where('statut_mouvement', StatutMouvement::VALIDER)
            ->sum('montant');
    }

    /**
     * ⛔ SUPPRIMÉ : syncStatutPaiement() n'est plus nécessaire
     * Le statut est géré directement dans valider()
     */

    public function getDepensesByStatut(int $statut, ?int $anneeId = null): Collection
    {
        $query = $this->activeQuery()->where('statut_depense', $statut);

        if ($anneeId) {
            $query->where('annee_id', $anneeId);
        }

        return $query->orderByDesc('date_depense')->get();
    }
}