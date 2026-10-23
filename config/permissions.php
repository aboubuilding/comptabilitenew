<?php

use App\Domain\Administration\Types\RoleUtilisateur as Role;

/*
|--------------------------------------------------------------------------
| Matrice profil → permissions
|--------------------------------------------------------------------------
| Transcription du cahier des charges : § 4 (« Profils concernés » de chaque
| sous-menu, avec leur niveau : gestion, consultation, validation...) et
| § 5 (description par profil). Le § 4, plus précis, l'emporte en cas de doute.
|
| Une permission s'écrit module.action. « module.* » accorde toutes les
| actions du module. Convention des actions :
|   voir    : consulter les écrans et exporter
|   gerer   : créer / modifier / archiver
|   autres  : action métier nommée (valider, encaisser, rapprocher...)
|
| L'Administrateur n'apparaît pas ici : il a tous les droits (§ 5.1,
| « accès de contrôle à l'ensemble des modules »), voir AuthServiceProvider.
| Le Parent non plus : le portail parent a sa propre authentification
| (table comptes), ce n'est pas un utilisateur interne.
|
| Lue par App\Support\Permissions.
*/

// § 5.5 — Secrétariat / Agent des inscriptions (un même profil, deux codes :
// 6 est le code historique « Secrétaire », 9 le profil ajouté depuis).
$secretariat = [
    'eleves.*',                 // 1.1 gestion
    'familles.*',               // 1.2 gestion
    'inscriptions.voir',        // 1.3 saisie
    'inscriptions.saisir',
    'communications.voir',      // 6.3
    'communications.envoyer',
];

