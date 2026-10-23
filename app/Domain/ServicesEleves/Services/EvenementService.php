<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\ServicesEleves\DTO\EvenementData;
use App\Domain\ServicesEleves\Models\Evenement;
use App\Domain\ServicesEleves\Models\ParticipationEvenement;
use App\Domain\ServicesEleves\Repositories\EvenementRepository;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EvenementService
{
    public function __construct(
        private EvenementRepository $evenements,
    ) {}

    /* ================================================================
       CRÉATION / MODIFICATION
       ================================================================ */

    public function creer(EvenementData $data): Evenement
    {
        if ($data->capaciteLimitee && ! $data->capacite) {
            throw new RuntimeException(
                "Un événement à capacité limitée doit préciser le nombre de places."
            );
        }

        return DB::transaction(fn () => $this->evenements->create([
            'nom'                     => $data->nom,
            'type'                    => $data->type,
            'date_evenement'          => $data->dateEvenement,
            'participation'           => $data->participation,
            'capacite'                => $data->capacite,
            'capacite_limitee'        => $data->capaciteLimitee,
            'date_limite_confirmation'=> $data->dateLimiteConfirmation,
            'description'             => $data->description,
        ]));
    }

    public function modifier(int $id, EvenementData $data): Evenement
    {
        $evenement = $this->evenements->findOrFail($id);

        $this->evenements->update($id, [
            'nom'                     => $data->nom,
            'type'                    => $data->type,
            'date_evenement'          => $data->dateEvenement,
            'participation'           => $data->participation,
            'capacite'                => $data->capacite,
            'capacite_limitee'        => $data->capaciteLimitee,
            'date_limite_confirmation'=> $data->dateLimiteConfirmation,
            'description'             => $data->description,
        ]);

        return $evenement->refresh();
    }

    public function supprimer(int $id): void
    {
        $evenement = $this->evenements->findOrFail($id);

        if ($evenement->participations()->where('etat', 1)->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer : des élèves sont déjà inscrits à cet événement."
            );
        }

        $this->evenements->delete($id);
    }

    /* ================================================================
       PRÉ-INSCRIPTION (événements à capacité limitée)
       ================================================================ */

    /**
     * Pré-inscrit un élève à un événement à capacité limitée.
     * N'encaisse aucun paiement.
     */
    public function preInscrire(int $evenementId, int $inscriptionId, int $userId): ParticipationEvenement
    {
        // L'événement appartient à une année ; à défaut, l'année de l'en-tête
        \App\Domain\Scolarite\Models\Annee::interdireSouscriptionSiCloturee(
            (int) (\Illuminate\Support\Facades\DB::table('evenements')->where('id', $evenementId)->value('annee_id')
                ?: app(\App\Support\AnneeScolaireContext::class)->id())
        );

        return DB::transaction(function () use ($evenementId, $inscriptionId, $userId) {
            $evenement = $this->evenements->findOrFail($evenementId);

            if (! $evenement->isCapaciteLimitee()) {
                throw new RuntimeException(
                    "Cet événement n'est pas à capacité limitée — paiement direct."
                );
            }

            if ($evenement->isDateLimitePassee()) {
                throw new RuntimeException(
                    "La date limite de pré-inscription est dépassée."
                );
            }

            // Capacité atteinte ?
            if ($evenement->places_restantes !== null && $evenement->places_restantes <= 0) {
                throw new RuntimeException(
                    "Cet événement est complet."
                );
            }

            // Déjà pré-inscrit ?
            $existe = ParticipationEvenement::query()
                ->where('evenement_scolaire_id', $evenementId)
                ->where('inscription_id', $inscriptionId)
                ->whereIn('statut', [
                    StatutParticipation::PRE_INSCRIT->value,
                    StatutParticipation::CONFIRME->value,
                ])
                ->exists();

            if ($existe) {
                throw new RuntimeException("Cet élève est déjà pré-inscrit à cet événement.");
            }

            $anneeId = app(AnneeScolaireContext::class)->id();

            return ParticipationEvenement::create([
                'evenement_scolaire_id' => $evenementId,
                'inscription_id'        => $inscriptionId,
                'date_inscription'      => now()->toDateString(),
                'montant_facture'       => $evenement->participation,
                'statut'                => StatutParticipation::PRE_INSCRIT->value,
                'utilisateur_id'        => $userId,
                'annee_id'              => $anneeId,
            ]);
        });
    }

    /**
     * Annule une pré-inscription.
     */
    public function annulerPreInscription(int $id, int $userId): ParticipationEvenement
    {
        return DB::transaction(function () use ($id) {
            $participation = ParticipationEvenement::findOrFail($id);

            if ($participation->statut === StatutParticipation::ANNULE) {
                throw new RuntimeException("Cette pré-inscription est déjà annulée.");
            }

            $participation->update(['statut' => StatutParticipation::ANNULE->value]);

            return $participation->refresh();
        });
    }

    /* ================================================================
       CONFIRMATION AUTOMATIQUE (appelée par l'observer sur details)
       ================================================================ */

    /**
     * Confirme automatiquement une pré-inscription quand le paiement
     * correspondant est encaissé. Appelé par DetailObserver.
     */
    public function confirmerParEncaissement(int $evenementId, int $inscriptionId): ?ParticipationEvenement
    {
        $participation = ParticipationEvenement::query()
            ->where('evenement_scolaire_id', $evenementId)
            ->where('inscription_id', $inscriptionId)
            ->where('statut', StatutParticipation::PRE_INSCRIT->value)
            ->first();

        if (! $participation) {
            // Événement sans limite de places : aucun prérequis, le paiement
            // encaissé vaut participation (cahier des charges § 6, régime 2).
            $evenement = $this->evenements->find($evenementId);
            $dejaConfirme = ParticipationEvenement::query()
                ->where('evenement_scolaire_id', $evenementId)
                ->where('inscription_id', $inscriptionId)
                ->where('statut', StatutParticipation::CONFIRME->value)
                ->exists();

            if ($evenement && ! $evenement->isCapaciteLimitee() && ! $dejaConfirme) {
                return ParticipationEvenement::create([
                    'evenement_scolaire_id' => $evenementId,
                    'inscription_id'        => $inscriptionId,
                    'date_inscription'      => now()->toDateString(),
                    'montant_facture'       => $evenement->participation,
                    'statut'                => StatutParticipation::CONFIRME->value,
                    'utilisateur_id'        => auth()->id(),
                    'annee_id'              => app(AnneeScolaireContext::class)->id(),
                ]);
            }

            Log::info('EvenementService : aucune pré-inscription à confirmer', [
                'evenement_id'   => $evenementId,
                'inscription_id' => $inscriptionId,
            ]);
            return null;
        }

        $participation->update(['statut' => StatutParticipation::CONFIRME->value]);

        return $participation->refresh();
    }
}