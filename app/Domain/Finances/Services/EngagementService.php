<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Repositories\SouscriptionRepository;
use App\Domain\Finances\Types\NatureCreance;
use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Domain\ServicesEleves\Types\StatutInscriptionCantine;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Engagements financiers d'une inscription.
 *
 * Une seule source de vérité pour le « dû » et le « payé » : l'écran
 * Paiements, le Recouvrement et la liste des élèves doivent afficher
 * exactement les mêmes montants.
 *
 * Le dû naît de l'engagement, jamais du paiement (cahier des charges,
 * « Souscription indépendante du paiement »). Un engagement vient, par ordre
 * de priorité pour une même nature de frais :
 *   1. d'une souscription (table souscriptions), réservée aux services :
 *      cantine, transport, activités, événements ;
 *   2. d'un abonnement de service (inscriptions_cantine, abonnements_bus),
 *      d'une inscription à une activité, d'une pré-inscription à un événement ;
 *   3. des montants portés par l'inscription elle-même (frais_scolarite,
 *      frais_inscription, frais_assurance, frais_cantine, frais_bus…) — c'est
 *      ainsi que la base historique enregistre le dû de la plupart des élèves.
 */
class EngagementService
{
    /** Natures suivies en recouvrement, dans l'ordre d'affichage. */
    public const NATURES = [
        NatureCreance::SCOLARITE,
        NatureCreance::CANTINE,
        NatureCreance::BUS,
        NatureCreance::ACTIVITE,
    ];

    /** Montants portés par l'inscription : colonne => nature de frais. */
    private const COLONNES_INSCRIPTION = [
        'frais_inscription' => TypeDetail::FRAIS_INSCRIPTION,
        'frais_scolarite'   => TypeDetail::FRAIS_SCOLAIRE,
        'frais_assurance'   => TypeDetail::FRAIS_ASSURANCE,
        'frais_examen'      => TypeDetail::FRAIS_EXAMEN,
        'frais_cantine'     => TypeDetail::CANTINE,
        'frais_bus'         => TypeDetail::BUS,
        'frais_livre'       => TypeDetail::LIVRE,
        'caution'           => TypeDetail::CAUTION,
    ];

    /** Ordre d'affichage des engagements. */
    private const ORDRE = [
        TypeDetail::FRAIS_INSCRIPTION, TypeDetail::FRAIS_SCOLAIRE, TypeDetail::FRAIS_ASSURANCE,
        TypeDetail::FRAIS_EXAMEN, TypeDetail::CANTINE, TypeDetail::BUS, TypeDetail::ACTIVITE,
        TypeDetail::LIVRE, TypeDetail::CAUTION, TypeDetail::EVENEMENT,
    ];

    /**
     * Codes type_paiement (TypeDetail) rattachés à une nature de créance.
     *
     * @return int[]
     */
    public function codesPour(string $nature): array
    {
        return array_values(array_map(
            fn (TypeDetail $type) => $type->value,
            array_filter(
                TypeDetail::cases(),
                fn (TypeDetail $type) => $type->natureRecouvrement()?->value === $nature
            )
        ));
    }

    public function montantDu(int $inscriptionId, string $nature): float
    {
        return round(array_sum(array_map(
            fn (array $engagement) => $engagement['nature'] === $nature ? $engagement['du'] : 0.0,
            $this->engagements($inscriptionId)
        )), 2);
    }

    /**
     * Dû de chaque nature suivie en recouvrement, en une seule lecture des
     * engagements (le recalcul nocturne traite toutes les inscriptions).
     *
     * @return array<string, float>
     */
    public function duParNature(int $inscriptionId): array
    {
        $dus = array_fill_keys(array_map(fn (NatureCreance $n) => $n->value, self::NATURES), 0.0);

        foreach ($this->engagements($inscriptionId) as $engagement) {
            if (isset($dus[$engagement['nature']])) {
                $dus[$engagement['nature']] = round($dus[$engagement['nature']] + $engagement['du'], 2);
            }
        }

        return $dus;
    }

