<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Eleve;
use App\Models\Paiement;
use App\Models\DetailPaiement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InscriptionService
{
    /**
     * Récupère l'année en cours depuis la session
     */
    protected function getCurrentAnneeId(): ?int
    {
        return session()->get('LoginUser')['annee_id'] ?? null;
    }

    /**
     * Liste des inscriptions pour l'année courante avec agrégats et filtres
     *
     * @param array $filters
     * @return array ['data' => Collection, 'aggregates' => array]
     */
    public function listWithAggregates(array $filters = []): array
    {
        $anneeId = $this->getCurrentAnneeId();
        if (!$anneeId) {
            return ['data' => collect(), 'aggregates' => $this->emptyAggregates()];
        }

        $query = Inscription::with(['eleve', 'cycle', 'niveau', 'classe'])
            ->where('annee_id', $anneeId)
            ->where('etat', 1);

        // 🔍 Filtres
        if (!empty($filters['cycle_id'])) {
            $query->where('cycle_id', $filters['cycle_id']);
        }
        if (!empty($filters['niveau_id'])) {
            $query->where('niveau_id', $filters['niveau_id']);
        }
        if (!empty($filters['classe_id'])) {
            $query->where('classe_id', $filters['classe_id']);
        }
        if (!empty($filters['sexe']) && in_array($filters['sexe'], [0,1,2])) {
            $query->whereHas('eleve', fn($q) => $q->where('sexe', $filters['sexe']));
        }
        if (!empty($filters['type_inscription'])) { // nouveau = 1, ancien = 2 (à adapter)
            $query->where('type_inscription', $filters['type_inscription']);
        }
        if (!empty($filters['nationalite_id'])) {
            $query->whereHas('eleve', fn($q) => $q->where('nationalite_id', $filters['nationalite_id']));
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', $search)
                  ->orWhere('prenom', 'like', $search)
                  ->orWhere('matricule', 'like', $search);
            });
        }

        // Pagination
        $perPage = $filters['per_page'] ?? 15;
        $inscriptions = $query->paginate($perPage);

        // Agrégats (total élèves, garçons, filles, nouveaux)
        $aggregates = $this->computeAggregates($anneeId, $filters);

        // Formatage des données
        $data = $inscriptions->map(fn($ins) => $this->formatInscription($ins));

        return [
            'data'       => $data,
            'aggregates' => $aggregates,
            'pagination' => [
                'current_page' => $inscriptions->currentPage(),
                'last_page'    => $inscriptions->lastPage(),
                'per_page'     => $inscriptions->perPage(),
                'total'        => $inscriptions->total(),
            ]
        ];
    }

    /**
     * Fiche complète d'un élève (info + paiements + CA + remises)
     */
    public function getFicheEleve(int $eleveId): array
    {
        $anneeId = $this->getCurrentAnneeId();
        $eleve = Eleve::with(['nationalite'])->findOrFail($eleveId);

        // Récupérer l'inscription de l'élève pour l'année courante
        $inscription = Inscription::where('eleve_id', $eleveId)
            ->where('annee_id', $anneeId)
            ->first();

        if (!$inscription) {
            throw new \Exception("Aucune inscription trouvée pour cet élève durant l'année scolaire courante.");
        }

        // Paiements associés à cette inscription
        $paiements = Paiement::where('inscription_id', $inscription->id)
            ->where('annee_id', $anneeId)
            ->with('details')
            ->orderBy('date_paiement', 'desc')
            ->get();

        // Calculs financiers
        $totalCA = $paiements->sum('montant');
        $totalRemise = $inscription->taux_remise ?? 0; // remise globale en pourcentage, ou montant ?
        // Si remise en pourcentage, on calcule le montant remisé sur le total des frais
        $montantTotalFrais = ($inscription->frais_scolarite ?? 0)
                            + ($inscription->frais_inscription ?? 0)
                            + ($inscription->frais_assurance ?? 0)
                            + ($inscription->frais_cantine ?? 0)
                            + ($inscription->frais_bus ?? 0)
                            + ($inscription->frais_livre ?? 0)
                            + ($inscription->frais_examen ?? 0);
        $montantRemise = ($inscription->taux_remise / 100) * $montantTotalFrais;

        // CA par type de paiement (mode_paiement)
        $caByMode = $paiements->groupBy('mode_paiement')->map(fn($group) => $group->sum('montant'));

        // Détails des paiements : pour chaque paiement, on liste ses lignes de détails
        $paiementsDetails = $paiements->map(function ($paiement) {
            return [
                'id'            => $paiement->id,
                'reference'     => $paiement->reference,
                'date_paiement' => $paiement->date_paiement,
                'montant_total' => $paiement->montant,
                'mode_paiement' => $paiement->mode_paiement,
                'statut'        => $paiement->statut_paiement,
                'details'       => $paiement->details->map(fn($d) => [
                    'libelle' => $d->libelle,
                    'montant' => $d->montant,
                    'type_paiement' => $d->type_paiement,
                ]),
            ];
        });

        return [
            'eleve' => $this->formatEleve($eleve),
            'inscription' => [
                'id'                => $inscription->id,
                'date_inscription'  => $inscription->date_inscription,
                'cycle'             => $inscription->cycle?->libelle,
                'niveau'            => $inscription->niveau?->libelle,
                'classe'            => $inscription->classe?->libelle,
                'type_inscription'  => $inscription->type_inscription,
                'statut_validation' => $inscription->statut_validation,
                'taux_remise'       => $inscription->taux_remise,
                'frais_scolarite'   => $inscription->frais_scolarite,
                'frais_inscription' => $inscription->frais_inscription,
                'frais_assurance'   => $inscription->frais_assurance,
                'frais_cantine'     => $inscription->frais_cantine,
                'frais_bus'         => $inscription->frais_bus,
                'frais_livre'       => $inscription->frais_livre,
                'frais_examen'      => $inscription->frais_examen,
                'montant_total_frais' => $montantTotalFrais,
                'remise_appliquee'   => $montantRemise,
            ],
            'paiements' => $paiementsDetails,
            'total_chiffre_affaires' => $totalCA,
            'chiffre_affaires_par_mode' => $caByMode,
        ];
    }

    /**
     * Mise à jour des informations personnelles de l'élève
     */
    public function updateEleveInfo(int $eleveId, array $data): Eleve
    {
        $eleve = Eleve::findOrFail($eleveId);
        $eleve->update($data);
        return $eleve;
    }

    // ─────────────────────────────────────────────────────────────
    // Méthodes privées
    // ─────────────────────────────────────────────────────────────

    private function emptyAggregates(): array
    {
        return [
            'total_eleves' => 0,
            'total_garcons' => 0,
            'total_filles' => 0,
            'total_nouveaux' => 0,
        ];
    }

    private function computeAggregates(int $anneeId, array $filters): array
    {
        $query = Inscription::where('annee_id', $anneeId)->where('etat', 1);

        // Appliquer les mêmes filtres (sauf pagination)
        if (!empty($filters['cycle_id']))     $query->where('cycle_id', $filters['cycle_id']);
        if (!empty($filters['niveau_id']))    $query->where('niveau_id', $filters['niveau_id']);
        if (!empty($filters['classe_id']))    $query->where('classe_id', $filters['classe_id']);
        if (!empty($filters['sexe'])) {
            $query->whereHas('eleve', fn($q) => $q->where('sexe', $filters['sexe']));
        }
        if (!empty($filters['type_inscription'])) {
            $query->where('type_inscription', $filters['type_inscription']);
        }
        if (!empty($filters['nationalite_id'])) {
            $query->whereHas('eleve', fn($q) => $q->where('nationalite_id', $filters['nationalite_id']));
        }
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query->whereHas('eleve', fn($q) => $q->where('nom', 'like', $search)->orWhere('prenom', 'like', $search));
        }

        $total = $query->count();
        $garcons = (clone $query)->whereHas('eleve', fn($q) => $q->where('sexe', 1))->count();
        $filles = (clone $query)->whereHas('eleve', fn($q) => $q->where('sexe', 0))->count();
        $nouveaux = (clone $query)->where('type_inscription', 1)->count(); // 1 = nouveau

        return [
            'total_eleves' => $total,
            'total_garcons' => $garcons,
            'total_filles' => $filles,
            'total_nouveaux' => $nouveaux,
        ];
    }

    private function formatInscription(Inscription $ins): array
    {
        return [
            'id' => $ins->id,
            'eleve' => $this->formatEleve($ins->eleve),
            'cycle' => $ins->cycle?->libelle,
            'niveau' => $ins->niveau?->libelle,
            'classe' => $ins->classe?->libelle,
            'date_inscription' => $ins->date_inscription,
            'type_inscription' => $ins->type_inscription,
            'statut_validation' => $ins->statut_validation,
        ];
    }

    private function formatEleve(?Eleve $eleve): ?array
    {
        if (!$eleve) return null;
        return [
            'id' => $eleve->id,
            'matricule' => $eleve->matricule,
            'nom' => $eleve->nom,
            'prenom' => $eleve->prenom,
            'prenom_usuel' => $eleve->prenom_usuel,
            'sexe' => $eleve->sexe,
            'date_naissance' => $eleve->date_naissance,
            'lieu_naissance' => $eleve->lieu_naissance,
            'nationalite' => $eleve->nationalite?->libelle,
            'ecole_provenance' => $eleve->ecole_provenance,
            'personne_prevenir' => $eleve->personne_prevenir,
            'allergie' => $eleve->allergie,
            'photo' => $eleve->photo,
        ];
    }

    // Dans App\Services\InscriptionService

