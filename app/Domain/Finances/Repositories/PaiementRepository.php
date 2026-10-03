<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Detail;
use App\Domain\Finances\Models\Paiement;
use App\Domain\Finances\Types\PaiementStatut;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PaiementRepository extends BaseRepository
{
    public function __construct(Paiement $model)
    {
        parent::__construct($model);
    }

    public function listePaginee(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->activeQuery()
            ->with([
                'details',
                'comptable:id,name',
                'cheque:id,numero,banque_id',
                'cheque.banque:id,nom',
                'inscription:id,eleve_id,classe_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
            ])
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when(
                isset($filters['statut_paiement']) && $filters['statut_paiement'] !== '',
                fn ($q) => $q->where('statut_paiement', (int) $filters['statut_paiement'])
            )
            ->when($filters['mode_paiement'] ?? null, fn ($q, $v) => $q->where('mode_paiement', $v))
            ->when($filters['search'] ?? null, function ($q, $v) {
                $q->where(function ($sub) use ($v) {
                    $sub->where('reference', 'like', "%{$v}%")
                        ->orWhere('payeur', 'like', "%{$v}%")
                        ->orWhere('telephone_payeur', 'like', "%{$v}%");
                });
            })
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('date_paiement', '>=', $v))
            ->when($filters['date_fin'] ?? null,   fn ($q, $v) => $q->whereDate('date_paiement', '<=', $v))
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findAvecDetails(int $id): Paiement
    {
        return $this->activeQuery()
            ->with([
                'details',
                'details.caisse:id,libelle',
                'details.comptable:id,name',
                'details.caissier:id,name',
                'comptable:id,name',
            ])
            ->findOrFail($id);
    }

    /** Génère une référence unique : PAI-20260120-0001. */
    public function genererReference(): string
    {
        $prefix = 'PAI-' . now()->format('Ymd') . '-';

        $dernier = $this->activeQuery()
            ->where('reference', 'like', "{$prefix}%")
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $dernier
            ? ((int) substr($dernier, -4)) + 1
            : 1;

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function compterParStatut(?int $anneeId = null): array
    {
        $rows = $this->activeQuery()
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->selectRaw('statut_paiement, COUNT(*) as total')
            ->groupBy('statut_paiement')
            ->pluck('total', 'statut_paiement')
            ->all();

        return [
            'attente'  => (int) ($rows[PaiementStatut::EN_ATTENTE->value] ?? 0),
            'encaisse' => (int) ($rows[PaiementStatut::ENCAISSE->value]   ?? 0),
            'annule'   => (int) ($rows[PaiementStatut::ANNULE->value]     ?? 0),
        ];
    }


/**
 * Détails en attente d'encaissement (file d'attente du caissier).
 * On filtre par année active + on exclut les détails de paiements annulés.
 */
public function detailsEnAttente(?int $anneeId = null, int $perPage = 20)
{
    return Detail::query()
        ->with([
            'paiement:id,reference,payeur,telephone_payeur,mode_paiement,utilisateur_id,date_paiement',
            'paiement.comptable:id,name',
        ])
        ->where('etat', 1)
        ->where('statut_paiement', \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE->value)
        ->whereNull('date_encaissement')
        ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
        ->orderBy('date_paiement')
        ->orderBy('id')
        ->paginate($perPage)
        ->withQueryString();
}

/**
 * Détails encaissés par un caissier donné aujourd'hui (historique du jour).
 */
public function detailsEncaisssesDuJour(int $caissierId, ?int $anneeId = null)
{
    return Detail::query()
        ->with(['paiement:id,reference,payeur', 'caisse:id,libelle'])
        ->where('etat', 1)
        ->where('caissier_id', $caissierId)
        ->whereDate('date_encaissement', now()->toDateString())
        ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
        ->orderByDesc('date_encaissement')
        ->orderByDesc('id')
        ->get();
}

/**
 * Compteurs pour les cartes stats de la file d'attente.
 */
public function compterFileAttente(?int $anneeId = null): array
{
    $q = Detail::query()
        ->where('etat', 1)
        ->where('statut_paiement', \App\Domain\Finances\Types\PaiementStatut::EN_ATTENTE->value)
        ->whereNull('date_encaissement')
        ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v));

    return [
        'nb_details' => (clone $q)->count(),
        'montant'    => round((float) (clone $q)->sum('montant'), 2),
    ];
}

/**
 * Compteurs d'un caissier sur la journée (déjà encaissés).
 */
public function compteurJourCaissier(int $caissierId): array
{
    $q = Detail::query()
        ->where('etat', 1)
        ->where('caissier_id', $caissierId)
        ->whereDate('date_encaissement', now()->toDateString());

    return [
        'nb'      => (clone $q)->count(),
        'montant' => round((float) (clone $q)->sum('montant'), 2),
    ];
}

