@extends('admin.layouts.app')

@section('title', 'Tranches de paiement')
@section('page_icon', 'fa-stream')
@section('page_title', 'Tranches de paiement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.frais-ecoles.index') }}">Frais scolaires</a></li>
    <li class="breadcrumb-item active">Tranches</li>
@endsection

@section('page_actions')
    @can('frais.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleTranche"
                data-bs-toggle="modal" data-bs-target="#trancheModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle tranche
        </button>
    @endcan
@endsection

@section('css')
<style>
    .tranches-page { --tr-bg-soft:#faf6ee; --tr-border:#ece4d6; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--tr-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--tr-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--tr-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--tr-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-tranches thead tr { background:var(--tr-bg-soft); }
    .table-tranches thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--tr-border); white-space:nowrap; }
    .table-tranches tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--tr-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-tranches tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }

    .taux-badge {
        display:inline-flex; align-items:center; justify-content:center;
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; font-weight:700; font-size:.85rem;
        padding:.35em .9em; border-radius:999px;
        min-width:60px;
    }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-tranches { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-tranches { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--tr-bg-soft); border:1px solid var(--tr-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--tr-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--tr-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="tranches-page">

    <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;border-left:3px solid #2563eb;">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Système historique.</strong> Les tranches offrent un découpage simple
        (% du frais + date butoir). Pour un échéancier détaillé (dates et montants précis),
        préférez le module <a href="{{ route('finances.plan-echeanciers.index') }}" style="color:#1e3a8a;text-decoration:underline;">Plans d'échéancier</a>.
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <select name="frais_ecole_id" class="form-select filtre-input">
                        <option value="">— Tous les frais —</option>
                        @foreach ($frais as $f)
                            <option value="{{ $f->id }}" @selected(($filters['frais_ecole_id'] ?? '') == $f->id)>
                                {{ $f->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control filtre-input"
                           placeholder="Rechercher une tranche..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('finances.tranches.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-tranches align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Libellé</th>
                            <th>Frais</th>
                            <th class="text-end">Taux</th>
                            <th>Date butoir</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($tranches as $tranche)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $tranche->libelle }}</td>
                            <td class="text-sm">{{ $tranche->fraisEcole?->libelle ?? '—' }}</td>
                            <td class="text-end">
                                <span class="taux-badge">{{ number_format((float) $tranche->taux, 2, ',', ' ') }} %</span>
                            </td>
                            <td class="text-sm">
                                <i class="far fa-calendar me-1 text-muted"></i>
                                {{ optional($tranche->date_butoire)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('frais.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-tranche"
                                                    data-id="{{ $tranche->id }}"
                                                    data-libelle="{{ $tranche->libelle }}"
                                                    data-frais="{{ $tranche->frais_ecole_id }}"
                                                    data-taux="{{ $tranche->taux }}"
                                                    data-date="{{ optional($tranche->date_butoire)->toDateString() }}"
                                                    data-bs-toggle="modal" data-bs-target="#trancheModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('frais.gerer')
                                        <li>
                                            <form action="{{ route('finances.tranches.destroy', $tranche) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-stream"></i><p>Aucune tranche enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $tranches->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale --}}
    @can('frais.gerer')
    <div class="modal fade" id="trancheModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-tranches">
                <form id="tranche-form">
                    @csrf
                    <input type="hidden" name="_method" id="tranche-method" value="POST">
                    <div class="modal-header modal-header-tranches">
                        <h5 class="modal-title" id="tranche-title"><i class="fas fa-stream me-2"></i>Nouvelle tranche</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="tranche-libelle" class="form-control" maxlength="100"
                                       placeholder="ex : 1ʳᵉ tranche, 2ᵉ tranche...">
                                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Frais concerné <span class="text-danger">*</span></label>
                                <select name="frais_ecole_id" id="tranche-frais" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($frais as $f)
                                        <option value="{{ $f->id }}">{{ $f->libelle }}</option>
                                    @endforeach
                                </select>
                                <div class="text-danger text-xs mt-1" id="frais_ecole_id-error"></div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Taux (% du frais) <span class="text-danger">*</span></label>
                                    <input type="number" name="taux" id="tranche-taux" step="0.01" min="0.01" max="100"
                                           class="form-control" required>
                                    <div class="text-danger text-xs mt-1" id="taux-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date butoir <span class="text-danger">*</span></label>
                                    <input type="date" name="date_butoire" id="tranche-date" class="form-control" required>
                                    <div class="text-danger text-xs mt-1" id="date_butoire-error"></div>
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
    const $form = $('#tranche-form');
    let currentId = null;

    $('#btnNouvelleTranche').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#tranche-method').val('POST');
        $('#tranche-title').html('<i class="fas fa-stream me-2"></i>Nouvelle tranche');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-tranche', function () {
        currentId = $(this).data('id');
        $('#tranche-libelle').val($(this).data('libelle'));
        $('#tranche-frais').val($(this).data('frais'));
        $('#tranche-taux').val($(this).data('taux'));
        $('#tranche-date').val($(this).data('date'));
        $('#tranche-method').val('PUT');
        $('#tranche-title').html('<i class="fas fa-pen me-2"></i>Modifier la tranche');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('finances/tranches') }}/${currentId}`
            : `{{ route('finances.tranches.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#trancheModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
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