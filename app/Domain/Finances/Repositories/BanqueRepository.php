<?php

namespace App\Domain\Finances\Repositories;

use App\Domain\Finances\Models\Banque;
use App\Domain\Finances\Types\ChequeStatut;
use Illuminate\Support\Facades\DB;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BanqueRepository extends BaseRepository
{
    public function __construct(Banque $model)
    {
        parent::__construct($model);
    }

    /**
     * Liste des banques avec, sur l'année de l'en-tête, leurs chèques : nombre,
     * en attente, et ce qui a été réellement perçu (chèques encaissés) ou reste
     * à percevoir (chèques émis, pas encore encaissés).
     */
    public function listePaginee(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $annee = $filters['annee_id'] ?? null;
        $cheques = fn (?ChequeStatut $statut = null) => fn ($q) => $q->where('etat', 1)
            ->when($annee, fn ($q, $v) => $q->where('annee_id', $v))
            ->when($statut, fn ($q, $s) => $q->where('statut', $s->value));

        return $this->activeQuery()
            ->withCount([
                'cheques as cheques_total' => $cheques(),
                'cheques as cheques_en_attente' => $cheques(ChequeStatut::EMIS),
            ])
            ->withSum(['cheques as montant_percu' => $cheques(ChequeStatut::ENCAISSE)], 'montant')
            ->withSum(['cheques as montant_reste' => $cheques(ChequeStatut::EMIS)], 'montant')
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('nom', 'like', "%{$v}%"))
            ->orderBy('nom')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Liste légère pour les <select> (nouveau chèque, filtres). */
    public function listePourSelect(): Collection
    {
        return $this->activeQuery()
            ->orderBy('nom')
            ->get(['id', 'nom']);
    }

    /**
     * Chèques de la banque sur l'année, chacun avec le paiement qu'il règle
     * (le chèque « adossé » au paiement). Le lien est porté par le chèque
     * (paiement_id) ou, à défaut, par le paiement (cheque_id).
     */
    public function chequesEtPaiements(int $banqueId, ?int $anneeId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return DB::table('cheques as c')
            ->leftJoin('paiements as p', fn ($j) => $j->where(fn ($w) => $w
                ->whereColumn('p.id', 'c.paiement_id')
                ->orWhere(fn ($x) => $x->whereNull('c.paiement_id')->whereColumn('p.cheque_id', 'c.id'))))
            ->leftJoin('inscriptions as i', 'i.id', '=', 'p.inscription_id')
            ->leftJoin('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->leftJoin('classes as cl', 'cl.id', '=', 'i.classe_id')
            ->where('c.banque_id', $banqueId)
            ->where('c.etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('c.annee_id', $v))
            ->when($filters['statut'] ?? null, fn ($q, $v) => $q->where('c.statut', $v))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('c.numero', 'like', "%{$search}%")
                ->orWhere('c.emetteur', 'like', "%{$search}%")
                ->orWhere('p.reference', 'like', "%{$search}%")
                ->orWhere('p.payeur', 'like', "%{$search}%")
                ->orWhere('e.matricule', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT(COALESCE(e.nom,''), ' ', COALESCE(e.prenom,'')) LIKE ?", ["%{$search}%"])))
            ->orderByDesc('c.date_emission')->orderByDesc('c.id')
            ->select([
                'c.id as cheque_id', 'c.numero', 'c.emetteur', 'c.montant as montant_cheque', 'c.statut as statut_cheque',
                'c.date_emission', 'c.date_encaissement', 'c.motif_rejet',
                'p.id as paiement_id', 'p.reference', 'p.payeur', 'p.date_paiement', 'p.montant as montant_paiement',
                'p.statut_paiement', 'e.nom as eleve_nom', 'e.prenom as eleve_prenom', 'e.matricule', 'cl.libelle as classe',
            ])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Situation des chèques de la banque sur l'année, par statut : nombre et
     * montant ; « perçu » = encaissés, « reste » = émis non encaissés.
     *
     * @return array<string, array{nb:int, montant:float}>
     */
    public function situationCheques(int $banqueId, ?int $anneeId): array
    {
        $lignes = DB::table('cheques')
            ->where('banque_id', $banqueId)->where('etat', 1)
            ->when($anneeId, fn ($q, $v) => $q->where('annee_id', $v))
            ->groupBy('statut')
            ->selectRaw('statut, COUNT(*) as nb, COALESCE(SUM(montant), 0) as montant')
            ->get()->keyBy('statut');

        $ligne = fn (ChequeStatut $s) => ['nb' => (int) ($lignes[$s->value]->nb ?? 0), 'montant' => round((float) ($lignes[$s->value]->montant ?? 0), 2)];

        return [
            'attente'  => $ligne(ChequeStatut::EMIS),
            'percu'    => $ligne(ChequeStatut::ENCAISSE),
            'rejete'   => $ligne(ChequeStatut::REJETE),
            'total'    => ['nb' => (int) $lignes->sum('nb'), 'montant' => round((float) $lignes->sum('montant'), 2)],
        ];
    }

    /** Détail d'une banque + ses chèques (vue show si tu en ajoutes une). */
    public function findAvecCheques(int $id): Banque
    {
        return $this->activeQuery()
            ->with(['cheques' => fn ($q) => $q->orderByDesc('date_emission')->orderByDesc('id')])
            ->findOrFail($id);
    }
}