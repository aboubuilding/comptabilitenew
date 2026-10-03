@extends('admin.layouts.app')

@section('title', 'Règles de ventilation')
@section('page_icon', 'fa-project-diagram')
@section('page_title', 'Règles de ventilation automatique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.analytique.centres.index') }}">Centres analytiques</a></li>
    <li class="breadcrumb-item active">Règles de ventilation</li>
@endsection

@section('page_actions')
    @can('analytique.gerer')
    <button type="button" class="btn btn-nouveau" id="btnNouvelleRegle"
            data-bs-toggle="modal" data-bs-target="#regleModal">
        <i class="fas fa-plus-circle me-1"></i> Nouvelle règle
    </button>
    @endcan
@endsection

@section('contenu')
<div>
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="nature" class="form-select filtre-input">
                        <option value="">— Nature —</option>
                        @foreach ($natures as $n)
                            <option value="{{ $n }}" @selected(($filters['nature'] ?? '') === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sens" class="form-select filtre-input">
                        <option value="">— Sens —</option>
                        @foreach ($sens as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['sens'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-4 text-end">
                    <span class="compteur-badge">{{ $regles->total() }} règle(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#faf6ee;">
                        <tr>
                            <th class="ps-4" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Nature</th>
                            <th style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Sens</th>
                            <th style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Centre cible</th>
                            <th class="text-end" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Quote-part</th>
                            <th class="text-end" style="font-size:.76rem;text-transform:uppercase;color:var(--school-red);">Priorité</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($regles as $regle)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $regle->nature }}</td>
                            <td>
                                <span class="badge-type {{ $regle->sens?->value }}"
                                      style="padding:.3em .8em;border-radius:999px;font-size:.72rem;
                                             background:{{ $regle->sens?->value === 'recette' ? '#e3f7ec' : '#fdecea' }};
                                             color:{{ $regle->sens?->value === 'recette' ? '#1c7a4d' : 'var(--school-red)' }};">
                                    {{ $regle->sens?->label() }}
                                </span>
                            </td>
                            <td>
                                <span class="centre-code">{{ $regle->centre?->code }}</span>
                                <span class="ms-2">{{ $regle->centre?->libelle }}</span>
                            </td>
                            <td class="num">{{ number_format($regle->pourcentage, 2, ',', ' ') }} %</td>
                            <td class="num">{{ $regle->priorite }}</td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('analytique.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-regle"
                                                    data-id="{{ $regle->id }}"
                                                    data-nature="{{ $regle->nature }}"
                                                    data-sens="{{ $regle->sens?->value }}"
                                                    data-centre="{{ $regle->centre_analytique_id }}"
                                                    data-pourcentage="{{ $regle->pourcentage }}"
                                                    data-priorite="{{ $regle->priorite }}"
                                                    data-bs-toggle="modal" data-bs-target="#regleModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('analytique.gerer')
                                        <li>
                                            <form action="{{ route('finances.regles.destroy', $regle) }}" method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><i class="fas fa-trash"></i> Supprimer</button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-project-diagram"></i><p>Aucune règle définie.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $regles->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('analytique.gerer')
    <div class="modal fade" id="regleModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-prev">
                <form id="regle-form">
                    @csrf
                    <input type="hidden" name="_method" id="regle-method" value="POST">
                    <div class="modal-header modal-header-prev">
                        <h5 class="modal-title" id="regle-title"><i class="fas fa-project-diagram me-2"></i>Nouvelle règle</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nature <span class="text-danger">*</span></label>
                                    <input type="text" name="nature" id="regle-nature" class="form-control"
                                           placeholder="ex : scolarite, cantine, bus, fournitures...">
                                    <div class="text-danger text-xs mt-1" id="nature-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sens <span class="text-danger">*</span></label>
                                    <select name="sens" id="regle-sens" class="form-select">
                                        @foreach ($sens as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Centre cible <span class="text-danger">*</span></label>
                                    <select name="centre_analytique_id" id="regle-centre" class="form-select">
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($centres as $c)
                                            <option value="{{ $c->id }}">{{ $c->code }} — {{ $c->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quote-part (%) <span class="text-danger">*</span></label>
                                    <input type="number" name="pourcentage" id="regle-pourcentage" step="0.01" min="0.01" max="100" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Priorité</label>
                                    <input type="number" name="priorite" id="regle-priorite" min="0" value="0" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    const $form = $('#regle-form');
    let currentId = null;

    $('#btnNouvelleRegle').on('click', function () {
        currentId = null; $form[0].reset(); $('#regle-method').val('POST');
        $('#regle-title').html('<i class="fas fa-project-diagram me-2"></i>Nouvelle règle');
    });

    $(document).on('click', '.btn-edit-regle', function () {
        currentId = $(this).data('id');
        $('#regle-nature').val($(this).data('nature'));
        $('#regle-sens').val($(this).data('sens'));
        $('#regle-centre').val($(this).data('centre'));
        $('#regle-pourcentage').val($(this).data('pourcentage'));
        $('#regle-priorite').val($(this).data('priorite'));
        $('#regle-method').val('PUT');
        $('#regle-title').html('<i class="fas fa-pen me-2"></i>Modifier la règle');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('finances/regles') }}/${currentId}`
            : `{{ route('finances.regles.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#regleModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else { window.showToast("Une erreur est survenue.", 'error'); }
            }
        });
    });
});
</script>
@endpush