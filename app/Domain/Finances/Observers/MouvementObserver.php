<?php

namespace App\Domain\Finances\Observers;

use App\Domain\Finances\Models\Depense;
use App\Domain\Finances\Models\Mouvement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Coordonne le lien entre une dépense et le mouvement de caisse qui
 * constate son règlement. Enregistré sur le modèle Depense.
 *
 * Règles appliquées :
 *  - Supprimer une dépense réglée supprime (logiquement) le mouvement
 *    de caisse associé.
 *  - Si la caisse est déjà clôturée, la suppression est refusée : le
 *    journal de la session est figé (CDC §6).
 *  - Toute incohérence est logguée (dépense "réglée" sans mouvement, ou
 *    plusieurs mouvements rattachés à la même dépense) sans bloquer la
 *    suppression, sauf en cas de caisse clôturée.
 */
class MouvementObserver
{
    /**
     * Intercepte la suppression logique (BaseRepository::delete() fait
     * un update(['etat' => INACTIF]), donc on écoute `updating` plutôt
     * que `deleting`. On vérifie que `etat` passe de 1 à 0.
     */
    public function updating(Depense $depense): void
    {
        if (! $depense->isDirty('etat')) {
            return;
        }

        $ancien  = (int) $depense->getOriginal('etat');
        $nouveau = (int) $depense->etat;

        if ($ancien === \App\Repositories\BaseRepository::ACTIF && $nouveau === \App\Repositories\BaseRepository::INACTIF) {
            $this->verifierEtSupprimerMouvements($depense);
        }
    }

    /**
     * Suppression physique (rare, réservée à un administrateur) : même
     * logique, mais on détache d'abord les mouvements pour ne pas laisser
     * de références orphelines (la clé `depense_id` n'est pas FK en base,
     * donc pas de cascade automatique côté SGBD).
     */
    public function deleting(Depense $depense): void
    {
        $this->verifierEtSupprimerMouvements($depense, force: true);
    }

    /**
     * @param  bool $force  true = suppression physique (deleting),
     *                      false = suppression logique (updating)
     */
    private function verifierEtSupprimerMouvements(Depense $depense, bool $force = false): void
    {
        // Aucun mouvement à traiter si la dépense n'a jamais été réglée.
        if (! $depense->date_reglement && ! $depense->caisse_id) {
            return;
        }

        $mouvements = Mouvement::query()
            ->where('depense_id', $depense->id)
            ->where('etat', 1)
            ->get();

        if ($mouvements->isEmpty()) {
            // La dépense est marquée réglée mais aucun mouvement n'existe :
            // anomalie de données. On log, mais on laisse la suppression se
            // poursuivre — sinon l'utilisateur serait bloqué sans solution.
            Log::warning('MouvementObserver : dépense réglée sans mouvement de caisse', [
                'depense_id'    => $depense->id,
                'caisse_id'     => $depense->caisse_id,
                'date_reglement' => optional($depense->date_reglement)->toDateTimeString(),
            ]);
            return;
        }

        // Règle CDC §6 : une session de caisse clôturée est figée.
        foreach ($mouvements as $mouvement) {
            $caisse = $mouvement->caisse;

            if ($caisse && ! $caisse->isOuverte()) {
                throw new RuntimeException(
                    "Impossible de supprimer cette dépense : son mouvement de caisse " .
                    "appartient à la session « {$caisse->libelle} » déjà clôturée. " .
                    "Le journal de cette session est figé."
                );
            }
        }

        DB::transaction(function () use ($mouvements, $force) {
            foreach ($mouvements as $mouvement) {
                if ($force) {
                    // Suppression physique demandée en amont — on suit la
                    // même intention côté mouvement.
                    $mouvement->delete();
                } else {
                    // Sinon : suppression logique (etat = 0), la ligne
                    // reste consultable dans l'historique.
                    $mouvement->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);
                }
            }
        });
    }
}