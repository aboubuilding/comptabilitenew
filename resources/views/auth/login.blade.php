{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion · EIM Compta</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo/eim-compta-icon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/logo/favicon-32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo/apple-touch-icon.png') }}">

    <!-- Police de la charte : Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('app/assets/css/bootstrap.min.css') }}" />

    <!-- Fontawesome & Tabler Icons -->
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/fontawesome.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/fontawesome/css/all.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('app/assets/plugins/tabler-icons/tabler-icons.min.css') }}" />

    <!-- SweetAlert2 & Toastr : bibliothèques propres à cet écran (pas
         utilisées dans layouts/app.blade.php, qui a son propre système de
         toasts maison) — justifié ici par la modale bloquante SweetAlert
         (compte désactivé, session expirée), qu'un simple toast ne rend
         pas correctement. -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet" />

    @include('admin.layouts.partials._charte')
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body.account-page {
            font-family: var(--eim-ff);
            min-height: 100vh;
            background: var(--eim-bg);
            color: var(--eim-ink);
        }

        #global-loader {
            position: fixed;
            inset: 0;
            z-index: 99999;
            background: var(--eim-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }
        #global-loader.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        #global-loader .spinner-border { color: var(--eim-red); }

        /* ===== Deux panneaux : identité à gauche, formulaire à droite ===== */
        .school-wrapper {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .school-brand-panel {
            flex: 0 0 45%;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 64px 56px;
            color: #fff;
            background: radial-gradient(130% 150% at 12% 8%, var(--eim-red) 0%, var(--eim-red-hover) 48%, var(--eim-red-deep) 100%);
            border-right: 3px solid var(--eim-gold);
        }
        /* cadre à double filet or */
        .school-frame {
            position: absolute;
            inset: 28px;
            border: 1px solid rgba(212, 169, 77, .45);
            border-radius: var(--eim-radius);
            pointer-events: none;
        }
        .school-frame::after {
            content: '';
            position: absolute;
            inset: 7px;
            border: 1px solid rgba(212, 169, 77, .22);
            border-radius: var(--eim-radius-sm);
        }
        .school-glow {
            position: absolute;
            width: 560px; height: 560px;
            right: -200px; bottom: -180px;
            background: radial-gradient(circle, rgba(212, 169, 77, .18) 0%, rgba(212, 169, 77, 0) 70%);
            pointer-events: none;
        }
        .school-brand-content { position: relative; z-index: 1; max-width: 420px; }
        .school-brand-seal img {
            width: 112px; height: 112px;
            filter: drop-shadow(0 10px 24px rgba(0, 0, 0, .28));
            margin-bottom: 28px;
        }
        .school-brand-kicker {
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--eim-gold-soft);
            margin-bottom: 10px;
        }
        .school-brand-title {
            font-size: clamp(2.2rem, 3.6vw, 3rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.08;
            margin-bottom: 18px;
        }
        .school-brand-desc {
            font-size: .95rem;
            line-height: 1.7;
            color: rgba(255, 255, 255, .88);
            margin-bottom: 32px;
        }
        .school-registry { list-style: none; padding: 0; margin: 0; border-top: 1px solid rgba(255, 255, 255, .18); }
        .school-registry li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid rgba(255, 255, 255, .18);
            font-size: .9rem;
            font-weight: 500;
            color: #fff;
        }
        .school-registry li i { color: var(--eim-gold); width: 18px; text-align: center; }
        .school-brand-footer { margin-top: 32px; font-size: .75rem; color: rgba(255, 255, 255, .7); }

        .school-form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 56px;
            background: var(--eim-bg);
        }
        .school-form-inner { width: 100%; max-width: 420px; }

        .school-seal { display: none; text-align: center; margin-bottom: 18px; }
        .school-seal img { width: 88px; height: 88px; filter: drop-shadow(0 8px 18px rgba(142, 18, 22, .18)); }

        @media (max-width: 991.98px) {
            .school-wrapper { flex-direction: column; }
            .school-brand-panel { display: none; }
            .school-form-panel { padding: 40px 20px; }
            .school-seal { display: block; }
        }
        /* sur grand écran, le copyright figure déjà dans le panneau gauche */
        @media (min-width: 992px) { .school-footer-note { display: none; } }

        .school-card-head { margin-bottom: 28px; }
        .school-card-head h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--eim-ink);
            margin-bottom: 4px;
        }
        .school-card-head h1 span { color: var(--eim-red); }
        .school-card-head .school-kicker {
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--eim-red-hover);
            margin-bottom: 6px;
        }
        .school-card-head p { color: var(--eim-ink-soft); font-size: .9rem; margin: 0; }

        /* ---- Bandeau d'alerte contextuel ---- */
        .school-alert-banner {
            display: none;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: var(--eim-radius);
            margin-bottom: 22px;
            font-size: 0.86rem;
            line-height: 1.5;
            border: 1px solid transparent;
            animation: alertIn 0.25s ease-out both;
        }
        .school-alert-banner.show { display: flex; }
        .school-alert-banner i { font-size: 1.1rem; margin-top: 1px; flex-shrink: 0; }
        .school-alert-banner .alert-title { font-weight: 700; display: block; margin-bottom: 2px; }
        .school-alert-banner.type-danger { background: #FCEBEB; border-color: #F3C4C5; color: var(--eim-danger); }
        .school-alert-banner.type-warning { background: var(--eim-cream); border-color: var(--eim-gold-soft); color: #6B4E10; }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .school-label {
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--eim-ink);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 7px;
        }
        .school-label .text-danger { color: var(--eim-danger) !important; }

        .school-input-group {
            display: flex;
            align-items: stretch;
            background: var(--eim-cream);
            border: 1.5px solid var(--eim-border);
            border-radius: var(--eim-radius-sm);
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
            overflow: hidden;
        }
        .school-input-group:focus-within {
            border-color: var(--eim-red);
            box-shadow: 0 0 0 2px rgba(227, 30, 36, .25);
            background: var(--eim-surface);
        }
        .school-input-group.is-invalid { border-color: var(--eim-danger); background: #FDF6F6; }
        .school-input-group .form-control {
            border: none;
            background: transparent;
            padding: 13px 14px;
            font-size: 0.92rem;
            box-shadow: none !important;
            color: var(--eim-ink);
        }
        .school-input-group .form-control::placeholder { color: #8A7B7B; }
        .school-input-group .form-control:focus { outline: none; box-shadow: none; }
        .school-input-group .input-group-text {
            border: none;
            background: transparent;
            color: var(--eim-ink-soft);
            padding: 0 14px;
            font-size: 1.05rem;
            cursor: pointer;
        }
        .school-input-group .toggle-password:hover { color: var(--eim-red-hover); }

        /* jQuery masque le message par style="display:none", que le !important de .d-block écrasait */
        .invalid-feedback.d-block[style*="none"] { display: none !important; }
        .invalid-feedback.d-block {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            margin-top: 6px;
            color: var(--eim-danger);
        }

        .school-row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 24px;
            font-size: 0.85rem;
        }
        .school-row-between .form-check-label { color: var(--eim-ink-soft); cursor: pointer; user-select: none; }
        .school-row-between .form-check-input:checked { background-color: var(--eim-red); border-color: var(--eim-red); }
        .school-row-between a { color: var(--eim-red-hover); font-weight: 600; text-decoration: none; }
        .school-row-between a:hover { color: var(--eim-red-deep); text-decoration: underline; }

        .school-btn-submit {
            width: 100%;
            border: none;
            border-radius: var(--eim-radius-sm);
            padding: 14px;
            font-family: var(--eim-ff);
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            background: var(--eim-red);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background .2s ease, box-shadow .2s ease;
            box-shadow: 0 10px 24px rgba(227, 30, 36, .22);
            cursor: pointer;
        }
        .school-btn-submit:hover:not(:disabled) { background: var(--eim-red-hover); }
        .school-btn-submit:active:not(:disabled) { background: var(--eim-red-deep); }
        .school-btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

        .school-access-note {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--eim-border);
            text-align: center;
            font-size: 0.83rem;
            color: var(--eim-ink-soft);
            line-height: 1.6;
        }

        .school-footer-note {
            text-align: left;
            margin-top: 18px;
            font-size: 0.75rem;
            color: var(--eim-ink-soft);
        }

        /* Focus clavier visible partout (accessibilité) */
        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        [role="button"]:focus-visible {
            outline: 2px solid var(--eim-red);
            outline-offset: 2px;
        }

        /* Toastr aux couleurs de la charte */
        #toast-container > div { font-family: var(--eim-ff); border-radius: var(--eim-radius); opacity: 1; }
        #toast-container > .toast-success { background-color: var(--eim-success) !important; }
        #toast-container > .toast-error { background-color: var(--eim-danger) !important; }
        #toast-container > .toast-warning { background-color: #8A6516 !important; }
        #toast-container > .toast-info { background-color: #2b6cb0 !important; }

        @media (max-width: 480px) {
            .school-form-panel { padding: 32px 16px; }
            .school-card-head h1 { font-size: 1.5rem; }
        }

        .school-form-inner, .school-brand-content { animation: fadeInUp 0.6s ease-out both; }
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(14px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-8px); }
            20%, 40%, 60%, 80% { transform: translateX(8px); }
        }
        .shake { animation: shake 0.45s ease; }

        @media (prefers-reduced-motion: reduce) {
            .school-form-inner, .school-brand-content, .shake, .school-alert-banner { animation: none !important; }
        }
    </style>
