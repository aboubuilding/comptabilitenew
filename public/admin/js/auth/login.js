/* =============================================
   login.js — Ecole Internationale Mariam
   Gestion avancée des notifications et erreurs
   ============================================= */

// ============================================
// CONFIGURATION
// ============================================

const CONFIG = {
    toastDuration: 4000,
    redirectDelay: 800,
    maxRetries: 3,
    retryDelay: 1000,
};

// ============================================
// INITIALISATION
// ============================================

jQuery(document).ready(function () {
    initForm();
    bindEvents();
    checkSession();
});

function initForm() {
    clearData();
    // Afficher les erreurs de session si présentes
    const errorMsg = $('#session-error').data('message');
    if (errorMsg) {
        showToast('error', errorMsg);
    }
}

function bindEvents() {
    $('#btn-login').on('click', function (e) {
        e.preventDefault();
        handleLogin();
    });

    $('#login, #mot_passe').on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleLogin();
        }
    });

    // Auto-focus sur le premier champ
    $('#login').focus();

    // Validation en temps réel
    $('#login').on('blur', function() {
        validateField('login');
    });

    $('#mot_passe').on('blur', function() {
        validateField('mot_passe');
    });

    // Nettoyer les erreurs en tapant
    $('#login, #mot_passe').on('input', function() {
        const field = $(this).attr('id');
        clearFieldError(field);
    });
}

// ============================================
// VALIDATION DES CHAMPS
// ============================================

function validateField(field) {
    const value = $('#' + field).val().trim();
    const errorId = field === 'login' ? 'error-login' : 'error-motpasse';

    if (value === '') {
        showFieldError(errorId);
        return false;
    } else {
        clearFieldError(errorId);
        return true;
    }
}

function clearFieldError(errorId) {
    $('#' + errorId).removeClass('show');
}

function clearErrors() {
    $('#error-login').removeClass('show');
    $('#error-motpasse').removeClass('show');
    $('#alert-serveur').removeClass('show');
    $('#erreurserveur').text('');
}

function showFieldError(id) {
    $('#' + id).addClass('show');
}

/* ── Réinitialise les champs ── */
function clearData() {
    $('#login').val('');
    $('#mot_passe').val('');
    clearErrors();
}

// ============================================
// GESTION DE L'AUTHENTIFICATION
// ============================================

let loginAttempts = 0;

function handleLogin() {
    // Validation des champs
    const isLoginValid = validateField('login');
    const isPasswordValid = validateField('mot_passe');

    if (!isLoginValid || !isPasswordValid) {
        showToast('error', 'Veuillez remplir tous les champs obligatoires.');
        return;
    }

    const login = $('#login').val().trim();
    const password = $('#mot_passe').val();

    // Vérifier la longueur minimale du mot de passe
    if (password.length < 6) {
        showFieldError('error-motpasse');
        $('#error-motpasse span').text('Le mot de passe doit contenir au moins 6 caractères.');
        showToast('error', 'Le mot de passe doit contenir au moins 6 caractères.');
        return;
    }

    authentifier(login, password);
}

/* ── Authentification AJAX ── */
function authentifier(login, password) {
    setLoading(true);
    clearErrors();

    // Incrémenter le compteur de tentatives
    loginAttempts++;

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
            password: password
        },
        timeout: 10000, // Timeout de 10 secondes

        success: function(data) {
            console.log('Réponse serveur:', data);

            if (data.success) {
                // Réinitialiser le compteur de tentatives
                loginAttempts = 0;

                // Notification de succès
                showToast('success', data.message || 'Connexion réussie ! Bienvenue.');

                // Redirection après un délai
                setTimeout(function() {
                    window.location.href = data.redirect || TABLEAU_ROUTE;
                }, CONFIG.redirectDelay);
            } else {
                handleLoginError(data.message || 'Identifiants incorrects.', data.code);
            }
        },

        error: function(xhr) {
            console.error('Erreur AJAX:', xhr);
            handleAjaxError(xhr);
        }
    });
}

// ============================================
// GESTION DES ERREURS
// ============================================

