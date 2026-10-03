<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Repositories\ImputationAnalytiqueRepository;
use Illuminate\Support\Collection;

class RapportAnalytiqueService
{
    public function __construct(
        private ImputationAnalytiqueRepository $imputations,
    ) {}

    // app/Domain/Finances/Services/RapportAnalytiqueService.php — méthode tableauDeBord() révisée

public function tableauDeBord(
    ?int $anneeId = null,
    ?string $dateDebut = null,
    ?string $dateFin = null
): Collection {
    $agregats = $this->imputations->agregerParCentre($anneeId, $dateDebut, $dateFin);

    // Budgets par centre — jointure directe sur centre_analytique_id.
    // Plus robuste que l'ancien matching par libellé.
    $budgets = app(\App\Domain\Finances\Repositories\PrevisionRepository::class)
        ->agregerParCentre($anneeId, $dateDebut, $dateFin);

    $agregats = $agregats->keyBy('centre_id');

    $centres = app(\App\Domain\Finances\Repositories\CentreAnalytiqueRepository::class)->listePourSelect();

    return $centres->map(function ($centre) use ($agregats, $budgets) {
        $row      = $agregats[$centre->id] ?? null;
        $recettes = round((float) ($row->total_recettes ?? 0), 2);
        $depenses = round((float) ($row->total_depenses ?? 0), 2);
        $resultat = round($recettes - $depenses, 2);
        $budget   = round((float) ($budgets[$centre->id]->total_prevu ?? 0), 2);
        $ecart    = round($resultat - $budget, 2);

        return [
            'centre_id'        => $centre->id,
            'code'             => $centre->code,
            'libelle'          => $centre->libelle,
            'recettes'         => $recettes,
            'depenses'         => $depenses,
            'resultat'         => $resultat,
            'budget'           => $budget,
            'ecart_budget'     => $ecart,
            'taux_realisation' => abs($budget) > 0.005 ? round(($resultat / $budget) * 100, 2) : 0.0,
        ];
    })->values();
}
}