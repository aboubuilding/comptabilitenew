/* =============================================
   login.js — Ecole Internationale Mariam
   AJAX équivalent du login.blade.php original
   ============================================= */

jQuery(document).ready(function () {

    clearData();
    bindTogglePassword();
    bindAlertClose();
    bindForgotPassword();

    $('#btn-login').on('click', function (e) {
        e.preventDefault();
        authentifier();
    });

    $('#login, #mot_passe').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); authentifier(); }
    });
});

/* ── Réinitialise les champs ── */
function clearData() {
    $('#login').val('');
    $('#mot_passe').val('');
    clearErrors();
}

function clearErrors() {
    $('#error-login').removeClass('show');
    $('#error-motpasse').removeClass('show');
    $('#alert-serveur').removeClass('show');
    $('#erreurserveur').text('');
}

/* ── Erreur inline sous un champ ── */
function showFieldError(id) {
    $('#' + id).addClass('show');
}

/* ── Toast notification ── */
function showToast(type, message) {
    var icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
    var toast = $('<div class="toast ' + type + '"></div>')
        .html('<i class="fas ' + (icons[type] || 'fa-info-circle') + '"></i> ' + message);
    $('#toast-container').append(toast);
    setTimeout(function () { toast.fadeOut(400, function () { $(this).remove(); }); }, 3500);
}

/* ── Toggle affichage mot de passe ── */
function bindTogglePassword() {
    $('#toggle-pw').on('click', function () {
        var inp = $('#mot_passe');
        var ico = $('#eye-icon');
        if (inp.attr('type') === 'password') {
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
    $(document).on('click', '.alert-close', function () {
        $(this).closest('.alert-box').removeClass('show');
    });
}

/* ── Mot de passe oublié ── */
function bindForgotPassword() {
    $('#forgotLink').on('click', function (e) {
        e.preventDefault();
        showToast('info', 'Veuillez contacter l\'administrateur pour réinitialiser votre mot de passe.');
    });
}

/* ── Authentification AJAX (équivalent de l'original) ── */
function authentifier() {

    var allValid = true;
    var login     = $('#login').val().trim();
    var mot_passe = $('#mot_passe').val();

    clearErrors();

    if (login === '') {
        showFieldError('error-login');
        allValid = false;
    }

    if (mot_passe === '') {
        showFieldError('error-motpasse');
        allValid = false;
    }

    if (!allValid) return;

    setLoading(true);

    $.ajax({
        dataType : 'json',
        type     : 'POST',
        url      : LOGIN_ROUTE,
        headers  : { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        data     : { login: login, mot_passe: mot_passe },

        success: function (data) {
            console.log(data);
            if (data.code == 1) {
                showToast('success', 'Connexion réussie ! Redirection…');
                setTimeout(function () { location.href = TABLEAU_ROUTE; }, 800);
            } else {
                setLoading(false);
                showServerError(data.msg || 'Identifiants incorrects.');
            }
        },

        error: function (xhr) {
            console.log(xhr);
            setLoading(false);
            var msg = (xhr.status === 419)
                ? 'Session expirée. Veuillez rafraîchir la page.'
                : 'Erreur serveur. Veuillez réessayer.';
            showServerError(msg);
        }
    });
}

function setLoading(state) {
    var btn = $('#btn-login');
    state ? btn.addClass('loading').prop('disabled', true)
           : btn.removeClass('loading').prop('disabled', false);
}

function showServerError(msg) {
    $('#erreurserveur').text(msg);
    $('#alert-serveur').addClass('show');
}