function handleLoginError(message, code) {
    setLoading(false);

    // Messages personnalisés selon le code d'erreur
    const errorMessages = {
        'USER_NOT_FOUND': 'Aucun compte trouvé avec ces identifiants.',
        'INVALID_PASSWORD': 'Mot de passe incorrect. Veuillez réessayer.',
        'ACCOUNT_INACTIVE': 'Votre compte est désactivé. Contactez l\'administrateur.',
        'NO_ACTIVE_YEAR': 'Aucune année scolaire active. Contactez l\'administrateur.',
        'ACCOUNT_LOCKED': 'Compte verrouillé. Contactez l\'administrateur.',
        'TOO_MANY_ATTEMPTS': 'Trop de tentatives. Veuillez attendre 15 minutes.',
    };

    const userMessage = errorMessages[code] || message || 'Identifiants incorrects.';

    // Afficher l'erreur dans le formulaire
    showServerError(userMessage);

    // Toast d'erreur
    showToast('error', userMessage);

    // Secouer le formulaire pour attirer l'attention
    shakeForm();

    // Focus sur le champ login
    $('#login').focus().select();
}

function handleAjaxError(xhr) {
    setLoading(false);

    let msg = 'Une erreur inattendue est survenue. Veuillez réessayer.';
    let showModal = false;
    let title = 'Erreur';
    let icon = 'error';

    switch (xhr.status) {
        case 0:
            msg = 'Impossible de se connecter au serveur. Vérifiez votre connexion internet.';
            icon = 'error';
            showModal = true;
            break;

        case 400:
            msg = 'Requête invalide. Veuillez vérifier vos informations.';
            break;

        case 401:
            try {
                const response = JSON.parse(xhr.responseText);
                msg = response.message || 'Identifiants incorrects.';
            } catch(e) {
                msg = 'Identifiants incorrects. Veuillez réessayer.';
            }
            // Secouer le formulaire
            shakeForm();
            break;

        case 403:
            msg = 'Accès interdit. Vous n\'avez pas les permissions nécessaires.';
            icon = 'warning';
            showModal = true;
            break;

        case 419:
            msg = 'Votre session a expiré. Veuillez rafraîchir la page et réessayer.';
            title = 'Session expirée';
            icon = 'warning';
            showModal = true;
            break;

        case 422:
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.errors) {
                    const errors = Object.values(response.errors).flat();
                    msg = errors.join(' ');
                } else if (response.message) {
                    msg = response.message;
                }
            } catch(e) {
                msg = 'Données invalides. Veuillez vérifier vos informations.';
            }
            break;

        case 429:
            msg = 'Trop de tentatives. Veuillez attendre quelques minutes.';
            title = 'Trop de tentatives';
            icon = 'warning';
            showModal = true;
            break;

        case 500:
            msg = 'Erreur interne du serveur. Veuillez contacter l\'administrateur.';
            title = 'Erreur serveur';
            icon = 'error';
            showModal = true;
            break;

        case 503:
            msg = 'Service indisponible. Veuillez réessayer plus tard.';
            title = 'Service indisponible';
            icon = 'warning';
            showModal = true;
            break;

        default:
            msg = 'Erreur ' + xhr.status + '. Veuillez réessayer ou contacter le support.';
            showModal = true;
    }

    // Afficher l'erreur
    showServerError(msg);
    showToast('error', msg);

    if (showModal) {
        showNotificationModal(title, msg, icon);
    }

    // Si c'est une erreur de validation, focus sur le champ en erreur
    if (xhr.status === 422) {
        try {
            const response = JSON.parse(xhr.responseText);
            if (response.errors) {
                if (response.errors.login) {
                    $('#login').focus().select();
                } else if (response.errors.password) {
                    $('#mot_passe').focus().select();
                }
            }
        } catch(e) {}
    }
}

// ============================================
// NOTIFICATIONS AVANCÉES
// ============================================

/* ── Toast notification avec gestion de file d'attente ── */
let toastQueue = [];
let isToastShowing = false;

function showToast(type, message, duration = CONFIG.toastDuration) {
    if (!message) return;

    // Types de toasts
    const config = {
        success: { icon: 'fa-check-circle', class: 'toast-success' },
        error: { icon: 'fa-exclamation-circle', class: 'toast-error' },
        warning: { icon: 'fa-triangle-exclamation', class: 'toast-warning' },
        info: { icon: 'fa-circle-info', class: 'toast-info' }
    };

    const conf = config[type] || config.info;
    const icon = conf.icon;
    const className = conf.class;

    // Créer le toast
    const toast = $('<div class="toast ' + className + '"></div>')
        .html('<i class="fas ' + icon + '"></i><span>' + message + '</span>')
        .appendTo('#toast-container');

    // Animation d'entrée
    toast.css('opacity', '0').animate({ opacity: 1 }, 300);

    // Auto-suppression
    setTimeout(function() {
        toast.animate({ opacity: 0, marginTop: '-20px' }, 300, function() {
            toast.remove();
        });
    }, duration);
}

