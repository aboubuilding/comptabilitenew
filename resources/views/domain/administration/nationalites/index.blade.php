@extends('admin.layouts.app')

@section('title', 'Nationalités')
@section('page_title', 'Référentiel des nationalités')
@section('page_icon', 'fa-flag')

@section('breadcrumb')
    <li><a href="{{ route('tableau') }}">Accueil</a></li>
    <li>Administration</li>
    <li>Référentiels</li>
    <li>Nationalités</li>
@endsection

@section('page_actions')
    <button type="button" class="btn-nouveau" id="btnNouvelleNationalite" data-bs-toggle="modal" data-bs-target="#nationaliteModal">
        <i class="fas fa-plus-circle"></i> Nouvelle nationalité
    </button>
@endsection

@section('css')
<style>
    .table-ref thead tr { background: var(--school-paper); }
    .table-ref thead th { color: var(--school-red); font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--school-paper-line); padding: .9rem 1rem; }
    .table-ref tbody td { padding: .85rem 1rem; border-bottom: 1px solid var(--school-paper-line); font-size: .92rem; }
    .table-ref tbody tr:last-child td { border-bottom: none; }
    .modal-header-ref { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
</style>
@endsection

@section('contenu')
    <div class="alert alert-light border small mb-3">
        <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
        Valeurs proposées dans les fiches élèves et parents. Les autres référentiels se gèrent dans leur module :
        <a href="{{ route('services-eleves.transport.zones.index') }}">zones</a> et
        <a href="{{ route('services-eleves.transport.lignes.index') }}">lignes de transport</a>.
    </div>

    <div class="main-card p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <x-libelle class="small">Recherche</x-libelle>
                <input type="text" name="search" class="form-control filtre-input" value="{{ $recherche }}" placeholder="Libellé...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
        </form>
    </div>

    <div class="main-card" style="overflow:hidden;">
        <x-tableau class="table-ref align-middle mb-0">
                <thead>
                    <tr>
                        <th>Libellé</th>
                        <th>Type de référentiel</th>
                        <th class="text-center">Élèves concernés</th>
                        <th>Statut</th>
                        <th class="text-center" style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nationalites as $nationalite)
                        <tr>
                            <td><strong>{{ $nationalite->libelle }}</strong></td>
                            <td class="text-muted">Nationalité</td>
                            <td class="text-center">{{ $nationalite->eleves_count }}</td>
                            <td><x-badge class="bg-success">Actif</x-badge></td>
                            <td class="text-center">
                                <x-actions>
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-nationalite"
                                                    data-id="{{ $nationalite->id }}" data-libelle="{{ $nationalite->libelle }}"
                                                    data-bs-toggle="modal" data-bs-target="#nationaliteModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        <x-actions.supprimer :action="route('administration.nationalites.destroy', $nationalite)" data-confirm-title="Désactiver cette nationalité ?" data-confirm-text="Elle ne sera plus proposée dans les fiches." />
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-flag" :paragraphe="false"><p class="mb-0">Aucune nationalité enregistrée.</p></x-vide>
                    @endforelse
                </tbody>
            </x-tableau>
    </div>

    <div class="mt-3"><x-pagination :liste="$nationalites" /></div>

    <x-modale id="nationaliteModal" data-bs-backdrop="static" aria-hidden="true" contenu-style="border:none;border-radius:16px;overflow:hidden;" entete-class="modal-header-ref" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="nationalite-form">@csrf
                    <input type="hidden" name="_method" id="nationalite-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="nationalite-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle nationalité</x-slot:titre>
        <x-libelle for="nationalite-libelle" requis>Libellé</x-libelle>
        <input type="text" name="libelle" id="nationalite-libelle" class="form-control" maxlength="150" placeholder="Ex : Togolaise">
        <div class="text-danger small mt-1" id="nationalite-libelle-error"></div>
        <x-slot:pied class="bg-light px-4 py-3">
            <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn-enregistrer"><i class="fas fa-save"></i> Enregistrer</button>
        </x-slot:pied>
    </x-modale>
@endsection

@push('js')
<script>
$(function () {
    const modal   = new bootstrap.Modal(document.getElementById('nationaliteModal'));
    const $form   = $('#nationalite-form');
    const $title  = $('#nationalite-modal-title');
    const $champ  = $('#nationalite-libelle');
    const $erreur = $('#nationalite-libelle-error');
    let currentId = null;

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $('#nationalite-method').val('POST');
        $erreur.text('');
        $champ.removeClass('is-invalid');
    }

    $('#btnNouvelleNationalite').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouvelle nationalité');
    });

    $(document).on('click', '.btn-edit-nationalite', function () {
        resetForm();
        currentId = $(this).data('id');
        $('#nationalite-method').val('PUT');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier la nationalité');
        $champ.val($(this).data('libelle'));
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $erreur.text('');
        $champ.removeClass('is-invalid');

        const url = currentId
            ? `{{ url('administration/nationalites') }}/${currentId}`
            : '{{ route('administration.nationalites.store') }}';

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors?.libelle) {
                    $champ.addClass('is-invalid');
                    $erreur.text(xhr.responseJSON.errors.libelle[0]);
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
