<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Eleve;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * paginateWithFilters() porte un calcul PROVISOIRE de "montant encaissé"
 * (somme des details.montant encaissés de l'année filtrée). Le "montant
 * dû" se lit via Inscription::montant_engage (accesseur). Les deux sont
 * à remplacer par une lecture de situations_recouvrement une fois le
 * module Recouvrement construit (architecture prévue dans le cahier des
 * charges, pas encore implémentée) — ne pas considérer ces chiffres
 * comme la source de vérité définitive du recouvrement.
 */
class EleveRepository extends BaseRepository
{
    public function __construct(Eleve $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, int $anneeId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'nationalite',
                'inscriptionPourAnnee' => fn ($q) => $q->where('annee_id', $anneeId)
                    ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::Validee->value)
                    ->with('classe'),
            ])
            ->withSum(['details as montant_encaisse' => function ($q) use ($anneeId) {
                $q->where('details.annee_id', $anneeId)->whereNotNull('date_encaissement');
            }], 'montant')
            ->whereHas('inscriptions', function ($q) use ($anneeId, $filters) {
                // Un élève n'apparaît pour une année que s'il y possède une
                // inscription VALIDÉE — une inscription en attente ou
                // rejetée ne suffit pas (cahier des charges : cycle de vie
                // "saisie -> validation Direction -> abandon éventuel").
                $q->where('annee_id', $anneeId)
                    ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::Validee->value);

                if (!empty($filters['cycle_id'])) {
                    $q->where('cycle_id', $filters['cycle_id']);
                }
                if (!empty($filters['niveau_id'])) {
                    $q->where('niveau_id', $filters['niveau_id']);
                }
                if (!empty($filters['classe_id'])) {
                    $q->where('classe_id', $filters['classe_id']);
                }
                if (isset($filters['statut']) && $filters['statut'] !== '') {
                    $q->where('statut_abandon', $filters['statut']);
                }
            })
            ->when($filters['sexe'] ?? null, fn ($q, $s) => $q->where('sexe', $s))
            ->when($filters['search'] ?? null, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('nom', 'like', "%{$s}%")
                        ->orWhere('prenom', 'like', "%{$s}%")
                        ->orWhere('matricule', 'like', "%{$s}%");
                });
            })
            ->orderBy('nom')
            ->paginate($perPage);
    }

    /**
     * TODO : bloquer l'archivage si des paiements/inscriptions ne sont
     * pas soldés, une fois le module Recouvrement construit. Retourne
     * true partout en attendant, pour ne pas bloquer sur une règle qui
     * n'existe pas encore.
     */
    public function canArchiver(int $eleveId): bool
    {
        return true;
    }
}