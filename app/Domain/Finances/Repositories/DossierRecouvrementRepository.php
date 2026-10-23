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
        $clos = [DossierStatut::SOLDE->value, DossierStatut::IRRECOUVRABLE->value];

        return $this->activeQuery()
            ->with([
                'responsable:id,name',
                'inscription:id,eleve_id,classe_id,niveau_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
                'relances',
                'echeances',
                'paiementCloture:id,reference,date_paiement,montant',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            // Onglet « Dossiers clôturés » : soldés ou irrécouvrables
            ->when(($filters['onglet'] ?? null) === 'clotures', fn ($q) => $q->whereIn('statut', $clos))
            ->when($filters['statut'] ?? null,   fn ($q, $v) => $q->where('statut', $v))
            // Nature : les dossiers qui réclament au moins une échéance de cette nature (écolage, cantine…)
            ->when($filters['nature'] ?? null,   fn ($q, $v) => $q->whereHas('echeances', fn ($e) => $e->where('nature', $v)))
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

    public function compterParStatut(?int $anneeId = null): array
    {
        $rows = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
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

    public function montantsEnCours(?int $anneeId = null): array
    {
        $rows = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
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
                'inscription:id,eleve_id,classe_id,niveau_id,espace_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
                'echeances',
                'paiementCloture:id,reference,date_paiement,montant',
                'relances' => fn ($q) => $q->orderByDesc('date_relance')->orderByDesc('id'),
                'relances.auteur:id,name',
            ])
            ->findOrFail($id);
    }

    /** Le dossier ouvert d'un élève (un seul par élève ; la nature, si donnée, le restreint). */
    public function dossierOuvertPour(int $inscriptionId, ?string $nature = null): ?DossierRecouvrement
    {
        return $this->activeQuery()
            ->where('inscription_id', $inscriptionId)
            ->when($nature, fn ($q, $v) => $q->where('nature', $v))
            ->whereNotIn('statut', [DossierStatut::SOLDE->value, DossierStatut::IRRECOUVRABLE->value])
            ->first();
    }
}