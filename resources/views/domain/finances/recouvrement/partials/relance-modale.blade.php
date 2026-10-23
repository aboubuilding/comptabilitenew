{{--
    Relance par message (email ou SMS), à un élève ou à un groupe.
    Ouverture : window.ouvrirRelance({ cible: 'dossiers'|'inscriptions', ids: [...], parametres: { jour, nature, niveau_id, libelle, tous }, nombre })
    (« tous » : le serveur relance tous les élèves répondant aux filtres ; « nombre » en donne le compte affiché)
--}}
@can('recouvrement.relancer')
<x-modale id="relanceMessageModal" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" form-contenu croix-blanche>
    <x-slot:formulaire id="relance-message-form" method="POST" action="{{ route('finances.recouvrement.relances') }}">@csrf
        <div id="relance-cibles"></div></x-slot:formulaire>
    <x-slot:titre><i class="fas fa-paper-plane me-2"></i>Relancer — <span id="relance-nombre">0</span> élève(s)</x-slot:titre>
    <div class="mb-3">
        <x-libelle requis>Canal</x-libelle>
        <div class="d-flex gap-3">
            @foreach ($canaux as $valeur => $libelle)
                <div class="form-check">
                    <input class="form-check-input relance-canal" type="radio" name="canal" id="relance-canal-{{ $valeur }}" value="{{ $valeur }}" @checked($loop->first)>
                    <label class="form-check-label" for="relance-canal-{{ $valeur }}">
                        <i class="fas {{ $valeur === 'sms' ? 'fa-sms' : 'fa-envelope' }} me-1"></i>{{ $libelle }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>
    <div class="mb-2">
        <x-libelle for="relance-message" requis>Message</x-libelle>
        <textarea name="message" id="relance-message" class="form-control" rows="6" maxlength="2000" required></textarea>
        <div class="form-text small">
            Variables remplacées pour chaque élève :
            <code>{eleve}</code> <code>{matricule}</code> <code>{classe}</code> <code>{montant}</code> <code>{echeances}</code> <code>{date}</code>
            <span class="ms-2" id="relance-longueur"></span>
        </div>
        <div class="text-danger small mt-1" id="relance-erreur"></div>
    </div>
    <div class="small text-muted"><i class="fas fa-info-circle me-1"></i>Le message est adressé au parent principal (son email ou son téléphone) ; il est enregistré « à envoyer » dans l'historique des relances.</div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit" id="relance-valider"><i class="fas fa-paper-plane me-1"></i>Enregistrer les relances</x-bouton>
    </x-slot:pied>
</x-modale>

@push('js')
<script>
(function () {
    const MODELES = @json($modeles);
    const $form = $('#relance-message-form');
    const $message = $('#relance-message');
    let modifie = false;

    const majLongueur = () => $('#relance-longueur').text($('[name=canal]:checked').val() === 'sms' ? `(${$message.val().length} caractères)` : '');
    $(document).on('change', '.relance-canal', function () {
        if (!modifie) $message.val(MODELES[$(this).val()] || '');
        majLongueur();
    });
    $message.on('input', function () { modifie = true; majLongueur(); });

    window.ouvrirRelance = function ({ cible, ids = [], parametres = {}, nombre = null }) {
        if (!ids.length && !nombre) { window.showToast('Sélectionnez au moins un élève.', 'error'); return; }
        const $cibles = $('#relance-cibles').empty();
        ids.forEach(id => $cibles.append($('<input type="hidden">').attr('name', cible + '[]').val(id)));
        Object.entries(parametres).forEach(([cle, valeur]) => valeur && $cibles.append($('<input type="hidden">').attr('name', cle).val(valeur)));
        $('#relance-nombre').text(nombre ?? ids.length);
        $('#relance-erreur').text('');
        modifie = false;
        $message.val(MODELES[$('[name=canal]:checked').val()] || '');
        majLongueur();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('relanceMessageModal')).show();
    };

    $form.on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#relance-valider').prop('disabled', true);
        $.ajax({ url: $form.attr('action'), method: 'POST', data: $form.serialize(), headers: { 'Accept': 'application/json' } })
            .done(res => {
                const b = res.data?.bilan || {};
                bootstrap.Modal.getInstance(document.getElementById('relanceMessageModal'))?.hide();
                window.showToastThenReload(`${b.relances ?? 0} relance(s) enregistrée(s)` + (b.sans_destinataire ? `, dont ${b.sans_destinataire} sans destinataire` : '') + '.', 'success');
            })
            .fail(xhr => {
                $btn.prop('disabled', false);
                const erreurs = xhr.responseJSON?.errors;
                $('#relance-erreur').text(erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'Une erreur est survenue.'));
            });
    });
})();
</script>
@endpush
@endcan
