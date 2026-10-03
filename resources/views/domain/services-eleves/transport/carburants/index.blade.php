@extends('admin.layouts.app')

@section('title', 'Carburant véhicules')
@section('page_icon', 'fa-gas-pump')
@section('page_title', 'Pleins de carburant')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Carburant</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.carburants.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.transport.entretiens.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-tools me-1"></i> Entretiens
    </a>
    @can('transport.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauPlein"
                data-bs-toggle="modal" data-bs-target="#carburantModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau plein
        </button>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes de entretiens/index — non dupliqué pour concision */
    .cb-page { --c-border:#ece4d6; --c-bg-soft:#faf6ee; }
    .stat-card { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--c-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--c-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--c-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--c-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--c-border); }

    .table-cb thead tr { background:var(--c-bg-soft); }
    .table-cb thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--c-border); white-space:nowrap; }
    .table-cb tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--c-border); font-size:.9rem; vertical-align:middle; }
    .table-cb tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-cb { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-cb { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--c-bg-soft); border:1px solid var(--c-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--c-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--c-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="cb-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-gas-pump"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['nb'] }}</div>
                    <div class="stat-label">Pleins</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-tint"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['litres'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Litres consommés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['montant'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Montant total</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-tag"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['prix_moyen'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Prix moyen / litre</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Consommation par véhicule --}}
    @if ($parVehicule->isNotEmpty())
        <div class="card main-card mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-chart-bar me-1" style="color:var(--school-gold-dark);"></i> Consommation par véhicule</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-cb mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Véhicule</th>
                                <th class="text-end">Pleins</th>
                                <th class="text-end">Litres</th>
                                <th class="text-end pe-4">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($parVehicule as $p)
                            <tr>
                                <td class="ps-4 fw-semibold">
                                    {{ $p->voiture?->plaque }} <span class="text-muted small">{{ $p->voiture?->marque }}</span>
                                </td>
                                <td class="num">{{ $p->nb }}</td>
                                <td class="num">{{ number_format((float) $p->litres, 0, ',', ' ') }} L</td>
                                <td class="num grand pe-4">{{ number_format((float) $p->montant, 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Station, facture..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="voiture_id" class="form-select filtre-input">
                        <option value="">— Véhicule —</option>
                        @foreach ($voitures as $v)
                            <option value="{{ $v->id }}" @selected(($filters['voiture_id'] ?? '') == $v->id)>{{ $v->plaque }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-cb align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Véhicule</th>
                            <th class="text-end">Litres</th>
                            <th class="text-end">P.U.</th>
                            <th class="text-end">Montant</th>
                            <th class="text-end">KM</th>
                            <th>Station</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($carburants as $c)
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($c->date_plein)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                @if ($c->voiture)
                                    <div class="fw-semibold">{{ $c->voiture->plaque }}</div>
                                    <div class="text-xs text-muted">{{ $c->voiture->marque }}</div>
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $c->quantite_litres, 2, ',', ' ') }} L</td>
                            <td class="num">{{ number_format((float) $c->prix_unitaire, 2, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format((float) $c->montant_total, 2, ',', ' ') }}</td>
                            <td class="num text-sm">{{ number_format((float) $c->kilometrage, 0, ',', ' ') }}</td>
                            <td class="text-sm text-muted">{{ $c->station_service ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-cb"
                                                    data-id="{{ $c->id }}"
                                                    data-voiture="{{ $c->voiture_id }}"
                                                    data-date="{{ optional($c->date_plein)->toDateString() }}"
                                                    data-litres="{{ $c->quantite_litres }}"
                                                    data-prix="{{ $c->prix_unitaire }}"
                                                    data-km="{{ $c->kilometrage }}"
                                                    data-station="{{ $c->station_service }}"
                                                    data-facture="{{ $c->facture }}"
                                                    data-bs-toggle="modal" data-bs-target="#carburantModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <li>
                                            <form action="{{ route('services-eleves.transport.carburants.destroy', $c) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-gas-pump"></i><p>Aucun plein enregistré.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $carburants->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('transport.gerer')
    <div class="modal fade" id="carburantModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cb">
                <form id="carburant-form">
                    @csrf
                    <input type="hidden" name="_method" id="cb-method" value="POST">
                    <div class="modal-header modal-header-cb">
                        <h5 class="modal-title" id="cb-title"><i class="fas fa-gas-pump me-2"></i>Nouveau plein</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Véhicule <span class="text-danger">*</span></label>
                                <select name="voiture_id" id="cb-voiture" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($voitures as $v)
                                        <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_plein" id="cb-date" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kilométrage <span class="text-danger">*</span></label>
                                    <input type="number" name="kilometrage" id="cb-km" min="0" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Litres <span class="text-danger">*</span></label>
                                    <input type="number" name="quantite_litres" id="cb-litres" step="0.01" min="0.01" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prix unitaire <span class="text-danger">*</span></label>
                                    <input type="number" name="prix_unitaire" id="cb-prix" step="0.01" min="0" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Station</label>
                                    <input type="text" name="station_service" id="cb-station" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">N° facture</label>
                                    <input type="text" name="facture" id="cb-facture" class="form-control" maxlength="100">
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
    const $form = $('#carburant-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouveauPlein').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#cb-method').val('POST');
        $('#cb-title').html('<i class="fas fa-gas-pump me-2"></i>Nouveau plein');
    });

    $(document).on('click', '.btn-edit-cb', function () {
        currentId = $(this).data('id');
        $('#cb-voiture').val($(this).data('voiture'));
        $('#cb-date').val($(this).data('date'));
        $('#cb-litres').val($(this).data('litres'));
        $('#cb-prix').val($(this).data('prix'));
        $('#cb-km').val($(this).data('km'));
        $('#cb-station').val($(this).data('station'));
        $('#cb-facture').val($(this).data('facture'));
        $('#cb-method').val('PUT');
        $('#cb-title').html('<i class="fas fa-pen me-2"></i>Modifier le plein');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/carburants') }}/${currentId}`
            : `{{ route('services-eleves.transport.carburants.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#carburantModal').modal('hide');
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