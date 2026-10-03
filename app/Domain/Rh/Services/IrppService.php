<?php

namespace App\Domain\Rh\Services;

/**
 * Calcul de l'IRPP par tranches progressives.
 *
 * Le calcul suit la méthode standard d'un impôt progressif par tranches :
 * on découpe le revenu imposable selon les tranches du barème, et chaque
 * portion est taxée au taux de sa tranche. La somme des impôts de chaque
 * portion donne l'IRPP total.
 *
 * Exemple avec le barème par défaut (FCFA) :
 *   Revenu imposable = 350 000
 *   - 0 → 60 000       : 0 %     → 0
 *   - 60 001 → 150 000 : 3 %  × 89 999 ≈ 2 700
 *   - 150 001 → 300 000: 10 % × 149 999 ≈ 15 000
 *   - 300 001 → 350 000: 15 % × 49 999 ≈ 7 500
 *   IRPP = 25 200 (arrondi)
 */
class IrppService
{
    /**
     * Calcule l'IRPP sur un revenu imposable.
     *
     * @param float $revenuImposable  Salaire brut − déductions
     * @param int   $nbCharges        Nombre d'enfants / personnes à charge
     * @return float                  Montant de l'IRPP (positif)
     */
    public function calculer(float $revenuImposable, int $nbCharges = 0): float
    {
        if ($revenuImposable <= 0) {
            return 0.0;
        }

        // 1) Abattement forfaitaire optionnel
        $abattementPct = (float) config('irpp.abattement_pourcentage', 0);
        if ($abattementPct > 0) {
            $revenuImposable = max(0, $revenuImposable * (1 - $abattementPct / 100));
        }

        // 2) Calcul par tranches
        $impot = 0.0;
        $tranches = config('irpp.tranches', []);

        foreach ($tranches as $tranche) {
            $min  = (float) $tranche['min'];
            $max  = $tranche['max'] !== null ? (float) $tranche['max'] : null;
            $taux = (float) $tranche['taux'];

            // Le revenu est-il dans cette tranche ?
            if ($revenuImposable < $min) {
                continue; // on n'a pas encore atteint cette tranche
            }

            // Portion imposable = min(revenu, max) − min + 1
            $plafond = $max ?? $revenuImposable;
            $portion = max(0, min($revenuImposable, $plafond) - $min + 1);

            if ($portion > 0 && $taux > 0) {
                $impot += $portion * ($taux / 100);
            }

            // Si le revenu est inférieur au max de cette tranche, on arrête
            if ($max !== null && $revenuImposable <= $max) {
                break;
            }
        }

        // 3) Réduction pour charges de famille (optionnelle)
        $reductionParCharge = (float) config('irpp.reduction_par_charge', 0);
        if ($reductionParCharge > 0 && $nbCharges > 0) {
            $impot -= $reductionParCharge * $nbCharges;
        }

        return round(max(0, $impot), 2);
    }

    /**
     * Détaille le calcul tranche par tranche (pour affichage ou debug).
     *
     * @return array<int, array{tranche:string, taux:float, base:float, impot:float}>
     */
    public function detailler(float $revenuImposable): array
    {
        if ($revenuImposable <= 0) {
            return [];
        }

        $detail = [];
        $tranches = config('irpp.tranches', []);

        foreach ($tranches as $tranche) {
            $min  = (float) $tranche['min'];
            $max  = $tranche['max'] !== null ? (float) $tranche['max'] : null;
            $taux = (float) $tranche['taux'];

            if ($revenuImposable < $min) {
                continue;
            }

            $plafond = $max ?? $revenuImposable;
            $portion = max(0, min($revenuImposable, $plafond) - $min + 1);

            if ($portion > 0) {
                $detail[] = [
                    'tranche' => $max !== null
                        ? number_format($min, 0, ',', ' ') . ' → ' . number_format($max, 0, ',', ' ')
                        : 'Au-delà de ' . number_format($min, 0, ',', ' '),
                    'taux'    => $taux,
                    'base'    => round($portion, 2),
                    'impot'   => round($portion * ($taux / 100), 2),
                ];
            }

            if ($max !== null && $revenuImposable <= $max) {
                break;
            }
        }

        return $detail;
    }
}