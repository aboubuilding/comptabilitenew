<?php

namespace App\Domain\Scolarite\Repositories;

use App\Domain\Scolarite\Models\Eleve;
use App\Repositories\BaseRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repository des élèves.
 *
 * ⚠️ Règle métier : la liste des élèves est TOUJOURS filtrée sur
 * l'année scolaire active (AnneeScolaireContext::id()). Un élève qui n'a
 * pas d'inscription validée pour l'année active n'apparaît pas.
 *
 * Méthode A pour le montant dû / encaissé : lecture de situations_recouvrement
 * avec dégradation gracieuse si la table n'existe pas.
 */
class EleveRepository extends BaseRepository
{
    public function __construct(
        Eleve $model,
        private readonly AnneeScolaireContext $anneeContext
    ) {
        parent::__construct($model);
    }

    public const SITUATION_INSCRITS = 'inscrits';
    public const SITUATION_SANS     = 'sans';
    public const SITUATION_TOUS     = 'tous';

    /**
     * Liste paginée des élèves ; par défaut, les INSCRITS de l'année active.
     */
    public function listePaginee(array $filtres = [], int $perPage = 15)
    {
        $anneeId = $this->anneeContext->id();

        $query = $this->activeQuery()
            ->with([
                'nationalite',
                'inscriptionActive.classe',
                'inscriptionActive.niveau',
                'inscriptionActive.cycle',
            ]);

        // Par défaut : les inscrits de l'année active (voir appliquerFiltreAnnee)
        $this->appliquerFiltreAnnee($query, $anneeId, $filtres['situation'] ?? self::SITUATION_INSCRITS);

        if (!empty($filtres['recherche'])) {
            $query->recherche($filtres['recherche']);
        }
        if (!empty($filtres['sexe'])) {
            $query->where('sexe', $filtres['sexe']);
        }
        if (!empty($filtres['nationalite_id'])) {
            $query->where('nationalite_id', $filtres['nationalite_id']);
        }
        if (!empty($filtres['classe_id'])) {
            $query->whereHas('inscriptions', function ($q) use ($filtres, $anneeId) {
                $q->where('classe_id', $filtres['classe_id'])->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
                if ($anneeId) $q->where('annee_id', $anneeId);
            });
        }
        if (!empty($filtres['niveau_id'])) {
            $query->whereHas('inscriptions', function ($q) use ($filtres, $anneeId) {
                $q->where('niveau_id', $filtres['niveau_id'])->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
                if ($anneeId) $q->where('annee_id', $anneeId);
            });
        }
        if (!empty($filtres['cycle_id'])) {
            $query->whereHas('inscriptions', function ($q) use ($filtres, $anneeId) {
                $q->where('cycle_id', $filtres['cycle_id'])->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
                if ($anneeId) $q->where('annee_id', $anneeId);
            });
        }

        $eleves = $query->orderBy('nom')->orderBy('prenom')->paginate($perPage);

        $this->injecterMontants($eleves->getCollection(), $anneeId);

        return $eleves;
    }

    /**
     * Liste complète pour export Excel.
     */
    public function listePourExport(array $filtres = [])
    {
        $anneeId = $this->anneeContext->id();

        $query = $this->activeQuery()
            ->with(['nationalite', 'inscriptionActive.classe', 'inscriptionActive.niveau', 'inscriptionActive.cycle']);

        $this->appliquerFiltreAnnee($query, $anneeId, $filtres['situation'] ?? self::SITUATION_INSCRITS);

        if (!empty($filtres['recherche'])) {
            $query->recherche($filtres['recherche']);
        }
        if (!empty($filtres['sexe'])) {
            $query->where('sexe', $filtres['sexe']);
        }

        $eleves = $query->orderBy('nom')->orderBy('prenom')->get();
        $this->injecterMontants($eleves, $anneeId);

        return $eleves;
    }

    /**
     * Génère un matricule unique au format EIM-{ANNEE}-{NNNN}.
     */
    public function genererMatricule(): string
    {
        $annee  = date('Y');
        $prefix = "EIM-{$annee}-";

        $dernier = $this->model
            ->where('matricule', 'like', "{$prefix}%")
            ->orderByDesc('matricule')
            ->value('matricule');

        $numero = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefix . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Filtre de situation par rapport à l'année active :
     *  - inscrits (défaut) : inscription validée sur l'année ;
     *  - sans : fiches sans inscription validée sur l'année (fiche tout juste
     *    créée, dossier en attente ou rejeté) — sans quoi elles seraient
     *    introuvables, donc ni modifiables ni archivables ;
     *  - tous : toutes les fiches actives.
     */
    protected function appliquerFiltreAnnee($query, ?int $anneeId, string $situation = self::SITUATION_INSCRITS): void
    {
        if ($situation === self::SITUATION_TOUS) {
            return;
        }

        if (!$anneeId) {
            $query->whereRaw('1 = 0');
            return;
        }

        $inscritSurAnnee = function ($q) use ($anneeId) {
            $q->where('annee_id', $anneeId)
              ->where('statut_validation', \App\Domain\Scolarite\Types\StatutValidationInscription::VALIDEE->value);
        };

        if ($situation === self::SITUATION_SANS) {
            $query->whereDoesntHave('inscriptions', $inscritSurAnnee);
            return;
        }

        $query->whereHas('inscriptions', $inscritSurAnnee);
    }

    /**
     * Injecte montant_du et montant_encaisse (méthode A).
     */
    protected function injecterMontants($eleves, ?int $anneeId): void
    {
        if ($eleves->isEmpty()) {
            return;
        }

        if (!Schema::hasTable('situations_recouvrement')) {
            foreach ($eleves as $e) {
                $e->montant_du = 0;
                $e->montant_encaisse = 0;
            }
            return;
        }

        $eleveIds = $eleves->pluck('id')->all();

        $dus = DB::table('situations_recouvrement as sr')
            ->join('inscriptions as i', 'i.id', '=', 'sr.inscription_id')
            ->whereIn('i.eleve_id', $eleveIds)
            ->when($anneeId, fn ($q) => $q->where('i.annee_id', $anneeId))
            ->groupBy('i.eleve_id')
            ->selectRaw('i.eleve_id, SUM(sr.retard) as total_du')
            ->pluck('total_du', 'i.eleve_id');

        $encaisses = collect();
        if (Schema::hasTable('paiements') && Schema::hasTable('details')) {
            $encaisses = DB::table('details as d')
                ->join('paiements as p', 'p.id', '=', 'd.paiement_id')
                ->join('inscriptions as i', 'i.id', '=', 'p.inscription_id')
                ->whereIn('i.eleve_id', $eleveIds)
                ->whereNotNull('d.date_encaissement')
                ->when($anneeId, fn ($q) => $q->where('i.annee_id', $anneeId))
                ->groupBy('i.eleve_id')
                ->selectRaw('i.eleve_id, SUM(d.montant) as total_encaisse')
                ->pluck('total_encaisse', 'i.eleve_id');
        }

        foreach ($eleves as $e) {
            $e->montant_du       = (float) ($dus[$e->id] ?? 0);
            $e->montant_encaisse = (float) ($encaisses[$e->id] ?? 0);
        }
    }
}