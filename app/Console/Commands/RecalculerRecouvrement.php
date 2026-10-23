<?php

namespace App\Console\Commands;

use App\Domain\Finances\Services\RecouvrementService;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\Scolarite\Types\StatutAnnee;
use App\Support\AnneeScolaireContext;
use Illuminate\Console\Command;

/**
 * Recalcul des dossiers de recouvrement d'après les plans d'échéancier :
 * planifié chaque jour à 2 h (app/Console/Kernel.php) pour les années
 * ouvertes ; --annee pour une année précise.
 */
class RecalculerRecouvrement extends Command
{
    protected $signature = 'recouvrement:recalculer {--annee=* : identifiant(s) d\'année ; par défaut les années ouvertes}';

    protected $description = 'Met à jour les dossiers de recouvrement échus d\'après les plans d\'échéancier';

    public function handle(RecouvrementService $service): int
    {
        $annees = $this->option('annee')
            ?: Annee::where('etat', 1)->where('statut_annee', StatutAnnee::Ouvert->value)->pluck('id')->all();

        foreach ($annees as $anneeId) {
            app(AnneeScolaireContext::class)->set((int) $anneeId);
            $stats = $service->recalculerTout((int) $anneeId);
            $libelle = Annee::find($anneeId)?->libelle ?? "#{$anneeId}";
            $this->info("{$libelle} : {$stats['echus']} élève(s) en retard, {$stats['dossiers_ouverts']} dossier(s) ouvert(s), "
                . "{$stats['dossiers_maj']} mis à jour, {$stats['dossiers_soldes']} soldé(s).");
        }

        return self::SUCCESS;
    }
}
