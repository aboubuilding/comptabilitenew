@extends('admin.layouts.app')

@section('title', 'Classes')
@section('page_title', 'Classes')
@section('page_icon', 'fa-chalkboard')

@section('breadcrumb')
    <li><a href="{{ route('tableau') }}">Accueil</a></li>
    <li>Scolarité</li>
    <li>Classes</li>
@endsection

@section('page_actions')
    <a href="{{ route('scolarite.niveaux.index') }}" class="btn-outline-scolarite"><i class="fas fa-flag"></i> Niveaux</a>
    @can('structure.gerer')
        <button type="button" class="btn-nouveau" id="btnNouvelleClasse" data-bs-toggle="modal" data-bs-target="#classeModal">
            <i class="fas fa-plus-circle"></i> Nouvelle classe
        </button>
    @endcan
@endsection

@section('css')
<style>
    .table-classes thead tr { background: var(--school-paper); }
    .table-classes thead th { color: var(--school-red); font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--school-paper-line); padding: .9rem 1rem; }
    .table-classes tbody td { padding: .85rem 1rem; border-bottom: 1px solid var(--school-paper-line); font-size: .92rem; }
    .table-classes tbody tr:last-child td { border-bottom: none; }
    .effectif { display: inline-block; min-width: 2.2rem; text-align: center; background: var(--school-paper); border: 1px solid var(--school-paper-line); border-radius: 999px; font-weight: 700; color: var(--school-red); padding: .15rem .6rem; }
    .modal-header-classes { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
</style>
@endsection

@section('contenu')
    <div class="alert alert-light border small mb-3">
        <i class="fas fa-calendar-alt me-1" style="color:var(--school-gold-dark);"></i>
        Les classes sont propres à chaque année scolaire : cette liste est celle de l'année sélectionnée dans l'en-tête.
    </div>

    <div class="main-card p-3 mb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Cycle</label>
                <select name="cycle_id" class="form-select filtre-input">
                    <option value="">Tous les cycles</option>
                    @foreach ($cyclesOptions as $cycle)
                        <option value="{{ $cycle->id }}" @selected(request('cycle_id') == $cycle->id)>{{ $cycle->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Niveau</label>
                <select name="niveau_id" class="form-select filtre-input">
                    <option value="">Tous les niveaux</option>
                    @foreach ($niveauxOptions as $niveau)
                        <option value="{{ $niveau->id }}" @selected(request('niveau_id') == $niveau->id)>{{ $niveau->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Recherche</label>
                <input type="text" name="search" class="form-control filtre-input" value="{{ request('search') }}" placeholder="Libellé de la classe...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100"><i class="fas fa-filter"></i> Filtrer</button>
            </div>
        </form>
    </div>

    <div class="main-card" style="overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-classes align-middle mb-0">
                <thead>
                    <tr>
                        <th>Classe</th>
                        <th>Niveau</th>
                        <th>Cycle</th>
                        <th>Emplacement</th>
                        <th class="text-center">Élèves inscrits</th>
                        <th class="text-center" style="width:80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classes as $classe)
                        <tr>
                            <td><strong>{{ $classe->libelle }}</strong></td>
                            <td>{{ $classe->niveau?->libelle ?? '—' }}</td>
                            <td>{{ $classe->cycle?->libelle ?? '—' }}</td>
                            <td class="text-muted">{{ $classe->emplacement ?: '—' }}</td>
                            <td class="text-center"><span class="effectif">{{ $classe->effectif }}</span></td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('structure.gerer')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-classe" data-id="{{ $classe->id }}"
                                                        data-bs-toggle="modal" data-bs-target="#classeModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li>
                                                <form action="{{ route('scolarite.classes.destroy', $classe) }}" method="POST" class="form-confirm-delete"
                                                      data-confirm-title="Désactiver cette classe ?"
                                                      data-confirm-text="Une classe qui compte des élèves inscrits ne peut pas être désactivée.">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash"></i> Désactiver
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
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-chalkboard"></i>
                                    <p class="mb-0">Aucune classe pour cette année scolaire.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $classes->links() }}</div>

    @can('structure.gerer')
        <div class="modal fade" id="classeModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
                    <form id="classe-form">
                        @csrf
                        <input type="hidden" name="_method" id="classe-method" value="POST">
                        <div class="modal-header modal-header-classes">
                            <h5 class="modal-title" id="classe-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle classe</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label" for="classe-libelle">Libellé <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="classe-libelle" class="form-control" maxlength="255" placeholder="Ex : CP A">
                                <div class="text-danger small mt-1" id="classe-libelle-error"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="classe-niveau">Niveau <span class="text-danger">*</span></label>
                                <select name="niveau_id" id="classe-niveau" class="form-select">
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($niveauxOptions as $niveau)
                                        <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="text-danger small mt-1" id="classe-niveau_id-error"></div>
                                <div class="form-text small">Le cycle de la classe est celui de son niveau.</div>
                            </div>
                            <div>
                                <label class="form-label" for="classe-emplacement">Emplacement</label>
                                <input type="text" name="emplacement" id="classe-emplacement" class="form-control" maxlength="255" placeholder="Ex : Bâtiment A, salle 3">
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn-annuler" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn-enregistrer"><i class="fas fa-save"></i> Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
@endsection

@push('js')
<script>
$(function () {
    const modalEl = document.getElementById('classeModal');
    if (!modalEl) return;

    const modal  = new bootstrap.Modal(modalEl);
    const $form  = $('#classe-form');
    const $title = $('#classe-modal-title');
    const erreurs = { libelle: $('#classe-libelle-error'), niveau_id: $('#classe-niveau_id-error') };
    let currentId = null;

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $('#classe-method').val('POST');
        Object.values(erreurs).forEach($e => $e.text(''));
        $form.find('.is-invalid').removeClass('is-invalid');
    }

    $('#btnNouvelleClasse').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouvelle classe');
    });

    $(document).on('click', '.btn-edit-classe', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier la classe');
        $('#classe-method').val('PUT');

        $.getJSON(`{{ url('scolarite/classes') }}/${currentId}/edit`, function (data) {
            $('#classe-libelle').val(data.classe.libelle);
            $('#classe-niveau').val(data.classe.niveau_id);
            $('#classe-emplacement').val(data.classe.emplacement);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        Object.values(erreurs).forEach($e => $e.text(''));
        $form.find('.is-invalid').removeClass('is-invalid');

        const url = currentId ? `{{ url('scolarite/classes') }}/${currentId}` : '{{ route('scolarite.classes.store') }}';

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function (champ) {
                        $form.find(`[name="${champ}"]`).addClass('is-invalid');
                        if (erreurs[champ]) erreurs[champ].text(xhr.responseJSON.errors[champ][0]);
                    });
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });
});
</script>
@endpush
