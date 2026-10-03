@extends('admin.layouts.app')

@section('title', 'Lignes de transport')
@section('page_icon', 'fa-route')
@section('page_title', 'Lignes de transport')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Lignes</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.zones.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-map-marked-alt me-1"></i> Zones
    </a>
    @can('transport.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleLigne"
                data-bs-toggle="modal" data-bs-target="#ligneModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle ligne
        </button>
    @endcan
@endsection

@section('css')
<style>
    .ligne-page { --l-bg-soft:#faf6ee; --l-border:#ece4d6; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--l-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--l-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--l-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--l-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--l-border); }

    .table-lignes thead tr { background:var(--l-bg-soft); }
    .table-lignes thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--l-border); }
    .table-lignes tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--l-border); font-size:.9rem; vertical-align:middle; }
    .table-lignes tbody tr:hover { background:#fdfaf5; }

    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .modal-l { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-l { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--l-bg-soft); border:1px solid var(--l-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--l-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--l-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="ligne-page">
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control filtre-input"
                           placeholder="Rechercher une ligne..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-3 text-end">
                    <span class="compteur-badge">{{ $lignes->total() }} ligne(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-lignes align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Libellé</th>
                            <th class="text-end">Prix minimal</th>
                            <th class="text-end">Prix plafond</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($lignes as $l)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $l->libelle }}</td>
                            <td class="num">{{ $l->prix_minimal ? number_format((float) $l->prix_minimal, 2, ',', ' ') : '—' }}</td>
                            <td class="num grand">{{ $l->prix_plafond ? number_format((float) $l->prix_plafond, 2, ',', ' ') : '—' }}</td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-ligne"
                                                    data-id="{{ $l->id }}"
                                                    data-libelle="{{ $l->libelle }}"
                                                    data-min="{{ $l->prix_minimal }}"
                                                    data-max="{{ $l->prix_plafond }}"
                                                    data-bs-toggle="modal" data-bs-target="#ligneModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <li>
                                            <form action="{{ route('services-eleves.transport.lignes.destroy', $l) }}" method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><i class="fas fa-trash"></i> Désactiver</button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state"><i class="fas fa-route"></i><p>Aucune ligne enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $lignes->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('transport.gerer')
    <div class="modal fade" id="ligneModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-l">
                <form id="ligne-form">
                    @csrf
                    <input type="hidden" name="_method" id="ligne-method" value="POST">
                    <div class="modal-header modal-header-l">
                        <h5 class="modal-title" id="ligne-title"><i class="fas fa-route me-2"></i>Nouvelle ligne</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="l-libelle" class="form-control" required maxlength="150">
                                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prix minimal</label>
                                    <input type="number" name="prix_minimal" id="l-min" step="0.01" min="0" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prix plafond</label>
                                    <input type="number" name="prix_plafond" id="l-max" step="0.01" min="0" class="form-control">
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
    const $form = $('#ligne-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleLigne').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#ligne-method').val('POST');
        $('#ligne-title').html('<i class="fas fa-route me-2"></i>Nouvelle ligne');
    });

    $(document).on('click', '.btn-edit-ligne', function () {
        currentId = $(this).data('id');
        $('#l-libelle').val($(this).data('libelle'));
        $('#l-min').val($(this).data('min'));
        $('#l-max').val($(this).data('max'));
        $('#ligne-method').val('PUT');
        $('#ligne-title').html('<i class="fas fa-pen me-2"></i>Modifier la ligne');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/lignes') }}/${currentId}`
            : `{{ route('services-eleves.transport.lignes.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#ligneModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });
});
</script>
@endpush