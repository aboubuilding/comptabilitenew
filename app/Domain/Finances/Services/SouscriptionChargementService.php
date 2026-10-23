<?php

namespace App\Domain\Finances\Services;

use App\Domain\Finances\Types\PaiementStatut;
use App\Domain\Finances\Types\TypeDetail;
use App\Domain\ServicesEleves\Types\StatutInscriptionActivite;
use App\Domain\ServicesEleves\Types\StatutInscriptionCantine;
use App\Domain\ServicesEleves\Types\StatutParticipation;
use Illuminate\Support\Facades\DB;

/**
 * Alimente la table des souscriptions (cantine, transport, activités
 * extrascolaires, événements).
 *
 * Deux sources :
 *  - la base historique (`recharger`) : la cantine et le transport sont les
 *    montants portés par l'inscription (frais_cantine, frais_bus) ; une
 *    activité ou un événement est souscrit dès qu'un paiement existe sur le
 *    frais correspondant. La date de souscription est celle du premier
 *    paiement de cette nature ;
 *  - les modules de services (`synchroniserModules`) : abonnements cantine et
 *    bus, inscriptions aux activités, participations aux événements.
 */
class SouscriptionChargementService
{
    public const REPRISE = 'reprise';
    public const SAISIE  = 'saisie';

    /** Origine => table du module qui porte la souscription. */
    public const MODULES = [
        'cantine'   => 'inscriptions_cantine',
        'bus'       => 'abonnements_bus',
        'activite'  => 'inscriptions_activites',
        'evenement' => 'participation_evenements',
    ];