return [

    'profils' => [

        // § 5.2 — Direction : validation et pilotage, consultation ailleurs.
        Role::Directeur->name => [
            'eleves.voir',              // 1.1 consultation
            'familles.voir',            // 1.2 consultation
            'inscriptions.voir',        // 1.3 validation
            'inscriptions.valider',
            'frais.voir',               // 2.1 consultation
            'souscriptions.voir',
            'souscriptions.remise',     // § 5.2 : arbitre les taux de remise
            'tableau-souscriptions.consulter', // tableau de bord des souscriptions : Direction et Administrateur
            'paiements.voir',           // 2.2 consultation (supervision financière, § 5.2)
            'tous-paiements.consulter', // onglet « Tous les paiements » : Direction et Administrateur (2026-10-14)
            'totaux-encaisses.consulter', // totaux encaissés (année, mois, semaine, jour) : Direction et Administrateur
            'caisses.voir',             // 2.4 validation des écarts
            'caisses.valider_ecart',
            'toutes-caisses.consulter', // onglet « Toutes les caisses » et tableau de bord des caisses : Direction et Administrateur (2026-10-14)
            'depenses.voir',            // 2.6 validation
            'depenses.valider',
            'decaissements.voir',       // suivi des décaissements
            'analytique.voir',          // 2.7 consultation
            'recouvrement.voir',        // 2.8 consultation / escalade
            'recouvrement.gerer',
            'previsions.*',             // 2.9
            'paie.voir',                // 5.2 consultation
            'communications.voir',      // 6.3
            'communications.envoyer',
        ],

        // § 5.3 — Comptable : pilotage financier. Enregistre les paiements,
        // n'encaisse pas (2.3 est réservé au Caissier).
        Role::Comptable->name => [
            'inscriptions.voir',        // 1.3 consultation
            'frais.*',                  // 2.1
            'souscriptions.*',
            'paiements.*',              // 2.2
            'cantine.inscrire',         // souscriptions faites depuis l'écran de paiement :
            'transport.abonner',        //   cantine, transport, activités et événements
            'activites.inscrire',
            'caisses.voir',             // 2.4 consultation
            'banques.*',                // 2.5
            'cheques.*',
            'depenses.voir',            // 2.6 saisie ; la validation revient à la Direction
            'depenses.creer',
            'decaissements.voir',       // suivi des décaissements des dépenses saisies
            'depenses.justifier',       // joindre les justificatifs manquants
            'depenses.regler',
            'analytique.voir',          // 2.7 : ventile, ne paramètre pas les centres
            'analytique.ventiler',
            'recouvrement.*',           // 2.8 (§ 3 : peut assurer le recouvrement)
            'previsions.*',             // 2.9
            'achats.voir',              // 3.2 suivi du paiement
            'paie.voir',                // 5.2 règlement
            'paie.payer',
            'communications.voir',      // 6.3 relances
            'communications.envoyer',
        ],

        // § 5.4 — Caissier : sa caisse et les encaissements.
        Role::Caissier->name => [
            'encaissements.*',          // 2.3
            'decaissements.*',          // décaissements des dépenses validées depuis sa caisse ouverte (2026-10-14)
            'depenses.justifier',       // joindre le justificatif d'une dépense décaissée
            'caisses.voir',             // 2.4 — tout sauf la validation de ses propres écarts
            'caisses.ouvrir',
            'caisses.cloturer',
            'caisses.mouvements',
        ],

        Role::Secretaire->name        => $secretariat,
        Role::AgentInscriptions->name => $secretariat,

        // § 5.6 — Contrôleur de gestion : paramètre l'analytique, contrôle les
        // imputations saisies sur les dépenses et les achats.
        Role::ControleurGestion->name => [
            'analytique.*',             // 2.7
            'previsions.voir',          // comparaison au budget
            'depenses.voir',
            'achats.voir',
        ],

        // § 5.7 — Chargé de recouvrement.
        Role::ChargeRecouvrement->name => [
            'recouvrement.*',           // 2.8
            'communications.voir',      // 6.3 relances par email
            'communications.envoyer',
        ],

        // § 5.8 — Responsable Achats & Stock.
        Role::ResponsableAchatsStock->name => [
            'fournisseurs.*',           // 3.1
            'achats.*',                 // 3.2
            'stock.*',                  // 3.3
        ],

        // § 5.9 — Responsable Boutique : vend, consulte le stock.
        Role::ResponsableBoutique->name => [
            'ventes.*',                 // 3.4
            'stock.voir',               // 3.3 consultation
        ],

        // § 5.10 — Responsable Transport scolaire.
        Role::ResponsableTransport->name => [
            'transport.*',              // 4.1
        ],

        // § 5.11 — Responsable Cantine : compose ses menus depuis le stock.
        Role::ResponsableCantine->name => [
            'cantine.*',                // 4.2
            'stock.voir',               // 3.3 consultation
        ],

        // § 5.12 — Bibliothécaire.
        Role::Bibliothecaire->name => [
            'bibliotheque.*',           // 4.3
        ],

        // § 5.13 — Responsable RH / Paie : prépare la paie ; le règlement
        // des bulletins revient au Comptable (5.2).
        Role::ResponsableRhPaie->name => [
            'employes.*',               // 5.1
            'paie.voir',                // 5.2
            'paie.gerer',
            'assurances.*',             // 5.3
        ],

        // § 5.14 — Responsable Activités & Événements.
        Role::ResponsableActivitesEvenements->name => [
            'activites.*',              // 4.4
        ],

        /*
         | Profils hérités de la base historique, absents du cahier des charges.
         | Choix prudent, à confirmer :
         |  - Administrateur adjoint : consultation de tous les modules, sans
         |    aucune action de gestion ni accès aux comptes utilisateurs ;
         |  - Enseignant : aucun module (tableau de bord seulement).
         */
        Role::AdministrateurAdjoint->name => ['*.voir'],
        Role::Enseignant->name            => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions réservées à l'Administrateur
    |--------------------------------------------------------------------------
    | § 4 : 1.4, 6.1, 6.2, 6.4 et les modèles de messages (6.3) n'ont que
    | l'Administrateur comme profil concerné. Aucun joker, pas même « *.voir »,
    | ne les accorde à un autre profil.
    */
    'reservees_administrateur' => [
        'structure.voir', 'structure.gerer',          // 1.4
        'utilisateurs.voir', 'utilisateurs.gerer',    // 6.1
        'referentiels.gerer',                         // 6.2
        'modeles.gerer',                              // 6.3 (modèles)
        'journal.voir',                               // 6.4
        'paiements-en-attente.annuler',               // 2.3 : annuler un paiement en attente depuis l'écran Encaissements
        'details-paiements.consulter',                // 2.2 : onglet « Détails des paiements » (demande de l'utilisateur, 2026-10-14)
    ],
];
