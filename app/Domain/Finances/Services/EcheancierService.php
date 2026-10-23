<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Models\FraisEcole;
use App\Domain\Finances\Models\PlanEcheancier;
use App\Domain\Finances\Models\PlanEcheancierLigne;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Finances\Types\TypePaiement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Le plan d'échéancier est le dispositif unique des paiements échelonnés.
 *
 *  - `definirPourFrais` : crée (ou remplace) l'échéancier saisi avec un frais ;
 *  - `apercuConversion` / `convertirTranches` : reprend les anciennes tranches
 *    (table `tranches`, en % du frais) dans les plans, pour UNE année scolaire ;
 *  - `suivi` : pour un frais de scolarité, prévu / payé / reste par échéance.
 *
 * La conversion ne supprime rien : chaque tranche garde l'identifiant de
 * l'échéance créée (`plan_echeancier_ligne_id`), ce qui la rend relançable.
 */
class EcheancierService
{
    /** Écart toléré entre la somme des échéances et le montant du frais (arrondis). */
    public const TOLERANCE = 1.0;

    /**
     * @param array<int, array{libelle:string, date_echeance:string, montant:float|string}> $echeances
     */
    public function definirPourFrais(FraisEcole $frais, array $echeances): PlanEcheancier
    {
        $echeances = array_values($echeances);
        if ($echeances === []) {
            throw new RuntimeException("Un frais échelonné doit avoir au moins une échéance.");
        }

        $total = round(array_sum(array_map(fn ($e) => (float) $e['montant'], $echeances)), 2);
        if (abs($total - (float) $frais->montant) > self::TOLERANCE) {
            throw new RuntimeException(sprintf(
                "La somme des échéances (%s) doit être égale au montant du frais (%s).",
                number_format($total, 0, ',', ' '),
                number_format((float) $frais->montant, 0, ',', ' ')
            ));
        }

        return DB::transaction(function () use ($frais, $echeances) {
            // Un plan propre au frais est réécrit ; un plan partagé n'est jamais modifié ici
            $plan = $frais->plan_echeancier_id ? PlanEcheancier::find($frais->plan_echeancier_id) : null;
            $partage = $plan && FraisEcole::where('plan_echeancier_id', $plan->id)->where('id', '!=', $frais->id)->exists();

            if (! $plan || $partage) {
                $plan = PlanEcheancier::create([
                    'nom'      => 'Échéancier — ' . $frais->libelle,
                    'annee_id' => $frais->annee_id,
                    'etat'     => 1,
                ]);
            } else {
                PlanEcheancierLigne::where('plan_echeancier_id', $plan->id)->update(['etat' => 2]);
            }

            $this->ecrireLignes($plan, (float) $frais->montant, array_map(fn ($e) => [
                'libelle' => trim((string) $e['libelle']),
                'date'    => $e['date_echeance'],
                'montant' => round((float) $e['montant'], 2),
            ], $echeances));

            $frais->update([
                'plan_echeancier_id' => $plan->id,
                'modalite_paiement'  => TypePaiement::ECHELONNE->value,
            ]);

            return $plan->fresh('lignes');
        });
    }

    /** @return array<int, PlanEcheancierLigne> */
    private function ecrireLignes(PlanEcheancier $plan, float $total, array $lignes): array
    {
        $creees = [];
        foreach ($lignes as $i => $ligne) {
            $creees[] = PlanEcheancierLigne::create([
                'plan_echeancier_id' => $plan->id,
                'ordre'              => $i + 1,
                'libelle'            => $ligne['libelle'] !== '' ? $ligne['libelle'] : 'Échéance ' . ($i + 1),
                'date_echeance'      => $ligne['date'],
                'montant'            => $ligne['montant'],
                'pourcentage'        => $total > 0 ? round($ligne['montant'] / $total * 100, 2) : null,
                'etat'               => 1,
            ]);
        }

        return $creees;
    }