/**
 * Récupère les données pour l'affichage de la liste (server-side)
 */
public function getListForView(array $filters = []): array
{
    $anneeId = $this->getCurrentAnneeId();
    if (!$anneeId) {
        return [
            'inscriptions' => collect(),
            'aggregates'   => $this->emptyAggregates(),
            'filters'      => $filters,
        ];
    }

    $query = Inscription::with(['eleve', 'cycle', 'niveau', 'classe'])
        ->where('annee_id', $anneeId)
        ->where('etat', 1);

    // Application des filtres (cycle, niveau, classe, sexe, type_inscription, nationalite, search)
    if (!empty($filters['cycle_id'])) {
        $query->where('cycle_id', $filters['cycle_id']);
    }
    if (!empty($filters['niveau_id'])) {
        $query->where('niveau_id', $filters['niveau_id']);
    }
    if (!empty($filters['classe_id'])) {
        $query->where('classe_id', $filters['classe_id']);
    }
    if (isset($filters['sexe']) && in_array($filters['sexe'], [0,1,2])) {
        $query->whereHas('eleve', fn($q) => $q->where('sexe', $filters['sexe']));
    }
    if (!empty($filters['type_inscription'])) {
        $query->where('type_inscription', $filters['type_inscription']);
    }
    if (!empty($filters['nationalite_id'])) {
        $query->whereHas('eleve', fn($q) => $q->where('nationalite_id', $filters['nationalite_id']));
    }
    if (!empty($filters['search'])) {
        $search = '%' . $filters['search'] . '%';
        $query->whereHas('eleve', function ($q) use ($search) {
            $q->where('nom', 'like', $search)
              ->orWhere('prenom', 'like', $search)
              ->orWhere('matricule', 'like', $search);
        });
    }

    // Pagination
    $perPage = $filters['per_page'] ?? 15;
    $inscriptions = $query->paginate($perPage);

    // Agrégats
    $aggregates = $this->computeAggregates($anneeId, $filters);

    return [
        'inscriptions' => $inscriptions,
        'aggregates'   => $aggregates,
        'filters'      => $filters,
    ];
}

