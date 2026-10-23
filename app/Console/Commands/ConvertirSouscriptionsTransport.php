<?php

namespace App\Console\Commands;

use App\Domain\Finances\Types\TypeDetail;
use App\Domain\ServicesEleves\Services\AbonnementBusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Transforme les souscriptions transport (reprises de la base historique) en
 * abonnements bus, pour une année ou pour toutes. Même traitement que le
 * bouton « Synchroniser avec les souscriptions » de l'écran Abonnements bus.
 */
class ConvertirSouscriptionsTransport extends Command
{
    protected $signature = 'transport:convertir-souscriptions {--annee=* : identifiant(s) d\'année ; toutes par défaut}';

    protected $description = 'Transforme les souscriptions transport en abonnements bus (relançable, ne supprime rien)';

    public function handle(AbonnementBusService $service): int
    {
        $annees = $this->option('annee') ?: DB::table('souscriptions')
            ->where('type_paiement', TypeDetail::BUS->value)->where('etat', 1)
            ->whereNotNull('annee_id')->distinct()->orderBy('annee_id')->pluck('annee_id')->all();

        foreach ($annees as $anneeId) {
            $libelle = DB::table('annees')->where('id', $anneeId)->value('libelle') ?? "#{$anneeId}";
            $bilan = $service->convertirSouscriptions((int) $anneeId);
            $this->info("{$libelle} : {$bilan['convertis']} abonnement(s) créé(s), {$bilan['ignores']} ignorée(s), "
                . "{$bilan['paiements']} ligne(s) de paiement rattachée(s).");
        }

        return self::SUCCESS;
    }
}