    /* =====================================================================
       Conversion des anciennes tranches (année scolaire de l'en-tête)
       ===================================================================== */

    /**
     * Ce que ferait la conversion, sans rien écrire.
     *
     * @return array{a_convertir:array, deja_convertis:int, incoherences:array<int, string>, hors_annee:int}
     */
    public function apercuConversion(int $anneeId): array
    {
        $tranches = DB::table('tranches as t')
            ->join('frais_ecoles as f', 'f.id', '=', 't.frais_ecole_id')
            ->where('t.etat', 1)->where('f.etat', 1)->where('f.annee_id', $anneeId)
            ->orderBy('t.frais_ecole_id')->orderBy('t.date_butoire')->orderBy('t.id')
            ->get(['t.id', 't.libelle', 't.date_butoire', 't.taux', 't.plan_echeancier_ligne_id',
                   'f.id as frais_id', 'f.libelle as frais', 'f.montant', 'f.plan_echeancier_id']);

        $aConvertir = [];
        $dejaConvertis = 0;
        $incoherences = [];

        foreach ($tranches->groupBy('frais_id') as $fraisId => $lignes) {
            $frais = $lignes->first();

            if ($lignes->every(fn ($l) => $l->plan_echeancier_ligne_id !== null)) {
                $dejaConvertis++;
                continue;
            }
            if ($lignes->contains(fn ($l) => $l->plan_echeancier_ligne_id !== null)) {
                $incoherences[] = "« {$frais->frais} » : une partie seulement de ses tranches est convertie.";
                continue;
            }
            if ($frais->plan_echeancier_id) {
                $incoherences[] = "« {$frais->frais} » a déjà un plan d'échéancier : ses tranches ne sont pas reprises.";
                continue;
            }
            $taux = round((float) $lignes->sum('taux'), 2);
            if (abs($taux - 100) > 0.05) {
                $incoherences[] = "« {$frais->frais} » : la somme des taux de ses tranches fait {$taux} % au lieu de 100 %.";
                continue;
            }
            if ($lignes->contains(fn ($l) => empty($l->date_butoire))) {
                $incoherences[] = "« {$frais->frais} » : une tranche n'a pas de date butoir.";
                continue;
            }

            $aConvertir[(int) $fraisId] = [
                'frais_id' => (int) $fraisId,
                'frais'    => $frais->frais,
                'montant'  => (float) $frais->montant,
                'tranches' => $lignes->values(),
            ];
        }

        return [
            'a_convertir'    => array_values($aConvertir),
            'deja_convertis' => $dejaConvertis,
            'incoherences'   => $incoherences,
            // Tranches actives que l'année de l'en-tête ne concerne pas (autres années, frais absent)
            'hors_annee'     => DB::table('tranches as t')
                ->leftJoin('frais_ecoles as f', 'f.id', '=', 't.frais_ecole_id')
                ->where('t.etat', 1)->whereNull('t.plan_echeancier_ligne_id')
                ->where(fn ($q) => $q->whereNull('f.id')->orWhere('f.annee_id', '!=', $anneeId)->orWhereNull('f.annee_id'))
                ->count(),
        ];
    }

