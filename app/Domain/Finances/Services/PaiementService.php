<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\PaiementData;
use App\Domain\Finances\Models\Paiement;
use App\Domain\Finances\Repositories\PaiementRepository;
use App\Domain\Finances\Types\PaiementStatut;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaiementService
{
    public function __construct(
        private PaiementRepository $paiements,
    ) {}

    /**
     * Enregistre un paiement complet (en-tête + détails ventilés par nature).
     * Le statut initial est EN_ATTENTE : l'entrée effective en caisse sera
     * constatée par le Caissier (module Encaissements).
     */
    public function enregistrer(PaiementData $data): Paiement
    {
        if (empty($data->lignes)) {
            throw new RuntimeException("Le panier est vide : ajoutez au moins une ligne.");
        }

        $montantTotal = $data->montantTotal();
        if ($montantTotal <= 0) {
            throw new RuntimeException("Le montant total doit être strictement positif.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();
        if (! $anneeId) {
            throw new RuntimeException("Aucune année scolaire active.");
        }

        return DB::transaction(function () use ($data, $montantTotal, $anneeId) {
            // 1) En-tête
            $paiement = $this->paiements->create([
                'reference'         => $this->paiements->genererReference(),
                'payeur'            => $data->payeur,
                'telephone_payeur'  => $data->telephonePayeur,
                'date_paiement'     => now()->toDateString(),
                'statut_paiement'   => PaiementStatut::EN_ATTENTE->value,
                'mode_paiement'     => $data->modePaiement,
                'inscription_id'    => $data->inscriptionId,
                'utilisateur_id'    => $data->comptableId,
                'annee_id'          => $anneeId,
                'montant'           => $montantTotal,
            ]);

            // 2) Lignes de détail (ventilation automatique par nature)
            foreach ($data->lignes as $ligne) {
                $paiement->details()->create(
                    $ligne->toDetailPayload(
                        paiementId:   $paiement->id,
                        inscriptionId: $data->inscriptionId,
                        anneeId:      $anneeId,
                        comptableId:  $data->comptableId,
                    )
                );
            }

            // 3) Paiement par chèque : le chèque entre au portefeuille (statut « émis »),
            //    où il sera rapproché (module Banques & Chèques).
            if ($data->cheque !== null) {
                $cheque = \App\Domain\Finances\Models\Cheque::create([
                    'numero'        => $data->cheque['numero'],
                    'emetteur'      => ($data->cheque['emetteur'] ?? null) ?: $data->payeur,
                    'montant'       => $montantTotal,
                    'banque_id'     => $data->cheque['banque_id'] ?? null,
                    'date_emission' => ($data->cheque['date_emission'] ?? null) ?: now()->toDateString(),
                    'statut'        => \App\Domain\Finances\Types\ChequeStatut::EMIS->value,
                    'paiement_id'   => $paiement->id,
                    'annee_id'      => $anneeId,
                    'etat'          => 1,
                ]);

                $paiement->update(['cheque_id' => $cheque->id]);
            }

            return $paiement->fresh('details');
        });
    }

    /**
     * Modifie un paiement non encore encaissé.
     * Stratégie simple : on remplace complètement le panier.
     */
    public function modifier(int $id, PaiementData $data): Paiement
    {
        return DB::transaction(function () use ($id, $data) {
            $paiement = $this->paiements->findOrFail($id);

            if (! $paiement->isEnAttente()) {
                throw new RuntimeException(
                    "Seul un paiement en attente d'encaissement peut être modifié."
                );
            }

            $montantTotal = $data->montantTotal();
            if ($montantTotal <= 0) {
                throw new RuntimeException("Le montant total doit être strictement positif.");
            }

            // 1) Mise à jour de l'en-tête
            $this->paiements->update($paiement->id, [
                'payeur'           => $data->payeur,
                'telephone_payeur' => $data->telephonePayeur,
                'mode_paiement'    => $data->modePaiement,
                'montant'          => $montantTotal,
            ]);

            // 2) Remplacement complet des détails (désactivation + réinsertion)
            $paiement->details()->update(['etat' => \App\Repositories\BaseRepository::INACTIF]);

            foreach ($data->lignes as $ligne) {
                $paiement->details()->create(
                    $ligne->toDetailPayload(
                        paiementId:   $paiement->id,
                        inscriptionId: $data->inscriptionId,
                        anneeId:      $paiement->annee_id,
                        comptableId:  $data->comptableId,
                    )
                );
            }

            return $paiement->fresh('details');
        });
    }

    /** Annule un paiement non encore encaissé. */
    public function annuler(int $id, string $motif, int $userId): Paiement
    {
        return DB::transaction(function () use ($id, $motif, $userId) {
            $paiement = $this->paiements->findOrFail($id);

            if (! $paiement->isEnAttente()) {
                throw new RuntimeException(
                    "Un paiement encaissé ou déjà annulé ne peut plus être annulé."
                );
            }

            // Une ligne déjà encaissée est entrée en caisse : on n'annule pas le paiement
            if ($paiement->details()->where('etat', 1)->where('statut_paiement', PaiementStatut::ENCAISSE->value)->exists()) {
                throw new RuntimeException(
                    "Une partie de ce paiement est déjà encaissée : annulez d'abord l'encaissement."
                );
            }

            $this->paiements->update($paiement->id, [
                'statut_paiement'    => PaiementStatut::ANNULE->value,
                'motif_suppression'  => $motif,
            ]);

            $paiement->details()->update(['statut_paiement' => PaiementStatut::ANNULE->value]);

            return $paiement->fresh('details');
        });
    }
}