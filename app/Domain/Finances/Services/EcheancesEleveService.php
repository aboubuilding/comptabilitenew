<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\PlanEcheancier;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Échéances de chaque élève sur une année, toutes natures confondues : la base
 * du recouvrement et de la planification des relances.
 *
 *  - scolarité : les échéances des plans d'échéancier (part de chaque échéance
 *    appliquée à la scolarité due par l'élève) ;
 *  - cantine et transport : une échéance par mois d'abonnement (date de début
 *    + n mois, mensualité), la dernière ajustée au total dû.
 *
 * Les paiements encaissés de chaque nature sont affectés aux échéances les plus
 * anciennes d'abord : une échéance est couverte, partiellement payée ou impayée.
 */
class EcheancesEleveService
{
    /** Natures suivies (une nature = un type de paiement). */
    public const NATURES = [NatureCreance::SCOLARITE, NatureCreance::CANTINE, NatureCreance::BUS];

    private const TYPE_DE = [
        'scolarite' => TypeDetail::FRAIS_SCOLAIRE,
        'cantine'   => TypeDetail::CANTINE,
        'bus'       => TypeDetail::BUS,
    ];

    public function __construct(private EcheancierService $echeancier) {}

    /**
     * Toutes les échéances de l'année (passées et à venir), une ligne par élève
     * et par échéance, payées selon les encaissements à ce jour.
     *
     * @param  array<int, string>|null  $natures  valeurs de NatureCreance (toutes par défaut)
     * @return Collection<int, array{inscription_id:int, nature:string, libelle:string, date:?Carbon, montant:float, paye:float, reste:float}>
     */
    public function echeances(int $anneeId, ?array $natures = null): Collection
    {
        $natures = $natures ?: array_map(fn (NatureCreance $n) => $n->value, self::NATURES);
        $lignes = collect();

        if (in_array('scolarite', $natures, true)) {
            $lignes = $lignes->merge($this->scolarite($anneeId));
        }
        foreach (['cantine' => 'inscriptions_cantine', 'bus' => 'abonnements_bus'] as $nature => $table) {
            if (in_array($nature, $natures, true)) {
                $lignes = $lignes->merge($this->abonnements($anneeId, $nature, $table));
            }
        }

        return $this->affecterPaiements($anneeId, $lignes);
    }

    /**
     * Élèves non à jour au jour donné : échéances dont la date est passée (≤ jour)
     * et qui ne sont pas couvertes par les paiements encaissés à ce jour.
     *
     * @param  string|null  $libelle  ne garder que les échéances dont le libellé contient ce texte (« T2 », « octobre »…)
     * @return Collection<int, array> une ligne par élève : identité, échéances non couvertes, montants
     */
    public function elevesNonAJour(int $anneeId, Carbon $jour, ?array $natures = null, ?string $libelle = null): Collection
    {
        $jour = $jour->copy()->endOfDay();
        $libelle = trim((string) $libelle);

        $dues = $this->echeances($anneeId, $natures)
            ->filter(fn ($e) => $e['date'] && $e['date']->lte($jour) && $e['reste'] > 0.5)
            ->filter(fn ($e) => $libelle === '' || mb_stripos($e['libelle'], $libelle) !== false);

        return $this->avecIdentite($dues->groupBy('inscription_id')->map(fn ($echeances, $inscriptionId) => [
            'inscription_id' => (int) $inscriptionId,
            'echeances'      => $echeances->sortBy(fn ($e) => $e['date']?->timestamp)->values()->all(),
            'montant'        => round($echeances->sum('montant'), 2),
            'paye'           => round($echeances->sum('paye'), 2),
            'reste'          => round($echeances->sum('reste'), 2),
            'natures'        => $echeances->pluck('nature')->unique()->values()->all(),
            'premiere'       => $echeances->min(fn ($e) => $e['date']),
        ]));
    }