/* ── Modal de notification ── */
function showNotificationModal(title, message, type = 'info') {
    const modal = $('#notificationModal');
    const iconMap = {
        error: 'fa-exclamation-circle',
        warning: 'fa-triangle-exclamation',
        success: 'fa-check-circle',
        info: 'fa-circle-info'
    };

    const colorMap = {
        error: '#dc3545',
        warning: '#ffc107',
        success: '#28a745',
        info: '#17a2b8'
    };

    $('#modalIcon').html('<i class="fas ' + (iconMap[type] || iconMap.info) + '"></i>')
        .css('color', colorMap[type] || colorMap.info);
    $('#modalTitle').text(title || 'Information');
    $('#modalMessage').text(message);

    modal.addClass('show');

    // Fermeture automatique après 10 secondes
    setTimeout(function() {
        closeModal();
    }, 10000);
}

function closeModal() {
    $('#notificationModal').removeClass('show');
}

// Fermeture du modal
$(document).on('click', '.modal-overlay, #modalBtn', function() {
    closeModal();
});

/* ── Secouer le formulaire ── */
function shakeForm() {
    const form = $('#form-login');
    form.addClass('shake');
    setTimeout(function() {
        form.removeClass('shake');
    }, 500);
}

// ============================================
// ÉTAT DE CHARGEMENT
// ============================================

function setLoading(state) {
    const btn = $('#btn-login');
    const text = $('.btn-text');
    const spinner = $('.btn-spinner');

    if (state) {
        btn.addClass('loading').prop('disabled', true);
        text.text('Connexion en cours...');
        spinner.show();
    } else {
        btn.removeClass('loading').prop('disabled', false);
        text.text('Se connecter');
        spinner.hide();
    }
}

// ============================================
// GESTION DES COMPOSANTS UI
// ============================================

/* ── Toggle affichage mot de passe ── */
function bindTogglePassword() {
    $('#toggle-pw').on('click', function() {
        const inp = $('#mot_passe');
        const ico = $('#eye-icon');
        const type = inp.attr('type');

        if (type === 'password') {
            inp.attr('type', 'text');
            ico.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            inp.attr('type', 'password');
            ico.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
}

/* ── Fermeture alerte serveur ── */
function bindAlertClose() {
    $(document).on('click', '.alert-close', function() {
        const alert = $(this).closest('.alert-box');
        alert.removeClass('show');
        setTimeout(function() {
            alert.css('display', 'none');
        }, 300);
    });
}

/* ── Mot de passe oublié ── */
function bindForgotPassword() {
    $('#forgotLink').on('click', function(e) {
        e.preventDefault();
        showToast('info', 'Veuillez contacter l\'administrateur pour réinitialiser votre mot de passe.');
    });
}

/* ── Vérification de session ── */
function checkSession() {
    $.ajax({
        url: '/check-session',
        type: 'GET',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(data) {
            if (data.authenticated) {
                // Si déjà connecté, rediriger
                window.location.href = TABLEAU_ROUTE;
            }
        },
        error: function() {
            // Pas de session active, c'est normal
        }
    });
}

// ============================================
// GESTION DES ERREURS SERVEUR
// ============================================

function showServerError(msg) {
    $('#erreurserveur').text(msg);
    const alert = $('#alert-serveur');
    alert.addClass('show');

    // Auto-fermeture après 8 secondes
    clearTimeout(window.alertTimeout);
    window.alertTimeout = setTimeout(function() {
        alert.removeClass('show');
    }, 8000);
}

// ============================================
// UTILITAIRES
// ============================================

// Gestion des erreurs globales
window.onerror = function(msg, url, line, col, error) {
    console.error('Erreur globale:', msg, error);
    // Ne pas afficher d'erreur utilisateur pour les erreurs de script
};

// Gestion des promesses non capturées
window.addEventListener('unhandledrejection', function(e) {
    console.error('Promesse non gérée:', e.reason);
    e.preventDefault();
});

// ============================================
// EXPORT (si nécessaire)
// ============================================

// Fonctions disponibles globalement
window.showToast = showToast;
window.showNotificationModal = showNotificationModal;
window.closeModal = closeModal;
