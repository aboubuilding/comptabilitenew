<script>
/**
 * Saisie d'un encaissement : charge le détail d'un paiement en attente, propose
 * le montant à encaisser, contrôle le montant reçu (insuffisant, monnaie à
 * rendre, chèque au montant exact) puis valide. Utilisé par l'écran « Nouvel
 * encaissement » et par la fenêtre « Encaisser » des paiements en attente.
 */
window.brancherEncaissement = function ({ $racine, $valider, onSucces }) {
    const URL_PAIEMENT = '{{ url('finances/encaissements/paiement') }}';
    const MODE_CHEQUE = '{{ \App\Domain\Finances\Types\ModePaiement::CHEQUE->value }}';
    const fmt = v => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v);
    const erreur = xhr => {
        const erreurs = xhr.responseJSON?.errors;
        return erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
    };
    const $resume = $racine.find('.enc-resume');
    const $saisie = $racine.find('.enc-saisie');
    const $montant = $racine.find('.enc-montant-recu');
    const $ecart = $racine.find('.enc-ecart');
    const libelleValider = $valider.html();
    let paiementId = null, aEncaisser = 0, parCheque = false;

    function reinitialiser() {
        paiementId = null; aEncaisser = 0;
        $resume.empty();
        $saisie.hide();
        $montant.val('');
        $ecart.empty();
        $valider.prop('disabled', true);
    }

    function majEcart() {
        const recu = parseFloat($montant.val()) || 0;
        let html = '', valide = recu > 0;
        if (recu > 0 && recu < aEncaisser) {
            html = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Il manque ' + fmt(aEncaisser - recu) + ' F : le paiement doit être corrigé par le comptable.</span>';
            valide = false;
        } else if (recu > aEncaisser) {
            if (parCheque) {
                html = '<span class="text-danger">Un chèque s\'encaisse pour son montant exact.</span>';
                valide = false;
            } else {
                html = '<span class="monnaie"><i class="fas fa-coins me-1"></i>Monnaie à rendre : ' + fmt(recu - aEncaisser) + ' F</span>';
            }
        } else if (recu === aEncaisser && recu > 0) {
            html = '<span class="text-success"><i class="fas fa-check me-1"></i>Montant exact</span>';
        }
        $ecart.html(html);
        $valider.prop('disabled', !paiementId || !valide);
    }
    $montant.on('input', majEcart);

    function charger(id) {
        reinitialiser();
        if (!id) return;
        $resume.html('<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i></div>');
        $.get(URL_PAIEMENT + '/' + id + '/resume').done(function (html) {
            $resume.html(html);
            const $r = $resume.find('.resume-paiement');
            paiementId = id;
            aEncaisser = parseFloat($r.data('a-encaisser')) || 0;
            parCheque = String($r.data('mode')) === MODE_CHEQUE;
            $racine.find('.enc-du').text(fmt(aEncaisser));
            $montant.val(aEncaisser);
            $saisie.show();
            majEcart();
            $montant.trigger('focus').trigger('select');
        }).fail(xhr => $resume.html('<div class="text-danger">' + erreur(xhr) + '</div>'));
    }

    $valider.on('click', function () {
        if (!paiementId) return;
        $valider.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Encaissement...');
        $.ajax({
            url: URL_PAIEMENT + '/' + paiementId + '/encaisser',
            method: 'POST',
            data: { _token: $('meta[name=csrf-token]').attr('content'), montant_recu: $montant.val() },
            headers: { 'Accept': 'application/json' },
        }).done(function (res) {
            const monnaie = res.data?.monnaie ?? 0;
            onSucces('Encaissement effectué.' + (monnaie > 0 ? ' Monnaie à rendre : ' + fmt(monnaie) + ' F.' : ''));
        }).fail(function (xhr) {
            window.showToast(erreur(xhr), 'error');
            $valider.prop('disabled', false).html(libelleValider);
        });
    });

    reinitialiser();
    return { charger, reinitialiser };
};
</script>
