<?php

namespace App\Console\Commands;

use App\Domain\Finances\Services\SouscriptionChargementService;
use Illuminate\Console\Command;

/**
 * Vide puis recharge la table des souscriptions (cantine, transport,
 * activités extrascolaires, événements) à partir des inscriptions et des
 * paiements de la base historique. Peut être relancée : les souscriptions
 * saisies à l'écran sont conservées, les lignes d'origine sont archivées
 * dans `souscriptions_archive`.
 */
class RechargerSouscriptions extends Command
{
    protected $signature = 'souscriptions:recharger {--simuler : Affiche le bilan sans rien enregistrer}';

    protected $description = 'Vide et recharge les souscriptions (cantine, transport, activités, événements)';

    public function handle(SouscriptionChargementService $chargement): int
    {
        $simuler = (bool) $this->option('simuler');
        $bilan = $chargement->recharger($simuler);

        $this->table(['Étape', 'Lignes'], [
            ['Anciennes lignes archivées', $bilan['archivees']],
            ['Lignes supprimées', $bilan['supprimees']],
            ['Souscriptions cantine', $bilan['cantine']],
            ['Souscriptions transport', $bilan['transport']],
            ['Souscriptions activités', $bilan['activites']],
            ['Souscriptions événements', $bilan['evenements']],
            ['Lignes venues des modules de services', $bilan['modules']],
            ['Lignes de paiement rattachées', $bilan['paiements_rattaches']],
        ]);

        $this->info($simuler ? 'Simulation : rien n\'a été enregistré.' : 'Souscriptions rechargées.');

        return self::SUCCESS;
    }
}