    /**
     * Convertit les tranches des frais de l'année en plans d'échéancier.
     *
     * @return array{plans_crees:int, echeances_creees:int, deja_convertis:int, incoherences:array<int, string>, hors_annee:int}
     */
    public function convertirTranches(int $anneeId): array
    {
        return DB::transaction(function () use ($anneeId) {
            $apercu = $this->apercuConversion($anneeId);
            $plans = $echeances = 0;

            foreach ($apercu['a_convertir'] as $cas) {
                $plan = PlanEcheancier::create([
                    'nom'         => 'Échéancier — ' . $cas['frais'],
                    'description' => 'Repris des tranches de paiement',
                    'annee_id'    => $anneeId,
                    'etat'        => 1,
                ]);

                // Montants arrondis au franc ; la dernière échéance absorbe l'écart d'arrondi
                $reste = $cas['montant'];
                $dernier = count($cas['tranches']) - 1;
                $lignes = [];
                foreach ($cas['tranches'] as $i => $tranche) {
                    $montant = $i === $dernier ? round($reste, 2) : round($cas['montant'] * (float) $tranche->taux / 100);
                    $reste -= $montant;
                    $lignes[] = ['libelle' => (string) $tranche->libelle, 'date' => $tranche->date_butoire, 'montant' => $montant];
                }

                foreach ($this->ecrireLignes($plan, $cas['montant'], $lignes) as $i => $ligne) {
                    // Le taux d'origine est conservé tel quel
                    $ligne->update(['pourcentage' => (float) $cas['tranches'][$i]->taux]);
                    DB::table('tranches')->where('id', $cas['tranches'][$i]->id)->update(['plan_echeancier_ligne_id' => $ligne->id]);
                    $echeances++;
                }

                DB::table('frais_ecoles')->where('id', $cas['frais_id'])->update([
                    'plan_echeancier_id' => $plan->id,
                    'modalite_paiement'  => TypePaiement::ECHELONNE->value,
                    'updated_at'         => now(),
                ]);
                $plans++;
            }

            return [
                'plans_crees'      => $plans,
                'echeances_creees' => $echeances,
                'deja_convertis'   => $apercu['deja_convertis'],
                'incoherences'     => $apercu['incoherences'],
                'hors_annee'       => $apercu['hors_annee'],
            ];
        });
    }

    /* =====================================================================
       Suivi des échéances d'un frais
       ===================================================================== */

    /**
     * Prévu, payé et reste par échéance, tous élèves confondus.
     *
     * Limité aux frais de scolarité : ce sont les seuls dont on sait, par
     * l'inscription, qui les doit (élèves du niveau et de l'année du frais)
     * et pour combien (montant et remises de l'inscription). Chaque élève
     * voit ses encaissements de scolarité affectés aux échéances dans
     * l'ordre : un même paiement n'est donc compté qu'une fois.
     *
     * @return null|array{eleves:int, prevu:float, paye:float, reste:float, lignes:array<int, array>}
     */
    public function suivi(FraisEcole $frais): ?array
    {
        $plan = $frais->planEcheancier;
        if (! $plan || $plan->lignes->isEmpty() || $frais->type_paiement !== TypeDetail::FRAIS_SCOLAIRE || ! $frais->niveau_id) {
            return null;
        }

        $dus = $this->dusScolarite($frais);
        $payes = DB::table('details')
            ->whereIn('inscription_id', array_keys($dus))
            ->where('etat', 1)
            ->where('statut_paiement', PaiementStatut::ENCAISSE->value)
            ->where('type_paiement', TypeDetail::FRAIS_SCOLAIRE->value)
            ->groupBy('inscription_id')
            ->selectRaw('inscription_id, SUM(montant) AS total')
            ->pluck('total', 'inscription_id');

        $echeances = $plan->lignes->values();
        $parts = $this->parts($echeances, (float) $frais->montant);
        $lignes = $echeances->map(fn ($l) => ['prevu' => 0.0, 'paye' => 0.0, 'a_jour' => 0])->all();
        $dernier = count($lignes) - 1;

        foreach ($dus as $inscriptionId => $du) {
            $restePaye = min((float) ($payes[$inscriptionId] ?? 0), $du);
            $resteDu = $du;

            foreach ($parts as $i => $part) {
                $prevu = $i === $dernier ? round($resteDu, 2) : round($du * $part);
                $resteDu -= $prevu;
                $regle = min($prevu, $restePaye);
                $restePaye -= $regle;

                $lignes[$i]['prevu'] += $prevu;
                $lignes[$i]['paye'] += $regle;
                $lignes[$i]['a_jour'] += ($regle + 0.5 >= $prevu) ? 1 : 0;
            }
        }

        $aujourdhui = Carbon::today();
        foreach ($echeances as $i => $echeance) {
            $prevu = round($lignes[$i]['prevu'], 2);
            $paye = round($lignes[$i]['paye'], 2);
            $echue = $echeance->date_echeance && $echeance->date_echeance->lte($aujourdhui);

            $lignes[$i] = [
                'libelle'  => $echeance->libelle,
                'date'     => $echeance->date_echeance,
                'part'     => round($parts[$i] * 100, 2),
                'prevu'    => $prevu,
                'paye'     => $paye,
                'reste'    => max(0.0, round($prevu - $paye, 2)),
                'a_jour'   => $lignes[$i]['a_jour'],
                'echue'    => $echue,
                'statut'   => match (true) {
                    $prevu > 0 && $paye + 0.5 >= $prevu => 'reglee',
                    $paye > 0                           => 'partielle',
                    $echue                              => 'exigible',
                    default                             => 'a_venir',
                },
            ];
        }

        $prevu = round(array_sum(array_column($lignes, 'prevu')), 2);
        $paye = round(array_sum(array_column($lignes, 'paye')), 2);

        return [
            'eleves' => count($dus),
            'prevu'  => $prevu,
            'paye'   => $paye,
            'reste'  => max(0.0, round($prevu - $paye, 2)),
            'lignes' => $lignes,
        ];
    }

