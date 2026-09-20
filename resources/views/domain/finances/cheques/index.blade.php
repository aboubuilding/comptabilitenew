{{-- resources/views/domain/finances/cheques/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Portefeuille des chèques')
@section('page_icon', 'fa-money-check-alt')
@section('page_title', 'Banques & Chèques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Chèques</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.cheques.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('cheques.gerer')
        <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#chequeModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau chèque
        </button>
    @endcan
@endsection

@section('css')
<style>
    .cheques-page { --cheques-bg-soft: #faf6ee; --cheques-border: #ece4d6; }
    .text-xs { font-size: .72rem; }
    .text-sm { font-size: .8rem; }

    .stat-card { background:#fff; border:1px solid var(--cheques-border); border-radius:12px; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition: transform .2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-icon { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0; }
    .stat-icon.blue   { background: rgba(37,99,235,.10); color:#2563eb; }
    .stat-icon.green  { background: rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.red    { background: rgba(200,30,58,.10); color: var(--school-red); }
    .stat-icon.gold   { background: rgba(212,169,77,.18); color: var(--school-gold-dark); }
    .stat-info .stat-value { font-size:1.2rem; font-weight:700; line-height:1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size:.78rem; color: var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--cheques-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--cheques-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }

    .btn-filtrer { background: var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color:#fff; }

    .compteur-badge { display:inline-block; background: var(--cheques-bg-soft); color: var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--cheques-border); }

    .btn-nouveau { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; box-shadow:0 4px 14px rgba(200,30,58,.25); }
    .btn-nouveau:hover { color:#fff; transform:translateY(-2px); }
    .btn-outline-scolarite { border:1px solid var(--cheques-border); color: var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background: var(--cheques-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    .table-cheques thead tr { background: var(--cheques-bg-soft); }
    .table-cheques thead th { color: var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--cheques-border); padding:.9rem 1rem; white-space:nowrap; }
    .table-cheques thead th i { color: var(--school-gold-dark); }
    .table-cheques tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--cheques-border); font-size:.9rem; color: var(--school-ink); vertical-align:middle; }
    .table-cheques tbody tr:hover { background:#fdfaf5; }

    .cheque-numero { font-weight:700; color: var(--school-ink); display:block; font-size:.92rem; }
    .cheque-emetteur { font-size:.75rem; color: var(--school-muted); }
    .cheque-montant { font-variant-numeric: tabular-nums; font-weight:700; text-align:right; }

    .badge-cheque { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-cheque-emis     { background:#dbeafe; color:#1d4ed8; }
    .badge-cheque-encaisse { background:#e3f7ec; color:#1c7a4d; }
    .badge-cheque-rejete   { background:#fdecea; color: var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; }
    .badge-cheque-emis .badge-dot     { background:#1d4ed8; }
    .badge-cheque-encaisse .badge-dot { background:#1c7a4d; }
    .badge-cheque-rejete .badge-dot   { background: var(--school-red); }

    .empty-state { padding: 3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-cheques { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-cheques { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background: var(--cheques-bg-soft); border:1px solid var(--cheques-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color: var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--cheques-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color: var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }

    .btn-annuler { background:#fff; border:1px solid var(--cheques-border); color:#5b6b7a; border-radius:8px; font-weight:500; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-annuler:hover { background: var(--cheques-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="cheques-page">

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['emis'] }}</div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['encaisse'] }}</div>
                    <div class="stat-label">Encaissés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['rejete'] }}</div>
                    <div class="stat-label">Rejetés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($montantAttente, 0, ',', ' ') }}</div>
                    <div class="stat-label">Montant en attente</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Numéro, émetteur..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['statut'] ?? '') == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="banque_id" class="form-select filtre-input">
                        <option value="">— Banque —</option>
                        @foreach ($banques as $b)
                            <option value="{{ $b->id }}" @selected(($filters['banque_id'] ?? '') == $b->id)>
                                {{ $b->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input"
                           value="{{ $filters['date_debut'] ?? '' }}" placeholder="Du">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input"
                           value="{{ $filters['date_fin'] ?? '' }}" placeholder="Au">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-filtrer w-100" title="Filtrer">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-cheques align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-hashtag me-2"></i>Numéro</th>
                            <th><i class="fas fa-user me-2"></i>Émetteur</th>
                            <th><i class="fas fa-university me-2"></i>Banque</th>
                            <th><i class="fas fa-calendar me-2"></i>Émission</th>
                            <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Montant</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($cheques as $cheque)
                        <tr>
                            <td class="ps-4">
                                <span class="cheque-numero">{{ $cheque->numero }}</span>
                                <span class="cheque-emetteur">#{{ $cheque->id }}</span>
                            </td>
                            <td class="text-sm">{{ $cheque->emetteur }}</td>
                            <td class="text-sm">{{ $cheque->banque?->nom ?? '—' }}</td>
                            <td class="text-sm">{{ optional($cheque->date_emission)->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @php $s = $cheque->statut; @endphp
                                <span class="badge-cheque badge-cheque-{{ $s?->css() }}">
                                    <span class="badge-dot"></span>{{ $s?->label() ?? '—' }}
                                </span>
                            </td>
                            <td class="cheque-montant">
                                {{ number_format((float) $cheque->montant, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @if ($cheque->isEnAttente())
                                            @can('cheques.rapprocher')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-encaisser"
                                                            data-id="{{ $cheque->id }}"
                                                            data-numero="{{ $cheque->numero }}"
                                                            data-montant="{{ number_format((float) $cheque->montant, 2, ',', ' ') }}"
                                                            data-bs-toggle="modal" data-bs-target="#rapprocheModal">
                                                        <i class="fas fa-check-circle"></i> Marquer encaissé
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-rejeter"
                                                            data-id="{{ $cheque->id }}"
                                                            data-numero="{{ $cheque->numero }}"
                                                            data-bs-toggle="modal" data-bs-target="#rapprocheModal">
                                                        <i class="fas fa-times-circle"></i> Rejeter
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ $cheque->statut->label() }}
                                                    @if ($cheque->date_rapprochement)
                                                        — {{ $cheque->date_rapprochement->format('d/m/Y') }}
                                                    @endif
                                                </span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-money-check-alt"></i>
                                    <p class="mb-0 fw-medium">Aucun chèque enregistré.</p>
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
        {{ $cheques->appends(request()->query())->links('vendor.pagination.custom') }}
    </div>

    {{-- Modale nouveau chèque --}}
    @can('cheques.gerer')
    <div class="modal fade" id="chequeModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cheques">
                <form id="cheque-form" method="POST" action="{{ route('finances.cheques.store') }}">
                    @csrf
                    <div class="modal-header modal-header-cheques">
                        <h5 class="modal-title">
                            <i class="fas fa-money-check me-2"></i>Nouveau chèque
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Émetteur &amp; banque</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Numéro du chèque <span class="text-danger">*</span></label>
                                    <input type="text" name="numero" class="form-control" maxlength="100">
                                    <div class="text-danger text-xs mt-1" id="numero-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Émetteur <span class="text-danger">*</span></label>
                                    <input type="text" name="emetteur" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="emetteur-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Banque <span class="text-danger">*</span></label>
                                    <select name="banque_id" class="form-select">
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($banques as $b)
                                            <option value="{{ $b->id }}">{{ $b->nom }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger text-xs mt-1" id="banque_id-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Montant <span class="text-danger">*</span></label>
                                    <input type="number" name="montant" step="0.01" min="0.01" class="form-control">
                                    <div class="text-danger text-xs mt-1" id="montant-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date d'émission <span class="text-danger">*</span></label>
                                    <input type="date" name="date_emission" class="form-control"
                                           value="{{ now()->toDateString() }}">
                                    <div class="text-danger text-xs mt-1" id="date_emission-error"></div>
                                </div>
                            </div>
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

    {{-- Modale rapprochement (encaissé / rejeté) --}}
    @can('cheques.rapprocher')
    <div class="modal fade" id="rapprocheModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cheques">
                <form id="rapproche-form" method="POST" action="">
                    @csrf
                    <input type="hidden" name="statut" id="rapproche-statut" value="">
                    <div class="modal-header modal-header-cheques">
                        <h5 class="modal-title" id="rapproche-title">Rapprochement</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3"
                             style="background: var(--cheques-bg-soft); border-radius:10px;">
                            <div>
                                <div class="text-muted text-xs">Chèque</div>
                                <div class="fw-semibold" id="rapproche-numero">—</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted text-xs">Montant</div>
                                <div class="fw-bold fs-6" id="rapproche-montant">—</div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="mb-3" id="date-encaissement-bloc">
                                <label class="form-label">Date d'encaissement</label>
                                <input type="date" name="date_encaissement" class="form-control"
                                       value="{{ now()->toDateString() }}">
                            </div>
                            <div class="mb-0 d-none" id="motif-rejet-bloc">
                                <label class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                                <textarea name="motif_rejet" rows="2" class="form-control"></textarea>
                                <div class="text-danger text-xs mt-1" id="motif_rejet-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer" id="rapproche-submit">
                            <i class="fas fa-check me-1"></i> Confirmer
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
    // =========== Création chèque ===========
    const $chequeForm = $('#cheque-form');
    if ($chequeForm.length) {
        $chequeForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($chequeForm);

            $.ajax({
                url: $chequeForm.attr('action'),
                method: 'POST',
                data: $chequeForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#chequeModal').modal('hide');
                    window.showToastThenReload(res.message || 'Chèque enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // =========== Rapprochement ===========
    const $rapprocheForm = $('#rapproche-form');
    if ($rapprocheForm.length) {
        $('.btn-encaisser').on('click', function () {
            const id      = $(this).data('id');
            const numero  = $(this).data('numero');
            const montant = $(this).data('montant');

            $rapprocheForm.attr('action', '{{ url("finances/cheques") }}/' + id + '/rapprocher');
            $('#rapproche-statut').val('{{ \App\Domain\Finances\Types\ChequeStatut::ENCAISSE->value }}');
            $('#rapproche-title').html('<i class="fas fa-check-circle me-2"></i>Marquer comme encaissé');
            $('#rapproche-numero').text(numero);
            $('#rapproche-montant').text(montant);
            $('#date-encaissement-bloc').removeClass('d-none');
            $('#motif-rejet-bloc').addClass('d-none');
            $('#rapproche-submit').html('<i class="fas fa-check me-1"></i> Encaisser');
            clearErrors($rapprocheForm);
        });

        $('.btn-rejeter').on('click', function () {
            const id     = $(this).data('id');
            const numero = $(this).data('numero');

            $rapprocheForm.attr('action', '{{ url("finances/cheques") }}/' + id + '/rapprocher');
            $('#rapproche-statut').val('{{ \App\Domain\Finances\Types\ChequeStatut::REJETE->value }}');
            $('#rapproche-title').html('<i class="fas fa-times-circle me-2"></i>Rejeter le chèque');
            $('#rapproche-numero').text(numero);
            $('#rapproche-montant').text('—');
            $('#date-encaissement-bloc').addClass('d-none');
            $('#motif-rejet-bloc').removeClass('d-none');
            $('#rapproche-submit').html('<i class="fas fa-times me-1"></i> Rejeter');
            clearErrors($rapprocheForm);
        });

        $rapprocheForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($rapprocheForm);

            $.ajax({
                url: $rapprocheForm.attr('action'),
                method: 'POST',
                data: $rapprocheForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#rapprocheModal').modal('hide');
                    window.showToastThenReload(res.message || 'Rapprochement enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors(xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // =========== Utilitaires ===========
    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[id$="-error"]').text('');
    }

    function showErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            $('[name="' + field + '"]').addClass('is-invalid');
            $('#' + field + '-error').text(errors[field][0]);
        });
    }
});
</script>
@endpush