    /**
     * Calendrier des échéances entre deux dates : par jour et par nature, le
     * nombre d'élèves concernés, le montant attendu, ce qui est déjà couvert et
     * ce qui reste à risque (non couvert à ce jour).
     *
     * @return Collection<int, array{date:Carbon, nature:string, eleves:int, a_risque:int, attendu:float, couvert:float, reste:float, libelles:array}>
     */
    public function calendrier(int $anneeId, Carbon $du, Carbon $au, ?array $natures = null): Collection
    {
        return $this->echeances($anneeId, $natures)
            ->filter(fn ($e) => $e['date'] && $e['date']->between($du->copy()->startOfDay(), $au->copy()->endOfDay()))
            ->groupBy(fn ($e) => $e['date']->toDateString() . '|' . $e['nature'])
            ->map(function ($lignes, $cle) {
                [$date, $nature] = explode('|', $cle);

                return [
                    'date'     => Carbon::parse($date),
                    'nature'   => $nature,
                    'eleves'   => $lignes->pluck('inscription_id')->unique()->count(),
                    'a_risque' => $lignes->filter(fn ($e) => $e['reste'] > 0.5)->pluck('inscription_id')->unique()->count(),
                    'attendu'  => round($lignes->sum('montant'), 2),
                    'couvert'  => round($lignes->sum('paye'), 2),
                    'reste'    => round($lignes->sum('reste'), 2),
                    'libelles' => $lignes->pluck('libelle')->map(fn ($l) => preg_replace('/^.* — /u', '', $l))->unique()->take(3)->values()->all(),
                ];
            })
            ->sortBy(fn ($l) => $l['date']->timestamp . $l['nature'])
            ->values();
    }

    /* ------------------------------------------------------------------ sources */

    /** Échéances de scolarité des plans d'échéancier de l'année. */
    private function scolarite(int $anneeId): Collection
    {
        $lignes = collect();
        $plans = PlanEcheancier::where('etat', 1)->where('annee_id', $anneeId)->with('lignes')->orderBy('id')->get();

        foreach ($plans as $plan) {
            $lignesPlan = $plan->lignes->values();
            if ($lignesPlan->isEmpty()) {
                continue;
            }
            foreach ($plan->fraisEcoles()->where('etat', 1)->get() as $frais) {
                if ($frais->type_paiement !== TypeDetail::FRAIS_SCOLAIRE || ! $frais->niveau_id) {
                    continue;
                }
                $parts = $this->echeancier->parts($lignesPlan, (float) $frais->montant);
                $dernier = count($parts) - 1;

                foreach ($this->echeancier->dusScolarite($frais) as $inscriptionId => $du) {
                    $resteDu = $du;
                    foreach ($parts as $i => $part) {
                        $montant = $i === $dernier ? round($resteDu, 2) : round($du * $part);
                        $resteDu -= $montant;
                        $ligne = $lignesPlan[$i];
                        $lignes->push([
                            'inscription_id' => (int) $inscriptionId,
                            'nature'         => 'scolarite',
                            'libelle'        => trim($frais->libelle) . ' — ' . $ligne->libelle,
                            'date'           => $ligne->date_echeance ? Carbon::parse($ligne->date_echeance)->startOfDay() : null,
                            'montant'        => (float) $montant,
                        ]);
                    }
                }
            }
        }

        return $lignes;
    }