    /**
     * Élèves à jour et en retard à chaque échéance d'un plan.
     *
     * L'intervalle [du, au] retient les échéances dont la date butoir y tombe ;
     * la situation est arrêtée à la date de fin : seuls comptent les
     * encaissements de scolarité faits jusqu'à ce jour. Un élève est à jour à
     * une échéance quand ses encaissements couvrent tout ce qui était dû
     * jusqu'à elle (les échéances se règlent dans l'ordre).
     *
     * Comme pour `suivi`, seuls les frais de scolarité rattachés à un niveau
     * sont suivis : ce sont les seuls dont l'inscription dit qui les doit.
     *
     * @return array{du:?Carbon, au:Carbon, eleves:int, frais_suivis:array<int, string>, frais_non_suivis:array<int, string>, echeances:array<int, array>}
     */
    public function suiviEleves(PlanEcheancier $plan, ?Carbon $du = null, ?Carbon $au = null): array
    {
        $au = ($au ?? Carbon::today())->copy()->endOfDay();
        $du = $du?->copy()->startOfDay();
        $lignesPlan = $plan->lignes->values();

        $echeances = [];
        foreach ($lignesPlan as $i => $ligne) {
            $date = $ligne->date_echeance;
            // Une échéance sans date n'est retenue que si aucune date de début n'est demandée
            $retenue = $date ? ((! $du || $date->gte($du)) && $date->lte($au)) : ! $du;
            if ($retenue) {
                $echeances[$i] = [
                    'libelle' => $ligne->libelle, 'date' => $date,
                    'prevu' => 0.0, 'paye' => 0.0, 'a_jour' => [], 'en_retard' => [],
                ];
            }
        }

        $suivis = $nonSuivis = [];
        $nbEleves = 0;

        foreach ($plan->fraisEcoles()->where('etat', 1)->orderBy('libelle')->get() as $frais) {
            if ($frais->type_paiement !== TypeDetail::FRAIS_SCOLAIRE || ! $frais->niveau_id) {
                $nonSuivis[] = $frais->libelle;
                continue;
            }
            $suivis[] = $frais->libelle;

            $dus = $this->dusScolarite($frais);
            $nbEleves += count($dus);
            if ($dus === [] || $echeances === []) {
                continue;
            }

            $payes = DB::table('details')
                ->whereIn('inscription_id', array_keys($dus))
                ->where('etat', 1)
                ->where('statut_paiement', PaiementStatut::ENCAISSE->value)
                ->where('type_paiement', TypeDetail::FRAIS_SCOLAIRE->value)
                ->whereRaw('COALESCE(date_encaissement, date_paiement, DATE(created_at)) <= ?', [$au->toDateString()])
                ->groupBy('inscription_id')
                ->selectRaw('inscription_id, SUM(montant) AS total')
                ->pluck('total', 'inscription_id');

            $eleves = DB::table('inscriptions as i')
                ->join('eleves as e', 'e.id', '=', 'i.eleve_id')
                ->leftJoin('niveaux as n', 'n.id', '=', 'i.niveau_id')
                ->leftJoin('classes as c', 'c.id', '=', 'i.classe_id')
                ->whereIn('i.id', array_keys($dus))
                ->orderBy('e.nom')->orderBy('e.prenom')
                ->get(['i.id', 'e.nom', 'e.prenom', 'e.matricule', 'n.libelle as niveau', 'c.libelle as classe']);

            $parts = $this->parts($lignesPlan, (float) $frais->montant);
            $dernier = count($parts) - 1;

            foreach ($eleves as $eleve) {
                $du_ = $dus[$eleve->id];
                $restePaye = min((float) ($payes[$eleve->id] ?? 0), $du_);
                $resteDu = $du_;

                foreach ($parts as $i => $part) {
                    $prevu = $i === $dernier ? round($resteDu, 2) : round($du_ * $part);
                    $resteDu -= $prevu;
                    $regle = min($prevu, $restePaye);
                    $restePaye -= $regle;

                    if (! isset($echeances[$i])) {
                        continue;
                    }
                    $echeances[$i]['prevu'] += $prevu;
                    $echeances[$i]['paye'] += $regle;
                    $echeances[$i][$regle + 0.5 >= $prevu ? 'a_jour' : 'en_retard'][] = [
                        'inscription_id' => (int) $eleve->id,
                        'nom'       => trim($eleve->nom . ' ' . $eleve->prenom),
                        'matricule' => $eleve->matricule,
                        'niveau'    => $eleve->niveau,
                        'classe'    => $eleve->classe,
                        // Libellé complet : « Frais de scolarité Terminale »
                        'frais'     => trim(($frais->type_paiement?->label() ?? '') . ' ' . $frais->libelle),
                        'prevu'     => $prevu,
                        'paye'      => round($regle, 2),
                        'reste'     => max(0.0, round($prevu - $regle, 2)),
                    ];
                }
            }
        }

        foreach ($echeances as &$echeance) {
            $echeance['prevu'] = round($echeance['prevu'], 2);
            $echeance['paye'] = round($echeance['paye'], 2);
            $echeance['reste'] = max(0.0, round($echeance['prevu'] - $echeance['paye'], 2));
        }
        unset($echeance);

        return [
            'du' => $du, 'au' => $au,
            'eleves' => $nbEleves,
            'frais_suivis' => $suivis,
            'frais_non_suivis' => $nonSuivis,
            'echeances' => array_values($echeances),
        ];
    }

