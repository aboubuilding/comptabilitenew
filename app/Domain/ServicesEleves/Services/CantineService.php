<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\InscriptionCantineData;
use App\Domain\ServicesEleves\Models\InscriptionCantine;
use App\Domain\ServicesEleves\Repositories\InscriptionCantineRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CantineService
{
    public function __construct(
        private InscriptionCantineRepository $inscriptions,
    ) {}

    /**
     * Souscrire un élève à la cantine.
     *
     * IMPORTANT : cette méthode crée l'engagement, PAS le paiement.
     * Le montant dû sera visible immédiatement dans la situation
     * financière de l'élève (module Recouvrement).
     */
    public function souscrire(InscriptionCantineData $data): InscriptionCantine
    {
        $existant = $this->inscriptions->activeQuery()
            ->where('inscription_id', $data->inscriptionId)
            ->where('statut', 1)
            ->exists();

        if ($existant) {
            throw new RuntimeException(
                "Cet élève a déjà une inscription cantine active. Abandonnez-la avant d'en créer une nouvelle."
            );
        }

        if ($data->montantMensuel <= 0) {
            throw new RuntimeException("Le montant mensuel doit être strictement positif.");
        }
        if ($data->nombreMois <= 0) {
            throw new RuntimeException("Le nombre de mois doit être strictement positif.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();
        \App\Domain\Scolarite\Models\Annee::interdireSouscriptionSiCloturee($anneeId);

        return DB::transaction(function () use ($data, $anneeId) {
            $anneeScolaire = \App\Domain\Scolarite\Models\Annee::find($anneeId);
            $dateFin = $anneeScolaire?->date_fin ?? now()->endOfYear()->toDateString();

            return $this->inscriptions->create([
                'inscription_id'   => $data->inscriptionId,
                'frais_ecole_id'   => $data->fraisEcoleId,
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

    public function abandonner(int $id, string $motif, int $userId): InscriptionCantine
    {
        return DB::transaction(function () use ($id, $motif, $userId) {
            $inscription = $this->inscriptions->findOrFail($id);

            if (! $inscription->isActif()) {
                throw new RuntimeException("Cette inscription n'est pas active.");
            }

            $this->inscriptions->update($inscription->id, [
                'statut'        => 0,
                'date_abandon'  => now()->toDateString(),
                'motif_abandon' => $motif,
                'abandonne_par' => $userId,
            ]);

            return $inscription->refresh();
        });
    }
}