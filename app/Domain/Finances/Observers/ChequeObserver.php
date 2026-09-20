<?php

namespace App\Domain\Finances\Observers;

use App\Domain\Finances\Models\Cheque;
use App\Domain\Finances\Models\Mouvement;
use App\Domain\Finances\Repositories\MouvementRepository;
use App\Domain\Finances\Types\ChequeStatut;
use App\Domain\Finances\Types\MouvementType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChequeObserver
{
    public function __construct(
        private MouvementRepository $mouvements,
    ) {}

    /**
     * Réagit au passage du statut d'un chèque.
     * On utilise updating() (et non updated()) pour ne déclencher
     * le traitement qu'une seule fois, quand le statut a effectivement
     * changé — et pas à chaque save() du modèle.
     */
    public function updating(Cheque $cheque): void
    {
        if (! $cheque->isDirty('statut')) {
            return;
        }

        $ancien = $cheque->getOriginal('statut');
        $nouveau = $cheque->statut;

        // Normalisation : getOriginal() peut renvoyer un int, on compare
        // toujours des int (les enums castés sont convertis en leur value).
        $ancienInt  = is_object($ancien) ? $ancien->value : (int) $ancien;
        $nouveauInt = $nouveau?->value;

        // Transition EMIS -> ENCAISSE : on constate l'entrée en caisse.
        if ($nouveauInt === ChequeStatut::ENCAISSE->value
            && $ancienInt === ChequeStatut::EMIS->value) {
            $this->enregistrerEncaissement($cheque);
        }
    }

    /**
     * Crée un mouvement de caisse d'encaissement lié au chèque.
     * Idempotent : si un mouvement existe déjà pour ce chèque, on ne
     * recrée rien (sécurité en cas de double-clic ou de retry).
     */
    private function enregistrerEncaissement(Cheque $cheque): void
    {
        // Le chèque doit être rattaché à une caisse via son paiement.
        // Si ton schéma ne stocke pas ce lien directement, adapte la
        // ligne ci-dessous à ta logique (ex. paiement->caisse_id, ou
        // details.caisse_id via paiement_id).
        $caisseId = $this->resoudreCaisseDepuisPaiement($cheque);

        if (! $caisseId) {
            // Pas de caisse identifiée : on ne crée pas de mouvement,
            // mais on log pour investigation. Le chèque reste encaissé
            // côté banque — c'est le rapprochement caisse qui sera fait
            // manuellement.
            Log::info('ChequeObserver : chèque encaissé sans caisse associée', [
                'cheque_id'    => $cheque->id,
                'paiement_id'  => $cheque->paiement_id,
            ]);
            return;
        }

        // Idempotence : un mouvement existe déjà pour ce chèque ?
        $existe = Mouvement::query()
            ->where('caisse_id', $caisseId)
            ->where('type_mouvement', MouvementType::ENCAISSEMENT->value)
            ->where('paiement_id', $cheque->paiement_id)
            ->where('annee_id', $cheque->annee_id)
            ->exists();

        if ($existe) {
            return;
        }

        DB::transaction(function () use ($cheque, $caisseId) {
            $this->mouvements->create([
                'libelle'          => "Encaissement chèque n°{$cheque->numero}",
                'beneficiaire'     => $cheque->emetteur,
                'motif'            => "Rapprochement bancaire du " . now()->format('d/m/Y'),
                'date_mouvement'   => now()->toDateString(),
                'montant'          => (float) $cheque->montant,
                'type_mouvement'   => MouvementType::ENCAISSEMENT->value,
                'caisse_id'        => $caisseId,
                'utilisateur_id'   => $cheque->rapproche_par,
                'paiement_id'      => $cheque->paiement_id,
                'annee_id'         => $cheque->annee_id,
                'statut_mouvement' => 1,
            ]);
        });
    }

    /**
     * Résout la caisse à créditer depuis le paiement rattaché au chèque.
     * À adapter selon la structure réelle de ta table paiements/details.
     *
     * Exemple 1 : paiements.caisse_id existe -> on l'utilise directement.
     * Exemple 2 : c'est details.caisse_id qui porte l'info -> on
     *             récupère le detail lié au paiement.
     * Exemple 3 : pas de lien -> on retourne null (log ci-dessus).
     */
    private function resoudreCaisseDepuisPaiement(Cheque $cheque): ?int
    {
        if (! $cheque->paiement_id) {
            return null;
        }

        // Adapte ce bloc à ton schéma réel.
        // Ici, on suppose que la table `details` porte caisse_id et
        // paiement_id — c'est le cas décrit dans le cahier des charges.
        $detail = DB::table('details')
            ->where('paiement_id', $cheque->paiement_id)
            ->whereNotNull('caisse_id')
            ->orderByDesc('id')
            ->first(['caisse_id']);

        return $detail?->caisse_id ? (int) $detail->caisse_id : null;
    }
}