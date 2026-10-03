<?php

/*
|--------------------------------------------------------------------------
| Menu de navigation — les 6 menus du cahier des charges (§ 2 et § 4)
|--------------------------------------------------------------------------
| Chaque entrée : [libellé, icône, nom de route, permission requise].
| Une entrée n'est affichée que si l'utilisateur a la permission ; un groupe
| ou un menu sans aucune entrée visible disparaît (voir _header.blade.php).
| Les permissions sont celles de config/permissions.php.
*/

return [

    [
        'libelle' => 'Scolarité',
        'icone'   => 'fa-school',
        'groupes' => [
            'Dossiers élèves' => [
                ['Élèves', 'fa-user-graduate', 'scolarite.eleves.index', 'eleves.voir'],
                ['Parents & Familles', 'fa-user-friends', 'scolarite.espaces.index', 'familles.voir'],
            ],
            'Inscriptions' => [
                ['Inscriptions', 'fa-file-signature', 'scolarite.inscriptions.index', 'inscriptions.voir'],
            ],
            'Structure pédagogique & Années scolaires' => [
                ['Années scolaires', 'fa-calendar-alt', 'scolarite.annees.index', 'structure.voir'],
                ['Cycles', 'fa-layer-group', 'scolarite.cycles.index', 'structure.voir'],
                ['Niveaux', 'fa-flag', 'scolarite.niveaux.index', 'structure.voir'],
                ['Classes', 'fa-chalkboard', 'scolarite.classes.index', 'structure.voir'],
            ],
        ],
    ],

    [
        'libelle' => 'Finances & Comptabilité',
        'icone'   => 'fa-wallet',
        'groupes' => [
            'Frais & Échéanciers' => [
                ['Frais scolaires', 'fa-tags', 'finances.frais-ecoles.index', 'frais.voir'],
                ['Plans d\'échéancier', 'fa-list-ol', 'finances.plan-echeanciers.index', 'frais.voir'],
                ['Tranches', 'fa-layer-group', 'finances.tranches.index', 'frais.voir'],
                ['Souscriptions', 'fa-handshake', 'finances.souscriptions.index', 'souscriptions.voir'],
            ],
            'Paiements & Caisses' => [
                ['Paiements', 'fa-receipt', 'finances.paiements.index', 'paiements.voir'],
                ['Encaissements', 'fa-cash-register', 'finances.encaissements.index', 'encaissements.voir'],
                ['Caisses', 'fa-clipboard-list', 'finances.caisses.index', 'caisses.voir'],
                ['Écarts de caisse à valider', 'fa-balance-scale', 'finances.caisses.ecarts.index', 'caisses.valider_ecart'],
                ['Banques', 'fa-university', 'finances.banques.index', 'banques.voir'],
                ['Chèques', 'fa-money-check', 'finances.cheques.index', 'cheques.voir'],
            ],
            'Pilotage financier' => [
                ['Dépenses', 'fa-file-invoice-dollar', 'finances.depenses.index', 'depenses.voir'],
                ['Prévisions budgétaires', 'fa-chart-line', 'finances.previsions.index', 'previsions.voir'],
                ['Recouvrement', 'fa-hand-holding-usd', 'finances.recouvrement.index', 'recouvrement.voir'],
            ],
            'Comptabilité analytique' => [
                ['Tableau de bord analytique', 'fa-chart-pie', 'finances.analytique.rapports.dashboard', 'analytique.voir'],
                ['Rapports par centre', 'fa-file-alt', 'finances.analytique.rapports.index', 'analytique.voir'],
                ['Centres analytiques', 'fa-sitemap', 'finances.analytique.centres.index', 'analytique.voir'],
                ['Règles de ventilation', 'fa-random', 'finances.regles.index', 'analytique.gerer'],
            ],
        ],
    ],

    [
        'libelle' => 'Logistique & Achats',
        'icone'   => 'fa-boxes',
        'groupes' => [
            'Approvisionnement' => [
                ['Fournisseurs', 'fa-truck', 'logistique.fournisseurs.index', 'fournisseurs.voir'],
                ['Achats', 'fa-shopping-cart', 'logistique.achats.index', 'achats.voir'],
            ],
            'Produits & Stock' => [
                ['Tableau de bord du stock', 'fa-chart-bar', 'logistique.stock.dashboard', 'stock.voir'],
                ['Produits', 'fa-box', 'logistique.produits.index', 'stock.voir'],
                ['Magasins', 'fa-warehouse', 'logistique.magasins.index', 'stock.voir'],
                ['Bons d\'entrée / sortie', 'fa-file-import', 'logistique.bons.index', 'stock.voir'],
                ['Mouvements de stock', 'fa-exchange-alt', 'logistique.mouvements-stock.index', 'stock.voir'],
            ],
            'Boutique / Point de vente' => [
                ['Point de vente', 'fa-store', 'logistique.pos.index', 'ventes.creer'],
                ['Ventes', 'fa-receipt', 'logistique.ventes.index', 'ventes.voir'],
                ['Clients', 'fa-address-book', 'logistique.clients.index', 'ventes.voir'],
                ['Reversements', 'fa-hand-holding-usd', 'logistique.ventes.reversement.index', 'ventes.creer'],
                ['Tableau de bord boutique', 'fa-chart-line', 'logistique.dashboard', 'ventes.voir'],
            ],
        ],
    ],

    [
        'libelle' => 'Services aux Élèves',
        'icone'   => 'fa-concierge-bell',
        'groupes' => [
            'Transport scolaire' => [
                ['Tableau de bord transport', 'fa-chart-bar', 'services-eleves.transport.dashboard', 'transport.voir'],
                ['Abonnements bus', 'fa-ticket-alt', 'services-eleves.transport.abonnements.index', 'transport.voir'],
                ['Véhicules', 'fa-bus-alt', 'services-eleves.transport.voitures.index', 'transport.voir'],
                ['Chauffeurs', 'fa-id-card', 'services-eleves.transport.chauffeurs.index', 'transport.voir'],
                ['Zones', 'fa-map-marked-alt', 'services-eleves.transport.zones.index', 'transport.voir'],
                ['Lignes', 'fa-route', 'services-eleves.transport.lignes.index', 'transport.voir'],
                ['Entretiens', 'fa-tools', 'services-eleves.transport.entretiens.index', 'transport.voir'],
                ['Carburant', 'fa-gas-pump', 'services-eleves.transport.carburants.index', 'transport.voir'],
                ['Assurances véhicules', 'fa-shield-alt', 'services-eleves.transport.assurances.index', 'transport.voir'],
            ],
            'Cantine scolaire' => [
                ['Tableau de bord cantine', 'fa-chart-bar', 'services-eleves.cantine.dashboard', 'cantine.voir'],
                ['Inscriptions cantine', 'fa-utensils', 'services-eleves.cantine.inscriptions.index', 'cantine.voir'],
                ['Menus', 'fa-book-open', 'services-eleves.cantine.menus.index', 'cantine.voir'],
                ['Préparations de repas', 'fa-concierge-bell', 'services-eleves.cantine.preparations.index', 'cantine.voir'],
            ],
            'Bibliothèque' => [
                ['Tableau de bord bibliothèque', 'fa-chart-bar', 'services-eleves.bibliotheque.dashboard', 'bibliotheque.voir'],
                ['Livres', 'fa-book', 'services-eleves.bibliotheque.livres.index', 'bibliotheque.voir'],
                ['Prêts', 'fa-exchange-alt', 'services-eleves.bibliotheque.prets.index', 'bibliotheque.voir'],
                ['Auteurs, éditeurs, catégories', 'fa-sliders-h', 'services-eleves.bibliotheque.referentiels.index', 'bibliotheque.voir'],
            ],
            'Activités & Événements' => [
                ['Tableau de bord activités', 'fa-chart-bar', 'services-eleves.activites-evenements.dashboard', 'activites.voir'],
                ['Activités', 'fa-futbol', 'services-eleves.activites-evenements.activites.index', 'activites.voir'],
                ['Inscriptions aux activités', 'fa-user-check', 'services-eleves.activites-evenements.inscriptions.index', 'activites.voir'],
                ['Événements', 'fa-calendar-check', 'services-eleves.activites-evenements.evenements.index', 'activites.voir'],
            ],
        ],
    ],

    [
        'libelle' => 'Ressources Humaines',
        'icone'   => 'fa-users',
        'groupes' => [
            'Employés' => [
                ['Employés', 'fa-id-badge', 'rh.employes.index', 'employes.voir'],
                ['Professions', 'fa-briefcase', 'rh.professions.index', 'employes.voir'],
            ],
            'Paie & Rémunérations' => [
                ['Tableau de bord paie', 'fa-chart-bar', 'rh.paies.dashboard', 'paie.voir'],
                ['Bulletins de paie', 'fa-money-check-alt', 'rh.paies.index', 'paie.voir'],
                ['Périodes de paie', 'fa-calendar-alt', 'rh.periodes.index', 'paie.voir'],
                ['Rémunérations', 'fa-coins', 'rh.remunerations.index', 'paie.voir'],
            ],
            'Assurances du personnel' => [
                ['Assurés', 'fa-user-shield', 'rh.assures.index', 'assurances.voir'],
                ['Polices d\'assurance', 'fa-shield-alt', 'rh.asurances.index', 'assurances.voir'],
            ],
        ],
    ],

    [
        'libelle' => 'Administration',
        'icone'   => 'fa-cog',
        'groupes' => [
            'Utilisateurs & Profils' => [
                ['Utilisateurs', 'fa-users-cog', 'administration.users.index', 'utilisateurs.voir'],
            ],
            'Référentiels & Paramètres généraux' => [
                ['Nationalités', 'fa-flag', 'administration.nationalites.index', 'referentiels.gerer'],
            ],
            'Communications' => [
                ['Messages', 'fa-envelope', 'administration.messages.index', 'communications.voir'],
                ['Historique des envois', 'fa-history', 'administration.messages.historique', 'communications.voir'],
                ['Modèles de messages', 'fa-file-alt', 'administration.modeles.index', 'modeles.gerer'],
            ],
            'Journal & Traçabilité' => [
                ['Journal des actions', 'fa-clipboard-list', 'administration.journal.index', 'journal.voir'],
            ],
        ],
    ],
];
