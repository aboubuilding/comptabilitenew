<?php

namespace App\Domain\Scolarite\Services;

use App\Domain\Scolarite\Models\Inscription;
use App\Domain\Scolarite\Repositories\InscriptionRepository;
use App\Domain\Scolarite\Types\StatutValidationInscription;
use Illuminate\Support\Facades\Log;

/**
 * Porte le cycle de vie d'une inscription (cahier des charges §4, 1.3) :
 * validation/rejet par la Direction, abandon en cours d'année. Justifie
 * un vrai Service — ce sont de vraies décisions métier, pas de simples
 * modifications de formulaire (même critère que AnneeService::cloturer()).
 */
class InscriptionService
{
    public function __construct(private InscriptionRepository $inscriptions)
    {
    }

    public function valider(Inscription $inscription, int $validateurId): void
    {
        $this->inscriptions->update($inscription->id, [
            'statut_validation' => StatutValidationInscription::Validee->value,
            'date_validation'   => now(),
            'utilisateur_id'    => $validateurId,
            'motif_rejet'       => null,
        ]);

        Log::info('Inscription validée', ['inscription_id' => $inscription->id, 'validateur_id' => $validateurId]);
    }

    public function rejeter(Inscription $inscription, string $motif, int $validateurId): void
    {
        $this->inscriptions->update($inscription->id, [
            'statut_validation' => StatutValidationInscription::Rejetee->value,
            'date_validation'   => now(),
            'utilisateur_id'    => $validateurId,
            'motif_rejet'       => $motif,
        ]);

        Log::info('Inscription rejetée', ['inscription_id' => $inscription->id, 'validateur_id' => $validateurId, 'motif' => $motif]);
    }

    public function enregistrerAbandon(Inscription $inscription, string $motif): void
    {
        $this->inscriptions->update($inscription->id, [
            'statut_abandon' => 1,
            'date_abandon'   => now(),
            'motif_abandon'  => $motif,
        ]);

        Log::info('Abandon enregistré', ['inscription_id' => $inscription->id, 'motif' => $motif]);
    }
}