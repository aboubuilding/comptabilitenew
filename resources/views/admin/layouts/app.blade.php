{{-- resources/views/layouts/app.blade.php --}}
    <!DOCTYPE html>
<html lang="fr" data-theme="light" data-layout="horizontal">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="École Internationale Mariam - Plateforme de gestion scolaire">

    <title>@yield('title', 'Tableau de bord') — École Internationale Mariam</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('app/assets/img/favicon.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('app/assets/img/apple-touch-icon.png') }}">

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
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- App CSS -->
    <link rel="stylesheet" href="{{ asset('app/assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('app/assets/css/mystyle.css') }}">

    {{--
        Jetons de couleur partagés par tout le back-office (header, footer,
        modale de recherche, et présente barre de titre de page). Les mêmes
        valeurs sont utilisées sur l'écran de connexion : c'est la charte
        unique de l'application, à ne pas dupliquer avec des valeurs
        différentes dans un autre fichier.
    --}}
    <style>
        :root {
            --school-red: #C81E3A;
            --school-red-dark: #7A0F22;
            --school-red-deep: #4A0C19;
            --school-gold: #D4A94D;
            --school-gold-soft: #E9CE9B;
            --school-gold-dark: #B8860B;
            --school-ink: #1f2d3a;
            --school-ink-deep: #141d27;
            --school-muted: #6f7e8c;
            --school-urgent: #ff7a1a;
            --school-urgent-dark: #cc5500;
            --school-paper: #FBF8F1;
            --school-paper-line: #EAE1CC;
            --school-page-bg: #F3F1E9;
            --school-ff: 'Kumbh Sans', sans-serif;
            --school-ff-display: 'Playfair Display', serif;
        }

        body.menu-horizontal {
            background: var(--school-page-bg);
            font-family: var(--school-ff);
        }

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
            font-family: var(--school-ff-display);
            font-weight: 700;
            font-size: 1.35rem;
            color: var(--school-ink);
            margin: 0;
        }
        .page-title-main .title-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            flex-shrink: 0;
            background: linear-gradient(145deg, var(--school-red), var(--school-red-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: var(--school-gold-soft);
            box-shadow: 0 4px 12px rgba(122, 15, 34, 0.25);
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
            border-radius: 10px;
            color: #fff;
            box-shadow: 0 10px 30px rgba(20, 29, 39, 0.18);
            animation: toast-in 0.25s ease-out both;
        }
        .toast-success {
            background: linear-gradient(120deg, var(--school-red-dark), var(--school-red));
            border-left: 4px solid var(--school-gold);
        }
        .toast-error {
            background: #8c1c1c;
            border-left: 4px solid var(--school-red-deep);
        }
        @keyframes toast-in {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @media (prefers-reduced-motion: reduce) {
            .toast-item { animation: none; }
        }

        /* ===== Dropdown d'actions (menu "..." sur les lignes de tableau) =====
           Partagé par tous les modules avec une liste d'actions par ligne
           (Modifier/Désactiver/etc.) — ne pas redéclarer localement dans
           les vues des 13 modules, réutiliser ces classes telles quelles. */
        .btn-action {
            border-radius: 8px;
            border: 1px solid var(--school-paper-line);
            background: #fff;
            color: #5b6b7a;
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

        .dropdown-menu-actions {
            border-radius: 10px;
            border: 1px solid var(--school-paper-line);
            box-shadow: 0 8px 24px rgba(122,15,28,.12);
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
        .dropdown-menu-actions .dropdown-item i { width: 16px; color: #8fa0ad; }
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
            background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
            border-color: var(--school-red);
            color: #fff;
            box-shadow: 0 4px 12px rgba(200,30,58,.25);
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
            confirmButtonColor: '#C81E3A',
            cancelButtonColor: '#6f7e8c',
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