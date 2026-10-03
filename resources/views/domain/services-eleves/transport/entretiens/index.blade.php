@extends('admin.layouts.app')

@section('title', 'Entretiens véhicules')
@section('page_icon', 'fa-tools')
@section('page_title', 'Entretiens des véhicules')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Entretiens</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.entretiens.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.transport.carburants.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-gas-pump me-1"></i> Carburant
    </a>
    <a href="{{ route('services-eleves.transport.assurances.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-shield-alt me-1"></i> Assurances
    </a>
    @can('transport.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelEntretien"
                data-bs-toggle="modal" data-bs-target="#entretienModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvel entretien
        </button>
    @endcan
@endsection

@section('css')
<style>
    .ent-page { --e-bg-soft:#faf6ee; --e-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--e-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--e-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--e-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--e-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--e-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--e-border); }

    .table-ent thead tr { background:var(--e-bg-soft); }
    .table-ent thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--e-border); white-space:nowrap; }
    .table-ent tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--e-border); font-size:.9rem; vertical-align:middle; }
    .table-ent tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; background:var(--e-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:600; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-ent { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-ent { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--e-bg-soft); border:1px solid var(--e-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--e-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--e-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="ent-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-tools"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['nb'] }}</div>
                    <div class="stat-label">Entretiens</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['cout_total'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Coût total</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-list"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ count($parType) }}</div>
                    <div class="stat-label">Types différents</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3 text-end d-flex align-items-center justify-content-end">
            <span class="compteur-badge">{{ $entretiens->total() }} entretien(s)</span>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Type, observations..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="voiture_id" class="form-select filtre-input">
                        <option value="">— Véhicule —</option>
                        @foreach ($voitures as $v)
                            <option value="{{ $v->id }}" @selected(($filters['voiture_id'] ?? '') == $v->id)>{{ $v->plaque }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type_entretien" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(($filters['type_entretien'] ?? '') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ent align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Véhicule</th>
                            <th>Type</th>
                            <th class="text-end">Kilométrage</th>
                            <th class="text-end">Coût</th>
                            <th>Effectué par</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($entretiens as $e)
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($e->date_entretien)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                @if ($e->voiture)
                                    <a href="{{ route('services-eleves.transport.voitures.show', $e->voiture) }}" class="text-decoration-none">
                                        <div class="fw-semibold">{{ $e->voiture->plaque }}</div>
                                        <div class="text-xs text-muted">{{ $e->voiture->marque }}</div>
                                    </a>
                                @else — @endif
                            </td>
                            <td><span class="badge-type">{{ $e->type_entretien }}</span></td>
                            <td class="num">{{ number_format((float) $e->kilometrage, 0, ',', ' ') }} km</td>
                            <td class="num grand">{{ number_format((float) $e->cout, 2, ',', ' ') }}</td>
                            <td class="text-sm">{{ $e->chauffeur?->nom_complet ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-ent"
                                                    data-id="{{ $e->id }}"
                                                    data-voiture="{{ $e->voiture_id }}"
                                                    data-date="{{ optional($e->date_entretien)->toDateString() }}"
                                                    data-type="{{ $e->type_entretien }}"
                                                    data-cout="{{ $e->cout }}"
                                                    data-km="{{ $e->kilometrage }}"
                                                    data-observations="{{ $e->observations }}"
                                                    data-chauffeur="{{ $e->effectue_par }}"
                                                    data-bs-toggle="modal" data-bs-target="#entretienModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <li>
                                            <form action="{{ route('services-eleves.transport.entretiens.destroy', $e) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-tools"></i><p>Aucun entretien enregistré.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $entretiens->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('transport.gerer')
    <div class="modal fade" id="entretienModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-ent">
                <form id="entretien-form">
                    @csrf
                    <input type="hidden" name="_method" id="ent-method" value="POST">
                    <div class="modal-header modal-header-ent">
                        <h5 class="modal-title" id="ent-title"><i class="fas fa-tools me-2"></i>Nouvel entretien</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Véhicule <span class="text-danger">*</span></label>
                                <select name="voiture_id" id="e-voiture" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($voitures as $v)
                                        <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_entretien" id="e-date" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <input type="text" name="type_entretien" id="e-type" class="form-control" maxlength="100" required placeholder="Vidange, révision, pneus...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Coût</label>
                                    <input type="number" name="cout" id="e-cout" step="0.01" min="0" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kilométrage <span class="text-danger">*</span></label>
                                    <input type="number" name="kilometrage" id="e-km" min="0" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Effectué par</label>
                                    <select name="effectue_par" id="e-chauffeur" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($chauffeurs as $c)
                                            <option value="{{ $c->id }}">{{ $c->nom_complet }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Observations</label>
                                    <textarea name="observations" id="e-observations" rows="2" class="form-control" maxlength="2000"></textarea>
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
    const $form = $('#entretien-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelEntretien').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#ent-method').val('POST');
        $('#ent-title').html('<i class="fas fa-tools me-2"></i>Nouvel entretien');
    });

    $(document).on('click', '.btn-edit-ent', function () {
        currentId = $(this).data('id');
        $('#e-voiture').val($(this).data('voiture'));
        $('#e-date').val($(this).data('date'));
        $('#e-type').val($(this).data('type'));
        $('#e-cout').val($(this).data('cout'));
        $('#e-km').val($(this).data('km'));
        $('#e-chauffeur').val($(this).data('chauffeur') ?? '');
        $('#e-observations').val($(this).data('observations'));
        $('#ent-method').val('PUT');
        $('#ent-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'entretien');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/entretiens') }}/${currentId}`
            : `{{ route('services-eleves.transport.entretiens.store') }}`;
        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#entretienModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            }
        });
    });
});
</script>
@endpush