/**
 * Récupère les données pour la fiche élève (vue)
 */
public function getFicheForView(int $eleveId): array
{
    $anneeId = $this->getCurrentAnneeId();
    $eleve = Eleve::with('nationalite')->findOrFail($eleveId);

    $inscription = Inscription::where('eleve_id', $eleveId)
        ->where('annee_id', $anneeId)
        ->first();

    if (!$inscription) {
        throw new \Exception("Aucune inscription trouvée pour l'année en cours.");
    }

    $paiements = Paiement::where('inscription_id', $inscription->id)
        ->where('annee_id', $anneeId)
        ->with('details')
        ->orderBy('date_paiement', 'desc')
        ->get();

    // Calculs financiers
    $montantTotalFrais = ($inscription->frais_scolarite ?? 0)
                        + ($inscription->frais_inscription ?? 0)
                        + ($inscription->frais_assurance ?? 0)
                        + ($inscription->frais_cantine ?? 0)
                        + ($inscription->frais_bus ?? 0)
                        + ($inscription->frais_livre ?? 0)
                        + ($inscription->frais_examen ?? 0);
    $montantRemise = ($inscription->taux_remise / 100) * $montantTotalFrais;
    $totalCA = $paiements->sum('montant');

    $caByMode = [];
    foreach ($paiements->groupBy('mode_paiement') as $mode => $group) {
        $caByMode[$mode] = $group->sum('montant');
    }

    return [
        'eleve'                 => $eleve,
        'inscription'           => $inscription,
        'paiements'             => $paiements,
        'montant_total_frais'   => $montantTotalFrais,
        'montant_remise'        => $montantRemise,
        'total_chiffre_affaires'=> $totalCA,
        'ca_par_mode'           => $caByMode,
    ];
}
}