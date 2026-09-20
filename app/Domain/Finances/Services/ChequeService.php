<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\ChequeData;
use App\Domain\Finances\Models\Cheque;
use App\Domain\Finances\Repositories\ChequeRepository;
use App\Domain\Finances\Types\ChequeStatut;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChequeService
{
    public function __construct(
        private ChequeRepository $cheques,
    ) {}

    /** Enregistre un nouveau chèque (statut initial : émis). */
    public function enregistrer(ChequeData $data): Cheque
    {
        return DB::transaction(fn () => $this->cheques->create([
            'numero'        => $data->numero,
            'emetteur'      => $data->emetteur,
            'montant'       => $data->montant,
            'banque_id'     => $data->banqueId,
            'date_emission' => $data->dateEmission,
            'statut'        => $data->statut->value,
            // annee_id injecté par BaseRepository (AnneeScolaireContext).
        ]));
    }

    /**
     * Rapprochement bancaire : marque un chèque comme encaissé (ou le
     * repasse en attente). Trace l'auteur et la date.
     */
    public function marquerEncaisse(int $chequeId, int $userId, ?string $dateEncaissement = null): Cheque
    {
        return DB::transaction(function () use ($chequeId, $userId, $dateEncaissement) {
            $cheque = $this->cheques->findOrFail($chequeId);

            if ($cheque->statut === ChequeStatut::ENCAISSE) {
                throw new RuntimeException("Ce chèque est déjà marqué comme encaissé.");
            }

            $this->cheques->update($cheque->id, [
                'statut'             => ChequeStatut::ENCAISSE->value,
                'date_encaissement'  => $dateEncaissement ?? now()->toDateString(),
                'date_rapprochement' => now(),
                'rapproche_par'      => $userId,
                'motif_rejet'        => null,
            ]);

            return $cheque->refresh();
        });
    }

    /** Marque un chèque comme rejeté (impayé). Motif obligatoire. */
    public function marquerRejete(int $chequeId, int $userId, string $motif): Cheque
    {
        if (blank($motif)) {
            throw new RuntimeException("Le motif de rejet est obligatoire.");
        }

        return DB::transaction(function () use ($chequeId, $userId, $motif) {
            $cheque = $this->cheques->findOrFail($chequeId);

            if ($cheque->statut === ChequeStatut::REJETE) {
                throw new RuntimeException("Ce chèque est déjà marqué comme rejeté.");
            }

            $this->cheques->update($cheque->id, [
                'statut'             => ChequeStatut::REJETE->value,
                'date_rapprochement' => now(),
                'rapproche_par'      => $userId,
                'motif_rejet'        => $motif,
            ]);

            return $cheque->refresh();
        });
    }

    /** Remet un chèque rejeté en attente (nouvelle tentative). */
    public function remettreEnAttente(int $chequeId): Cheque
    {
        return DB::transaction(function () use ($chequeId) {
            $cheque = $this->cheques->findOrFail($chequeId);

            if (! $cheque->statut->isFinal()) {
                throw new RuntimeException("Ce chèque n'est pas dans un état final.");
            }

            $this->cheques->update($cheque->id, [
                'statut'             => ChequeStatut::EMIS->value,
                'date_rapprochement' => null,
                'rapproche_par'      => null,
                'motif_rejet'        => null,
            ]);

            return $cheque->refresh();
        });
    }
}