    /**
     * Vide la table (les lignes d'origine sont archivées, les souscriptions
     * saisies à l'écran sont gardées) puis la recharge.
     *
     * @return array<string, int>  nombre de lignes par étape
     */
    public function recharger(bool $simuler = false): array
    {
        $bilan = [];

        DB::beginTransaction();
        try {
            $bilan['archivees'] = $this->archiver();
            $bilan['supprimees'] = DB::table('souscriptions')
                ->where(fn ($q) => $q->whereNull('origine')->orWhere('origine', '!=', self::SAISIE))
                ->delete();

            $bilan['cantine']    = $this->chargerDepuisInscriptions('frais_cantine', TypeDetail::CANTINE, 'Cantine', 'inscriptions_cantine');
            $bilan['transport']  = $this->chargerDepuisInscriptions('frais_bus', TypeDetail::BUS, 'Transport', 'abonnements_bus');
            $bilan['activites']  = $this->chargerDepuisPaiements(TypeDetail::ACTIVITE);
            $bilan['evenements'] = $this->chargerDepuisPaiements(TypeDetail::EVENEMENT);
            $bilan['modules']    = $this->synchroniserModules();
            $bilan['paiements_rattaches'] = $this->rattacherPaiements();

            $simuler ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $bilan;
    }

    /**
     * Synchronise les souscriptions d'UNE année scolaire (bouton « Synchroniser
     * les souscriptions ») : crée celles qui manquent, met à jour le montant de
     * celles reprises de l'inscription, ne supprime rien. Relançable sans doublon.
     *
     * @return array{creees:int, mises_a_jour:int, ignorees:int, erreurs:array<int, string>, services:array<string, int>}
     */
    public function synchroniser(int $anneeId): array
    {
        $types = [TypeDetail::CANTINE->value, TypeDetail::BUS->value, TypeDetail::ACTIVITE->value, TypeDetail::EVENEMENT->value];
        $existantes = fn () => DB::table('souscriptions')->where('annee_id', $anneeId)->where('etat', 1)
            ->whereIn('type_paiement', $types)->count();

        return DB::transaction(function () use ($anneeId, $existantes) {
            $avant = $existantes();

            $services = [
                'Cantine'    => $this->chargerDepuisInscriptions('frais_cantine', TypeDetail::CANTINE, 'Cantine', 'inscriptions_cantine', $anneeId),
                'Transport'  => $this->chargerDepuisInscriptions('frais_bus', TypeDetail::BUS, 'Transport', 'abonnements_bus', $anneeId),
                'Activités'  => $this->chargerDepuisPaiements(TypeDetail::ACTIVITE, $anneeId),
                'Événements' => $this->chargerDepuisPaiements(TypeDetail::EVENEMENT, $anneeId),
            ];
            $this->synchroniserModules($anneeId);
            $creees = max(0, $existantes() - $avant);

            // Montant de l'inscription modifié depuis la reprise
            $misesAJour = 0;
            foreach (['frais_cantine' => TypeDetail::CANTINE, 'frais_bus' => TypeDetail::BUS] as $colonne => $type) {
                $misesAJour += DB::affectingStatement(
                    "UPDATE souscriptions s JOIN inscriptions i ON i.id = s.inscription_id
                        SET s.montant_annuel_prevu = i.{$colonne}, s.updated_at = NOW()
                      WHERE s.annee_id = ? AND s.etat = 1 AND s.origine = ? AND s.type_paiement = ?
                        AND i.{$colonne} > 0 AND s.montant_annuel_prevu <> i.{$colonne}",
                    [$anneeId, self::REPRISE, $type->value]
                );
            }
            $this->rattacherPaiements($anneeId);

            return [
                'creees'       => $creees,
                'mises_a_jour' => $misesAJour,
                'ignorees'     => max(0, $avant - $misesAJour),
                'erreurs'      => $this->anomalies($anneeId),
                'services'     => $services,
            ];
        });
    }

    /**
     * Prestations payées qu'aucune souscription ne peut porter de façon sûre :
     * elles sont signalées, jamais créées d'office.
     *
     * @return array<int, string>
     */
    private function anomalies(int $anneeId): array
    {
        $statuts = [PaiementStatut::EN_ATTENTE->value, PaiementStatut::ENCAISSE->value];
        $messages = [];

        foreach (['frais_cantine' => [TypeDetail::CANTINE, 'de cantine', 'inscriptions_cantine'],
                  'frais_bus'     => [TypeDetail::BUS, 'de transport', 'abonnements_bus']] as $colonne => [$type, $nom, $module]) {
            $nb = DB::table('details as d')
                ->join('inscriptions as i', 'i.id', '=', 'd.inscription_id')
                ->where('i.annee_id', $anneeId)->where('i.etat', 1)
                ->where('d.etat', 1)->where('d.type_paiement', $type->value)->whereIn('d.statut_paiement', $statuts)
                ->where(fn ($q) => $q->whereNull("i.{$colonne}")->orWhere("i.{$colonne}", '<=', 0))
                ->whereNotExists(fn ($q) => $q->from("{$module} as m")->whereColumn('m.inscription_id', 'i.id')->where('m.etat', 1))
                ->distinct()->count('i.id');
            if ($nb > 0) {
                $messages[] = "{$nb} élève(s) ont un paiement {$nom} sans montant {$nom} sur leur inscription : le montant attendu est inconnu.";
            }
        }

        foreach ([TypeDetail::ACTIVITE->value => "d'activité", TypeDetail::EVENEMENT->value => "d'événement"] as $type => $nom) {
            $nb = DB::table('details as d')
                ->join('inscriptions as i', 'i.id', '=', 'd.inscription_id')
                ->leftJoin('frais_ecoles as f', fn ($j) => $j->on('f.id', '=', 'd.frais_ecole_id')->where('f.type_paiement', $type))
                ->where('i.annee_id', $anneeId)->where('i.etat', 1)
                ->where('d.etat', 1)->where('d.type_paiement', $type)->whereIn('d.statut_paiement', $statuts)
                ->whereNull('f.id')->whereNull('d.activite_id')->whereNull('d.evenement_id')
                ->count();
            if ($nb > 0) {
                $messages[] = "{$nb} ligne(s) de paiement {$nom} ne sont rattachées à aucun frais : impossible de savoir quelle prestation est souscrite.";
            }
        }

        return $messages;
    }

    /** Copie dans l'archive les lignes d'avant la réorganisation (sans origine). */
    private function archiver(): int
    {
        return DB::affectingStatement(
            'INSERT INTO souscriptions_archive
                (id, date_souscription, montant_annuel_prevu, taux_remise, type_paiement, frais_ecole_id, niveau_id,
                 annee_id, inscription_id, utilisateur_id, etat, created_at, updated_at, archive_le)
             SELECT s.id, s.date_souscription, s.montant_annuel_prevu, s.taux_remise, s.type_paiement, s.frais_ecole_id, s.niveau_id,
                    s.annee_id, s.inscription_id, s.utilisateur_id, s.etat, s.created_at, s.updated_at, NOW()
               FROM souscriptions s
              WHERE s.origine IS NULL
                AND NOT EXISTS (SELECT 1 FROM souscriptions_archive a WHERE a.id = s.id)'
        );
    }

    /**
     * Cantine et transport : une souscription par inscription dont le montant
     * est renseigné. L'abonnement pris dans le module, s'il existe, prime.
     */
    private function chargerDepuisInscriptions(string $colonne, TypeDetail $type, string $libelle, string $tableModule, ?int $anneeId = null): int
    {
        $annee = $anneeId ? ' AND i.annee_id = ' . (int) $anneeId : '';

        return DB::affectingStatement(
            "INSERT INTO souscriptions
                (date_souscription, montant_annuel_prevu, taux_remise, type_paiement, origine, libelle,
                 niveau_id, annee_id, inscription_id, etat, created_at, updated_at)
             SELECT COALESCE(p.premier, i.date_inscription, DATE(i.created_at)), i.{$colonne}, 0, ?, ?, ?,
                    i.niveau_id, i.annee_id, i.id, 1, NOW(), NOW()
               FROM inscriptions i
               LEFT JOIN ({$this->premiersPaiements()}) p ON p.inscription_id = i.id AND p.type_paiement = ?
              WHERE i.etat = 1{$annee}
                AND i.{$colonne} > 0
                AND NOT EXISTS (SELECT 1 FROM {$tableModule} m
                                 WHERE m.inscription_id = i.id AND m.etat = 1 AND m.statut = ?)
                AND NOT EXISTS (SELECT 1 FROM souscriptions s
                                 WHERE s.inscription_id = i.id AND s.type_paiement = ? AND s.etat = 1)",
            [$type->value, self::REPRISE, $libelle, $type->value, StatutInscriptionCantine::ACTIF->value, $type->value]
        );
    }

    /**
     * Activités et événements : une souscription par élève et par frais réglé
     * (même partiellement). Le montant attendu est celui du frais.
     */
    private function chargerDepuisPaiements(TypeDetail $type, ?int $anneeId = null): int
    {
        $annee = $anneeId ? ' AND i.annee_id = ' . (int) $anneeId : '';

        return DB::affectingStatement(
            "INSERT INTO souscriptions
                (date_souscription, montant_annuel_prevu, taux_remise, type_paiement, origine, libelle,
                 frais_ecole_id, niveau_id, annee_id, inscription_id, etat, created_at, updated_at)
             SELECT x.premier, f.montant, 0, ?, ?, f.libelle, f.id, i.niveau_id, i.annee_id, i.id, 1, NOW(), NOW()
               FROM (SELECT d.inscription_id, d.frais_ecole_id, MIN(COALESCE(d.date_paiement, DATE(d.created_at))) AS premier
                       FROM details d
                      WHERE d.etat = 1 AND d.type_paiement = ? AND d.frais_ecole_id IS NOT NULL
                        AND d.statut_paiement IN (?, ?)
                      GROUP BY d.inscription_id, d.frais_ecole_id) x
               JOIN frais_ecoles f ON f.id = x.frais_ecole_id AND f.type_paiement = ?
               JOIN inscriptions i ON i.id = x.inscription_id AND i.etat = 1{$annee}
              WHERE NOT EXISTS (SELECT 1 FROM souscriptions s
                                 WHERE s.inscription_id = i.id AND s.frais_ecole_id = f.id AND s.etat = 1)",
            [$type->value, self::REPRISE, $type->value, PaiementStatut::EN_ATTENTE->value, PaiementStatut::ENCAISSE->value, $type->value]
        );
    }

    /** Premier paiement (saisi ou encaissé) de chaque nature, par inscription. */
    private function premiersPaiements(): string
    {
        return 'SELECT d.inscription_id, d.type_paiement, MIN(COALESCE(d.date_paiement, DATE(d.created_at))) AS premier
                  FROM details d
                 WHERE d.etat = 1 AND d.statut_paiement IN (' . PaiementStatut::EN_ATTENTE->value . ', ' . PaiementStatut::ENCAISSE->value . ')
                 GROUP BY d.inscription_id, d.type_paiement';
    }

    /**
     * Reflète dans les souscriptions ce que portent les modules de services :
     * crée les lignes manquantes, met à jour les montants, désactive celles
     * dont l'abonnement est abandonné, annulé ou supprimé. Sans effet si rien
     * n'a changé ; appelée à l'ouverture de l'écran Souscriptions.
     */
    public function synchroniserModules(?int $anneeId = null): int
    {
        $anneeX = $anneeId ? ' AND x.annee_id = ' . (int) $anneeId : '';
        $anneeS = $anneeId ? ' AND s.annee_id = ' . (int) $anneeId : '';
        $anneeR = $anneeId ? ' AND r.annee_id = ' . (int) $anneeId : '';

        $actif = StatutInscriptionCantine::ACTIF->value;

        $sources = [
            // origine => [type, SELECT origine_id, inscription_id, annee_id, niveau_id, date_souscription, montant, libelle, activite_id, evenement_id, actif]
            'cantine' => [TypeDetail::CANTINE,
                "SELECT m.id AS origine_id, m.inscription_id, COALESCE(m.annee_id, i.annee_id) AS annee_id, i.niveau_id,
                        m.date_debut AS date_souscription,
                        COALESCE(m.montant_total_du, m.montant_mensuel * COALESCE(m.nombre_mois, 1)) AS montant,
                        'Cantine' AS libelle, NULL AS activite_id, NULL AS evenement_id,
                        (m.etat = 1 AND m.statut = {$actif}) AS actif
                   FROM inscriptions_cantine m JOIN inscriptions i ON i.id = m.inscription_id"],
            'bus' => [TypeDetail::BUS,
                "SELECT m.id AS origine_id, m.inscription_id, COALESCE(m.annee_id, i.annee_id) AS annee_id, i.niveau_id,
                        m.date_debut AS date_souscription,
                        COALESCE(m.montant_total_du, m.montant_mensuel * COALESCE(m.nombre_mois, 1)) AS montant,
                        'Transport' AS libelle, NULL AS activite_id, NULL AS evenement_id,
                        (m.etat = 1 AND m.statut = {$actif}) AS actif
                   FROM abonnements_bus m JOIN inscriptions i ON i.id = m.inscription_id"],
            'activite' => [TypeDetail::ACTIVITE,
                'SELECT m.id AS origine_id, i.id AS inscription_id, m.annee_id, i.niveau_id,
                        COALESCE(m.date_inscription, DATE(m.created_at)) AS date_souscription,
                        m.montant_du AS montant, a.libelle, m.activite_id, NULL AS evenement_id,
                        (m.etat = 1 AND m.statut <> ' . StatutInscriptionActivite::ANNULEE->value . ') AS actif
                   FROM inscriptions_activites m
                   JOIN activites a ON a.id = m.activite_id
                   JOIN inscriptions i ON i.eleve_id = m.eleve_id AND i.annee_id = m.annee_id AND i.etat = 1'],
            'evenement' => [TypeDetail::EVENEMENT,
                "SELECT m.id AS origine_id, m.inscription_id, COALESCE(m.annee_id, i.annee_id) AS annee_id, i.niveau_id,
                        m.date_inscription AS date_souscription,
                        e.participation AS montant, e.nom AS libelle, NULL AS activite_id, e.id AS evenement_id,
                        (m.etat = 1 AND m.statut <> '" . StatutParticipation::ANNULE->value . "') AS actif
                   FROM participation_evenements m
                   JOIN evenements e ON e.id = m.evenement_scolaire_id
                   JOIN inscriptions i ON i.id = m.inscription_id"],
        ];

        $touchees = 0;

        foreach ($sources as $origine => [$type, $select]) {
            // Lignes manquantes
            $touchees += DB::affectingStatement(
                "INSERT INTO souscriptions
                    (date_souscription, montant_annuel_prevu, taux_remise, type_paiement, origine, origine_id, libelle,
                     activite_id, evenement_id, niveau_id, annee_id, inscription_id, etat, created_at, updated_at)
                 SELECT x.date_souscription, x.montant, 0, ?, ?, x.origine_id, x.libelle,
                        x.activite_id, x.evenement_id, x.niveau_id, x.annee_id, x.inscription_id, 1, NOW(), NOW()
                   FROM ({$select}) AS x
                  WHERE x.actif = 1{$anneeX}
                    AND NOT EXISTS (SELECT 1 FROM souscriptions s WHERE s.origine = ? AND s.origine_id = x.origine_id)",
                [$type->value, $origine, $origine]
            );

            // Montant, libellé et état à jour
            $touchees += DB::affectingStatement(
                "UPDATE souscriptions s
                   JOIN ({$select}) AS x ON x.origine_id = s.origine_id
                    SET s.montant_annuel_prevu = x.montant, s.libelle = x.libelle,
                        s.etat = IF(x.actif = 1, 1, 2), s.updated_at = NOW()
                  WHERE s.origine = ?{$anneeS}
                    AND (s.montant_annuel_prevu <> x.montant OR s.libelle <> x.libelle OR s.etat <> IF(x.actif = 1, 1, 2))",
                [$origine]
            );

            // Abonnement supprimé du module
            $touchees += DB::affectingStatement(
                'UPDATE souscriptions s
                    SET s.etat = 2, s.updated_at = NOW()
                  WHERE s.origine = ? AND s.etat = 1' . $anneeS . '
                    AND NOT EXISTS (SELECT 1 FROM ' . self::MODULES[$origine] . ' m WHERE m.id = s.origine_id)',
                [$origine]
            );
        }

        // Un abonnement cantine ou bus pris dans le module remplace la reprise historique
        $touchees += DB::affectingStatement(
            'UPDATE souscriptions r
               JOIN souscriptions m ON m.inscription_id = r.inscription_id AND m.type_paiement = r.type_paiement
                                   AND m.origine IN (?, ?) AND m.etat = 1
                SET r.etat = 2, r.updated_at = NOW()
              WHERE r.origine = ? AND r.etat = 1' . $anneeR,
            ['cantine', 'bus', self::REPRISE]
        );

        return $touchees;
    }

    /**
     * Les lignes de paiement pointent vers la souscription réglée : celles
     * qui visaient une souscription supprimée sont détachées, celles de la
     * base historique sont rattachées par nature (et par frais).
     */
    private function rattacherPaiements(?int $anneeId = null): int
    {
        $annee = $anneeId ? ' AND s.annee_id = ' . (int) $anneeId : '';

        DB::statement(
            'UPDATE details d LEFT JOIN souscriptions s ON s.id = d.souscription_id
                SET d.souscription_id = NULL
              WHERE d.souscription_id IS NOT NULL AND s.id IS NULL'
        );

        return DB::affectingStatement(
            'UPDATE details d
               JOIN souscriptions s ON s.inscription_id = d.inscription_id AND s.type_paiement = d.type_paiement
                                   AND s.etat = 1 AND s.origine = ?
                                   AND (s.type_paiement IN (?, ?) OR s.frais_ecole_id = d.frais_ecole_id)
                SET d.souscription_id = s.id
              WHERE d.etat = 1 AND d.souscription_id IS NULL' . $annee,
            [self::REPRISE, TypeDetail::CANTINE->value, TypeDetail::BUS->value]
        );
    }
}
