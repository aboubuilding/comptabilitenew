<?php

namespace App\Domain\Rh\Services;

use App\Domain\Rh\Models\Deduction;
use App\Domain\Rh\Models\Employe;
use App\Domain\Rh\Models\Paie;
use App\Domain\Rh\Models\Periode;
use App\Domain\Rh\Models\Remuneration;
use App\Domain\Rh\Models\Retenue;
use App\Domain\Rh\Repositories\PaieRepository;
use App\Domain\Rh\Types\StatutPaie;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaieService
{
    public function __construct(
        private PaieRepository $paies,
        private IrppService    $irpp,
    ) {}

    /* ================================================================
       GÉNÉRATION D'UN BULLETIN
       ================================================================ */

    /**
     * Génère un bulletin de paie pour un employé sur une période donnée.
     * Le bulletin est créé en BROUILLON. Le passage à PAYE se fait
     * manuellement (ou par un job batch).
     */
    public function genererBulletin(int $employeId, int $periodeId): Paie
    {
        // Vérifie l'absence de doublon
        if ($this->paies->existePour($employeId, $periodeId)) {
            throw new RuntimeException(
                "Un bulletin existe déjà pour cet employé sur cette période."
            );
        }

        $employe = Employe::findOrFail($employeId);
        $periode = Periode::findOrFail($periodeId);

        // Récupère les référentiels (ou null si absents)
        $remuneration = Remuneration::query()
            ->where('employe_id', $employeId)
            ->where('etat', 1)
            ->first();

        $deduction = Deduction::query()
            ->where('personnel_id', $employeId)
            ->where('etat', 1)
            ->first();

        $retenue = Retenue::query()
            ->where('employe_id', $employeId)
            ->where('etat', 1)
            ->first();

        // ============ Calcul des montants ============
        $salaireBase     = $remuneration?->salaire_base     ?? $employe->salaire ?? 0;
        $primeAnciennete = $remuneration?->prime_anciennete ?? 0;
        $sursalaire      = $remuneration?->sursalaire       ?? 0;
        $bonus           = $remuneration?->bonus            ?? 0;
        $logement        = $remuneration?->logement         ?? 0;
        $nourriture      = $remuneration?->nourriture       ?? 0;
        $deplacement     = $remuneration?->deplacement      ?? 0;
        $autreAvantage   = $remuneration?->autre_avantage   ?? 0;

        $totalBrut = round(
            $salaireBase + $primeAnciennete + $sursalaire + $bonus
            + $logement + $nourriture + $deplacement + $autreAvantage,
            2
        );

        $totalDeductions = round(
            (float) ($deduction?->cnss ?? 0)
            + (float) ($deduction?->charges_familiale ?? 0)
            + (float) ($deduction?->forfait_professionnel ?? 0),
            2
        );

        // ============ IRPP : auto (barème) ou manuel ============
        $totalRetenues = $this->calculerIrpp($employe, $retenue, $totalBrut, $totalDeductions);

        $netAPayer = round($totalBrut - $totalDeductions - $totalRetenues, 2);

        $anneeId = app(AnneeScolaireContext::class)->id();

        return DB::transaction(fn () => $this->paies->create([
            'personnel_id'       => $employeId,
            'periode_id'         => $periodeId,

            'salaire_base'       => $salaireBase,
            'prime_anciennete'   => $primeAnciennete,
            'sursalaire'         => $sursalaire,
            'bonus'              => $bonus,
            'logement'           => $logement,
            'nourriture'         => $nourriture,
            'deplacement'        => $deplacement,
            'autre_avantage'     => $autreAvantage,

            'total_brut'         => $totalBrut,
            'total_deductions'   => $totalDeductions,
            'total_retenues'     => $totalRetenues,
            'net_a_payer'        => $netAPayer,

            // Mode de paiement copié depuis l'employé
            'mode_paiement'      => $employe->mode_paiement ?? 'billette',
            'banque'             => $employe->banque,
            'iban'               => $employe->iban,

            'statut'             => StatutPaie::BROUILLON->value,
            'reference'          => $this->genererReference($periode),
            'annee_id'           => $anneeId,
        ]));
    }

    /* ================================================================
       GÉNÉRATION EN MASSE
       ================================================================ */

    /**
     * Génère en masse les bulletins pour une période : tous les employés
     * actifs n'ayant pas encore de bulletin pour cette période.
     *
     * @return array{
     *     crees:int, ignores:int, erreurs:array,
     *     total_brut:float, total_net:float,
     *     repartition:{
     *         billette:{nb:int, montant:float},
     *         banque:{nb:int, montant:float}
     *     }
     * }
     */
    public function genererBulletinsPeriode(int $periodeId): array
    {
        $periode = Periode::findOrFail($periodeId);

        $employes = Employe::query()
            ->where('etat', 1)
            ->get();

        $crees = 0;
        $ignores = 0;
        $erreurs = [];
        $totalBrut = 0;
        $totalNet = 0;
        $billetteNb = 0;
        $billetteMontant = 0;
        $banqueNb = 0;
        $banqueMontant = 0;

        foreach ($employes as $employe) {
            try {
                $paie = $this->genererBulletin($employe->id, $periodeId);
                $crees++;

                $totalBrut += (float) $paie->total_brut;
                $totalNet  += (float) $paie->net_a_payer;

                if ($paie->mode_paiement === 'banque') {
                    $banqueNb++;
                    $banqueMontant += (float) $paie->net_a_payer;
                } else {
                    $billetteNb++;
                    $billetteMontant += (float) $paie->net_a_payer;
                }
            } catch (RuntimeException $e) {
                // Bulletin déjà existant → on ignore silencieusement
                if (str_contains($e->getMessage(), 'existe déjà')) {
                    $ignores++;
                } else {
                    $erreurs[] = [
                        'employe' => $employe->nom_prenom,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'crees'       => $crees,
            'ignores'     => $ignores,
            'erreurs'     => $erreurs,
            'total_brut'  => round($totalBrut, 2),
            'total_net'   => round($totalNet, 2),
            'repartition' => [
                'billette' => [
                    'nb'      => $billetteNb,
                    'montant' => round($billetteMontant, 2),
                ],
                'banque' => [
                    'nb'      => $banqueNb,
                    'montant' => round($banqueMontant, 2),
                ],
            ],
        ];
    }

    /* ================================================================
       CYCLE DE VIE D'UN BULLETIN
       ================================================================ */

    /** Valide un bulletin (brouillon → validé). */
    public function valider(int $paieId): Paie
    {
        return DB::transaction(function () use ($paieId) {
            $paie = $this->paies->findOrFail($paieId);

            if ($paie->statut !== StatutPaie::BROUILLON) {
                throw new RuntimeException("Seul un bulletin brouillon peut être validé.");
            }

            $this->paies->update($paieId, ['statut' => StatutPaie::VALIDE->value]);

            return $paie->refresh();
        });
    }

    /**
     * Marque un bulletin comme payé.
     * Pour un virement bancaire : le retrait est marqué automatiquement
     * (l'argent est considéré remis dès le virement).
     * Pour la billetterie : le retrait effectif reste à confirmer via
     * marquerRetrait().
     */
    public function payer(int $paieId, int $payeurId, ?string $datePaiement = null): Paie
    {
        return DB::transaction(function () use ($paieId, $payeurId, $datePaiement) {
            $paie = $this->paies->findOrFail($paieId);

            if ($paie->isPaye()) {
                throw new RuntimeException("Ce bulletin est déjà marqué payé.");
            }

            if ($paie->statut === StatutPaie::ANNULE) {
                throw new RuntimeException("Impossible de payer un bulletin annulé.");
            }

            $payload = [
                'statut'        => StatutPaie::PAYE->value,
                'date_paiement' => $datePaiement ?? now()->toDateString(),
                'paye_par'      => $payeurId,
            ];

            // Pour un virement bancaire : considéré comme "remis" immédiatement
            if ($paie->mode_paiement === 'banque') {
                $payload['retire_a_la_billetterie'] = true;
                $payload['date_retrait']            = now()->toDateString();
                $payload['retire_par']              = $payeurId;
            }

            $this->paies->update($paieId, $payload);

            return $paie->refresh();
        });
    }

    /** Annule un bulletin (uniquement si brouillon ou validé). */
    public function annuler(int $paieId, string $motif): Paie
    {
        return DB::transaction(function () use ($paieId, $motif) {
            $paie = $this->paies->findOrFail($paieId);

            if ($paie->statut === StatutPaie::PAYE) {
                throw new RuntimeException("Impossible d'annuler un bulletin déjà payé.");
            }

            $this->paies->update($paieId, [
                'statut'       => StatutPaie::ANNULE->value,
                'observations' => trim(($paie->observations ?? '') . " | Annulé : {$motif}"),
            ]);

            return $paie->refresh();
        });
    }

    /**
     * Marque le retrait effectif à la billetterie.
     * L'employé est venu chercher ses espèces en main propre.
     */
    public function marquerRetrait(int $paieId, int $retireurId): Paie
    {
        return DB::transaction(function () use ($paieId, $retireurId) {
            $paie = $this->paies->findOrFail($paieId);

            if (! $paie->isPaye()) {
                throw new RuntimeException("Ce bulletin n'a pas encore été payé.");
            }

            if ($paie->mode_paiement !== 'billette') {
                throw new RuntimeException(
                    "Ce bulletin n'est pas un paiement à la billetterie."
                );
            }

            if ($paie->retire_a_la_billetterie) {
                throw new RuntimeException("Le retrait a déjà été enregistré.");
            }

            $this->paies->update($paieId, [
                'retire_a_la_billetterie' => true,
                'date_retrait'            => now()->toDateString(),
                'retire_par'              => $retireurId,
            ]);

            return $paie->refresh();
        });
    }

    /* ================================================================
       CALCUL IRPP
       ================================================================ */

    /**
     * Calcule l'IRPP selon le mode de l'employé :
     *   - 'auto'   : barème progressif sur le revenu net imposable
     *   - 'manuel' : montant fixe saisi dans `retenues.irpp`
     *
     * La valeur est TOUJOURS persistée dans `paies.total_retenues`, ce
     * qui fige le calcul du moment.
     */
    private function calculerIrpp(
        Employe $employe,
        ?Retenue $retenue,
        float $totalBrut,
        float $totalDeductions
    ): float {
        // Mode manuel : on prend la valeur saisie
        if ($employe->irpp_mode === 'manuel') {
            return round((float) ($retenue?->irpp ?? 0), 2);
        }

        // Mode auto : barème progressif sur le revenu net imposable
        $revenuImposable = max(0, $totalBrut - $totalDeductions);

        // Nombre de charges (enfants à charge)
        $nbCharges = (int) ($employe->nbre_enfant ?? 0);

        return $this->irpp->calculer($revenuImposable, $nbCharges);
    }

    /* ================================================================
       INTERNALS
       ================================================================ */

    /** Génère une référence unique : BUL-YYYYMMDD-NNNN. */
    private function genererReference(Periode $periode): string
    {
        $prefix = 'BUL-' . now()->format('Ymd') . '-';

        $dernier = $this->paies->activeQuery()
            ->where('reference', 'like', "{$prefix}%")
            ->orderByDesc('reference')
            ->value('reference');

        $seq = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}