    /** Encaissements effectifs : une ligne saisie mais non encaissée ne compte pas. */
    public function montantPaye(int $inscriptionId, string $nature): float
    {
        $codes = $this->codesPour($nature);
        if ($codes === []) {
            return 0.0;
        }

        return round((float) DB::table('details')
            ->where('inscription_id', $inscriptionId)
            ->where('etat', 1)
            ->where('statut_paiement', PaiementStatut::ENCAISSE->value)
            ->whereIn('type_paiement', $codes)
            ->sum('montant'), 2);
    }

    public function resteDu(int $inscriptionId, string $nature): float
    {
        return max(0.0, round($this->montantDu($inscriptionId, $nature) - $this->montantPaye($inscriptionId, $nature), 2));
    }

    /* =====================================================================
       Situation détaillée d'une inscription, frais par frais
       (écran de saisie d'un paiement)
       ===================================================================== */

    /**
     * Une ligne par engagement de l'élève, avec le dû, ce qui est encaissé,
     * ce qui est saisi mais pas encore encaissé, le reste, et le montant
     * encore saisissable (reste moins ce qui attend déjà l'encaissement).
     *
     * Les lignes de paiement de la base historique ne portent pas
     * l'engagement réglé : elles sont rattachées par nature de frais, puis
     * par frais, à défaut au premier engagement de cette nature non soldé.
     *
     * @return array<int, array{
     *     cle:string, nature:string, nature_libelle:string, type:int, libelle:string,
     *     souscription_id:?int, frais_ecole_id:?int, activite_id:?int, evenement_id:?int,
     *     du:float, paye:float, en_attente:float, reste:float, payable:float
     * }>
     */
    public function lignes(int $inscriptionId): array
    {
        $lignes = $this->engagements($inscriptionId);
        if ($lignes === []) {
            return [];
        }

        $details = DB::table('details')
            ->where('inscription_id', $inscriptionId)
            ->where('etat', 1)
            ->whereIn('statut_paiement', [PaiementStatut::EN_ATTENTE->value, PaiementStatut::ENCAISSE->value])
            ->orderBy('id')
            ->get(['montant', 'type_paiement', 'statut_paiement', 'souscription_id', 'frais_ecole_id', 'activite_id', 'evenement_id']);

        foreach ($details as $detail) {
            $index = $this->ligneConcernee($lignes, $detail);
            if ($index === null) {
                continue; // achat libre (produit, service…) : pas un engagement
            }

            $champ = (int) $detail->statut_paiement === PaiementStatut::ENCAISSE->value ? 'paye' : 'en_attente';
            $lignes[$index][$champ] = round($lignes[$index][$champ] + (float) $detail->montant, 2);
        }

        foreach ($lignes as &$ligne) {
            $ligne['reste']   = max(0.0, round($ligne['du'] - $ligne['paye'], 2));
            $ligne['payable'] = max(0.0, round($ligne['du'] - $ligne['paye'] - $ligne['en_attente'], 2));
        }
        unset($ligne);

        return $lignes;
    }

    /**
     * Refuse un paiement qui dépasserait ce qui reste à saisir sur un
     * engagement (reste dû moins ce qui attend déjà l'encaissement).
     *
     * @param array<string, float> $montantsParCle  clé d'engagement => montant saisi
     */
    public function verifierPlafonds(int $inscriptionId, array $montantsParCle): void
    {
        if ($montantsParCle === []) {
            return;
        }

        $lignes = collect($this->lignes($inscriptionId))->keyBy('cle');

        foreach ($montantsParCle as $cle => $montant) {
            $ligne = $lignes->get($cle);

            if (! $ligne) {
                throw new RuntimeException("L'engagement « {$cle} » n'existe pas (ou plus) pour cet élève.");
            }

            if ($montant > $ligne['payable'] + 0.005) {
                $max = number_format($ligne['payable'], 0, ',', ' ');
                throw new RuntimeException(
                    "Le montant saisi pour « {$ligne['libelle']} » dépasse ce qui reste à payer ({$max})."
                );
            }
        }
    }

    /* =====================================================================
       Construction des engagements
       ===================================================================== */

