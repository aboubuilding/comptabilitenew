@extends('admin.layouts.app')

@section('title', 'Centres analytiques')
@section('page_icon', 'fa-sitemap')
@section('page_title', 'Centres analytiques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Centres analytiques</li>
@endsection

@section('page_actions')
    @can('analytique.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauCentre"
                data-bs-toggle="modal" data-bs-target="#centreModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau centre
        </button>
    @endcan
@endsection

@section('css')
<style>
    .centres-page { --cp-bg-soft:#faf6ee; --cp-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--cp-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--cp-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--cp-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--cp-border); }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .table-centres thead tr { background:var(--cp-bg-soft); }
    .table-centres thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--cp-border); }
    .table-centres thead th i { color:var(--school-gold-dark); }
    .table-centres tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--cp-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-centres tbody tr:hover { background:#fdfaf5; }
    .centre-code { font-family:monospace; background:var(--cp-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-size:.75rem; font-weight:700; }
    .centre-libelle { font-weight:700; }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .modal-centres { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-centres { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--cp-bg-soft); border:1px solid var(--cp-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--cp-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--cp-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="centres-page">

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Code ou libellé..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-2 text-end">
                    <span class="compteur-badge">{{ $centres->total() }} centre(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-centres align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Code</th>
                            <th>Libellé</th>
                            <th>Type</th>
                            <th>Responsable</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($centres as $centre)
                        <tr>
                            <td class="ps-4"><span class="centre-code">{{ $centre->code }}</span></td>
                            <td><span class="centre-libelle">{{ $centre->libelle }}</span>
                                @if ($centre->description)<div class="text-xs text-muted">{{ \Str::limit($centre->description, 80) }}</div>@endif
                            </td>
                            <td><span class="compteur-badge">{{ $centre->type?->label() }}</span></td>
                            <td class="text-sm">{{ $centre->responsable?->name ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('analytique.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-centre"
                                                    data-id="{{ $centre->id }}"
                                                    data-code="{{ $centre->code }}"
                                                    data-libelle="{{ $centre->libelle }}"
                                                    data-description="{{ $centre->description }}"
                                                    data-type="{{ $centre->type?->value }}"
                                                    data-responsable="{{ $centre->responsable_id }}"
                                                    data-bs-toggle="modal" data-bs-target="#centreModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        @if ($centre->imputations()->count() === 0)
                                            <li><hr class="dropdown-divider"></li>
                                            @can('analytique.gerer')
                                            <li>
                                                <form action="{{ route('finances.analytique.centres.destroy', $centre) }}"
                                                      method="POST" class="form-confirm-delete"
                                                      data-confirm-title="Désactiver ce centre ?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash"></i> Désactiver
                                                    </button>
                                                </form>
                                            </li>
                                            @endcan
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-sitemap"></i><p>Aucun centre analytique configuré.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $centres->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modal création / édition --}}
    @can('analytique.gerer')
    <div class="modal fade" id="centreModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-centres">
                <form id="centre-form">
                    @csrf
                    <input type="hidden" name="_method" id="centre-method" value="POST">
                    <div class="modal-header modal-header-centres">
                        <h5 class="modal-title" id="centre-modal-title"><i class="fas fa-sitemap me-2"></i>Nouveau centre analytique</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Identification</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" id="centre-code" class="form-control" maxlength="30" placeholder="SCOL">
                                    <div class="text-danger text-xs mt-1" id="code-error"></div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="centre-libelle" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="centre-type" class="form-select">
                                        @foreach ($types as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <select name="responsable_id" id="centre-responsable" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($users as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="centre-description" rows="2" class="form-control" maxlength="1000"></textarea>
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
    const $form = $('#centre-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauCentre').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#centre-method').val('POST');
        $('#centre-modal-title').html('<i class="fas fa-sitemap me-2"></i>Nouveau centre analytique');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-centre', function () {
        currentId = $(this).data('id');
        $('#centre-code').val($(this).data('code'));
        $('#centre-libelle').val($(this).data('libelle'));
        $('#centre-type').val($(this).data('type'));
        $('#centre-responsable').val($(this).data('responsable') ?? '');
        $('#centre-description').val($(this).data('description') ?? '');
        $('#centre-method').val('PUT');
        $('#centre-modal-title').html('<i class="fas fa-pen me-2"></i>Modifier le centre');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('finances/analytique/centres') }}/${currentId}`
            : `{{ route('finances.analytique.centres.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#centreModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => {
                        $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]);
                    });
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() { $form.find('[id$="-error"]').text(''); }
});
</script>
@endpush