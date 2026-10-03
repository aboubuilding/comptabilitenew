<?php

namespace App\Domain\Finances\Observers;

use App\Domain\Finances\Models\Detail;
use App\Domain\ServicesEleves\Services\EvenementService;
use App\Domain\Finances\Types\PaiementStatut;
use Illuminate\Support\Facades\Log;

/**
 * Confirme automatiquement une pré-inscription événement quand le
 * détail de paiement correspondant est encaissé.
 */
class DetailEvenementObserver
{
    public function __construct(
        private EvenementService $evenements,
    ) {}

    public function updated(Detail $detail): void
    {
        // On ne réagit qu'au passage EN_ATTENTE → ENCAISSE
        if (! $detail->isDirty('statut_paiement')) return;

        if ($detail->statut_paiement !== PaiementStatut::ENCAISSE) return;

        // Seuls les détails liés à un événement nous intéressent
        if (! $detail->evenement_id) return;

        try {
            $this->evenements->confirmerParEncaissement(
                evenementId:   (int) $detail->evenement_id,
                inscriptionId: (int) $detail->inscription_id,
            );
        } catch (\Throwable $e) {
            Log::warning('DetailEvenementObserver : échec confirmation', [
                'detail_id'    => $detail->id,
                'evenement_id' => $detail->evenement_id,
                'exception'    => $e->getMessage(),
            ]);
        }
    }
}