    /** Engagements d'une inscription, sans les paiements (paye et en_attente à zéro). */
    private function engagements(int $inscriptionId): array
    {
        $inscription = DB::table('inscriptions')->where('id', $inscriptionId)->first();
        if (! $inscription) {
            return [];
        }

        $lignes = [];
        $ajouter = function (string $cle, TypeDetail $type, string $libelle, float $du, array $refs = []) use (&$lignes) {
            $nature = $type->natureRecouvrement() ?? NatureCreance::AUTRE;

            $lignes[] = $refs + [
                'cle'             => $cle,
                'nature'          => $nature->value,
                // « Écolage » regroupe plusieurs frais : on affiche alors le frais lui-même
                'nature_libelle'  => in_array($nature, [NatureCreance::SCOLARITE, NatureCreance::AUTRE], true) ? $type->label() : $nature->label(),
                'type'            => $type->value,
                'libelle'         => $libelle,
                'souscription_id' => null,
                'frais_ecole_id'  => null,
                'activite_id'     => null,
                'evenement_id'    => null,
                'du'              => max(0.0, round($du, 2)),
                'paye'            => 0.0,
                'en_attente'      => 0.0,
            ];
        };

        // 1) Frais souscrits (remise déduite). Les souscriptions qui ne font que
        //    refléter un abonnement pris dans un module (cantine, bus, activité,
        //    événement) sont écartées : l'abonnement est compté plus bas.
        $souscriptions = DB::table('souscriptions as s')
            ->leftJoin('frais_ecoles as f', 'f.id', '=', 's.frais_ecole_id')
            ->where('s.inscription_id', $inscriptionId)
            ->where('s.etat', 1)
            ->whereIn('s.type_paiement', array_map(fn (TypeDetail $t) => $t->value, SouscriptionRepository::SERVICES))
            ->where(fn ($q) => $q->whereNull('s.origine')
                ->orWhereNotIn('s.origine', array_keys(SouscriptionChargementService::MODULES)))
            ->orderBy('s.id')
            ->get(['s.id', 's.type_paiement', 's.montant_annuel_prevu', 's.taux_remise', 's.frais_ecole_id',
                   's.origine', DB::raw('COALESCE(s.libelle, f.libelle) as libelle')]);

        // Cantine et bus repris de la base historique : l'abonnement du module, s'il existe, prime
        $abonnes = [
            TypeDetail::CANTINE->value => 'inscriptions_cantine',
            TypeDetail::BUS->value     => 'abonnements_bus',
        ];
        $souscriptions = $souscriptions->reject(fn ($sousc) => $sousc->origine === SouscriptionChargementService::REPRISE
            && isset($abonnes[(int) $sousc->type_paiement])
            && DB::table($abonnes[(int) $sousc->type_paiement])
                ->where('inscription_id', $inscriptionId)->where('etat', 1)
                ->where('statut', StatutInscriptionCantine::ACTIF->value)->exists());

        foreach ($souscriptions as $sousc) {
            $type = TypeDetail::tryFrom((int) $sousc->type_paiement);
            if (! $type) {
                continue;
            }
            $ajouter(
                "souscription:{$sousc->id}",
                $type,
                $sousc->libelle ?: $type->label(),
                (float) $sousc->montant_annuel_prevu * (1 - (float) ($sousc->taux_remise ?? 0) / 100),
                ['souscription_id' => (int) $sousc->id, 'frais_ecole_id' => $sousc->frais_ecole_id ? (int) $sousc->frais_ecole_id : null],
            );
        }

        // 2) Abonnements de service souscrits depuis leur module (un abandon éteint l'engagement)
        foreach (['inscriptions_cantine' => [TypeDetail::CANTINE, 'cantine', 'Abonnement cantine'],
                  'abonnements_bus'      => [TypeDetail::BUS, 'bus', 'Abonnement bus']] as $table => [$type, $prefixe, $libelle]) {
            $abonnements = DB::table($table)
                ->where('inscription_id', $inscriptionId)
                ->where('etat', 1)
                ->where('statut', StatutInscriptionCantine::ACTIF->value)
                ->get(['id', 'montant_total_du', 'montant_mensuel', 'nombre_mois']);

            foreach ($abonnements as $abo) {
                $ajouter(
                    "{$prefixe}:{$abo->id}",
                    $type,
                    $libelle,
                    (float) ($abo->montant_total_du ?? ((float) $abo->montant_mensuel * (int) ($abo->nombre_mois ?? 1))),
                );
            }
        }

        // 3) Activités extrascolaires (souscrites par élève et par année)
        $activites = DB::table('inscriptions_activites as ia')
            ->leftJoin('activites as a', 'a.id', '=', 'ia.activite_id')
            ->where('ia.eleve_id', $inscription->eleve_id)
            ->where('ia.annee_id', $inscription->annee_id)
            ->where('ia.etat', 1)
            ->where('ia.statut', '!=', StatutInscriptionActivite::ANNULEE->value)
            ->get(['ia.id', 'ia.activite_id', 'ia.montant_du', 'a.libelle']);

        foreach ($activites as $act) {
            $ajouter(
                "activite:{$act->id}",
                TypeDetail::ACTIVITE,
                'Activité : ' . ($act->libelle ?? '—'),
                (float) $act->montant_du,
                ['activite_id' => (int) $act->activite_id],
            );
        }

        // 4) Pré-inscriptions à un événement à capacité limitée
        $preInscriptions = DB::table('participation_evenements as pe')
            ->join('evenements as e', 'e.id', '=', 'pe.evenement_scolaire_id')
            ->where('pe.inscription_id', $inscriptionId)
            ->where('pe.etat', 1)
            ->where('pe.statut', StatutParticipation::PRE_INSCRIT->value)
            ->get(['pe.id', 'e.id as evenement_id', 'e.participation', 'e.nom']);

        foreach ($preInscriptions as $pre) {
            $ajouter(
                "evenement:{$pre->id}",
                TypeDetail::EVENEMENT,
                'Pré-inscription : ' . $pre->nom,
                (float) $pre->participation,
                ['evenement_id' => (int) $pre->evenement_id],
            );
        }

        // 5) Montants portés par l'inscription, pour les natures que rien d'autre ne couvre
        $typesCouverts = array_column($lignes, 'type');

        foreach (self::COLONNES_INSCRIPTION as $colonne => $type) {
            $montant = (float) ($inscription->{$colonne} ?? 0);
            if ($montant <= 0 || in_array($type->value, $typesCouverts, true)) {
                continue;
            }

            // La remise accordée à l'inscription porte sur la scolarité
            if ($type === TypeDetail::FRAIS_SCOLAIRE) {
                $montant = $montant * (1 - (float) ($inscription->taux_remise ?? 0) / 100)
                    - (float) ($inscription->remise_scolarite ?? 0);
            }

            $ajouter("inscription:{$type->value}", $type, $type->label(), $montant);
        }

        $rang = array_flip(array_map(fn (TypeDetail $t) => $t->value, self::ORDRE));
        usort($lignes, fn (array $a, array $b) => [$rang[$a['type']] ?? 99, $a['cle']] <=> [$rang[$b['type']] ?? 99, $b['cle']]);

        return $lignes;
    }

