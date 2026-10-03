@extends('admin.layouts.app')

@section('title', 'Préparations repas')
@section('page_icon', 'fa-fire')
@section('page_title', 'Préparations de repas')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.cantine.inscriptions.index') }}">Cantine</a></li>
    <li class="breadcrumb-item active">Préparations</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.cantine.preparations.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('cantine.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvellePrep"
                data-bs-toggle="modal" data-bs-target="#preparationModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle préparation
        </button>
    @endcan
@endsection

@section('contenu')
<div class="container-fluid p-0">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-fire"></i></div>
                <div class="stat-info"><div class="stat-value">{{ $stats['nb'] }}</div>
                <div class="stat-label">Préparations</div></div></div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-utensils"></i></div>
                <div class="stat-info"><div class="stat-value">{{ $stats['parts'] }}</div>
                <div class="stat-label">Parts servies</div></div></div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info"><div class="stat-value">{{ number_format($stats['cout_total'], 0, ',', ' ') }}</div>
                <div class="stat-label">Coût total</div></div></div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card"><div class="stat-icon red"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info"><div class="stat-value">{{ number_format($stats['cout_moyen_par_part'], 0, ',', ' ') }}</div>
                <div class="stat-label">Coût moyen / part</div></div></div>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="menu_id" class="form-select filtre-input">
                        <option value="">— Menu —</option>
                        @foreach ($menus as $m)
                            <option value="{{ $m->id }}" @selected(($filters['menu_id'] ?? '') == $m->id)>
                                {{ $m->libelle }} ({{ optional($m->date_service)->format('d/m/Y') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-insc align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Menu</th>
                            <th>Magasin</th>
                            <th class="text-end">Parts</th>
                            <th class="text-end">Coût réel</th>
                            <th class="text-end">Coût / part</th>
                            <th>Responsable</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($preparations as $p)
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($p->date_preparation)->format('d/m/Y') }}</td>
                            <td class="text-sm">
                                <div class="fw-semibold">{{ $p->menu?->libelle ?? '—' }}</div>
                                <div class="text-xs text-muted">{{ $p->menu?->type_repas?->label() }}</div>
                            </td>
                            <td class="text-sm">{{ $p->magasin?->libelle ?? '—' }}</td>
                            <td class="num grand">{{ $p->nombre_parts }}</td>
                            <td class="num">{{ number_format((float) $p->cout_reel, 0, ',', ' ') }}</td>
                            <td class="num">
                                {{ $p->nombre_parts > 0 ? number_format((float) $p->cout_reel / $p->nombre_parts, 0, ',', ' ') : '—' }}
                            </td>
                            <td class="text-sm text-muted">{{ $p->responsable?->name ?? '—' }}</td>
                            <td>
                                @if ($p->stock_decremente)
                                    <span class="badge-statut actif" style="background:#e3f7ec;color:#1c7a4d;"><span class="badge-dot"></span>Déduit</span>
                                @else
                                    <span class="badge-statut abandonne"><span class="badge-dot"></span>Non déduit</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state"><i class="fas fa-fire"></i><p>Aucune préparation enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $preparations->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('cantine.gerer')
    <div class="modal fade" id="preparationModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cantine">
                <form id="preparation-form">
                    @csrf
                    <div class="modal-header modal-header-cantine">
                        <h5 class="modal-title"><i class="fas fa-fire me-2"></i>Nouvelle préparation</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
                            <i class="fas fa-info-circle me-1"></i>
                            Le stock sera automatiquement décrémenté selon les produits du menu et le nombre de parts servies.
                        </div>
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Menu <span class="text-danger">*</span></label>
                                <select name="menu_id" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($menus as $m)
                                        <option value="{{ $m->id }}">{{ $m->libelle }} ({{ optional($m->date_service)->format('d/m/Y') }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Magasin source <span class="text-danger">*</span></label>
                                <select name="magasin_id" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($magasins as $m)
                                        <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_preparation" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre de parts <span class="text-danger">*</span></label>
                                    <input type="number" name="nombre_parts" min="1" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Coût réel (laisser vide = calcul auto)</label>
                                    <input type="number" name="cout_reel" step="0.01" min="0" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Observations</label>
                                    <input type="text" name="observations" class="form-control" maxlength="2000">
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
    $('#preparation-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.cantine.preparations.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#preparationModal').modal('hide');
                window.showToastThenReload(res.message || 'Préparation enregistrée.', 'success');
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