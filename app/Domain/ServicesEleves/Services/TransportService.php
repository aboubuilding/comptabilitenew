<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\ChauffeurData;
use App\Domain\ServicesEleves\DTO\VoitureData;
use App\Domain\ServicesEleves\Models\Chauffeur;
use App\Domain\ServicesEleves\Models\Voiture;
use App\Domain\ServicesEleves\Repositories\ChauffeurRepository;
use App\Domain\ServicesEleves\Repositories\VoitureRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransportService
{
    public function __construct(
        private VoitureRepository   $voitures,
        private ChauffeurRepository $chauffeurs,
    ) {}

    /* ---- Véhicules ---- */

    public function creerVoiture(VoitureData $data): Voiture
    {
        $existe = $this->voitures->activeQuery()
            ->where('plaque', $data->plaque)
            ->exists();

        if ($existe) {
            throw new RuntimeException("Un véhicule avec la plaque « {$data->plaque} » existe déjà.");
        }

        return DB::transaction(fn () => $this->voitures->create([
            'marque'            => $data->marque,
            'modele'            => $data->modele,
            'plaque'            => $data->plaque,
            'nombre_place'      => $data->nombrePlace,
            'annee_fabrication' => $data->anneeFabrication,
            'numero_chassis'    => $data->numeroChassis,
            'date_achat'        => $data->dateAchat,
            'prix_achat'        => $data->prixAchat,
            'fournisseur'       => $data->fournisseur,
            'kilometrage_actuel'=> $data->kilometrageActuel ?? 0,
            'statut'            => $data->statut,
        ]));
    }

    public function modifierVoiture(int $id, VoitureData $data): Voiture
    {
        $voiture = $this->voitures->findOrFail($id);

        $this->voitures->update($id, [
            'marque'            => $data->marque,
            'modele'            => $data->modele,
            'plaque'            => $data->plaque,
            'nombre_place'      => $data->nombrePlace,
            'annee_fabrication' => $data->anneeFabrication,
            'numero_chassis'    => $data->numeroChassis,
            'date_achat'        => $data->dateAchat,
            'prix_achat'        => $data->prixAchat,
            'fournisseur'       => $data->fournisseur,
            'kilometrage_actuel'=> $data->kilometrageActuel,
            'statut'            => $data->statut,
        ]);

        return $voiture->refresh();
    }

    public function supprimerVoiture(int $id): void
    {
        $voiture = $this->voitures->findOrFail($id);

        if ($voiture->affectations()->exists() || $voiture->zones()->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce véhicule : il est affecté à une zone ou à un chauffeur."
            );
        }

        $this->voitures->delete($id);
    }

    /* ---- Chauffeurs ---- */

    public function creerChauffeur(ChauffeurData $data): Chauffeur
    {
        if ($data->permisConduire) {
            $existe = $this->chauffeurs->activeQuery()
                ->where('permis_conduire', $data->permisConduire)
                ->exists();

            if ($existe) {
                throw new RuntimeException("Ce numéro de permis est déjà enregistré.");
            }
        }

        return DB::transaction(fn () => $this->chauffeurs->create([
            'nom'                 => $data->nom,
            'prenom'              => $data->prenom,
            'permis_conduire'     => $data->permisConduire,
            'date_validite_permis'=> $data->dateValiditePermis,
            'telephone'           => $data->telephone,
            'email'               => $data->email,
            'adresse'             => $data->adresse,
            'statut'              => $data->statut,
        ]));
    }

    public function modifierChauffeur(int $id, ChauffeurData $data): Chauffeur
    {
        $chauffeur = $this->chauffeurs->findOrFail($id);

        $this->chauffeurs->update($id, [
            'nom'                 => $data->nom,
            'prenom'              => $data->prenom,
            'permis_conduire'     => $data->permisConduire,
            'date_validite_permis'=> $data->dateValiditePermis,
            'telephone'           => $data->telephone,
            'email'               => $data->email,
            'adresse'             => $data->adresse,
            'statut'              => $data->statut,
        ]);

        return $chauffeur->refresh();
    }

    public function supprimerChauffeur(int $id): void
    {
        $chauffeur = $this->chauffeurs->findOrFail($id);

        if ($chauffeur->affectations()->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce chauffeur : il a des affectations."
            );
        }

        $this->chauffeurs->delete($id);
    }

    /* ---- Affectations ---- */

    public function affecter(int $voitureId, int $chauffeurId, array $data): void
    {
        DB::transaction(function () use ($voitureId, $chauffeurId, $data) {
            // 1) Clôture l'affectation en cours sur cette voiture
            \App\Domain\ServicesEleves\Models\AffectationVehicule::query()
                ->where('voiture_id', $voitureId)
                ->where(function ($q) {
                    $q->whereNull('date_fin')
                      ->orWhere('date_fin', '>=', now()->toDateString());
                })
                ->update(['date_fin' => now()->toDateString()]);

            // 2) Créer la nouvelle affectation
            \App\Domain\ServicesEleves\Models\AffectationVehicule::create([
                'voiture_id'       => $voitureId,
                'chauffeur_id'     => $chauffeurId,
                'date_debut'       => $data['date_debut'] ?? now()->toDateString(),
                'date_fin'         => $data['date_fin'] ?? null,
                'motif'            => $data['motif'] ?? null,
                'type_affectation' => $data['type_affectation'] ?? 1,
                'annee_id'         => app(\App\Support\AnneeScolaireContext::class)->id(),
            ]);
        });
    }

    /**
     * Relevé de compteur saisi lors d'un entretien ou d'un plein : le
     * kilométrage actuel du véhicule le suit, sans jamais reculer (une
     * saisie rétroactive ne doit pas écraser un relevé plus récent).
     */
    public function releverKilometrage(int $voitureId, int|float|string|null $kilometrage): void
    {
        if ($kilometrage === null || $kilometrage === '') {
            return;
        }

        \Illuminate\Support\Facades\DB::table('voitures')
            ->where('id', $voitureId)
            ->where(function ($q) use ($kilometrage) {
                $q->whereNull('kilometrage_actuel')->orWhere('kilometrage_actuel', '<', (float) $kilometrage);
            })
            ->update(['kilometrage_actuel' => (float) $kilometrage, 'updated_at' => now()]);
    }
}