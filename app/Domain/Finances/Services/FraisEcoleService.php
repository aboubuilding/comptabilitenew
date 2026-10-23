<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\FraisEcoleData;
use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypePaiement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Frais scolaires. Un frais créé ici est rattaché à l'année scolaire de
 * l'en-tête (injection par BaseRepository) ; seuls l'inscription, l'assurance
 * et la scolarité s'y créent. Un frais échelonné reçoit son plan d'échéancier
 * dans la même opération.
 */
class FraisEcoleService
{
    public function __construct(
        private FraisEcoleRepository $frais,
        private EcheancierService    $echeanciers,
    ) {}

    public function creer(FraisEcoleData $data): FraisEcole
    {
        $this->verifier($data);

        if (! in_array(TypeDetail::tryFrom($data->typeDetail), FraisEcoleRepository::CREABLES, true)) {
            throw new RuntimeException(
                "Seuls les frais d'inscription, d'assurance et de scolarité se créent ici : "
                . "la cantine, le transport, les activités et les événements se définissent dans leurs modules."
            );
        }

        return DB::transaction(function () use ($data) {
            $frais = $this->frais->create($this->attributs($data));
            $this->echelonner($frais, $data);

            return $frais->fresh();
        });
    }

    public function modifier(int $id, FraisEcoleData $data): FraisEcole
    {
        $frais = $this->frais->findOrFail($id);
        $this->verifier($data);

        // La nature d'un frais existant peut rester la sienne ; elle ne devient que l'une des natures créables
        $nouvelle = TypeDetail::tryFrom($data->typeDetail);
        if ($nouvelle !== $frais->type_paiement && ! in_array($nouvelle, FraisEcoleRepository::CREABLES, true)) {
            throw new RuntimeException("Ce frais ne peut pas devenir un frais de cette nature depuis cet écran.");
        }

        return DB::transaction(function () use ($frais, $data) {
            $attributs = $this->attributs($data);
            if ($data->echeances !== []) {
                // L'échéancier ressaisi remplace les échéances du plan propre au frais
                unset($attributs['plan_echeancier_id']);
            }
            $frais->update($attributs);
            $this->echelonner($frais, $data);

            return $frais->refresh();
        });
    }

    public function supprimer(int $id): void
    {
        $frais = $this->frais->findOrFail($id);

        if ($frais->souscriptions()->where('etat', 1)->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce frais : des souscriptions y sont rattachées."
            );
        }

        $this->frais->delete($id);
    }

    private function verifier(FraisEcoleData $data): void
    {
        if ($data->montant <= 0) {
            throw new RuntimeException("Le montant doit être strictement positif.");
        }

        if ($data->modalitePaiement === TypePaiement::ECHELONNE->value
            && ! $data->planEcheancierId && $data->echeances === []) {
            throw new RuntimeException(
                "Un frais échelonné doit avoir un échéancier : saisissez ses échéances ou choisissez un plan existant."
            );
        }

        // Un plan exprimé en montants ne convient qu'à un frais de ce montant
        if ($data->modalitePaiement === TypePaiement::ECHELONNE->value && $data->planEcheancierId && $data->echeances === []) {
            $total = (float) \App\Domain\Finances\Models\PlanEcheancierLigne::where('plan_echeancier_id', $data->planEcheancierId)
                ->where('etat', 1)->sum('montant');
            if ($total > 0 && abs($total - $data->montant) > EcheancierService::TOLERANCE) {
                throw new RuntimeException(sprintf(
                    "Les échéances de ce plan totalisent %s, pas %s : ajustez l'échéancier du frais.",
                    number_format($total, 0, ',', ' '),
                    number_format($data->montant, 0, ',', ' ')
                ));
            }
        }
    }

    private function attributs(FraisEcoleData $data): array
    {
        $echelonne = $data->modalitePaiement === TypePaiement::ECHELONNE->value;

        return [
            'libelle'            => $data->libelle,
            'montant'            => $data->montant,
            'type_paiement'      => $data->typeDetail,
            'modalite_paiement'  => $data->modalitePaiement,
            'type_forfait'       => $data->typeForfait,
            'niveau_id'          => $data->niveauId,
            // Un frais payé comptant n'a pas d'échéancier
            'plan_echeancier_id' => $echelonne ? $data->planEcheancierId : null,
        ];
    }

    /** Échéances saisies avec le frais : elles deviennent son plan d'échéancier. */
    private function echelonner(FraisEcole $frais, FraisEcoleData $data): void
    {
        if ($data->modalitePaiement === TypePaiement::ECHELONNE->value && $data->echeances !== []) {
            $this->echeanciers->definirPourFrais($frais, $data->echeances);
        }
    }
}
