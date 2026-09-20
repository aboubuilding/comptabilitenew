<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\Caisse;
use App\Domain\Finances\Models\Detail;
use App\Domain\Finances\Repositories\DetailRepository;
use App\Domain\Finances\Types\StatutPaiement;
use Illuminate\Support\Facades\Log;

/**
 * "Encaisser" = constater l'entrée effective de l'argent en caisse
 * (cahier des charges §4, 2.3) — pas une simple modification de champ :
 * ça engage la caisse du caissier connecté, d'où le Service plutôt
 * qu'un update() direct depuis le Controller.
 */
class EncaissementService
{
    public function __construct(private DetailRepository $details)
    {
    }

    public function encaisser(Detail $detail, Caisse $caisse, int $caissierId): void
    {
        $this->details->update($detail->id, [
            'statut_paiement'   => StatutPaiement::Encaisse->value,
            'date_encaissement' => now(),
            'caisse_id'         => $caisse->id,
            'caissier_id'       => $caissierId,
            'motif_annulation'  => null,
        ]);

        Log::info('Paiement encaissé', ['detail_id' => $detail->id, 'caisse_id' => $caisse->id, 'caissier_id' => $caissierId]);
    }

    public function annuler(Detail $detail, string $motif): void
    {
        $this->details->update($detail->id, [
            'statut_paiement'   => StatutPaiement::EnAttente->value,
            'date_encaissement' => null,
            'caisse_id'         => null,
            'caissier_id'       => null,
            'motif_annulation'  => $motif,
        ]);

        Log::info('Encaissement annulé', ['detail_id' => $detail->id, 'motif' => $motif]);
    }
}