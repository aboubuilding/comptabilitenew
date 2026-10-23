{{-- resources/views/layouts/app.blade.php --}}
    <!DOCTYPE html>
<html lang="fr" data-theme="light" data-layout="horizontal">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="École Internationale Mariam - Plateforme de gestion scolaire">

    <title>@yield('title', 'Tableau de bord') — EIM Compta</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/eim-compta-icon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo/apple-touch-icon.png') }}">

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('app/assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/animate.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/tabler-icons/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/@simonwep/pickr/themes/nano.min.css') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('app/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/mystyle.css') }}">

    @include('admin.layouts.partials._charte')
    <style>

        body, body.menu-horizontal {
            background: var(--eim-bg);
            font-family: var(--eim-ff);
            color: var(--eim-ink);
        }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: var(--eim-ff);
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--eim-ink);
        }
        .text-muted { color: var(--eim-ink-soft) !important; }
        .eim-label, .table thead th, .form-label-caps {
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }
        .table thead th {
            font-size: .7rem;
            color: var(--eim-ink-soft);
            background-color: var(--eim-cream) !important;
            border-bottom: 1px solid var(--eim-border);
        }
        .montant, .text-end, .stat-value, td.num, .tabular { font-variant-numeric: tabular-nums; }

        /* Anneau de focus unique, visible au clavier */
        a:focus-visible, button:focus-visible, .btn:focus-visible, [tabindex]:focus-visible,
        .nav-link:focus-visible, .page-link:focus-visible, .form-check-input:focus-visible {
            outline: 2px solid var(--eim-red) !important;
            outline-offset: 2px;
            box-shadow: none !important;
        }
        .form-control:focus, .form-select:focus, .select2-container--focus .select2-selection,
        .select2-container--open .select2-selection {
            border-color: var(--eim-red) !important;
            box-shadow: 0 0 0 2px rgba(227, 30, 36, .25) !important;
        }
        .form-check-input:checked { background-color: var(--eim-red); border-color: var(--eim-red); }
        a { color: var(--eim-red-hover); }
        a:hover { color: var(--eim-red-deep); }

        /* Cartes, modales, alertes, toasts */
        .card { border-color: var(--eim-border); border-radius: var(--eim-radius); }
        .modal-content { border-radius: var(--eim-radius-lg); border: 1px solid var(--eim-border); overflow: hidden; }
        .alert { border-radius: var(--eim-radius); }
        .alert-success { background: #E8F4EE; border-color: #BFDCCB; color: var(--eim-success); }
        .alert-danger { background: #FCEBEB; border-color: #F3C4C5; color: var(--eim-danger); }
        .alert-warning { background: var(--eim-cream); border-color: var(--eim-gold-soft); color: #6B4E10; }
        .badge { border-radius: var(--eim-radius-pill); font-weight: 600; }
        .table > :not(caption) > * > * { border-color: var(--eim-border); }
        .table-hover > tbody > tr:hover > * { --bs-table-accent-bg: var(--eim-hover); }
        .nav-tabs .nav-link.active { color: var(--eim-red-hover); border-bottom-color: var(--eim-red); }
        .swal2-popup { font-family: var(--eim-ff) !important; border-radius: var(--eim-radius-lg) !important; }
        .swal2-styled.swal2-confirm { background: var(--eim-red) !important; }

        /* ===== Barre de titre de page ===== */
        .page-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: #ffffff;
            border-bottom: 1px solid var(--school-paper-line);
            padding: 18px 28px;
        }
        .page-title-main {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: var(--eim-ff);
            font-weight: 800;
            letter-spacing: -0.02em;
            font-size: 1.35rem;
            color: var(--school-ink);
            margin: 0;
        }
        .page-title-main .title-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            flex-shrink: 0;
            background: var(--eim-red);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: var(--school-gold-soft);
            box-shadow: 0 4px 12px rgba(142,18,22, 0.25);
        }
        .breadcrumb-custom {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            list-style: none;
            margin: 6px 0 0 50px;
            padding: 0;
            font-size: 0.8rem;
            color: var(--school-muted);
        }
        .breadcrumb-custom li { display: flex; align-items: center; gap: 6px; }
        .breadcrumb-custom li + li::before {
            content: '/';
            color: var(--school-paper-line);
            margin-right: 6px;
        }
        /* Le bootstrap du thème porte un séparateur de fil d'Ariane corrompu
           (une URL à la place de « / ») : on le rétablit, et on neutralise
           la règle .breadcrumb-item + .breadcrumb-item de Bootstrap, plus
           spécifique que la nôtre, pour les vues qui utilisent cette classe. */
        :root { --bs-breadcrumb-divider: '/'; }
        .breadcrumb-custom .breadcrumb-item + .breadcrumb-item { padding-left: 0; }
        .breadcrumb-custom .breadcrumb-item + .breadcrumb-item::before {
            content: '/';
            float: none;
            padding-right: 0;
            color: var(--school-paper-line);
            margin-right: 6px;
        }
        .breadcrumb-custom a { color: var(--school-muted); text-decoration: none; }
        .breadcrumb-custom a:hover { color: var(--school-red); }
        .breadcrumb-custom li:last-child { color: var(--school-ink); font-weight: 600; }

        .page-header-right { display: flex; align-items: center; gap: 10px; }

        .content-area { padding: 24px 28px 48px; min-height: 60vh; }

        @media (max-width: 600px) {
            .page-header-bar { padding: 16px 18px; }
            .breadcrumb-custom { margin-left: 0; }
            .content-area { padding: 18px 16px 36px; }
        }

        /* ===== Notifications (flash de session + AJAX des 13 modules) =====
           #toast-container existait déjà dans le markup mais n'était
           alimenté par rien : voir le bloc de script après le chargement
           de jQuery, qui le peuple depuis les flashs de session et expose
           window.showToast()/showToastThenReload() pour les modales AJAX. */
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 12000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 360px;
        }
        .toast-item {
            font-family: var(--school-ff);
            font-size: 0.85rem;
            font-weight: 500;
            padding: 12px 18px;
            border-radius: var(--eim-radius);
            color: #fff;
            box-shadow: 0 10px 30px rgba(43, 30, 30, 0.2);
            animation: toast-in 0.25s ease-out both;
        }
        .toast-success {
            background: var(--eim-success);
            border-left: 4px solid var(--eim-gold);
        }
        .toast-error {
            background: var(--eim-danger);
            border-left: 4px solid var(--eim-red-deep);
        }
        .toast-success::before, .toast-error::before {
            font-family: 'Font Awesome 6 Free', 'Font Awesome 5 Free'; font-weight: 900; margin-right: 8px;
        }
        .toast-success::before { content: '\f058'; }
        .toast-error::before { content: '\f06a'; }
        @keyframes toast-in {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .toast-item { animation: none; }
        }

        /* ===== Socle des composants partagés =====
           Boutons, cartes, filtres et badges utilisés par tous les modules.
           Une vue peut les ajuster dans sa section css (chargée après), mais
           n'a plus à les redéclarer : sans ce socle, les écrans qui ne le
           faisaient pas affichaient des boutons bruts du navigateur. */
        .btn-nouveau, .btn-filtrer, .btn-annuler, .btn-enregistrer, .btn-outline-scolarite {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            font-family: var(--school-ff);
            font-size: .88rem;
            line-height: 1.4;
            text-decoration: none;
            cursor: pointer;
            transition: all .2s ease;
        }
        .btn-nouveau {
            background: var(--eim-red);
            color: #fff; border: none; border-radius: var(--eim-radius-sm); padding: .6rem 1.2rem; font-weight: 700;
            box-shadow: 0 4px 14px rgba(227,30,36,.22);
        }
        .btn-nouveau:hover { background: var(--eim-red-hover); box-shadow: 0 6px 18px rgba(227,30,36,.3); color: #fff; }
        .btn-nouveau.btn-sm { padding: .4rem .85rem; font-size: .8rem; }
        .btn-filtrer {
            background: var(--eim-red); color: #fff; border: none; border-radius: var(--eim-radius-sm);
            padding: .55rem 1rem; font-weight: 700;
        }
        .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }
        .btn-annuler {
            background: var(--eim-surface); border: 1px solid var(--eim-border); color: var(--eim-ink-soft);
            border-radius: var(--eim-radius-sm); font-weight: 600; padding: .5rem 1.1rem;
        }
        .btn-annuler:hover { background: var(--school-paper); color: var(--school-ink); }
        .btn-enregistrer {
            background: var(--eim-red); color: #fff;
            border: none; border-radius: var(--eim-radius-sm); font-weight: 700; padding: .5rem 1.3rem;
        }
        .btn-enregistrer:hover { color: #fff; background: var(--eim-red-hover); box-shadow: 0 4px 12px rgba(227,30,36,.3); }
        .btn-enregistrer:disabled, .btn-nouveau:disabled { opacity: .55; cursor: not-allowed; box-shadow: none; transform: none; }
        .btn-outline-scolarite {
            background: #fff; border: 1px solid var(--school-paper-line); color: var(--school-ink);
            border-radius: var(--eim-radius-sm); font-weight: 600; padding: .55rem 1rem;
        }
        .btn-outline-scolarite:hover { background: var(--school-paper); border-color: var(--school-red); color: var(--school-red); }

        .main-card {
            border: 1px solid var(--eim-border); border-radius: var(--eim-radius); background: var(--eim-surface);
            box-shadow: 0 10px 30px rgba(142,18,22,.05);
        }
        .filtre-input { border-radius: var(--eim-radius-sm); border: 1px solid var(--school-paper-line); padding: .55rem .9rem; }
        .filtre-input:focus { border-color: var(--eim-red); box-shadow: 0 0 0 2px rgba(227,30,36,.25); outline: none; }
        .badge-statut {
            display: inline-flex; align-items: center; gap: 6px; padding: .35em .85em;
            border-radius: 999px; font-size: .72rem; font-weight: 600;
        }
        .badge-statut[class*="bg-"]:not(.text-dark) { color: #fff; }
        .empty-state { padding: 3rem 1rem; text-align: center; color: var(--eim-ink-soft); }
        .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

        /* ===== En-têtes de modale colorés =====
           Titre lisible et croix de fermeture visible sur fond rouge. */
        .modal-header[class*="modal-header-"] .modal-title { color: #fff; }
        .modal-header .btn-close.btn-close-white {
            filter: none;
            opacity: .9;
            background-color: rgba(255, 255, 255, .18);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath d='M.293.293a1 1 0 0 1 1.414 0L8 6.586 14.293.293a1 1 0 1 1 1.414 1.414L9.414 8l6.293 6.293a1 1 0 0 1-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 0 1-1.414-1.414L6.586 8 .293 1.707a1 1 0 0 1 0-1.414z'/%3e%3c/svg%3e");
            background-size: 11px;
            background-position: center;
            background-repeat: no-repeat;
            border-radius: 50%;
        }
        .modal-header .btn-close.btn-close-white:hover { opacity: 1; background-color: rgba(255, 255, 255, .3); }

        /* ===== Dropdown d'actions (menu "..." sur les lignes de tableau) =====
           Partagé par tous les modules avec une liste d'actions par ligne
           (Modifier/Désactiver/etc.) — ne pas redéclarer localement dans
           les vues des 13 modules, réutiliser ces classes telles quelles. */
        .btn-action {
            border-radius: 8px;
            border: 1px solid var(--school-paper-line);
            background: #fff;
            color: var(--eim-ink-soft);
            width: 34px;
            height: 34px;
            transition: all .15s ease;
        }
        .btn-action:hover {
            background: var(--school-paper);
            border-color: var(--school-red);
            color: var(--school-red);
        }
        .btn-action.dropdown-toggle::after { display: none; } /* icône "..." seule, pas de caret */
        /* Aucune action autorisée pour ce profil : pas de bouton ouvrant un menu vide */
        .dropdown:has(> .dropdown-menu-actions):not(:has(.dropdown-item)) { display: none; }

        .dropdown-menu-actions {
            border-radius: 10px;
            border: 1px solid var(--school-paper-line);
            box-shadow: 0 8px 24px rgba(142,18,22,.12);
            padding: 6px;
            min-width: 180px;
        }
        .dropdown-menu-actions .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            border-radius: 6px;
            font-size: .88rem;
            padding: .5rem .75rem;
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
        }
        .dropdown-menu-actions .dropdown-item i { width: 16px; color: #7A6A6A; }
        .dropdown-menu-actions .dropdown-item:hover { background: var(--school-paper); }
        .dropdown-menu-actions .dropdown-item.text-danger { color: var(--school-red); }
        .dropdown-menu-actions .dropdown-item.text-danger i { color: var(--school-red); }
        .dropdown-menu-actions form { margin: 0; }

        /* ===== Pagination ===== Partagée par tous les modules — appliquée
           automatiquement à chaque `->links()` via Paginator::defaultView()
           dans AppServiceProvider::boot(). Ne pas redéclarer dans les vues. */
        .mariam-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
            font-family: var(--school-ff);
        }
        .mariam-pagination-info { font-size: .85rem; color: var(--school-muted); }
        .mariam-pagination-info strong { color: var(--school-ink); font-weight: 600; }
        .mariam-pagination-list {
            display: flex;
            align-items: center;
            gap: 6px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .mariam-page-link, .mariam-page-nav {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: 8px;
            border: 1px solid var(--school-paper-line);
            background: #fff;
            color: var(--school-ink);
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .15s ease;
        }
        .mariam-page-link:hover, .mariam-page-nav:hover {
            background: var(--school-paper);
            border-color: var(--school-red);
            color: var(--school-red);
        }
        .mariam-page-link.active {
            background: var(--eim-red);
            border-color: var(--school-red);
            color: #fff;
            box-shadow: 0 4px 12px rgba(227,30,36,.25);
        }
        .mariam-page-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            color: var(--school-muted);
            font-weight: 700;
        }
        .mariam-page-nav.disabled { opacity: .4; pointer-events: none; cursor: default; }
        .mariam-page-nav i { font-size: .75rem; }

        @media (max-width: 576px) {
            .mariam-pagination { flex-direction: column; align-items: flex-start; }
        }
    </style>

    {{-- Styles communs des composants (avant le CSS propre à chaque écran) --}}
    @include('admin.layouts.components.styles')

    {{-- CSS supplémentaire --}}
    @stack('css')
    @yield('css')
</head>

<body class="menu-horizontal">

{{-- Toast notifications : pré-rempli côté serveur depuis les flashs de
     session, ce qui les rend visibles même si le JS met du temps à
     s'exécuter (dégradation propre). Le script plus bas les fait
     disparaître automatiquement et gère les toasts déclenchés en AJAX. --}}
<div id="toast-container" aria-live="polite" aria-atomic="true">
    @if (session('success'))
        <div class="toast-item toast-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="toast-item toast-error" role="alert">{{ session('error') }}</div>
    @endif
    @if (isset($errors) && $errors->any())
        <div class="toast-item toast-error" role="alert">{{ $errors->first() }}</div>
    @endif
</div>

{{-- Modale de recherche --}}
@include('admin.layouts.partials._search')

{{-- En-tête personnalisé --}}
@include('admin.layouts.partials._header')

{{-- Wrapper principal --}}
<div class="page-wrapper">

    {{-- Barre de contexte --}}
    @hasSection('page_title')
        <div class="page-header-bar">
            <div class="page-header-left">
                <h1 class="page-title-main">
                    <span class="title-icon">
                        <i class="fas @yield('page_icon', 'fa-graduation-cap')"></i>
                    </span>
                    @yield('page_title')
                </h1>
                @hasSection('breadcrumb')
                    <nav aria-label="Fil d'Ariane">
                        <ul class="breadcrumb-custom">
                            @yield('breadcrumb')
                        </ul>
                    </nav>
                @endif
            </div>
            <div class="page-header-right">
                @yield('page_actions')
            </div>
        </div>
    @endif

    {{-- Contenu principal --}}
    <main class="content-area" role="main">
        @yield('contenu')
    </main>

    {{-- Pied de page --}}
    @include('admin.layouts.partials._footer')

</div>

{{-- Scripts requis --}}
<script src="{{ asset('app/assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="{{ asset('app/assets/js/feather.min.js') }}"></script>
<script src="{{ asset('app/assets/js/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('app/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('app/assets/js/moment.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('app/assets/plugins/chartjs/chart.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/chartjs/chart-data.js') }}"></script>
<script src="{{ asset('app/assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/apexchart/apexcharts.min.js') }}"></script>
<script src="{{ asset('app/assets/plugins/apexchart/chart-data.js') }}"></script>
<script src="{{ asset('app/assets/plugins/@simonwep/pickr/pickr.es5.min.js') }}"></script>
<script src="{{ asset('app/assets/js/theme-colorpicker.js') }}"></script>
<script src="{{ asset('app/assets/js/script.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /**
     * Système de toasts partagé par les 13 modules.
     * - Les toasts nés d'une redirection classique sont déjà dans le DOM
     *   au chargement (rendus côté serveur ci-dessus) : ce bloc les fait
     *   juste disparaître après quelques secondes.
     * - window.showToast(message, type) : à appeler depuis une réponse
     *   AJAX qui reste sur la même page (pas de reload).
     * - window.showToastThenReload(message, type) : pour un flux AJAX
     *   (ex. modale Cycle) qui DOIT recharger la page pour rafraîchir une
     *   liste paginée — un reload immédiat effacerait un toast affiché
     *   juste avant lui, donc le message est stocké puis réaffiché après
     *   le rechargement.
     */
    $(function () {
        $('#toast-container .toast-item').each(function () {
            const $t = $(this);
            setTimeout(() => $t.fadeOut(300, () => $t.remove()), 4000);
        });

        const pending = sessionStorage.getItem('pendingToast');
        if (pending) {
            sessionStorage.removeItem('pendingToast');
            const { type, message } = JSON.parse(pending);
            window.showToast(message, type);
        }
    });

    window.showToast = function (message, type = 'success') {
        const $toast = $('<div class="toast-item"></div>')
            .addClass(type === 'error' ? 'toast-error' : 'toast-success')
            .attr('role', type === 'error' ? 'alert' : 'status')
            .text(message);
        $('#toast-container').append($toast);
        setTimeout(() => $toast.fadeOut(300, () => $toast.remove()), 4000);
    };

    window.showToastThenReload = function (message, type = 'success') {
        sessionStorage.setItem('pendingToast', JSON.stringify({ type, message }));
        window.location.reload();
    };

    /**
     * Sélecteur d'élèves commun (admin/partials/select-eleve) : recherche
     * par nom ou matricule. Dans une fenêtre modale, la liste doit être
     * rattachée à la modale, sinon le champ de recherche ne reçoit pas le focus.
     */
    $(function () {
        $('select.select-eleve').each(function () {
            const $modale = $(this).closest('.modal');
            $(this).select2({
                dropdownParent: $modale.length ? $modale : $(document.body),
                placeholder: 'Rechercher un élève...',
                allowClear: !this.required,
                width: '100%',
            });
        });
    });

    /**
     * Filet de sécurité des appels AJAX : une erreur ne doit jamais rester
     * muette. Chaque écran affiche ses erreurs à sa façon (champ en rouge,
     * message sous le champ, notification) ; si, une fois son propre
     * traitement passé, rien de visible ne signale l'échec — champ fautif
     * absent du formulaire, emplacement d'erreur dans un bloc masqué... —
     * le message du serveur est affiché en notification.
     */
    $(document).ajaxError(function (event, xhr, settings) {
        if (xhr.statusText === 'abort' || (settings.type || 'GET').toUpperCase() === 'GET') return;

        setTimeout(function () {
            const signale =
                $('#toast-container .toast-error').length > 0 ||
                $('.is-invalid:visible').length > 0 ||
                $('.invalid-feedback:visible, [id$="-error"]:visible').filter(function () {
                    return $.trim($(this).text()) !== '';
                }).length > 0;

            if (signale) return;

            const reponse = xhr.responseJSON || {};
            const erreurs = reponse.errors ? Object.values(reponse.errors) : [];
            let message = erreurs.length ? erreurs[0][0] : reponse.message;

            if (xhr.status === 403) message = "Vous n'avez pas le droit d'effectuer cette action.";
            if (xhr.status === 419) message = 'Votre session a expiré. Rechargez la page puis recommencez.';
            if (!message || xhr.status >= 500) message = "Une erreur est survenue. L'opération n'a pas été enregistrée.";

            window.showToast(message, 'error');
        }, 0);
    });

    /**
     * Confirmation avant suppression/désactivation, partagée par les 13
     * modules : ajouter class="form-confirm-delete" à un <form> classique
     * (méthode DELETE via @@method), avec en option data-confirm-title et
     * data-confirm-text pour personnaliser le message. Rien d'autre à
     * écrire côté vue — ne pas redéclarer ce gestionnaire localement.
     */
    $(document).on('submit', '.form-confirm-delete', function (e) {
        e.preventDefault();
        const form = this;
        const title = form.dataset.confirmTitle || 'Confirmer la suppression ?';
        const text  = form.dataset.confirmText || 'Cette action peut être annulée plus tard.';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#E31E24',
            cancelButtonColor: '#5A4A4A',
            confirmButtonText: 'Oui, confirmer',
            cancelButtonText: 'Annuler'
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    });
</script>

{{-- JS supplémentaire --}}
@stack('js')
@yield('js')

</body>
</html>