    /** Index de l'engagement auquel rattacher une ligne de paiement, ou null. */
    private function ligneConcernee(array $lignes, object $detail): ?int
    {
        if ($detail->evenement_id) {
            foreach ($lignes as $i => $ligne) {
                if ($ligne['evenement_id'] === (int) $detail->evenement_id) {
                    return $i;
                }
            }

            return null;
        }

        if ($detail->souscription_id) {
            foreach ($lignes as $i => $ligne) {
                if ($ligne['souscription_id'] === (int) $detail->souscription_id) {
                    return $i;
                }
            }
        }

        $candidates = array_filter($lignes, fn (array $ligne) => $ligne['type'] === (int) $detail->type_paiement);
        if ($candidates === []) {
            return null;
        }

        if ($detail->activite_id) {
            foreach ($candidates as $i => $ligne) {
                if ($ligne['activite_id'] === (int) $detail->activite_id) {
                    return $i;
                }
            }
        }

        if ($detail->frais_ecole_id) {
            foreach ($candidates as $i => $ligne) {
                if ($ligne['frais_ecole_id'] === (int) $detail->frais_ecole_id) {
                    return $i;
                }
            }
        }

        foreach ($candidates as $i => $ligne) {
            if ($ligne['du'] - $ligne['paye'] - $ligne['en_attente'] > 0.005) {
                return $i;
            }
        }

        return array_key_first($candidates);
    }
}
