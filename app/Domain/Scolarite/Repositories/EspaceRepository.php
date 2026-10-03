<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Espace;
use App\Repositories\BaseRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;

/**
 * Repository des espaces familiaux.
 *
 * ⚠️ La liste est filtrée sur l'année active : on ne garde que les espaces
 * qui contiennent au moins un élève inscrit (validé) pour l'année active.
 */
class EspaceRepository extends BaseRepository
{
    public function __construct(
        Espace $model,
        private readonly AnneeScolaireContext $anneeContext
    ) {
        parent::__construct($model);
    }

    /**
     * Liste paginée des espaces avec parents + count élèves (année active).
     */
    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $anneeId = $this->anneeContext->id();

        $query = $this->activeQuery()
            ->with([
                'parents' => function ($q) {
                    $q->where('etat', 1)->orderByDesc('is_principal');
                },
                'comptes' => fn ($q) => $q->where('etat', 1),
            ])
            // Fratrie : tous les élèves actifs rattachés à l'espace (la fiche
            // de l'espace affiche la même liste).
            ->withCount(['eleves' => fn ($q) => $q->where('etat', 1)]);

        // Espaces avec au moins un élève inscrit l'année active, ou encore vides :
        // un espace tout juste créé doit rester visible pour qu'on puisse y
        // rattacher ses parents et ses élèves.
        if ($anneeId) {
            $query->where(function ($q) use ($anneeId) {
                $q->whereHas('eleves', function ($qe) use ($anneeId) {
                    $qe->where('etat', 1)
                       ->whereHas('inscriptions', function ($qi) use ($anneeId) {
                           $qi->where('annee_id', $anneeId)
                              ->where('statut_validation', '!=', \App\Domain\Scolarite\Types\StatutValidationInscription::REJETEE->value);
                       });
                })
                // ... ou espace en cours de constitution : aucun élève, ou des
                // fiches qui n'ont encore jamais été inscrites. Les familles
                // des années passées (élèves inscrits ailleurs) restent masquées.
                ->orWhereDoesntHave('eleves', fn ($qe) => $qe->where('etat', 1)->whereHas('inscriptions'));
            });
        } else {
            $query->whereRaw('1 = 0');
        }

        if (!empty($filtres['recherche'])) {
            $r = $filtres['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('nom_famille', 'like', "%{$r}%")
                  ->orWhereHas('parents', function ($qp) use ($r) {
                      $qp->where('nom_parent', 'like', "%{$r}%")
                         ->orWhere('prenom_parent', 'like', "%{$r}%")
                         ->orWhere('telephone', 'like', "%{$r}%")
                         ->orWhere('email', 'like', "%{$r}%");
                  });
            });
        }

        if (isset($filtres['avec_compte'])) {
            if ($filtres['avec_compte'] === '1') {
                $query->whereHas('comptes', fn ($q) => $q->where('etat', 1));
            } elseif ($filtres['avec_compte'] === '0') {
                $query->whereDoesntHave('comptes', fn ($q) => $q->where('etat', 1));
            }
        }

        return $query->orderBy('nom_famille')->paginate($perPage);
    }

    public function listePourExport(array $filtres = [])
    {
        $anneeId = $this->anneeContext->id();

        $query = $this->activeQuery()
            ->with(['parents' => fn ($q) => $q->where('etat', 1)->orderByDesc('is_principal')])
            ->withCount(['eleves' => function ($q) use ($anneeId) {
                $q->where('etat', 1);
                if ($anneeId) {
                    $q->whereHas('inscriptions', function ($qi) use ($anneeId) {
                        $qi->where('annee_id', $anneeId)
                           ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
                    });
                }
            }]);

        if ($anneeId) {
            $query->whereHas('eleves', function ($q) use ($anneeId) {
                $q->where('etat', 1)
                  ->whereHas('inscriptions', function ($qi) use ($anneeId) {
                      $qi->where('annee_id', $anneeId)
                         ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
                  });
            });
        }

        if (!empty($filtres['recherche'])) {
            $r = $filtres['recherche'];
            $query->where(function ($q) use ($r) {
                $q->where('nom_famille', 'like', "%{$r}%")
                  ->orWhereHas('parents', function ($qp) use ($r) {
                      $qp->where('nom_parent', 'like', "%{$r}%")
                         ->orWhere('prenom_parent', 'like', "%{$r}%");
                  });
            });
        }

        return $query->orderBy('nom_famille')->get();
    }

    /**
     * Élèves sans espace (pour regroupement).
     */
    public function elevesSansEspace(?int $anneeId)
    {
        $query = \App\Domain\Scolarite\Models\Eleve::where('etat', 1)
            ->whereNull('espace_id');

        // Élèves de l'année (dossier non rejeté) ou fiches pas encore inscrites :
        // la famille se constitue dès la création du dossier, avant sa validation.
        if ($anneeId) {
            $query->where(function ($q) use ($anneeId) {
                $q->whereHas('inscriptions', function ($qi) use ($anneeId) {
                    $qi->where('annee_id', $anneeId)
                       ->where('statut_validation', '!=', \App\Domain\Scolarite\Types\StatutValidationInscription::REJETEE->value);
                })->orWhereDoesntHave('inscriptions');
            });
        }

        return $query->orderBy('nom')->get(['id', 'nom', 'prenom', 'matricule']);
    }
}