<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\DossierRecouvrement;
use App\Domain\Finances\Types\DossierStatut;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DossierRecouvrementRepository extends BaseRepository
{
    public function __construct(DossierRecouvrement $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'responsable:id,name',
                'inscription:id,eleve_id,classe_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'relances',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['statut'] ?? null,   fn ($q, $v) => $q->where('statut', $v))
            ->when($filters['nature'] ?? null,   fn ($q, $v) => $q->where('nature', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                // Recherche par élève (nom, prénom, matricule)
                $q->whereHas('inscription.eleve', function ($eleve) use ($v) {
                    $eleve->where('nom', 'like', "%{$v}%")
                        ->orWhere('prenom', 'like', "%{$v}%")
                        ->orWhere('matricule', 'like', "%{$v}%");
                });
            })
            ->orderByRaw("FIELD(statut, 'en_cours', 'echeancier', 'contentieux', 'irrecouvrable', 'solde')")
            ->orderByDesc('date_ouverture')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function compterParStatut(): array
    {
        $rows = $this->activeQuery()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->all();

        return [
            'en_cours'      => (int) ($rows[DossierStatut::EN_COURS->value]      ?? 0),
            'echeancier'    => (int) ($rows[DossierStatut::ECHEANCIER->value]    ?? 0),
            'solde'         => (int) ($rows[DossierStatut::SOLDE->value]         ?? 0),
            'irrecouvrable' => (int) ($rows[DossierStatut::IRRECOUVRABLE->value] ?? 0),
            'contentieux'   => (int) ($rows[DossierStatut::CONTENTIEUX->value]   ?? 0),
        ];
    }

    public function montantsEnCours(): array
    {
        $rows = $this->activeQuery()
            ->whereIn('statut', [DossierStatut::EN_COURS->value, DossierStatut::ECHEANCIER->value, DossierStatut::CONTENTIEUX->value])
            ->selectRaw('SUM(montant_initial_du - montant_recouvre) as total_restant')
            ->first();

        return [
            'total_restant' => round((float) ($rows->total_restant ?? 0), 2),
        ];
    }

    public function findAvecDetails(int $id): DossierRecouvrement
    {
        return $this->activeQuery()
            ->with([
                'responsable:id,name',
                'inscription:id,eleve_id,classe_id,espace_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'relances' => fn ($q) => $q->orderByDesc('date_relance'),
                'relances.auteur:id,name',
            ])
            ->findOrFail($id);
    }

    /** Dossiers ouverts pour un élève/inscription donnés (unicité). */
    public function dossierOuvertPour(int $inscriptionId, string $nature): ?DossierRecouvrement
    {
        return $this->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->where('nature', $nature)
            ->whereNotIn('statut', [DossierStatut::SOLDE->value, DossierStatut::IRRECOUVRABLE->value])
            ->first();
    }
}