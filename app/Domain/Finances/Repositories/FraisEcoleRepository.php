<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypePaiement;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FraisEcoleRepository extends BaseRepository
{
    /** Onglets de l'écran des frais : clé => [libellé, natures de frais] (null = toutes). */
    public const ONGLETS = [
        'inscription' => ["Inscription & assurance", [TypeDetail::FRAIS_INSCRIPTION, TypeDetail::FRAIS_ASSURANCE]],
        'scolarite'   => ['Scolarité', [TypeDetail::FRAIS_SCOLAIRE]],
        'cantine'     => ['Cantine', [TypeDetail::CANTINE]],
        'transport'   => ['Transport', [TypeDetail::BUS]],
        'tous'        => ['Tous les frais', null],
    ];

    /** Seules natures que le formulaire général des frais peut créer. */
    public const CREABLES = [TypeDetail::FRAIS_INSCRIPTION, TypeDetail::FRAIS_ASSURANCE, TypeDetail::FRAIS_SCOLAIRE];

    public function __construct(FraisEcole $model)
    {
        parent::__construct($model);
    }

    /** @return int[]|null */
    public static function typesDe(string $onglet): ?array
    {
        $types = self::ONGLETS[$onglet][1] ?? null;

        return $types === null ? null : array_map(fn (TypeDetail $t) => $t->value, $types);
    }

    /** Frais d'une année scolaire, filtrés. L'année est obligatoire : jamais de mélange entre années. */
    private function filtree(int $anneeId, array $filters): Builder
    {
        return $this->activeQuery()
            ->where('annee_id', $anneeId)
            ->when(isset($filters['types']), fn ($q) => $q->whereIn('type_paiement', $filters['types']))
            ->when($filters['type_paiement'] ?? null, fn ($q, $v) => $q->where('type_paiement', $v))
            ->when($filters['niveau_id'] ?? null, fn ($q, $v) => $q->where('niveau_id', $v))
            ->when($filters['modalite_paiement'] ?? null, fn ($q, $v) => $q->where('modalite_paiement', $v))
            ->when($filters['type_forfait'] ?? null, fn ($q, $v) => $q->where('type_forfait', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('libelle', 'like', "%{$v}%"));
    }

    public function listePaginee(int $anneeId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $tris = ['libelle' => 'libelle', 'montant' => 'montant', 'type' => 'type_paiement', 'niveau' => 'niveau_id'];
        $tri = $tris[$filters['tri'] ?? ''] ?? null;
        $sens = ($filters['sens'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $this->filtree($anneeId, $filters)
            ->with(['niveau:id,libelle', 'planEcheancier:id,nom'])
            ->when($tri, fn ($q) => $q->orderBy($tri, $sens), fn ($q) => $q->orderBy('type_paiement')->orderBy('niveau_id'))
            ->orderBy('libelle')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listePourSelect(?int $anneeId = null)
    {
        return $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->orderBy('libelle')
            ->get(['id', 'libelle', 'montant', 'type_paiement']);
    }

    /** Natures dont le paiement ne cite pas le frais : le dû est porté par l'inscription. */
    private const PORTES_PAR_INSCRIPTION = [
        TypeDetail::FRAIS_INSCRIPTION, TypeDetail::FRAIS_SCOLAIRE, TypeDetail::FRAIS_ASSURANCE, TypeDetail::FRAIS_EXAMEN,
    ];

    /**
     * Montant encaissé pour chaque frais affiché.
     *
     *  - Produits, cantine, transport, activités, événements… : les lignes de
     *    paiement citent le frais, on les additionne. Règle fixée par
     *    l'utilisateur : un paiement est rattaché à l'année où le frais a été
     *    créé, pas à celle où il a été versé — aucun filtre sur l'année du
     *    paiement ici (un frais de transport de 2024-2025 encore réglé en
     *    2026-2027 voit ces versements comptés sur lui).
     *  - Inscription, scolarité, assurance, examen : les paiements ne citent
     *    aucun frais. Ils sont attribués au frais de cette nature qui vaut
     *    pour le niveau de l'élève ; si un niveau a plusieurs frais de
     *    scolarité, chaque élève est rattaché à celui dont le montant est le
     *    sien. Quand rien ne permet de trancher, la valeur est null.
     *
     * @param iterable<FraisEcole> $frais
     * @return array<int, ?float>  identifiant du frais => montant, ou null si non attribuable
     */
    public function montantsRecoltes(iterable $frais, int $anneeId): array
    {
        $frais = collect($frais);
        if ($frais->isEmpty()) {
            return [];
        }
        $encaisse = \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value;
        $portes = array_map(fn (TypeDetail $t) => $t->value, self::PORTES_PAR_INSCRIPTION);

        $parFrais = \Illuminate\Support\Facades\DB::table('details')
            ->where('etat', 1)->where('statut_paiement', $encaisse)
            ->whereIn('frais_ecole_id', $frais->pluck('id'))
            ->groupBy('frais_ecole_id')
            ->selectRaw('frais_ecole_id, SUM(montant) AS total')
            ->pluck('total', 'frais_ecole_id');

        // Paiements sans frais cité, par nature, niveau et montant de scolarité de l'élève
        $parNiveau = \Illuminate\Support\Facades\DB::table('details as d')
            ->join('inscriptions as i', 'i.id', '=', 'd.inscription_id')
            ->where('i.annee_id', $anneeId)
            ->where('d.etat', 1)->where('d.statut_paiement', $encaisse)
            ->whereIn('d.type_paiement', $portes)->whereNull('d.frais_ecole_id')
            ->groupBy('d.type_paiement', 'i.niveau_id', \Illuminate\Support\Facades\DB::raw('ROUND(COALESCE(i.frais_scolarite, 0))'))
            ->selectRaw('d.type_paiement AS type, i.niveau_id, ROUND(COALESCE(i.frais_scolarite, 0)) AS scolarite, SUM(d.montant) AS total')
            ->get();

        // Tous les frais de l'année portés par l'inscription, pour repérer les cas ambigus
        $memeNature = $this->activeQuery()->where('annee_id', $anneeId)->whereIn('type_paiement', $portes)
            ->toBase()->get(['id', 'type_paiement', 'niveau_id']);

        $resultat = [];
        foreach ($frais as $f) {
            $type = $f->type_paiement?->value;
            if (! in_array($type, $portes, true)) {
                $resultat[$f->id] = round((float) ($parFrais[$f->id] ?? 0), 2);
                continue;
            }

            $lignes = $parNiveau->where('type', $type);
            $autres = $memeNature->where('type_paiement', $type)->where('id', '!=', $f->id);

            if ($f->niveau_id === null) {
                // Frais valable pour tous les niveaux : attribuable s'il est le seul de sa nature
                $resultat[$f->id] = $autres->isEmpty() ? round((float) $lignes->sum('total'), 2) : null;
                continue;
            }

            $lignes = $lignes->where('niveau_id', $f->niveau_id);
            if ($autres->where('niveau_id', $f->niveau_id)->isEmpty()) {
                $resultat[$f->id] = round((float) $lignes->sum('total'), 2);
            } elseif ($type === TypeDetail::FRAIS_SCOLAIRE->value) {
                $resultat[$f->id] = round((float) $lignes->filter(fn ($l) => abs((float) $l->scolarite - (float) $f->montant) < 1)->sum('total'), 2);
            } else {
                $resultat[$f->id] = null;
            }
        }

        return $resultat;
    }

    /**
     * Encaissé de l'année pour des natures de frais, d'après le champ
     * `type_paiement` des lignes de paiement actives (etat = 1) : c'est lui
     * qui dit de quoi relève un paiement, même quand aucun frais n'est cité
     * (l'ancienne application enregistrait alors la référence 0).
     *
     * @param int[] $types
     * @return array{total:float, sans_frais:float, lignes_sans_frais:int}
     */
    public function encaisseParType(int $anneeId, array $types): array
    {
        $ligne = \Illuminate\Support\Facades\DB::table('details as d')
            ->leftJoin('frais_ecoles as f', 'f.id', '=', 'd.frais_ecole_id')
            ->where('d.etat', 1)
            ->where('d.annee_id', $anneeId)
            ->where('d.statut_paiement', \App\Domain\Finances\Types\PaiementStatut::ENCAISSE->value)
            ->whereIn('d.type_paiement', $types)
            ->selectRaw('COALESCE(SUM(d.montant), 0) AS total,
                COALESCE(SUM(CASE WHEN f.id IS NULL THEN d.montant ELSE 0 END), 0) AS sans_frais,
                COALESCE(SUM(CASE WHEN f.id IS NULL THEN 1 ELSE 0 END), 0) AS lignes_sans_frais')
            ->first();

        return [
            'total'             => round((float) $ligne->total, 2),
            'sans_frais'        => round((float) $ligne->sans_frais, 2),
            'lignes_sans_frais' => (int) $ligne->lignes_sans_frais,
        ];
    }

    /** Nombre de frais de l'année dans chaque onglet. */
    public function compterParOnglet(int $anneeId): array
    {
        $parType = $this->activeQuery()->where('annee_id', $anneeId)
            ->selectRaw('type_paiement, COUNT(*) AS total')->groupBy('type_paiement')
            ->toBase()->pluck('total', 'type_paiement');

        $compteurs = [];
        foreach (array_keys(self::ONGLETS) as $onglet) {
            $types = self::typesDe($onglet);
            $compteurs[$onglet] = (int) ($types === null ? $parType->sum() : $parType->only($types)->sum());
        }

        return $compteurs;
    }

    /** Frais comptant / échelonnés de l'année (et de l'onglet affiché). */
    public function compterParTypePaiement(int $anneeId, array $filters = []): array
    {
        $rows = $this->filtree($anneeId, $filters)
            ->selectRaw('modalite_paiement, COUNT(*) as total')
            ->groupBy('modalite_paiement')
            ->toBase()
            ->pluck('total', 'modalite_paiement');

        return [
            'comptant'  => (int) ($rows[TypePaiement::COMPTANT->value] ?? 0),
            'echelonne' => (int) ($rows[TypePaiement::ECHELONNE->value] ?? 0),
        ];
    }
}
