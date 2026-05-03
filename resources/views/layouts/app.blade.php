<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="École Internationale Mariam - Système de Gestion">
    <meta name="author" content="École Mariam">

    <title>@yield('title', 'Tableau de bord') | École Internationale Mariam</title>

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('admin/images/favicon.png') }}">

    {{-- Polices Google --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    {{-- Vendors --}}
    <link href="{{ asset('admin/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- Styles template original --}}
    <link href="{{ asset('admin/css/style.css') }}" rel="stylesheet">

    {{-- Layout personnalisé École Mariam --}}
    <link href="{{ asset('admin/css/layout-mariam.css?v=2.0') }}" rel="stylesheet">

    @yield('css')
    @stack('styles')
</head>

<body class="eim-body">

    {{-- Topbar avec logo --}}
    @include('layouts.partials.nav-header')

    {{-- Menu horizontal --}}
    @include('layouts.partials.nav-menu')

    {{-- Contenu principal --}}
    <main class="eim-main-content" id="main-content">
        {{-- Breadcrumb --}}
        @hasSection('breadcrumb')
        <div class="eim-breadcrumb-wrapper">
            @yield('breadcrumb')
        </div>
        @endif

        {{-- Titre de page --}}
        @hasSection('titre')
        <div class="eim-page-header">
            <h1 class="eim-page-title">@yield('titre')</h1>
        </div>
        @endif

        {{-- Contenu --}}
        <div class="eim-content-wrapper">
            @yield('content')
        </div>
    </main>

    {{-- Overlay mobile --}}
    <div id="eim-mobile-overlay" class="eim-mobile-overlay"></div>

    {{-- Toast container --}}
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    {{-- Scripts vendors --}}
    <script src="{{ asset('admin/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin/js/dlabnav-init.js') }}"></script>

    {{-- Layout JS --}}
    <script src="{{ asset('admin/js/layout-mariam.js?v=2.0') }}"></script>

    @yield('js')
    @stack('scripts')

</body>
</html>