/** Charge un détail + paiement + caisse, prêt pour l'encaissement. */
public function findDetailAvecRelations(int $detailId): Detail
{
    return Detail::query()
        ->with(['paiement', 'paiement.comptable:id,name', 'caisse:id,libelle'])
        ->findOrFail($detailId);
}

    /**
     * Lignes de paiement d'un élève sur une année scolaire, les plus
     * récentes d'abord (écran de saisie : « paiements déjà enregistrés »).
     */
    public function historiqueEleve(int $eleveId, ?int $anneeId, int $limite = 300): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('details as d')
            ->join('inscriptions as i', 'i.id', '=', 'd.inscription_id')
            ->leftJoin('paiements as p', 'p.id', '=', 'd.paiement_id')
            ->leftJoin('annees as a', 'a.id', '=', 'i.annee_id')
            ->where('i.eleve_id', $eleveId)
            ->where('i.annee_id', $anneeId)
            ->where('d.etat', 1)
            ->orderByDesc('d.date_paiement')
            ->orderByDesc('d.id')
            ->limit($limite)
            ->get([
                'd.id', 'd.libelle', 'd.montant', 'd.type_paiement', 'd.statut_paiement',
                'd.date_paiement', 'd.date_encaissement',
                'p.reference', 'p.mode_paiement', 'p.payeur',
                'a.libelle as annee', 'i.annee_id',
            ]);
    }

    /**
     * Totaux encaissés d'une année scolaire : toute l'année, le mois, la
     * semaine (du lundi au dimanche) et le jour en cours. Seul ce qui est
     * effectivement entré en caisse compte (lignes au statut « encaissé »).
     *
     * @return array{annee:float, mois:float, semaine:float, jour:float}
     */
    public function totauxEncaisses(?int $anneeId): array
    {
        $jour = \Illuminate\Support\Carbon::today();
        // La base historique n'a pas toujours la date d'encaissement : on retombe sur la date de saisie
        $date = 'DATE(COALESCE(date_encaissement, date_paiement))';

        $ligne = \Illuminate\Support\Facades\DB::table('details')
            ->where('etat', 1)
            ->where('statut_paiement', PaiementStatut::ENCAISSE->value)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->selectRaw(
                "COALESCE(SUM(montant), 0) AS annee,
                 COALESCE(SUM(CASE WHEN {$date} BETWEEN ? AND ? THEN montant ELSE 0 END), 0) AS mois,
                 COALESCE(SUM(CASE WHEN {$date} BETWEEN ? AND ? THEN montant ELSE 0 END), 0) AS semaine,
                 COALESCE(SUM(CASE WHEN {$date} = ? THEN montant ELSE 0 END), 0) AS jour",
                [
                    $jour->copy()->startOfMonth()->toDateString(), $jour->copy()->endOfMonth()->toDateString(),
                    $jour->copy()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->toDateString(),
                    $jour->copy()->endOfWeek(\Illuminate\Support\Carbon::SUNDAY)->toDateString(),
                    $jour->toDateString(),
                ]
            )
            ->first();

        return [
            'annee'   => (float) $ligne->annee,
            'mois'    => (float) $ligne->mois,
            'semaine' => (float) $ligne->semaine,
            'jour'    => (float) $ligne->jour,
        ];
    }

    /* =================================================================
       Écran Encaissements
       ================================================================= */

    /**
     * Encaissements regroupés par paiement : une ligne par paiement encaissé
     * dans une caisse, par un caissier, un jour donné. Sert à l'onglet « Tous
     * les encaissements » et, filtré sur le caissier et le jour, à l'onglet
     * « Mes encaissements du jour ».
     */
    public function encaissementsQuery(array $filters = []): \Illuminate\Database\Query\Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return \Illuminate\Support\Facades\DB::table('details as d')
            ->join('paiements as p', 'p.id', '=', 'd.paiement_id')
            ->leftJoin('inscriptions as i', 'i.id', '=', 'p.inscription_id')
            ->leftJoin('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->leftJoin('classes as cl', 'cl.id', '=', 'i.classe_id')
            ->leftJoin('caisses as c', 'c.id', '=', 'd.caisse_id')
            ->leftJoin('users as u', 'u.id', '=', 'd.caissier_id')
            ->where('d.etat', 1)
            ->where('d.statut_paiement', PaiementStatut::ENCAISSE->value)
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('d.annee_id', $v))
            ->when($filters['caissier_id'] ?? null, fn ($q, $v) => $q->where('d.caissier_id', $v))
            ->when($filters['caisse_id'] ?? null, fn ($q, $v) => $q->where('d.caisse_id', $v))
            ->when($filters['jour'] ?? null, fn ($q, $v) => $q->whereDate('d.date_encaissement', $v))
            ->when($filters['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('d.date_encaissement', '>=', $v))
            ->when($filters['date_fin'] ?? null, fn ($q, $v) => $q->whereDate('d.date_encaissement', '<=', $v))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('p.reference', 'like', "%{$search}%")
                      ->orWhere('p.payeur', 'like', "%{$search}%")
                      ->orWhere('e.matricule', 'like', "%{$search}%")
                      ->orWhereRaw("CONCAT(COALESCE(e.nom,''), ' ', COALESCE(e.prenom,'')) LIKE ?", ["%{$search}%"]);
                });
            })
            ->groupBy('d.paiement_id', 'd.caisse_id', 'd.caissier_id', 'd.date_encaissement',
                'p.reference', 'p.payeur', 'p.telephone_payeur', 'p.mode_paiement', 'p.montant', 'p.montant_recu',
                'e.nom', 'e.prenom', 'e.matricule', 'cl.libelle', 'c.libelle', 'c.statut', 'u.nom', 'u.prenom')
            ->select([
                'd.paiement_id', 'd.caisse_id', 'd.caissier_id', 'd.date_encaissement',
                'p.reference', 'p.payeur', 'p.telephone_payeur', 'p.mode_paiement',
                'p.montant as montant_paiement', 'p.montant_recu',
                'e.nom as eleve_nom', 'e.prenom as eleve_prenom', 'e.matricule', 'cl.libelle as classe',
                'c.libelle as caisse', 'c.statut as statut_caisse',
                \Illuminate\Support\Facades\DB::raw("TRIM(CONCAT(COALESCE(u.nom,''), ' ', COALESCE(u.prenom,''))) as caissier"),
                \Illuminate\Support\Facades\DB::raw('SUM(d.montant) as montant'),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as nb_lignes'),
                \Illuminate\Support\Facades\DB::raw('MIN(d.id) as detail_id'),
                \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT d.type_paiement) as natures'),
            ])
            ->orderByDesc('d.date_encaissement')
            ->orderByDesc('d.paiement_id');
    }

    public function encaissementsPagines(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->encaissementsQuery($filters)
            ->paginate($perPage, ['*'], 'page_tous')
            ->withQueryString();
    }

    /**
     * Encaissements d'un caissier sur une journée, toutes années scolaires
     * confondues : c'est l'argent réellement reçu ce jour-là, qui doit
     * correspondre à sa caisse, quelle que soit l'année consultée.
     */
    public function encaissementsDuJour(int $caissierId, ?string $jour = null): \Illuminate\Support\Collection
    {
        return $this->encaissementsQuery([
            'caissier_id' => $caissierId,
            'jour'        => $jour ?? now()->toDateString(),
        ])->get();
    }

    /** Paiements dont il reste au moins une ligne à encaisser (onglet « Paiements en attente »). */
    public function paiementsEnAttenteQuery(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $enAttente = PaiementStatut::EN_ATTENTE->value;

        return $this->activeQuery()
            ->with([
                'details' => fn ($q) => $q->where('etat', 1)->where('statut_paiement', $enAttente)->orderBy('id'),
                'comptable:id,nom,prenom',
                'cheque:id,numero,banque_id',
                'cheque.banque:id,nom',
                'inscription:id,eleve_id,classe_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
            ])
            ->where('statut_paiement', $enAttente)
            ->whereHas('details', fn ($q) => $q->where('etat', 1)->where('statut_paiement', $enAttente))
            ->when($filters['annee_id'] ?? null, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('reference', 'like', "%{$search}%")
                      ->orWhere('payeur', 'like', "%{$search}%")
                      ->orWhereHas('inscription.eleve', fn ($e) => $e
                          ->where('matricule', 'like', "%{$search}%")
                          ->orWhereRaw("CONCAT(COALESCE(nom,''), ' ', COALESCE(prenom,'')) LIKE ?", ["%{$search}%"])
                          ->orWhereRaw("CONCAT(COALESCE(prenom,''), ' ', COALESCE(nom,'')) LIKE ?", ["%{$search}%"]));
                });
            })
            ->orderByDesc('date_paiement')
            ->orderByDesc('id');
    }

    public function paiementsEnAttentePagines(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->paiementsEnAttenteQuery($filters)
            ->paginate($perPage, ['*'], 'page_attente')
            ->withQueryString();
    }

    /** @return array{nb:int, montant:float} */
    public function compterPaiementsEnAttente(?int $anneeId): array
    {
        $ligne = Detail::query()
            ->join('paiements as p', 'p.id', '=', 'details.paiement_id')
            ->where('details.etat', 1)
            ->where('p.etat', 1)
            ->where('details.statut_paiement', PaiementStatut::EN_ATTENTE->value)
            ->where('p.statut_paiement', PaiementStatut::EN_ATTENTE->value)
            ->when($anneeId, fn ($q, $v) => $q->where('details.annee_id', $v))
            ->selectRaw('COUNT(DISTINCT details.paiement_id) as nb, COALESCE(SUM(details.montant), 0) as montant')
            ->first();

        return ['nb' => (int) $ligne->nb, 'montant' => round((float) $ligne->montant, 2)];
    }

    /** Paiement chargé pour l'encaissement ou son détail : élève, lignes, caisse, caissier, chèque. */
    public function findPourEncaissement(int $id): Paiement
    {
        return $this->activeQuery()
            ->with([
                'details' => fn ($q) => $q->where('etat', 1)->orderBy('id'),
                'details.caisse:id,libelle',
                'details.caissier:id,nom,prenom',
                'comptable:id,nom,prenom',
                'cheque.banque:id,nom',
                'inscription:id,eleve_id,classe_id,niveau_id',
                'inscription.eleve:id,nom,prenom,matricule',
                'inscription.classe:id,libelle',
                'inscription.niveau:id,libelle',
            ])
            ->findOrFail($id);
    }
}