</head>
<body class="account-page">

<div id="global-loader">
    <div class="spinner-border" role="status" style="width:3.5rem;height:3.5rem;">
        <span class="visually-hidden">Chargement…</span>
    </div>
</div>

<div class="school-wrapper">

    <!-- PANNEAU GAUCHE : identité -->
    <aside class="school-brand-panel">
        <div class="school-frame"></div>
        <div class="school-glow"></div>
        <div class="school-brand-content">
            <div class="school-brand-seal"><x-logo variant="sceau" decoratif /></div>
            <div class="school-brand-kicker">École Internationale Mariam</div>
            <h2 class="school-brand-title">EIM Compta</h2>
            <p class="school-brand-desc">
                La gestion financière de l'établissement : encaissements, caisses, dépenses, recouvrement et pilotage budgétaire.
            </p>
            <ul class="school-registry">
                <li><i class="fas fa-cash-register" aria-hidden="true"></i>Encaissements et caisses suivis au jour le jour</li>
                <li><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i>Dépenses, décaissements et lignes budgétaires</li>
                <li><i class="fas fa-bell" aria-hidden="true"></i>Recouvrement et relances des familles</li>
            </ul>
            <div class="school-brand-footer">&copy; {{ date('Y') }} École Internationale Mariam — Tous droits réservés.</div>
        </div>
    </aside>

    <!-- PANNEAU DROIT : connexion -->
    <main class="school-form-panel">
        <div class="school-form-inner">
            <div class="school-seal"><x-logo variant="sceau" /></div>

            <div class="school-card-head">
                <div class="school-kicker">EIM Compta</div>
                <h1>Connexion</h1>
                <p>Saisissez vos identifiants pour accéder à votre espace.</p>
            </div>

            <!-- Bandeau d'alerte global, alimenté dynamiquement selon le code d'erreur -->
            <div id="alert-banner" class="school-alert-banner" role="alert" aria-live="assertive">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <span class="alert-title" id="alert-banner-title"></span>
                    <span id="alert-banner-text"></span>
                </div>
            </div>

            <form id="form-login" autocomplete="off" novalidate>
                @csrf

                <!-- Champ Identifiant -->
                <div class="mb-3">
                    <label class="school-label" for="login"><i class="ti ti-user-circle"></i> Identifiant <span class="text-danger">*</span></label>
                    <div class="school-input-group" id="group-login">
                        <span class="input-group-text"><i class="ti ti-user"></i></span>
                        <input type="text" name="login" id="login" class="form-control"
                               placeholder="Entrez votre identifiant" autocomplete="username" required autofocus
                               aria-describedby="error-login">
                    </div>
                    <div class="invalid-feedback d-block" id="error-login" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i> <span>Le login est obligatoire</span>
                    </div>
                </div>

                <!-- Champ Mot de passe -->
                <div class="mb-2">
                    <label class="school-label" for="mot_passe"><i class="ti ti-lock"></i> Mot de passe <span class="text-danger">*</span></label>
                    <div class="school-input-group" id="group-password">
                        <span class="input-group-text"><i class="ti ti-key"></i></span>
                        <input type="password" name="password" id="mot_passe" class="form-control"
                               placeholder="••••••••" autocomplete="current-password" required
                               aria-describedby="error-motpasse">
                        <span class="input-group-text toggle-password" id="togglePassword" role="button" tabindex="0" aria-label="Afficher le mot de passe">
                            <i class="ti ti-eye-off" id="eye-icon"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback d-block" id="error-motpasse" style="display:none;">
                        <i class="fas fa-exclamation-circle"></i> <span>Le mot de passe est obligatoire</span>
                    </div>
                </div>

                <!-- Options -->
                <div class="school-row-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Se souvenir de moi</label>
                    </div>
                    <a href="#" id="forgotLink">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="school-btn-submit" id="btn-login">
                    <span class="btn-spinner" style="display:none;">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </span>
                    <span class="btn-text"><i class="ti ti-login"></i> Connexion</span>
                </button>

                <p class="school-access-note mb-0">
                    Vous n'avez pas de compte ? Contactez l'administrateur de l'établissement.
                </p>

            </form>

            <div class="school-footer-note">
                &copy; {{ date('Y') }} École Internationale Mariam — Tous droits réservés.
            </div>
        </div>
    </main>