    /**
     * Élèves non à jour à une date donnée, tous échéanciers en cours de
     * l'année confondus : pour chaque élève, ce qui était dû à cette date
     * (échéances dont la date butoir est passée), ce qui est encaissé, le
     * retard et les échéances non réglées. Les échéances à venir ne comptent pas.
     *
     * @return array{jour:Carbon, plans:int, suivis:int, du:float, paye:float, retard:float, eleves:array<int, array>}
     */
    public function elevesNonAJour(int $anneeId, Carbon $jour): array
    {
        $plans = PlanEcheancier::where('etat', 1)->where('annee_id', $anneeId)->with('lignes')->orderBy('id')->get();
        $parEleve = [];
        $nbPlans = 0;

        foreach ($plans as $plan) {
            $suivi = $this->suiviEleves($plan, null, $jour);
            if ($suivi['frais_suivis'] === []) {
                continue;   // plan sans frais de scolarité suivi
            }
            $nbPlans++;

            foreach ($suivi['echeances'] as $echeance) {
                foreach (['a_jour', 'en_retard'] as $groupe) {
                    foreach ($echeance[$groupe] as $eleve) {
                        $id = $eleve['inscription_id'];
                        $parEleve[$id] ??= [
                            'inscription_id' => $id, 'nom' => $eleve['nom'], 'matricule' => $eleve['matricule'],
                            'niveau' => $eleve['niveau'], 'classe' => $eleve['classe'], 'frais' => $eleve['frais'],
                            'du' => 0.0, 'paye' => 0.0, 'echeances' => [],
                        ];
                        $parEleve[$id]['du'] += $eleve['prevu'];
                        $parEleve[$id]['paye'] += $eleve['paye'];
                        if ($groupe === 'en_retard') {
                            $parEleve[$id]['echeances'][] = [
                                'libelle' => $echeance['libelle'], 'date' => $echeance['date'], 'reste' => $eleve['reste'],
                            ];
                        }
                    }
                }
            }
        }

        $suivis = count($parEleve);
        $enRetard = [];
        foreach ($parEleve as $eleve) {
            $eleve['du'] = round($eleve['du'], 2);
            $eleve['paye'] = round($eleve['paye'], 2);
            $eleve['retard'] = max(0.0, round($eleve['du'] - $eleve['paye'], 2));
            if ($eleve['echeances'] !== [] && $eleve['retard'] > 0.5) {
                $enRetard[] = $eleve;
            }
        }
        usort($enRetard, fn ($a, $b) => [$a['niveau'], $a['nom']] <=> [$b['niveau'], $b['nom']]);

        return [
            'jour'   => $jour->copy()->startOfDay(),
            'plans'  => $nbPlans,
            'suivis' => $suivis,
            'du'     => round(array_sum(array_column($enRetard, 'du')), 2),
            'paye'   => round(array_sum(array_column($enRetard, 'paye')), 2),
            'retard' => round(array_sum(array_column($enRetard, 'retard')), 2),
            'eleves' => $enRetard,
        ];
    }

