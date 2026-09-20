{{-- resources/views/domain/finances/banques/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Comptes bancaires')
@section('page_icon', 'fa-university')
@section('page_title', 'Comptes bancaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Banques</li>
@endsection

@section('page_actions')
    @can('banques.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleBanque"
                data-bs-toggle="modal" data-bs-target="#banqueModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau compte bancaire
        </button>
    @endcan
@endsection

@section('css')
<style>
    .banques-page { --banques-bg-soft: #faf6ee; --banques-border: #ece4d6; }
    .text-xs { font-size: .72rem; }
    .text-sm { font-size: .8rem; }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--banques-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--banques-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .search-wrapper { position: relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform: translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left: 36px !important; }

    .btn-filtrer { background: var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color:#fff; }

    .compteur-badge { display:inline-block; background: var(--banques-bg-soft); color: var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--banques-border); }

    .btn-nouveau { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; box-shadow:0 4px 14px rgba(200,30,58,.25); }
    .btn-nouveau:hover { color:#fff; transform: translateY(-2px); }

    .table-banques thead tr { background: var(--banques-bg-soft); }
    .table-banques thead th { color: var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--banques-border); padding:.9rem 1rem; }
    .table-banques thead th i { color: var(--school-gold-dark); }
    .table-banques tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--banques-border); font-size:.9rem; color: var(--school-ink); vertical-align:middle; }
    .table-banques tbody tr:hover { background:#fdfaf5; }

    .banque-nom { font-weight:700; color: var(--school-ink); display:block; }
    .banque-id { font-size:.72rem; color: var(--school-muted); }

    .empty-state { padding: 3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .empty-state p { font-size:.9rem; }

    .modal-banques { border:none; border-radius:16px; overflow:hidden; box-shadow: 0 20px 40px rgba(0,0,0,.15); }
    .modal-header-banques { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .modal-header-banques .modal-title { font-weight:600; font-size:1.05rem; }

    .form-section { background: var(--banques-bg-soft); border:1px solid var(--banques-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color: var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--banques-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .btn-annuler { background:#fff; border:1px solid var(--banques-border); color:#5b6b7a; border-radius:8px; font-weight:500; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-annuler:hover { background: var(--banques-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="banques-page">

    {{-- Tableau principal --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Rechercher une banque..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <button type="submit" class="btn btn-filtrer w-100">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-3 col-6 text-end">
                    <span class="compteur-badge">{{ $banques->total() }} banque(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-banques align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-university me-2"></i>Banque</th>
                            <th class="text-center"><i class="fas fa-money-check me-2"></i>Chèques</th>
                            <th class="text-center"><i class="fas fa-clock me-2"></i>En attente</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Montant en attente</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($banques as $banque)
                            <tr>
                                <td class="ps-4">
                                    <span class="banque-nom">{{ $banque->nom }}</span>
                                    <span class="banque-id">#{{ $banque->id }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="compteur-badge">{{ $banque->cheques_total ?? 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="compteur-badge">{{ $banque->cheques_en_attente ?? 0 }}</span>
                                </td>
                                <td class="text-end" style="font-variant-numeric: tabular-nums; font-weight:600;">
                                    {{ number_format($banque->montant_en_attente, 2, ',', ' ') }}
                                </td>
                                <td class="text-center pe-4">
                                    <div class="dropdown">
                                        <button class="btn-action dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-banque"
                                                        data-id="{{ $banque->id }}"
                                                        data-nom="{{ $banque->nom }}"
                                                        data-bs-toggle="modal" data-bs-target="#banqueModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            @if (($banque->cheques_total ?? 0) === 0)
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('finances.banques.destroy', $banque) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Désactiver cette banque ?"
                                                          data-confirm-text="Elle n'apparaîtra plus dans les listes.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash"></i> Désactiver
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-university"></i>
                                        <p class="mb-0 fw-medium">Aucun compte bancaire enregistré.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $banques->appends(request()->query())->links('vendor.pagination.custom') }}
    </div>

    {{-- Modale création / édition --}}
    @can('banques.gerer')
    <div class="modal fade" id="banqueModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-banques">
                <form id="banque-form">
                    @csrf
                    <input type="hidden" name="_method" id="banque-method" value="POST">
                    <div class="modal-header modal-header-banques">
                        <h5 class="modal-title" id="banque-modal-title">
                            <i class="fas fa-university me-2"></i>Nouveau compte bancaire
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations</div>
                            <label class="form-label">Nom de la banque <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="banque-nom" class="form-control"
                                   maxlength="150" placeholder="Ex : Ecobank, BOA, UTB...">
                            <div class="text-danger text-xs mt-1" id="nom-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer">
                            <i class="fas fa-save me-1"></i> Enregistrer
                        </button>
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
    const $form   = $('#banque-form');
    const $title  = $('#banque-modal-title');
    const $method = $('#banque-method');
    let currentId = null;

    $('#btnNouvelleBanque').on('click', function () {
        currentId = null;
        $form[0].reset();
        $method.val('POST');
        $title.html('<i class="fas fa-university me-2"></i>Nouveau compte bancaire');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-banque', function () {
        currentId = $(this).data('id');
        $('#banque-nom').val($(this).data('nom'));
        $method.val('PUT');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le compte bancaire');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('finances/banques') }}/${currentId}`
            : `{{ route('finances.banques.store') }}`;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#banqueModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(function (f) {
                        $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]);
                    });
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() {
        $form.find('[id$="-error"]').text('');
    }
});
</script>
@endpush