</div>

<!-- SCRIPTS -->
<script src="{{ asset('app/assets/js/jquery-3.7.1.min.js') }}"></script>
<script src="{{ asset('app/assets/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    // ============================================
    // CONFIGURATION
    // ============================================

    // login.store, pas login.post : nom de route réellement défini dans
    // routes/auth.php, cohérent avec la convention *.store utilisée
    // partout ailleurs (scolarite.cycles.store, etc.)
    var LOGIN_ROUTE   = "{{ route('login.store') }}";
    var TABLEAU_ROUTE = "{{ route('tableau') }}";

    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000",
        "extendedTimeOut": "2000",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    /**
     * Table de correspondance entre les codes métier renvoyés par
     * AuthService::attempt() / LoginController::store() et l'affichage à
     * adopter côté formulaire. Chaque code peut :
     *  - cibler un champ précis (highlight + focus)
     *  - afficher un bandeau inline (danger / warning)
     *  - déclencher une SweetAlert pour les cas bloquants nécessitant
     *    une action de l'administrateur
     *
     * NO_ACTIVE_YEAR et ERROR sont laissés en garde-fou mais ne sont plus
     * produits par l'AuthService actuel : AnneeService::resolveActive()
     * crée toujours une année par défaut si aucune n'existe (ne peut donc
     * plus échouer), et attempt() ne lève plus d'exception interceptée
     * avec ce code précis (une erreur imprévue remonte en 500, gérée par
     * handleAjaxError ci-dessous, pas par cette table).
     */
    var ERROR_MAP = {
        USER_NOT_FOUND: {
            field: 'login',
            banner: 'danger',
            title: 'Identifiant introuvable',
            text: "Aucun compte ne correspond à cet identifiant. Vérifiez la saisie ou contactez l'administrateur."
        },
        INVALID_PASSWORD: {
            field: 'password',
            banner: 'danger',
            title: 'Mot de passe incorrect',
            text: "Le mot de passe saisi est incorrect. Vérifiez votre saisie ou réinitialisez-le."
        },
        ACCOUNT_INACTIVE: {
            sweetalert: {
                icon: 'warning',
                title: 'Compte désactivé',
                text: "Votre compte a été désactivé. Merci de contacter l'administrateur pour le réactiver."
            }
        },
        NO_ACTIVE_YEAR: {
            sweetalert: {
                icon: 'error',
                title: 'Année scolaire indisponible',
                text: "Aucune année scolaire active n'est configurée. Merci de contacter l'administrateur de la plateforme."
            }
        },
        ERROR: {
            banner: 'danger',
            title: 'Erreur technique',
            text: "Une erreur technique est survenue. Veuillez réessayer ou contacter le support si le problème persiste."
        }
    };

    // ============================================
    // INITIALISATION
    // ============================================

    $(document).ready(function() {
        setTimeout(function() {
            $('#global-loader').addClass('hidden');
        }, 400);

        clearData();
        bindEvents();
        // Pas de vérification de session côté client ici : le middleware
        // 'guest' déjà appliqué sur la route /login (routes/auth.php)
        // redirige déjà un utilisateur authentifié avant même que cette
        // page ne s'affiche. Un appel AJAX supplémentaire vers
        // /check-session serait redondant (et cette route n'existe pas).
        // A vérifier : RedirectIfAuthenticated doit rediriger vers
        // route('tableau'), pas le /home par défaut de Laravel.
    });

    // ============================================
    // GESTION DES ÉVÉNEMENTS
    // ============================================

    function bindEvents() {
        $('#form-login').on('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });

        $('#togglePassword').on('click keydown', function(e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
            e.preventDefault();
            const input = $('#mot_passe');
            const icon = $('#eye-icon');
            const isHidden = input.attr('type') === 'password';
            input.attr('type', isHidden ? 'text' : 'password');
            icon.toggleClass('ti-eye-off', !isHidden).toggleClass('ti-eye', isHidden);
            $(this).attr('aria-label', isHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });

        $('#login, #mot_passe').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleLogin();
            }
        });

        $('#login').on('blur', function() { validateField('login'); });
        $('#mot_passe').on('blur', function() { validateField('mot_passe'); });

        // Nettoyer les erreurs dès que l'utilisateur corrige sa saisie
        $('#login, #mot_passe').on('input', function() {
            const field = $(this).attr('id');
            const errorId = field === 'login' ? 'error-login' : 'error-motpasse';
            const groupId = field === 'login' ? 'group-login' : 'group-password';
            clearFieldError(errorId, groupId);
            hideBanner();
        });

        $('#forgotLink').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                icon: 'info',
                title: 'Mot de passe oublié',
                text: "Veuillez contacter l'administrateur pour réinitialiser votre mot de passe.",
                confirmButtonColor: '#E31E24',
                confirmButtonText: 'Compris'
            });
        });
    }

    // ============================================
    // VALIDATION CÔTÉ CLIENT
    // ============================================

    function validateField(field) {
        const value = $('#' + field).val().trim();
        const errorId = field === 'login' ? 'error-login' : 'error-motpasse';
        const groupId = field === 'login' ? 'group-login' : 'group-password';

        if (value === '') {
            showFieldError(errorId, groupId, field === 'login'
                ? 'Le login est obligatoire'
                : 'Le mot de passe est obligatoire');
            return false;
        }

        clearFieldError(errorId, groupId);
        return true;
    }

    function showFieldError(errorId, groupId, message) {
        $('#' + errorId + ' span').text(message);
        $('#' + errorId).show();
        $('#' + groupId).addClass('is-invalid');
    }

    function clearFieldError(errorId, groupId) {
        $('#' + errorId).hide();
        $('#' + groupId).removeClass('is-invalid');
    }

    function clearErrors() {
        clearFieldError('error-login', 'group-login');
        clearFieldError('error-motpasse', 'group-password');
        hideBanner();
    }

    function clearData() {
        $('#login').val('');
        $('#mot_passe').val('');
        clearErrors();
    }

    // ============================================
    // BANDEAU D'ALERTE INLINE
    // ============================================

    function showBanner(type, title, text) {
        const banner = $('#alert-banner');
        banner.removeClass('type-danger type-warning').addClass('type-' + type);
        banner.find('i').attr('class', type === 'warning' ? 'fas fa-triangle-exclamation' : 'fas fa-exclamation-circle');
        $('#alert-banner-title').text(title);
        $('#alert-banner-text').text(text);
        banner.addClass('show');
    }

    function hideBanner() {
        $('#alert-banner').removeClass('show type-danger type-warning');
    }

    // ============================================
    // AUTHENTIFICATION
    // ============================================

    function handleLogin() {
        const login = $('#login').val().trim();
        const password = $('#mot_passe').val();

        clearErrors();

        const loginValid = validateField('login');
        const passwordValid = validateField('mot_passe');

        if (!loginValid || !passwordValid) {
            toastr.warning('Veuillez corriger les champs en surbrillance.');
            return;
        }

        // Pas de contrôle de longueur minimale ici : une règle de
        // complexité s'applique à la CRÉATION d'un mot de passe, jamais à
        // sa VÉRIFICATION — sinon un compte dont le mot de passe a été
        // fixé avant qu'une telle règle existe serait bloqué en
        // permanence, même avec le bon mot de passe. Le serveur (voir
        // LoginRequest) n'impose d'ailleurs aucune longueur minimale à la
        // connexion, pour la même raison.

        authentifier(login, password);
    }

    function authentifier(login, password) {
        setLoading(true);

        $.ajax({
            dataType: 'json',
            type: 'POST',
            url: LOGIN_ROUTE,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: {
                login: login,
                password: password,
                remember: $('#remember').is(':checked')
            },
            timeout: 10000,

            success: function(data) {
                if (data.success) {
                    handleLoginSuccess(data);
                } else {
                    // Réponse HTTP 200 mais success=false (cas rare, sécurité supplémentaire)
                    setLoading(false);
                    handleBusinessError(data.code, data.message);
                }
            },

            error: function(xhr) {
                setLoading(false);
                handleAjaxError(xhr);
            }
        });
    }

    /**
     * Connexion réussie : on informe explicitement l'utilisateur via un toast
     * ET on garde le bouton dans un état "redirection en cours" pour que le
     * toast ait le temps d'être vu avant que la vue tableau de bord ne s'affiche.
     */
    function handleLoginSuccess(data) {
        const welcomeMessage = data.message || 'Connexion réussie !';

        toastr.success(welcomeMessage, 'Connexion réussie', {
            timeOut: 3000,
            extendedTimeOut: 1000
        });

        setRedirecting();

        setTimeout(function() {
            window.location.href = data.redirect || TABLEAU_ROUTE;
        }, 1200);
    }

    /**
     * Affiche une erreur avec DEUX niveaux de visibilité complémentaires :
     *  - le bandeau inline (#alert-banner), qui reste affiché tant que
     *    l'utilisateur n'a pas corrigé sa saisie ;
     *  - un toast (coin haut droit), qui attire l'œil immédiatement même si
     *    le bandeau est hors du champ de vision (mobile, formulaire long, etc.)
     */
    function showError(type, title, text) {
        showBanner(type, title, text);

        if (type === 'warning') {
            toastr.warning(text, title);
        } else {
            toastr.error(text, title);
        }
    }

    /**
     * Traite les erreurs "métier" renvoyées par AuthService (code + message),
     * en s'appuyant sur ERROR_MAP pour choisir la présentation adaptée.
     */
    function handleBusinessError(code, fallbackMessage) {
        const mapping = ERROR_MAP[code];

        if (mapping && mapping.sweetalert) {
            const text = mapping.sweetalert.text || fallbackMessage;

            // Toast discret en complément de la modale bloquante, pour garder
            // une notification cohérente même si l'utilisateur ferme la modale vite.
            toastr.error(text, mapping.sweetalert.title);

            Swal.fire({
                icon: mapping.sweetalert.icon,
                title: mapping.sweetalert.title,
                text: text,
                confirmButtonColor: '#E31E24',
                confirmButtonText: "J'ai compris"
            });
            return;
        }

        if (mapping && mapping.field) {
            // Message précis (identifiant introuvable / mot de passe incorrect) prioritaire
            // sur le message générique du serveur ("Identifiant ou mot de passe incorrect."),
            // qui est volontairement vague pour ne pas être exploité tel quel.
            const specificMessage = mapping.text;
            const errorId = mapping.field === 'login' ? 'error-login' : 'error-motpasse';
            const groupId = mapping.field === 'login' ? 'group-login' : 'group-password';

            showFieldError(errorId, groupId, specificMessage);
            $(mapping.field === 'login' ? '#login' : '#mot_passe').trigger('focus');
            showError(mapping.banner, mapping.title, specificMessage);
            shakeForm();
            return;
        }

        // Cas sans champ ciblé (ex: ERROR, TOO_MANY_ATTEMPTS) : on affiche
        // le message serveur s'il est plus précis que notre texte par
        // défaut, sinon notre texte par défaut.
        showError(
            (mapping && mapping.banner) || 'danger',
            (mapping && mapping.title) || 'Connexion impossible',
            (mapping && mapping.text) || fallbackMessage || 'Identifiant ou mot de passe incorrect.'
        );

        shakeForm();
    }

    // ============================================
    // GESTION DES ERREURS RÉSEAU / HTTP
    // ============================================

    function handleAjaxError(xhr) {
        // 401 : échec d'authentification -> utiliser le code métier renvoyé par le contrôleur
        if (xhr.status === 401) {
            try {
                const response = JSON.parse(xhr.responseText);
                handleBusinessError(response.code, response.message);
            } catch (e) {
                handleBusinessError('ERROR', 'Identifiant ou mot de passe incorrect.');
            }
            return;
        }

        // 422 : erreurs de validation Laravel (règles de LoginRequest)
        if (xhr.status === 422) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.errors) {
                    const errors = Object.values(response.errors).flat();
                    showError('danger', 'Formulaire incomplet', errors.join(' '));
                } else {
                    showError('danger', 'Données invalides', response.message || 'Veuillez vérifier vos informations.');
                }
            } catch (e) {
                showError('danger', 'Données invalides', 'Veuillez vérifier vos informations.');
            }
            shakeForm();
            return;
        }

        // 429 : trop de tentatives -> LoginController::store() renvoie ce
        // statut directement (voir le contrôleur), avec code TOO_MANY_ATTEMPTS
        if (xhr.status === 429) {
            try {
                const response = JSON.parse(xhr.responseText);
                handleBusinessError(response.code, response.message);
            } catch (e) {
                showError('warning', 'Trop de tentatives', 'Trop de tentatives de connexion. Veuillez patienter quelques minutes avant de réessayer.');
            }
            return;
        }

        // Cas réseau / serveur : message explicite + SweetAlert quand une action est nécessaire
        switch (xhr.status) {
            case 0:
                showError('danger', 'Connexion impossible', 'Impossible de joindre le serveur. Vérifiez votre connexion internet.');
                break;
            case 419:
                toastr.warning('Votre session a expiré.', 'Session expirée');
                Swal.fire({
                    icon: 'warning',
                    title: 'Session expirée',
                    text: 'Votre session a expiré. La page va être actualisée.',
                    confirmButtonColor: '#E31E24',
                    confirmButtonText: 'Actualiser'
                }).then(function() { window.location.reload(); });
                break;
            case 500:
                showError('danger', 'Erreur serveur', "Une erreur interne est survenue. Merci de contacter l'administrateur si le problème persiste.");
                break;
            case 503:
                showError('danger', 'Service indisponible', 'Le service est momentanément indisponible. Veuillez réessayer plus tard.');
                break;
            default:
                showError('danger', 'Erreur ' + xhr.status, 'Une erreur est survenue. Veuillez réessayer.');
        }
    }

    // ============================================
    // ÉTAT DE CHARGEMENT
    // ============================================

    function setLoading(state) {
        const btn = $('#btn-login');
        const text = $('.btn-text');
        const spinner = $('.btn-spinner');

        if (state) {
            btn.prop('disabled', true);
            text.html('Connexion en cours...');
            spinner.show();
        } else {
            btn.prop('disabled', false);
            text.html('<i class="ti ti-login"></i> Connexion');
            spinner.hide();
        }
    }

    /**
     * État visuel affiché entre la confirmation de connexion (toast) et la
     * redirection effective vers le tableau de bord.
     */
    function setRedirecting() {
        const btn = $('#btn-login');
        const text = $('.btn-text');
        const spinner = $('.btn-spinner');

        btn.prop('disabled', true);
        text.html('<i class="ti ti-check"></i> Redirection...');
        spinner.show();
    }

    function shakeForm() {
        $('.school-form-panel').addClass('shake');
        setTimeout(function() { $('.school-form-panel').removeClass('shake'); }, 450);
    }
</script>

</body>
</html>