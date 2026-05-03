<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Connexion sécurisée — Comptabilité | École Internationale Mariam">

    <title>Ecole Mariam | Comptabilité — Connexion</title>

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="{{ asset('admin') }}/images/favicon.png">

    {{-- ── Kumbh Sans (corps + UI) + Cormorant Garamond (accents décoratifs) ── --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;500;600;700;800;900&family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="{{ asset('admin') }}/vendor/fontawesome/css/all.min.css">

    {{-- CSS dédié → public/css/auth/login.css --}}
    <link rel="stylesheet" href="{{ asset('admin/css/auth/login.css') }}">
</head>

<body>

<!-- Toast container -->
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>

<div class="page-wrapper">

    {{-- ════════════════════════════════════════════
         PANNEAU GAUCHE — Branding École Mariam (55%)
         ════════════════════════════════════════════ --}}
    <div class="left-panel">
        <div class="left-inner">

            {{-- badge supérieur --}}
            <div class="school-badge animate-in">
                <i class="fas fa-graduation-cap"></i>
                <span>Excellence &amp; Savoir</span>
            </div>

            {{-- logo circulaire --}}
            <div class="logo-ring animate-in delay-1">
                <img src="{{ asset('admin') }}/images/logo_mariam.png" alt="Logo École Mariam">
            </div>

            {{-- séparateur doré --}}
            <div class="gold-sep animate-in delay-2">
                <span></span><i class="fas fa-star"></i><span></span>
            </div>

            {{-- nom école : grande typo Kumbh Extra-Bold + accent Cormorant --}}
            <h1 class="school-name animate-in delay-2">
                École Internationale
                <br>
                <em>Mariam</em>
            </h1>

            {{-- slogan en Kumbh 300 --}}
            <p class="school-tagline animate-in delay-3">
                Système de gestion comptable sécurisé — réservé au personnel
                administratif autorisé et accrédité.
            </p>

            {{-- grille stats --}}
            <div class="stats-grid animate-in delay-3">
                <div class="stat-item">
                    <div class="stat-value">SSL</div>
                    <div class="stat-label">Sécurisé</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">24/7</div>
                    <div class="stat-label">Disponible</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">100%</div>
                    <div class="stat-label">Numérique</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">AES</div>
                    <div class="stat-label">Chiffrement</div>
                </div>
            </div>

            {{-- citation --}}
            <div class="quote-bar animate-in delay-4">
                <p>
                    « La rigueur dans la gestion, la transparence dans les comptes. »
                    <br>
                    <strong>Direction Générale — École Mariam</strong>
                </p>
            </div>

        </div>
    </div>

    {{-- ════════════════════════════════════════════
         PANNEAU DROIT — Formulaire de connexion (45%)
         ════════════════════════════════════════════ --}}
    <div class="right-panel">
        <div class="form-card">

            {{-- eyebrow --}}
            <p class="form-eyebrow animate-in">
                <i class="fas fa-lock"></i>
                <span>Portail d'accès sécurisé</span>
            </p>

            {{-- titre principal en Kumbh 800 --}}
            <h2 class="form-title animate-in delay-1">Connexion</h2>

            {{-- description en Kumbh 400 --}}
            <p class="form-desc animate-in delay-2">
                Entrez vos identifiants pour accéder au module de comptabilité de l'École Mariam.
            </p>

            {{-- ── Alerte erreur serveur (masquée, affichée par JS) ── --}}
            <div class="alert-box animate-in delay-2" id="alert-serveur" role="alert">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="alert-body">
                    <strong>Erreur de connexion</strong>
                    <span id="erreurserveur"></span>
                </div>
                <button type="button" class="alert-close" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form id="form-login" autocomplete="off" class="animate-in delay-3">
                @csrf

                {{-- ── Champ Login ── --}}
                <div class="field-group">
                    <label class="field-label" for="login">
                        <i class="fas fa-user"></i>
                        <span>Login</span>
                    </label>
                    <div class="input-shell">
                        <i class="input-icon far fa-user"></i>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            class="field-input"
                            placeholder="Votre identifiant"
                            autocomplete="username"
                            aria-required="true"
                        >
                    </div>
                    <div class="error-inline" id="error-login">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>Le login est obligatoire</span>
                    </div>
                </div>

                {{-- ── Champ Mot de passe ── --}}
                <div class="field-group">
                    <label class="field-label" for="mot_passe">
                        <i class="fas fa-lock"></i>
                        <span>Mot de passe</span>
                    </label>
                    <div class="input-shell">
                        <i class="input-icon fas fa-lock"></i>
                        <input
                            type="password"
                            id="mot_passe"
                            name="mot_passe"
                            class="field-input"
                            placeholder="••••••••••"
                            autocomplete="current-password"
                            aria-required="true"
                        >
                        <button type="button" class="toggle-pw" id="toggle-pw" aria-label="Afficher / masquer le mot de passe">
                            <i class="far fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                    <div class="error-inline" id="error-motpasse">
                        <i class="fas fa-circle-exclamation"></i>
                        <span>Le mot de passe est obligatoire</span>
                    </div>
                </div>

                {{-- ── Options ── --}}
                <div class="options-row">
                    <label class="custom-check">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="check-box"></span>
                        <span class="check-label">Rester connecté</span>
                    </label>
                    <a href="#" class="forgot-link" id="forgotLink">
                        <i class="fas fa-question-circle"></i>
                        <span>Mot de passe oublié ?</span>
                    </a>
                </div>

                {{-- ── Bouton Se connecter ── --}}
                <button type="button" class="btn-submit" id="btn-login">
                    <span class="btn-spinner"></span>
                    <span class="btn-text">Se connecter</span>
                    <i class="fas fa-arrow-right btn-arrow"></i>
                </button>

            </form>

            {{-- note bas --}}
            <p class="form-note animate-in delay-4">
                Accès exclusivement réservé au personnel autorisé.
            </p>

            {{-- footer --}}
            <div class="form-footer animate-in delay-4">
                <span class="footer-copy">&copy; {{ date('Y') }} — École Internationale Mariam</span>
                <span class="footer-ssl">
                    <i class="fas fa-shield-alt"></i>
                    <span>Connexion chiffrée SSL / TLS</span>
                </span>
            </div>

        </div>
    </div>

</div>{{-- /.page-wrapper --}}

{{-- ── Scripts ── --}}
<script src="{{ asset('admin') }}/vendor/global/global.min.js"></script>

{{-- Routes Laravel exposées en JS global (garder les routes hors du fichier login.js) --}}
<script>
    var LOGIN_ROUTE   = "{{ route('utilisateur_authenticate') }}";
    var TABLEAU_ROUTE = "{{ route('tableau') }}";
</script>

{{-- JS dédié → public/js/auth/login.js --}}
<script src="{{ asset('admin/js/auth/login.js') }}"></script>

</body>
</html>