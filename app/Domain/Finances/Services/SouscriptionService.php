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
     * Souscrit un frais pour une inscription et applique une remise.
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

        return DB::transaction(fn () => $this->souscriptions->create([
            'frais_ecole_id'       => $frais->id,
            'inscription_id'       => $data->inscriptionId,
            'niveau_id'            => $frais->niveau_id,
            'montant_annuel_prevu' => (float) $frais->montant,
            'taux_remise'          => $data->tauxRemise,
            'type_paiement'        => $frais->type_paiement?->value,
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