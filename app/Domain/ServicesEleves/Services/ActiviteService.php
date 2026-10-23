<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\ActiviteData;
use App\Domain\ServicesEleves\DTO\InscriptionActiviteData;
use App\Domain\ServicesEleves\Models\Activite;
use App\Domain\ServicesEleves\Models\InscriptionActivite;
use App\Domain\ServicesEleves\Repositories\ActiviteRepository;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ActiviteService
{
    public function __construct(
        private ActiviteRepository $activites,
    ) {}

    public function creer(ActiviteData $data): Activite
    {
        if ($data->montant < 0) {
            throw new RuntimeException("Le montant ne peut pas être négatif.");
        }

        return DB::transaction(fn () => $this->activites->create([
            'libelle'          => $data->libelle,
            'description'      => $data->description,
            'montant'          => $data->montant,
            'type'             => $data->type,
            'encadreur'        => $data->encadreur,
            'contact_encadreur'=> $data->contactEncadreur,
            'niveau_id'        => $data->niveauId,
        ]));
    }

    public function modifier(int $id, ActiviteData $data): Activite
    {
        $activite = $this->activites->findOrFail($id);

        $this->activites->update($id, [
            'libelle'          => $data->libelle,
            'description'      => $data->description,
            'montant'          => $data->montant,
            'type'             => $data->type,
            'encadreur'        => $data->encadreur,
            'contact_encadreur'=> $data->contactEncadreur,
            'niveau_id'        => $data->niveauId,
        ]);

        return $activite->refresh();
    }

    public function supprimer(int $id): void
    {
        $activite = $this->activites->findOrFail($id);

        if ($activite->inscriptions()->where('etat', 1)->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer : des élèves sont inscrits à cette activité."
            );
        }

        $this->activites->delete($id);
    }

    /**
     * Souscrire un élève à une activité.
     * Crée l'engagement SANS créer de paiement (CDC §6).
     * Le montant dû apparaît immédiatement dans la situation financière.
     */
    public function souscrire(InscriptionActiviteData $data): InscriptionActivite
    {
        $activite = $this->activites->findOrFail($data->activiteId);

        // Un seul actif par (élève, activité)
        $existe = InscriptionActivite::query()
            ->where('eleve_id', $data->eleveId)
            ->where('activite_id', $data->activiteId)
            ->whereIn('statut', [
                StatutInscriptionActivite::EN_ATTENTE->value,
                StatutInscriptionActivite::CONFIRMEE->value,
            ])
            ->where('etat', 1)
            ->exists();

        if ($existe) {
            throw new RuntimeException("Cet élève est déjà inscrit à cette activité.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();
        \App\Domain\Scolarite\Models\Annee::interdireSouscriptionSiCloturee($anneeId);

        return DB::transaction(fn () => InscriptionActivite::create([
            'eleve_id'         => $data->eleveId,
            'activite_id'      => $data->activiteId,
            'annee_id'         => $anneeId,
            'date_inscription' => $data->dateInscription,
            'montant_du'       => $data->montantDu ?? $activite->montant,
            'statut'           => StatutInscriptionActivite::EN_ATTENTE->value,
        ]));
    }

    /**
     * Annuler l'inscription d'un élève à une activité.
     */
    public function annulerInscription(int $id, string $motif = 'Annulation manuelle'): InscriptionActivite
    {
        return DB::transaction(function () use ($id) {
            $inscription = InscriptionActivite::findOrFail($id);

            if ($inscription->statut === StatutInscriptionActivite::ANNULEE) {
                throw new RuntimeException("Cette inscription est déjà annulée.");
            }

            $inscription->update([
                'statut' => StatutInscriptionActivite::ANNULEE->value,
            ]);

            return $inscription->refresh();
        });
    }
}