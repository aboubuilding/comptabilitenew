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
                <label class="form-label small">Recherche</label>
                <input type="text" name="search" class="form-control filtre-input" value="{{ $recherche }}" placeholder="Libellé...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
        </form>
    </div>

    <div class="main-card" style="overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-ref align-middle mb-0">
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
                            <td><span class="badge-statut bg-success">Actif</span></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-nationalite"
                                                    data-id="{{ $nationalite->id }}" data-libelle="{{ $nationalite->libelle }}"
                                                    data-bs-toggle="modal" data-bs-target="#nationaliteModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        <li>
                                            <form action="{{ route('administration.nationalites.destroy', $nationalite) }}" method="POST" class="form-confirm-delete"
                                                  data-confirm-title="Désactiver cette nationalité ?"
                                                  data-confirm-text="Elle ne sera plus proposée dans les fiches.">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash"></i> Désactiver
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-flag"></i>
                                    <p class="mb-0">Aucune nationalité enregistrée.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $nationalites->links() }}</div>

    <div class="modal fade" id="nationaliteModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                <form id="nationalite-form">
                    @csrf
                    <input type="hidden" name="_method" id="nationalite-method" value="POST">
                    <div class="modal-header modal-header-ref">
                        <h5 class="modal-title" id="nationalite-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle nationalité</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4">
                        <label class="form-label" for="nationalite-libelle">Libellé <span class="text-danger">*</span></label>
                        <input type="text" name="libelle" id="nationalite-libelle" class="form-control" maxlength="150" placeholder="Ex : Togolaise">
                        <div class="text-danger small mt-1" id="nationalite-libelle-error"></div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn-enregistrer"><i class="fas fa-save"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
