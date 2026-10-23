<?php

namespace App\Domain\ServicesEleves\Services;

use App\Domain\Finances\Services\SouscriptionChargementService;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\Scolarite\Models\Annee;
use App\Domain\ServicesEleves\DTO\AbonnementBusData;
use App\Domain\ServicesEleves\Models\AbonnementBus;
use App\Domain\ServicesEleves\Models\Zone;
use App\Domain\ServicesEleves\Repositories\AbonnementBusRepository;
use App\Support\AnneeScolaireContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AbonnementBusService
{
    /** Abonnement créé à partir d'une souscription transport de la base historique. */
    public const ORIGINE_REPRISE = 'reprise';

    public function __construct(
        private AbonnementBusRepository $abonnements,
    ) {}

    /**
     * Souscrire un élève à un abonnement bus.
     *
     * On choisit la zone ; la ligne et le véhicule en découlent. Le tarif de la
     * zone n'est qu'indicatif : le montant mensuel convenu peut s'en écarter.
     *
     * IMPORTANT : cette méthode crée l'engagement, PAS le paiement.
     * Le montant dû sera visible immédiatement dans la situation
     * financière de l'élève (module Recouvrement).
     */
    public function souscrire(AbonnementBusData $data): AbonnementBus
    {
        // 1) Un seul abonnement actif par inscription
        $existant = $this->abonnements->activeQuery()
            ->where('inscription_id', $data->inscriptionId)
            ->where('statut', 1)
            ->exists();

        if ($existant) {
            throw new RuntimeException(
                "Cet élève a déjà un abonnement bus actif. Abandonnez-le avant d'en créer un nouveau."
            );
        }

        // 2) Validation des données
        if ($data->montantMensuel <= 0) {
            throw new RuntimeException("Le montant mensuel doit être strictement positif.");
        }
        if ($data->nombreMois <= 0) {
            throw new RuntimeException("Le nombre de mois doit être strictement positif.");
        }

        $anneeId = app(AnneeScolaireContext::class)->id();
        Annee::interdireSouscriptionSiCloturee($anneeId);

        return DB::transaction(function () use ($data, $anneeId) {
            $anneeScolaire = Annee::find($anneeId);
            $dateFin = $anneeScolaire?->date_fin ?? now()->endOfYear()->toDateString();

            return $this->abonnements->create($this->desserte($data->zoneId) + [
                'inscription_id'   => $data->inscriptionId,
                'zone_id'          => $data->zoneId,
                'sens'             => $data->sens,
                'date_debut'       => $data->dateDebut,
                'date_fin'         => $dateFin,
                'montant_mensuel'  => $data->montantMensuel,
                'nombre_mois'      => $data->nombreMois,
                'montant_total_du' => $data->montantTotalDu(),
                'statut'           => 1,
                'annee_id'         => $anneeId,
            ]);
        });
    }

    /**
     * Abandonner un abonnement.
     */
    public function abandonner(int $id, string $motif, int $userId): AbonnementBus
    {
        return DB::transaction(function () use ($id, $motif, $userId) {
            $abonnement = $this->abonnements->findOrFail($id);

            if (! $abonnement->isActif()) {
                throw new RuntimeException("Cet abonnement n'est pas actif.");
            }

            $this->abonnements->update($abonnement->id, [
                'statut'        => 0,
                'date_abandon'  => now()->toDateString(),
                'motif_abandon' => $motif,
                'abandonne_par' => $userId,
            ]);

            return $abonnement->refresh();
        });
    }

    /**
     * Changer la zone (donc la ligne et le véhicule) ou le sens d'un abonnement.
     */
    public function assigner(int $id, array $data): AbonnementBus
    {
        $abonnement = $this->abonnements->findOrFail($id);
        $zoneId = array_key_exists('zone_id', $data) ? ($data['zone_id'] ? (int) $data['zone_id'] : null) : $abonnement->zone_id;

        $this->abonnements->update($abonnement->id, $this->desserte($zoneId) + [
            'zone_id' => $zoneId,
            'sens'    => $data['sens'] ?? $abonnement->sens,
        ]);

        return $abonnement->refresh();
    }

    /**
     * Position GPS du domicile de l'élève, renseignée après la souscription
     * (null / null l'efface).
     */
    public function positionner(int $id, ?float $latitude, ?float $longitude): AbonnementBus
    {
        if (($latitude === null) !== ($longitude === null)) {
            throw new RuntimeException('Renseignez la latitude et la longitude, ou aucune des deux.');
        }

        $abonnement = $this->abonnements->findOrFail($id);
        $this->abonnements->update($abonnement->id, ['latitude' => $latitude, 'longitude' => $longitude]);

        return $abonnement->refresh();
    }

    /**
     * Les abonnements actifs suivent la ligne de leur zone, et le véhicule de
     * cette ligne : à rappeler quand une zone change de ligne ou qu'une ligne
     * change de véhicule.
     */
    public function realigner(?int $zoneId = null, ?int $ligneId = null): int
    {
        return DB::affectingStatement(
            'UPDATE abonnements_bus a
               JOIN zones z ON z.id = a.zone_id
               LEFT JOIN lignes l ON l.id = z.ligne_id
                SET a.ligne_id = z.ligne_id, a.voiture_id = l.voiture_id, a.updated_at = NOW()
              WHERE a.etat = 1 AND a.statut = 1'
            . ($zoneId ? ' AND z.id = ' . (int) $zoneId : '')
            . ($ligneId ? ' AND z.ligne_id = ' . (int) $ligneId : '')
            . ' AND (NOT (a.ligne_id <=> z.ligne_id) OR NOT (a.voiture_id <=> l.voiture_id))'
        );
    }

    /**
     * Souscriptions transport de la base historique de l'année qui ne sont pas
     * encore des abonnements bus (aperçu du bouton de synchronisation).
     */
    public function souscriptionsAConvertir(int $anneeId): int
    {
        return $this->reprisesAConvertir($anneeId)->count();
    }

    /**
     * Transforme les souscriptions transport reprises de la base historique en
     * abonnements bus (zone à préciser ensuite). Le montant annuel est gardé à
     * l'identique ; la mensualité en découle, sur les mois restant jusqu'à la
     * fin de l'année scolaire. La souscription reprise est remplacée par celle
     * de l'abonnement, et ses lignes de paiement y sont rattachées.
     *
     * Relançable : une inscription qui a déjà un abonnement bus (même
     * abandonné) n'est pas reprise. Rien n'est supprimé.
     *
     * @return array{convertis:int, ignores:int, paiements:int}
     */
    public function convertirSouscriptions(int $anneeId): array
    {
        $annee = Annee::findOrFail($anneeId);
        $fin = $annee->date_fin ? Carbon::parse($annee->date_fin) : null;
        $rentree = $annee->date_rentree ? Carbon::parse($annee->date_rentree) : null;

        return DB::transaction(function () use ($anneeId, $fin, $rentree) {
            $convertis = 0;
            $ignores = 0;

            foreach ($this->reprisesAConvertir($anneeId)->get() as $s) {
                $total = round((float) $s->montant_annuel_prevu * (1 - (float) ($s->taux_remise ?? 0) / 100), 2);
                if ($total <= 0) {
                    $ignores++;
                    continue;
                }
                $debut = Carbon::parse($s->date_souscription ?: ($rentree ?? now()));
                $depart = $rentree && $debut->lt($rentree) ? $rentree : $debut;
                $mois = $fin ? Annee::moisEntre($depart, $fin) : 9;

                DB::table('abonnements_bus')->insert([
                    'inscription_id'   => $s->inscription_id,
                    'sens'             => 'aller_retour',
                    'date_debut'       => $debut->toDateString(),
                    'date_fin'         => $fin?->toDateString(),
                    'montant_mensuel'  => round($total / $mois, 2),
                    'nombre_mois'      => $mois,
                    'montant_total_du' => $total,
                    'statut'           => 1,
                    'origine'          => self::ORIGINE_REPRISE,
                    'annee_id'         => $anneeId,
                    'etat'             => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
                $convertis++;
            }

            // La souscription de l'abonnement remplace la reprise historique
            app(SouscriptionChargementService::class)->synchroniserModules($anneeId);

            // Les paiements de transport suivent : souscription et abonnement réglés
            $paiements = DB::affectingStatement(
                'UPDATE details d
                   JOIN abonnements_bus a ON a.inscription_id = d.inscription_id AND a.etat = 1 AND a.origine = ?
                   JOIN souscriptions s ON s.origine = ? AND s.origine_id = a.id AND s.etat = 1
                    SET d.souscription_id = s.id, d.abonnement_bus_id = a.id
                  WHERE d.etat = 1 AND d.type_paiement = ? AND a.annee_id = ?
                    AND (d.abonnement_bus_id IS NULL OR NOT (d.souscription_id <=> s.id))',
                [self::ORIGINE_REPRISE, 'bus', TypeDetail::BUS->value, $anneeId]
            );

            return ['convertis' => $convertis, 'ignores' => $ignores, 'paiements' => $paiements];
        });
    }

    /** Souscriptions transport reprises, actives, sans abonnement bus pour l'inscription. */
    private function reprisesAConvertir(int $anneeId): \Illuminate\Database\Query\Builder
    {
        return DB::table('souscriptions as s')
            ->join('inscriptions as i', fn ($j) => $j->on('i.id', '=', 's.inscription_id')->where('i.etat', 1))
            ->where('s.annee_id', $anneeId)
            ->where('s.etat', 1)
            ->where('s.type_paiement', TypeDetail::BUS->value)
            ->where('s.origine', SouscriptionChargementService::REPRISE)
            ->whereNotExists(fn ($q) => $q->from('abonnements_bus as a')
                ->whereColumn('a.inscription_id', 's.inscription_id')->where('a.etat', 1))
            ->select('s.*');
    }

    /** Ligne et véhicule qui desservent la zone. */
    private function desserte(?int $zoneId): array
    {
        $zone = $zoneId ? Zone::with('ligne:id,voiture_id')->find($zoneId) : null;

        return [
            'ligne_id'   => $zone?->ligne_id,
            'voiture_id' => $zone?->ligne?->voiture_id,
        ];
    }
}
