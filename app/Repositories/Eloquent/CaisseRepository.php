<?php

namespace App\Repositories\Eloquent;

use App\Models\Caisse;
use App\Repositories\Interfaces\CaisseRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use App\Types\StatutCaisse;
use App\Types\StatutMouvement;
use App\Types\TypeMouvement;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class CaisseRepository extends BaseRepository implements CaisseRepositoryInterface
{
    public function __construct(Caisse $model)
    {
        parent::__construct($model);
    }

    /**
     * Retourne la caisse ouverte d'un utilisateur (ou d'un responsable)
     */
    public function getActiveOuverte(?int $userId = null, ?int $anneeId = null): ?Caisse
    {
        // Correction : Initialisation correcte de la query
        $query = $this->activeQuery()->where('statut', StatutCaisse::OUVERT);

        if ($userId) {
            // On cherche soit si l'user est le créateur, soit s'il est le responsable (caissier)
            $query->where(function (Builder $q) use ($userId) {
                $q->where('utilisateur_id', $userId)
                  ->orWhere('responsable_id', $userId);
            });
        }
        if ($anneeId) {
            $query->where('annee_id', $anneeId);
        }

        return $query->first();
    }

    /**
     * Vérifie si un utilisateur (caissier) a déjà une caisse ouverte
     */
    public function hasUserOpenCaisse(int $userId): bool
    {
        return $this->activeQuery()
            ->where('responsable_id', $userId) // On vérifie sur le responsable (celui qui manipule la caisse)
            ->where('statut', StatutCaisse::OUVERT)
            ->exists();
    }

    /**
     * Ouvre une caisse
     * @param int $caisseId ID de la caisse
     * @param float $soldeInitial Fond de caisse
     * @param int $userId ID de l'utilisateur qui fait l'action (Admin ou Caissier)
     * @param int|null $responsableId ID du caissier qui gèrera la caisse (si null, $userId est utilisé)
     */
    public function ouvrir(int $caisseId, float $soldeInitial, int $userId, ?int $responsableId = null): bool
    {
        $caisse = $this->findOrFail($caisseId);

        if ($caisse->statut === StatutCaisse::OUVERT) {
            throw new Exception('Cette caisse est déjà ouverte.');
        }

        // Règle métier : 
        // - utilisateur_id = Celui qui crée/ouvre (Admin)
        // - responsable_id = Celui qui gère au quotidien (Caissier)
        $finalResponsable = $responsableId ?? $userId;

        return $caisse->update([
            'statut'           => StatutCaisse::OUVERT,
            'solde_initial'    => $soldeInitial,
            'date_ouverture'   => now(),
            'utilisateur_id'   => $userId,           // Créateur de l'ouverture (ex: Admin)
            'responsable_id'   => $finalResponsable, // Gestionnaire (ex: Caissier)
        ]);
    }

    /**
     * Clôture une caisse
     */
    public function cloturer(int $caisseId, float $soldePhysique, int $userId, ?string $observation = null): bool
    {
        $caisse = $this->findOrFail($caisseId);

        if ($caisse->statut !== StatutCaisse::OUVERT) {
            throw new Exception('Cette caisse n\'est pas ouverte.');
        }

        // Correction : Retour complet avec toutes les colonnes et fermeture des parenthèses
        return $caisse->update([
            'statut'           => StatutCaisse::CLOTURE,
            'solde_final'      => $soldePhysique,
            'date_cloture'     => now(),
           
        ]);
    }

    /**
     * Calcule le solde théorique : solde_initial + entrées - sorties
     */
    public function calculerSoldeTheorique(int $caisseId): float
    {
        $caisse = $this->findOrFail($caisseId);
        
        // Correction : Syntaxe whereIn et constantes
        $mouvements = $this->model->newQuery()
            ->where('caisse_id', $caisse->id)
            ->where('etat', self::ACTIF)
            ->whereIn('statut_mouvement', [StatutMouvement::VALIDER, StatutMouvement::DECAISSER])
            ->get();

        $entrees = $mouvements->where('type_mouvement', TypeMouvement::ENCAISSEMENT)->sum('montant');
        $sorties = $mouvements->where('type_mouvement', TypeMouvement::DECAISSEMENT)->sum('montant');

        return (float) ($caisse->solde_initial + $entrees - $sorties);
    }
}