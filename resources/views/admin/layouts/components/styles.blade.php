{{--
    Styles communs des composants (cartes, chiffres clés, pastilles, blocs de
    formulaire, recherche). Inclus par le gabarit avant le CSS propre à
    chaque écran : une vue peut toujours surcharger une règle.
--}}
<style>
    /* Chiffres clés (KPI) : carte blanche, filet or à gauche, valeur en chiffres tabulaires */
    .stat-card { background: var(--eim-surface); border: 1px solid var(--eim-border); border-left: 3px solid var(--eim-gold); border-radius: var(--eim-radius); }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card .stat-value { font-weight: 800; letter-spacing: -0.02em; color: var(--eim-ink); font-variant-numeric: tabular-nums; }
    .stat-card .stat-label { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--eim-ink-soft); }
    .stat-icon { width: 46px; height: 46px; border-radius: var(--eim-radius-sm); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
    .stat-icon.blue { background: rgba(37,99,235,.10); color: #2563eb; }
    .stat-icon.gold { background: rgba(212,169,77,.18); color: var(--school-gold-dark); }
    .stat-icon.green { background: rgba(31,122,77,.10); color: var(--eim-success); }
    .stat-icon.red { background: rgba(227,30,36,.10); color: var(--eim-red-hover); }
    .stat-info .stat-sub { font-size: .7rem; color: var(--school-muted); margin-top: 2px; }
    .badge-statut.actif { background: #e3f7ec; color: var(--eim-success); }
    .badge-statut.annule { background: #eceff2; color: var(--eim-ink-soft); }
    .badge-statut.attente { background: #fdf4e0; color: var(--school-gold-dark); }
    .badge-statut.encaisse { background: #e3f7ec; color: var(--eim-success); }
    .badge-statut.encours { background: #e0ecfc; color: #2563eb; }
    .badge-statut.impaye { background: #fdecea; color: var(--eim-danger); }
    .badge-statut.inactif { background: #eceff2; color: var(--eim-ink-soft); }
    .badge-statut.paye { background: #e3f7ec; color: var(--eim-success); }
    .badge-statut.perdu { background: #eceff2; color: var(--eim-ink-soft); }
    .badge-statut.rendu { background: #e3f7ec; color: var(--eim-success); }
    .badge-statut.retard { background: #fdecea; color: var(--eim-danger); font-weight: 700; }
    /* Le statut ne passe jamais par la seule couleur : une icône accompagne le libellé */
    .badge-statut:is(.actif, .encaisse, .paye, .rendu, .attente, .encours, .impaye, .retard, .annule, .perdu, .inactif)::before {
        font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free'; font-weight: 900; font-size: .9em;
    }
    .badge-statut:is(.actif, .encaisse, .paye, .rendu)::before { content: '\f00c'; }
    .badge-statut:is(.attente)::before { content: '\f017'; }
    .badge-statut:is(.encours)::before { content: '\f110'; }
    .badge-statut:is(.impaye, .retard)::before { content: '\f071'; }
    .badge-statut:is(.annule, .perdu, .inactif)::before { content: '\f05e'; }
    .badge-statut:has(.badge-dot)::before { content: none; }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color: var(--eim-red); box-shadow: 0 0 0 2px rgba(227,30,36,.25); }
    .form-section-title { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--eim-red-hover); margin-bottom: .75rem; }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--eim-ink-soft); font-size: .85rem; }
    .search-input { padding-left: 36px !important; }
</style>
