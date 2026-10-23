<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Detail;
use App\Domain\Finances\Models\Souscription;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SouscriptionRepository extends BaseRepository
{
    /** Services suivis par le tableau de bord, dans l'ordre d'affichage. */
    public const SERVICES = [TypeDetail::CANTINE, TypeDetail::BUS, TypeDetail::EVENEMENT, TypeDetail::ACTIVITE];

    public function __construct(Souscription $model)
    {
        parent::__construct($model);
    }

    /**
     * Lignes de paiement d'une souscription : même élève, même nature. La
     * cantine et le transport n'ont qu'une souscription par élève ; une
     * activité ou un événement se reconnaît à son identifiant, à défaut au frais.
     */
    private const LIEN_PAIEMENT = 'd.inscription_id = souscriptions.inscription_id
        AND d.type_paiement = souscriptions.type_paiement
        AND d.etat = 1
        AND (souscriptions.type_paiement IN (%d, %d)
             OR (souscriptions.activite_id IS NOT NULL AND d.activite_id = souscriptions.activite_id)
             OR (souscriptions.evenement_id IS NOT NULL AND d.evenement_id = souscriptions.evenement_id)
             OR (souscriptions.activite_id IS NULL AND souscriptions.evenement_id IS NULL
                 AND d.frais_ecole_id = souscriptions.frais_ecole_id))';

    private function lienPaiement(): string
    {
        return sprintf(self::LIEN_PAIEMENT, TypeDetail::CANTINE->value, TypeDetail::BUS->value);
    }

    /** Montant encaissé sur la souscription (sous-requête corrélée). */
    private function sqlPaye(): string
    {
        return '(SELECT COALESCE(SUM(d.montant), 0) FROM details d WHERE ' . $this->lienPaiement()
            . ' AND d.statut_paiement = ' . PaiementStatut::ENCAISSE->value . ')';
    }

    private const SQL_NET = 'ROUND(souscriptions.montant_annuel_prevu * (1 - COALESCE(souscriptions.taux_remise, 0) / 100), 2)';

    private function filtree(array $filters): Builder
    {
        return $this->activeQuery()
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type_paiement', $v))
            ->when($filters['frais_ecole_id'] ?? null, fn ($q, $v) => $q->where('frais_ecole_id', $v))
            ->when($filters['inscription_id'] ?? null, fn ($q, $v) => $q->where('inscription_id', $v))
            ->when($filters['niveau_id'] ?? null, fn ($q, $v) => $q->whereHas('inscription', fn ($i) => $i->where('niveau_id', $v)))
            ->when($filters['classe_id'] ?? null, fn ($q, $v) => $q->whereHas('inscription', fn ($i) => $i->where('classe_id', $v)))
            // Onglet « non à jour » : il reste quelque chose à percevoir
            ->when($filters['non_a_jour'] ?? false, fn ($q) => $q->whereRaw($this->sqlPaye() . ' + 0.005 < ' . self::SQL_NET))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->whereHas('inscription.eleve', function ($sub) use ($v) {
                    $sub->where('nom', 'like', "%{$v}%")
                        ->orWhere('prenom', 'like', "%{$v}%")
                        ->orWhere('matricule', 'like', "%{$v}%");
                });
            })
            ->when($filters['paiement'] ?? null, function ($q, $v) {
                $paye = $this->sqlPaye();
                match ($v) {
                    'solde'   => $q->whereRaw("{$paye} + 0.005 >= " . self::SQL_NET),
                    'partiel' => $q->whereRaw("{$paye} > 0 AND {$paye} + 0.005 < " . self::SQL_NET),
                    'impaye'  => $q->whereRaw("{$paye} <= 0 AND " . self::SQL_NET . ' > 0'),
                    default   => null,
                };
            });
    }

    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->filtree($filters)
            ->select('souscriptions.*')
            ->selectRaw($this->sqlPaye() . ' AS montant_paye')
            ->with([
                'fraisEcole:id,libelle,montant',
                'inscription:id,eleve_id,classe_id,niveau_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
                'auteur:id,name',
            ])
            ->orderByDesc('date_souscription')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Indicateurs de la liste filtrée : élèves concernés, souscriptions
     * réglées / partielles / impayées, attendu, encaissé, reste, et la
     * répartition par niveau. Un élève à plusieurs souscriptions n'est compté
     * qu'une fois ; chaque souscription n'est comptée qu'une fois.
     *
     * @return array{eleves:int, souscriptions:int, soldees:int, partielles:int, impayees:int,
     *               attendu:float, percu:float, reste:float, niveaux:\Illuminate\Support\Collection}
     */
    public function statistiques(array $filters): array
    {
        $paye = $this->sqlPaye();
        // Pas de jointure dans la requête filtrée (ses colonnes ne sont pas préfixées) :
        // le niveau de l'inscription est lu par sous-requête, son libellé à l'extérieur.
        $base = fn () => DB::query()->fromSub(
            $this->filtree($filters)
                ->selectRaw('souscriptions.inscription_id,
                    (SELECT ins.niveau_id FROM inscriptions ins WHERE ins.id = souscriptions.inscription_id) AS niveau_id, '
                    . self::SQL_NET . " AS net, {$paye} AS paye")
                ->toBase(),
            'x'
        );
        $agregats = 'COUNT(*) AS souscriptions, COUNT(DISTINCT inscription_id) AS eleves,
            SUM(paye + 0.005 >= net) AS soldees, SUM(paye > 0 AND paye + 0.005 < net) AS partielles,
            SUM(paye <= 0 AND net > 0) AS impayees,
            COALESCE(SUM(net), 0) AS attendu, COALESCE(SUM(LEAST(paye, net)), 0) AS percu,
            COALESCE(SUM(GREATEST(net - paye, 0)), 0) AS reste';

        $total = $base()->selectRaw($agregats)->first();

        return [
            'eleves'        => (int) $total->eleves,
            'souscriptions' => (int) $total->souscriptions,
            'soldees'       => (int) $total->soldees,
            'partielles'    => (int) $total->partielles,
            'impayees'      => (int) $total->impayees,
            'attendu'       => round((float) $total->attendu, 2),
            'percu'         => round((float) $total->percu, 2),
            'reste'         => round((float) $total->reste, 2),
            'niveaux'       => $base()->leftJoin('niveaux as niv', 'niv.id', '=', 'x.niveau_id')
                ->groupBy('x.niveau_id', 'niv.libelle')->orderBy('niv.libelle')
                ->selectRaw("x.niveau_id, niv.libelle AS niveau, {$agregats}")->get(),
        ];
    }

    /** Souscription avec son montant encaissé. */
    public function avecPaiement(int $id): Souscription
    {
        return $this->activeQuery()
            ->select('souscriptions.*')
            ->selectRaw($this->sqlPaye() . ' AS montant_paye')
            ->with(['fraisEcole:id,libelle', 'inscription.eleve:id,nom,prenom,matricule'])
            ->findOrFail($id);
    }

    public function compter(array $filters): int
    {
        return $this->filtree($filters)->count();
    }

    /** Toutes les lignes filtrées, pour les exports Excel et PDF (élève, niveau et classe chargés). */
    public function listePourExport(array $filters): Collection
    {
        return $this->filtree($filters)
            ->select('souscriptions.*')
            ->selectRaw($this->sqlPaye() . ' AS montant_paye')
            ->with([
                'fraisEcole:id,libelle',
                'inscription:id,eleve_id,classe_id,niveau_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
            ])
            ->orderBy('type_paiement')
            ->orderByDesc('date_souscription')
            ->orderByDesc('id')
            ->get();
    }

    /** Lignes de paiement liées à la souscription (saisies, encaissées ou annulées). */
    public function paiementsDe(Souscription $souscription): Collection
    {
        return Detail::query()
            ->from('details as d')
            ->join('souscriptions', 'souscriptions.id', '=', DB::raw((int) $souscription->id))
            ->whereRaw($this->lienPaiement())
            ->leftJoin('paiements as p', 'p.id', '=', 'd.paiement_id')
            ->leftJoin('caisses as c', 'c.id', '=', 'd.caisse_id')
            ->orderBy('d.date_paiement')
            ->orderBy('d.id')
            ->get([
                'd.id', 'd.libelle', 'd.montant', 'd.statut_paiement', 'd.date_paiement', 'd.date_encaissement',
                'p.reference', 'p.payeur', 'p.mode_paiement', 'c.libelle as caisse',
            ]);
    }

    /**
     * Tableau de bord : par service, le nombre de souscriptions, le montant
     * attendu (remises déduites), l'encaissé et le reste à percevoir.
     *
     * @return array<int, array{type:TypeDetail, nb:int, attendu:float, percu:float, reste:float}>
     */
    public function tableauDeBord(?int $anneeId): array
    {
        $types = array_map(fn (TypeDetail $t) => $t->value, self::SERVICES);
        $paye = $this->sqlPaye();

        $lignes = DB::query()
            ->fromSub(
                $this->activeQuery()
                    ->whereIn('type_paiement', $types)
                    ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
                    ->selectRaw('type_paiement, ' . self::SQL_NET . " AS net, {$paye} AS paye")
                    ->toBase(),
                'x'
            )
            ->groupBy('type_paiement')
            ->selectRaw('type_paiement, COUNT(*) AS nb, SUM(net) AS attendu, SUM(LEAST(paye, net)) AS percu, SUM(GREATEST(net - paye, 0)) AS reste')
            ->get()
            ->keyBy('type_paiement');

        return array_map(fn (TypeDetail $type) => [
            'type'    => $type,
            'nb'      => (int) ($lignes[$type->value]->nb ?? 0),
            'attendu' => round((float) ($lignes[$type->value]->attendu ?? 0), 2),
            'percu'   => round((float) ($lignes[$type->value]->percu ?? 0), 2),
            'reste'   => round((float) ($lignes[$type->value]->reste ?? 0), 2),
        ], self::SERVICES);
    }

    /** Somme totale des souscriptions d'une inscription (pour info). */
    public function totalPourInscription(int $inscriptionId): float
    {
        return round(
            (float) $this->activeQuery()
                ->where('inscription_id', $inscriptionId)
                ->sum('montant_annuel_prevu'),
            2
        );
    }
}
