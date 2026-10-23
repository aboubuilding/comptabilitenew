@extends('admin.layouts.app')

@section('page_title', 'Modèles de messages')
@section('page_icon', 'fa-file-lines')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('administration.messages.index') }}">Communications</a></li>
    <li class="breadcrumb-item active">Modèles</li>
@endsection

@section('page_actions')
    @can('modeles.gerer')
    <button type="button" class="btn-nouveau" id="btn-nouveau-modele">
        <i class="fa fa-plus"></i> Nouveau modèle
    </button>
    @endcan
@endsection

@section('contenu')
    <div class="main-card p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}" placeholder="Code, libellé, sujet...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100"><i class="fa fa-filter"></i> Filtrer</button>
            </div>
        </form>
    </div>

    <div class="main-card">
        <x-tableau class="table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Libellé</th>
                        <th>Sujet</th>
                        <th>Type</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modeles as $m)
                        <tr>
                            <td><code>{{ $m->code }}</code></td>
                            <td><strong>{{ $m->libelle }}</strong></td>
                            <td>{{ $m->sujet }}</td>
                            <td>{{ $m->type ?? '—' }}</td>
                            <td class="text-end">
                                <x-actions>
                                        @can('modeles.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-modele"
                                                    data-url="{{ route('administration.modeles.edit', $m) }}">
                                                <i class="fa fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        @can('modeles.gerer')
                                        <x-actions.supprimer :action="route('administration.modeles.destroy', $m)" icone="fa fa-trash">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" :paragraphe="false" class="text-center py-4">Aucun modèle.</x-vide>
                    @endforelse
                </tbody>
            </x-tableau>
        <div class="p-3"><x-pagination :liste="$modeles" /></div>
    </div>

    <x-modale id="modalModele" aria-hidden="true" dialogue="modal-lg modal-dialog-centered" contenu-id="modalModeleContent" />
@endsection

@push('js')
<script>
$(function () {
    const modalEl  = document.getElementById('modalModele');
    const modal    = new bootstrap.Modal(modalEl);
    const $contenu = $('#modalModeleContent');

    function charger(url) {
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get(url, html => $contenu.html(html))
         .fail(() => $contenu.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    }

    $('#btn-nouveau-modele').on('click', () => charger("{{ route('administration.modeles.create') }}"));
    $(document).on('click', '.btn-edit-modele', function () { charger($(this).data('url')); });

    $(document).on('submit', '#formModele', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url   = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $.ajax({
            url, type: 'POST',
            data: $form.serialize() + '&_method=' + methode,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modal.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
            }
        });
    });

    modalEl.addEventListener('hidden.bs.modal', () => $contenu.html(''));
});
</script>
@endpush