    /** Une échéance par mois d'abonnement actif (cantine ou transport). */
    private function abonnements(int $anneeId, string $nature, string $table): Collection
    {
        $libelle = $nature === 'bus' ? 'Transport' : 'Cantine';
        $lignes = collect();

        $abonnements = DB::table("{$table} as a")
            ->join('inscriptions as i', 'i.id', '=', 'a.inscription_id')
            ->where('a.etat', 1)->where('a.statut', 1)
            ->where(fn ($q) => $q->where('a.annee_id', $anneeId)->orWhere(fn ($w) => $w->whereNull('a.annee_id')->where('i.annee_id', $anneeId)))
            ->whereNotNull('a.date_debut')
            ->get(['a.inscription_id', 'a.date_debut', 'a.montant_mensuel', 'a.nombre_mois', 'a.montant_total_du']);

        foreach ($abonnements as $a) {
            $mois = max(1, (int) ($a->nombre_mois ?: 1));
            $mensuel = (float) $a->montant_mensuel;
            $total = (float) ($a->montant_total_du ?? $mensuel * $mois);
            $debut = Carbon::parse($a->date_debut)->startOfDay();
            $cumul = 0.0;

            for ($k = 0; $k < $mois; $k++) {
                $date = $debut->copy()->addMonthsNoOverflow($k);
                $montant = $k === $mois - 1 ? round($total - $cumul, 2) : $mensuel;
                $cumul += $montant;
                $lignes->push([
                    'inscription_id' => (int) $a->inscription_id,
                    'nature'         => $nature,
                    'libelle'        => $libelle . ' — ' . ucfirst($date->locale('fr')->isoFormat('MMMM YYYY')),
                    'date'           => $date,
                    'montant'        => $montant,
                ]);
            }
        }

        return $lignes;
    }

    /* ------------------------------------------------------------------ paiements */

    /** Affecte les encaissements de chaque nature aux échéances les plus anciennes d'abord. */
    private function affecterPaiements(int $anneeId, Collection $lignes): Collection
    {
        if ($lignes->isEmpty()) {
            return $lignes;
        }

        $payes = DB::table('details')
            ->whereIn('inscription_id', $lignes->pluck('inscription_id')->unique()->all())
            ->where('etat', 1)
            ->where('statut_paiement', PaiementStatut::ENCAISSE->value)
            ->whereIn('type_paiement', array_map(fn (TypeDetail $t) => $t->value, self::TYPE_DE))
            ->groupBy('inscription_id', 'type_paiement')
            ->selectRaw('inscription_id, type_paiement, SUM(montant) AS total')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->inscription_id . '|' . $p->type_paiement => (float) $p->total]);

        return $lignes
            ->groupBy(fn ($e) => $e['inscription_id'] . '|' . $e['nature'])
            ->flatMap(function ($echeances, $cle) use ($payes) {
                [$inscriptionId, $nature] = explode('|', $cle);
                $disponible = $payes[$inscriptionId . '|' . self::TYPE_DE[$nature]->value] ?? 0.0;

                return $echeances
                    ->sortBy(fn ($e) => $e['date']?->timestamp ?? PHP_INT_MAX)
                    ->map(function ($e) use (&$disponible) {
                        $paye = round(min($e['montant'], max(0.0, $disponible)), 2);
                        $disponible -= $paye;

                        return $e + ['paye' => $paye, 'reste' => max(0.0, round($e['montant'] - $paye, 2))];
                    });
            })
            ->values();
    }

    /** Ajoute nom, matricule, niveau et classe aux lignes par élève, triées par niveau puis nom. */
    private function avecIdentite(Collection $parEleve): Collection
    {
        if ($parEleve->isEmpty()) {
            return collect();
        }

        $identites = DB::table('inscriptions as i')
            ->join('eleves as e', 'e.id', '=', 'i.eleve_id')
            ->leftJoin('niveaux as n', 'n.id', '=', 'i.niveau_id')
            ->leftJoin('classes as c', 'c.id', '=', 'i.classe_id')
            ->whereIn('i.id', $parEleve->keys()->all())
            ->get(['i.id', 'i.niveau_id', 'e.nom', 'e.prenom', 'e.matricule', 'n.libelle as niveau', 'c.libelle as classe'])
            ->keyBy('id');

        return $parEleve->map(function ($ligne, $id) use ($identites) {
            $identite = $identites[$id] ?? null;

            return $ligne + [
                'nom'       => $identite ? trim($identite->nom . ' ' . $identite->prenom) : '—',
                'matricule' => $identite?->matricule,
                'niveau_id' => $identite?->niveau_id ? (int) $identite->niveau_id : null,
                'niveau'    => $identite?->niveau,
                'classe'    => $identite?->classe,
            ];
        })->sortBy(fn ($l) => [$l['niveau'] ?? '', $l['nom']])->values();
    }
}
