<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\SouscriptionData;
use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Models\Souscription;
use App\Domain\Finances\Repositories\SouscriptionRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SouscriptionService
{
    public function __construct(
        private SouscriptionRepository $souscriptions,
    ) {}

    /**
     * Souscrit un service (cantine, transport, activité, événement) pour une
     * inscription et applique une remise. La scolarité ne se souscrit pas :
     * son montant et ses remises sont portés par l'inscription.
     * Règles :
     *  - un seul actif par (frais, inscription)
     *  - remise entre 0 et 100 %
     *  - montant_annuel_prevu = montant du frais
     */
    public function creer(SouscriptionData $data): Souscription
    {
        if ($data->tauxRemise < 0 || $data->tauxRemise > 100) {
            throw new RuntimeException("Le taux de remise doit être compris entre 0 et 100 %.");
        }

        $existe = $this->souscriptions->activeQuery()
            ->where('frais_ecole_id', $data->fraisEcoleId)
            ->where('inscription_id', $data->inscriptionId)
            ->exists();

        if ($existe) {
            throw new RuntimeException("Cet élève est déjà souscrit à ce frais.");
        }

        $frais = FraisEcole::findOrFail($data->fraisEcoleId);
        \App\Domain\Scolarite\Models\Annee::interdireSouscriptionSiCloturee(
            (int) ($frais->annee_id ?: app(\App\Support\AnneeScolaireContext::class)->id())
        );

        if (! in_array($frais->type_paiement, SouscriptionRepository::SERVICES, true)) {
            throw new RuntimeException(
                "Seuls la cantine, le transport, les activités et les événements se souscrivent ici : "
                . "la scolarité et ses remises sont portées par l'inscription."
            );
        }

        return DB::transaction(fn () => $this->souscriptions->create([
            'frais_ecole_id'       => $frais->id,
            'inscription_id'       => $data->inscriptionId,
            'niveau_id'            => $frais->niveau_id,
            'montant_annuel_prevu' => (float) $frais->montant,
            'taux_remise'          => $data->tauxRemise,
            'type_paiement'        => $frais->type_paiement?->value,
            'origine'              => SouscriptionChargementService::SAISIE,
            'libelle'              => $frais->libelle,
            'date_souscription'    => $data->dateSouscription,
            'utilisateur_id'       => $data->utilisateurId,
        ]));
    }

    /** Modifie le taux de remise d'une souscription existante. */
    public function modifierRemise(int $id, float $nouveauTaux, ?string $motif = null): Souscription
    {
        if ($nouveauTaux < 0 || $nouveauTaux > 100) {
            throw new RuntimeException("Le taux de remise doit être compris entre 0 et 100 %.");
        }

        $souscription = $this->souscriptions->findOrFail($id);

        $this->souscriptions->update($id, [
            'taux_remise' => $nouveauTaux,
        ]);

        return $souscription->refresh();
    }

    public function supprimer(int $id): void
    {
        $this->souscriptions->delete($id);
    }
}