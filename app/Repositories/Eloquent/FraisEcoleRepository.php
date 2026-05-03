<?php

namespace App\Repositories\Eloquent;

use App\Models\FraisEcole;
use App\Repositories\Interfaces\FraisEcoleRepositoryInterface;
use App\Repositories\Eloquent\BaseRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class FraisEcoleRepository extends BaseRepository implements FraisEcoleRepositoryInterface
{
    public function __construct(FraisEcole $model)
    {
        parent::__construct($model);
    }

    public function getByNiveauEtAnnee(int $niveauId, int $anneeId): Collection
    {
        return $this->activeQuery()
            ->where('niveau_id', $niveauId)
            ->where('annee_id', $anneeId)
            ->orderBy('type_paiement')
            ->orderBy('libelle')
            ->get();
    }

    public function existsForNiveauAnnee(int $niveauId, int $anneeId, int $typePaiement, ?int $excludeId = null): bool
    {
        $query = $this->activeQuery()
            ->where('niveau_id', $niveauId)
            ->where('annee_id', $anneeId)
            ->where('type_paiement', $typePaiement);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function getMontantTotalByNiveauEtAnnee(int $niveauId, int $anneeId): float
    {
        return (float) $this->activeQuery()
            ->where('niveau_id', $niveauId)
            ->where('annee_id', $anneeId)
            ->sum('montant');
    }

    public function clonerPourAnnee(int $anneeSource, int $anneeDestination): int
    {
        $frais = $this->activeQuery()->where('annee_id', $anneeSource)->get();

        if ($frais->isEmpty()) {
            return 0;
        }

        // Vérification rapide : éviter de cloner si l'année destination a déjà des frais
        $hasExisting = $this->activeQuery()->where('annee_id', $anneeDestination)->exists();
        if ($hasExisting) {
            throw new Exception("Des frais existent déjà pour l'année de destination.");
        }

        return DB::transaction(function () use ($frais, $anneeDestination) {
            $count = 0;
            foreach ($frais as $f) {
                $this->create([
                    'libelle'       => $f->libelle,
                    'montant'       => $f->montant,
                    'type_paiement' => $f->type_paiement,
                    'type_forfait'  => $f->type_forfait,
                    'niveau_id'     => $f->niveau_id,
                    'annee_id'      => $anneeDestination,
                    'etat'          => 1,
                ]);
                $count++;
            }
            return $count;
        });
    }

    public function getListeGroupedByType(int $anneeId, ?int $niveauId = null): Collection
    {
        $query = $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->select('id', 'libelle', 'montant', 'type_paiement', 'type_forfait', 'niveau_id')
            ->orderBy('type_paiement')
            ->orderBy('libelle');

        if ($niveauId) {
            $query->where('niveau_id', $niveauId);
        }

        return $query->get()->groupBy('type_paiement');
    }
}