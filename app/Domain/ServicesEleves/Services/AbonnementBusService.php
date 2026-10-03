<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\AbonnementBusData;
use App\Domain\ServicesEleves\Models\AbonnementBus;
use App\Domain\ServicesEleves\Repositories\AbonnementBusRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AbonnementBusService
{
    public function __construct(
        private AbonnementBusRepository $abonnements,
    ) {}

    /**
     * Souscrire un élève à un abonnement bus.
     *
     * IMPORTANT : cette méthode crée l'engagement, PAS le paiement.
     * Le montant dû sera visible immédiatement dans la situation
     * financière de l'élève (module Recouvrement).
     */
    public function souscrire(AbonnementBusData $data): AbonnementBus
    {
        // 1) Un seul abonnement actif par inscription
        $existant = $this->abonnements->activeQuery()
            ->where('inscription_id', $data->inscriptionId)
            ->where('statut', 1)
            ->exists();

        if ($existant) {
            throw new RuntimeException(
                "Cet élève a déjà un abonnement bus actif. Abandonnez-le avant d'en créer un nouveau."
            );
        }

        // 2) Validation des données
        if ($data->montantMensuel <= 0) {
            throw new RuntimeException("Le montant mensuel doit être strictement positif.");
        }
        if ($data->nombreMois <= 0) {
            throw new RuntimeException("Le nombre de mois doit être strictement positif.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();

        return DB::transaction(function () use ($data, $anneeId) {
            $anneeScolaire = \App\Domain\Scolarite\Models\Annee::find($anneeId);
            $dateFin = $anneeScolaire?->date_fin ?? now()->endOfYear()->toDateString();

            return $this->abonnements->create([
                'inscription_id'   => $data->inscriptionId,
                'zone_id'          => $data->zoneId,
                'voiture_id'       => $data->voitureId,
                'ligne_id'         => $data->ligneId,
                'sens'             => $data->sens,
                'point_arret'      => $data->pointArret,
                'date_debut'       => $data->dateDebut,
                'date_fin'         => $dateFin,
                'montant_mensuel'  => $data->montantMensuel,
                'nombre_mois'      => $data->nombreMois,
                'montant_total_du' => $data->montantTotalDu(),
                'statut'           => 1,
                'annee_id'         => $anneeId,
            ]);
        });
    }

    /**
     * Abandonner un abonnement.
     */
    public function abandonner(int $id, string $motif, int $userId): AbonnementBus
    {
        return DB::transaction(function () use ($id, $motif, $userId) {
            $abonnement = $this->abonnements->findOrFail($id);

            if (! $abonnement->isActif()) {
                throw new RuntimeException("Cet abonnement n'est pas actif.");
            }

            $this->abonnements->update($abonnement->id, [
                'statut'        => 0,
                'date_abandon'  => now()->toDateString(),
                'motif_abandon' => $motif,
                'abandonne_par' => $userId,
            ]);

            return $abonnement->refresh();
        });
    }

    /**
     * Assigner un abonnement à une zone / un véhicule / une ligne.
     */
    public function assigner(int $id, array $data): AbonnementBus
    {
        $abonnement = $this->abonnements->findOrFail($id);

        $this->abonnements->update($abonnement->id, [
            'zone_id'    => $data['zone_id'] ?? $abonnement->zone_id,
            'voiture_id' => $data['voiture_id'] ?? $abonnement->voiture_id,
            'ligne_id'   => $data['ligne_id'] ?? $abonnement->ligne_id,
            'sens'       => $data['sens'] ?? $abonnement->sens,
            'point_arret'=> $data['point_arret'] ?? $abonnement->point_arret,
        ]);

        return $abonnement->refresh();
    }
}