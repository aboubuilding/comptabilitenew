{{-- resources/views/domain/services-eleves/bibliotheque/referentiels/_partials/list.blade.php --}}
@can('bibliotheque.gerer')
<div class="ref-add-form">
    <input type="text" placeholder="Ajouter un {{ strtolower($label) }}..."
           data-type="{{ $type }}" class="ref-new-input" maxlength="200">
    <button type="button" class="ref-add-btn" data-type="{{ $type }}">
        <i class="fas fa-plus me-1"></i> Ajouter
    </button>
</div>
@endcan

@forelse ($items as $item)
    <div class="ref-item" data-id="{{ $item->id }}" data-type="{{ $type }}">
        <div>
            <div class="ref-item-label" data-field-value="{{ $item->{$field} }}">{{ $item->{$field} }}</div>
            @if (method_exists($item, 'livres'))
                <div class="ref-item-count">{{ $item->livres()->count() }} livre(s)</div>
            @endif
        </div>
        @can('bibliotheque.gerer')
            <div class="ref-actions">
                <button type="button" class="btn-ref-action ref-edit-btn" data-type="{{ $type }}" data-id="{{ $item->id }}">
                    <i class="fas fa-pen"></i>
                </button>
                <button type="button" class="btn-ref-action ref-del-btn" data-type="{{ $type }}" data-id="{{ $item->id }}">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        @endcan
    </div>
@empty
    <div class="p-4 text-center text-muted">
        <i class="fas fa-inbox fa-2x mb-2 d-block" style="color:#d8c8ae;"></i>
        Aucun élément enregistré.
    </div>
@endforelse

@push('js')
<script>
$(function () {
    // Ajouter
    $(document).on('click', '.ref-add-btn', function () {
        const type = $(this).data('type');
        const $input = $(`.ref-new-input[data-type="${type}"]`);
        const libelle = $input.val().trim();
        if (! libelle) { window.showToast('Veuillez saisir un libellé.', 'error'); return; }

        $.ajax({
            url: '{{ route("services-eleves.bibliotheque.referentiels.store") }}',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', type, libelle },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                window.showToastThenReload(res.message || 'Ajouté.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    // Modifier
    $(document).on('click', '.ref-edit-btn', function () {
        const type = $(this).data('type');
        const id = $(this).data('id');
        const $item = $(`.ref-item[data-id="${id}"][data-type="${type}"]`);
        const ancien = $item.find('.ref-item-label').text().trim();
        const nouveau = prompt('Nouveau libellé :', ancien);
        if (! nouveau || nouveau === ancien) return;

        $.ajax({
            url: `{{ url('services-eleves/bibliotheque/referentiels') }}/${type}/${id}`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'PUT', libelle: nouveau },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                window.showToastThenReload(res.message || 'Modifié.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    // Désactiver
    $(document).on('click', '.ref-del-btn', function () {
        const type = $(this).data('type');
        const id = $(this).data('id');
        if (! confirm('Désactiver cet élément ?')) return;

        $.ajax({
            url: `{{ url('services-eleves/bibliotheque/referentiels') }}/${type}/${id}`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' },
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                window.showToastThenReload(res.message || 'Désactivé.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush