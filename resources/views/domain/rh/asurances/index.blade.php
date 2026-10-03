@extends('admin.layouts.app')

@section('page_title', 'Catalogue des assurances')
@section('page_icon', 'fa-building-shield')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('rh.assures.index') }}">Assurances du personnel</a></li>
    <li class="breadcrumb-item active">Catalogue</li>
@endsection

@section('page_actions')
    @can('assurances.gerer')
    <button type="button" class="btn-nouveau" id="btn-nouvelle-asurance">
        <i class="fa fa-plus"></i> Nouvelle assurance
    </button>
    @endcan
    <a href="{{ route('rh.asurances.export', request()->query()) }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
    <a href="{{ route('rh.assures.index') }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-list"></i> Voir les assurés
    </a>
@endsection

@section('contenu')

    {{-- Filtres --}}
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('rh.asurances.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Recherche</label>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}"
                       placeholder="Assureur ou n° de police...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100">
                    <i class="fa fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Assureur</th>
                        <th>N° de police</th>
                        <th class="text-end">Montant annuel</th>
                        <th class="text-center">Nombre d'assurés</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($asurances as $asurance)
                        <tr>
                            <td><strong>{{ $asurance->nom_assureur }}</strong></td>
                            <td>{{ $asurance->police_assurance }}</td>
                            <td class="text-end">
                                @if($asurance->montant_annuel !== null)
                                    {{ number_format((float) $asurance->montant_annuel, 0, ',', ' ') }} FCFA
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-statut bg-info">
                                    {{ $asurance->assures_count }} assuré{{ $asurance->assures_count > 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-action" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('assurances.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-asurance"
                                                    data-url="{{ route('rh.asurances.edit', $asurance) }}">
                                                <i class="fa fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        @can('assurances.gerer')
                                        <li>
                                            <form action="{{ route('rh.asurances.destroy', $asurance) }}"
                                                  method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fa fa-trash"></i> Désactiver
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state text-center py-4">
                                    <i class="fa fa-building-shield fa-2x text-muted"></i>
                                    <p class="mt-2 mb-0">Aucune assurance dans le catalogue.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $asurances->links('vendor.pagination.custom') }}
        </div>
    </div>

    {{-- Modale unique --}}
    <div class="modal fade" id="modalAsurance" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalAsuranceContent"></div>
        </div>
    </div>

@endsection

@push('js')
<script>
$(function () {
    const modalEl  = document.getElementById('modalAsurance');
    const modal    = new bootstrap.Modal(modalEl);
    const $contenu = $('#modalAsuranceContent');

    $('#btn-nouvelle-asurance').on('click', function () {
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get("{{ route('rh.asurances.create') }}", function (html) {
            $contenu.html(html);
        }).fail(function () {
            $contenu.html('<div class="p-4 text-danger">Erreur de chargement du formulaire.</div>');
        });
    });

    $(document).on('click', '.btn-edit-asurance', function () {
        const url = $(this).data('url');
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get(url, function (html) {
            $contenu.html(html);
        }).fail(function () {
            $contenu.html('<div class="p-4 text-danger">Erreur de chargement du formulaire.</div>');
        });
    });

    $(document).on('submit', '#formAsurance', function (e) {
        e.preventDefault();

        const $form   = $(this);
        const url     = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $.ajax({
            url: url,
            type: 'POST',
            data: $form.serialize() + '&_method=' + methode,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (reponse) {
            modal.hide();
            if (window.showToastThenReload) {
                window.showToastThenReload(reponse.message || 'Opération réussie.');
            } else {
                window.location.reload();
            }
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                if (window.showToast) { window.showToast(msg, 'error'); }
                else { alert(msg); }
            }
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        $contenu.html('');
    });
});
</script>
@endpush