    /** Part de chaque échéance dans le total (somme = 1). */
    public function parts($echeances, float $total): array
    {
        $montants = $echeances->sum(fn ($l) => (float) $l->montant);
        if ($montants > 0) {
            return $echeances->map(fn ($l) => (float) $l->montant / $montants)->all();
        }
        $pourcentages = $echeances->sum(fn ($l) => (float) $l->pourcentage) ?: 100;

        return $echeances->map(fn ($l) => (float) $l->pourcentage / $pourcentages)->all();
    }

    /**
     * Scolarité due par chaque élève concerné par ce frais : inscriptions du
     * niveau et de l'année du frais, remises de l'inscription déduites. Si le
     * niveau a plusieurs frais de scolarité, seuls les élèves dont le montant
     * de scolarité est celui du frais lui sont attribués.
     *
     * @return array<int, float>  inscription_id => dû
     */
    public function dusScolarite(FraisEcole $frais): array
    {
        $plusieurs = FraisEcole::where('etat', 1)->where('annee_id', $frais->annee_id)
            ->where('niveau_id', $frais->niveau_id)->where('type_paiement', TypeDetail::FRAIS_SCOLAIRE->value)
            ->count() > 1;

        return DB::table('inscriptions')
            ->where('etat', 1)->where('statut_abandon', 0)
            ->where('annee_id', $frais->annee_id)->where('niveau_id', $frais->niveau_id)
            ->where('frais_scolarite', '>', 0)
            ->when($plusieurs, fn ($q) => $q->whereBetween('frais_scolarite', [$frais->montant - 0.5, $frais->montant + 0.5]))
            ->get(['id', 'frais_scolarite', 'taux_remise', 'remise_scolarite'])
            ->mapWithKeys(fn ($i) => [(int) $i->id => max(0.0, round(
                (float) $i->frais_scolarite * (1 - (float) ($i->taux_remise ?? 0) / 100) - (float) ($i->remise_scolarite ?? 0),
                2
            ))])
            ->filter(fn ($du) => $du > 0)
            ->all();
    }
}
