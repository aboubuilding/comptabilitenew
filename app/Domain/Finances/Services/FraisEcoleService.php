<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\DTO\FraisEcoleData;
use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Repositories\FraisEcoleRepository;
use App\Domain\Finances\Types\TypePaiement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FraisEcoleService
{
    public function __construct(
        private FraisEcoleRepository $frais,
    ) {}

    public function creer(FraisEcoleData $data): FraisEcole
    {
        if ($data->montant <= 0) {
            throw new RuntimeException("Le montant doit être strictement positif.");
        }

        // Règle : un frais échelonné DOIT avoir un plan d'échéancier.
        if ($data->modalitePaiement === TypePaiement::ECHELONNE->value && ! $data->planEcheancierId) {
            throw new RuntimeException(
                "Un frais échelonné doit être rattaché à un plan d'échéancier."
            );
        }

        return DB::transaction(fn () => $this->frais->create([
            'libelle'            => $data->libelle,
            'montant'            => $data->montant,
            'type_paiement'      => $data->typeDetail,
            'modalite_paiement'  => $data->modalitePaiement,
            'type_forfait'       => $data->typeForfait,
            'niveau_id'          => $data->niveauId,
            'plan_echeancier_id' => $data->planEcheancierId,
            // annee_id injecté par BaseRepository (AnneeScolaireContext)
        ]));
    }

    public function modifier(int $id, FraisEcoleData $data): FraisEcole
    {
        $frais = $this->frais->findOrFail($id);

        if ($data->montant <= 0) {
            throw new RuntimeException("Le montant doit être strictement positif.");
        }

        if ($data->modalitePaiement === TypePaiement::ECHELONNE->value && ! $data->planEcheancierId) {
            throw new RuntimeException(
                "Un frais échelonné doit être rattaché à un plan d'échéancier."
            );
        }

        $this->frais->update($id, [
            'libelle'            => $data->libelle,
            'montant'            => $data->montant,
            'type_paiement'      => $data->typeDetail,
            'modalite_paiement'  => $data->modalitePaiement,
            'type_forfait'       => $data->typeForfait,
            'niveau_id'          => $data->niveauId,
            'plan_echeancier_id' => $data->planEcheancierId,
        ]);

        return $frais->refresh();
    }

    public function supprimer(int $id): void
    {
        $frais = $this->frais->findOrFail($id);

        if ($frais->souscriptions()->exists()) {
            throw new RuntimeException(
                "Impossible de supprimer ce frais : des souscriptions y sont rattachées."
            );
        }

        $this->frais->delete($id);
    }
}