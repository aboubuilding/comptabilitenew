@extends('admin.layouts.app')

@section('title', 'Magasins')
@section('page_icon', 'fa-warehouse')
@section('page_title', 'Magasins & Entrepôts')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Magasins</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.produits.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-boxes me-1"></i> Produits
    </a>
    @can('stock.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauMagasin"
                data-bs-toggle="modal" data-bs-target="#magasinModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau magasin
        </button>
    @endcan
@endsection

@section('css')
<style>
    .mag-page { --mg-bg-soft:#faf6ee; --mg-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--mg-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--mg-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--mg-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--mg-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--mg-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--mg-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--mg-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-magasins thead tr { background:var(--mg-bg-soft); }
    .table-magasins thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--mg-border); white-space:nowrap; }
    .table-magasins tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--mg-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-magasins tbody tr:hover { background:#fdfaf5; }

    .magasin-libelle { font-weight:700; color:var(--school-ink); display:block; }
    .magasin-desc { font-size:.75rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }

    .badge-magasin { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-magasin.entrepot { background:#e0ecfc; color:#2563eb; }
    .badge-magasin.boutique { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-magasin { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-magasin { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--mg-bg-soft); border:1px solid var(--mg-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--mg-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--mg-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="mag-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-warehouse"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $magasins->total() }}</div>
                    <div class="stat-label">Magasins actifs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-map-marker-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $magasins->sum('stocks_actuels_count') }}</div>
                    <div class="stat-label">Produits en stock (total)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4 d-flex align-items-end justify-content-md-end">
            <span class="compteur-badge">{{ $magasins->total() }} magasin(s)</span>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Rechercher un magasin..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('logistique.magasins.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-magasins align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Magasin</th>
                            <th>Type</th>
                            <th>Responsable</th>
                            <th>Contact</th>
                            <th class="text-center">Produits en stock</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($magasins as $m)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.magasins.show', $m) }}" class="text-decoration-none">
                                    <span class="magasin-libelle">{{ $m->libelle }}</span>
                                    @if ($m->description)
                                        <span class="magasin-desc">{{ \Str::limit($m->description, 60) }}</span>
                                    @endif
                                </a>
                            </td>
                            <td>
                                <span class="badge-magasin {{ $m->type?->css() }}">
                                    <span class="badge-dot"></span>{{ $m->type?->label() }}
                                </span>
                            </td>
                            <td class="text-sm">{{ $m->responsable ?? '—' }}</td>
                            <td class="text-sm">
                                @if ($m->telephone)
                                    <div><i class="fas fa-phone me-1 text-muted"></i>{{ $m->telephone }}</div>
                                @endif
                                @if ($m->adresse)
                                    <div class="text-xs text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ \Str::limit($m->adresse, 40) }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="compteur-badge">{{ $m->stocks_actuels_count ?? 0 }}</span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.magasins.show', $m) }}">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </a>
                                        </li>
                                        @can('stock.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-magasin"
                                                    data-id="{{ $m->id }}"
                                                    data-libelle="{{ $m->libelle }}"
                                                    data-responsable="{{ $m->responsable }}"
                                                    data-description="{{ $m->description }}"
                                                    data-adresse="{{ $m->adresse }}"
                                                    data-telephone="{{ $m->telephone }}"
                                                    data-type="{{ $m->type?->value }}"
                                                    data-bs-toggle="modal" data-bs-target="#magasinModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('stock.gerer')
                                        <li>
                                            <form action="{{ route('logistique.magasins.destroy', $m) }}" method="POST" class="form-confirm-delete"
                                                  data-confirm-title="Désactiver ce magasin ?">
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
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-warehouse"></i><p>Aucun magasin enregistré.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $magasins->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('stock.gerer')
    <div class="modal fade" id="magasinModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-magasin">
                <form id="magasin-form">
                    @csrf
                    <input type="hidden" name="_method" id="magasin-method" value="POST">
                    <div class="modal-header modal-header-magasin">
                        <h5 class="modal-title" id="magasin-title"><i class="fas fa-warehouse me-2"></i>Nouveau magasin</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Identité</div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="m-libelle" class="form-control" maxlength="150" required>
                                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="m-type" class="form-select" required>
                                        @foreach ($types as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Responsable</label>
                                    <input type="text" name="responsable" id="m-responsable" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" name="telephone" id="m-telephone" class="form-control" maxlength="30">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" name="adresse" id="m-adresse" class="form-control" maxlength="500">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="m-description" rows="2" class="form-control" maxlength="1000"></textarea>
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
    const $form = $('#magasin-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauMagasin').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#magasin-method').val('POST');
        $('#magasin-title').html('<i class="fas fa-warehouse me-2"></i>Nouveau magasin');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-magasin', function () {
        currentId = $(this).data('id');
        $('#m-libelle').val($(this).data('libelle'));
        $('#m-responsable').val($(this).data('responsable'));
        $('#m-description').val($(this).data('description'));
        $('#m-adresse').val($(this).data('adresse'));
        $('#m-telephone').val($(this).data('telephone'));
        $('#m-type').val($(this).data('type'));
        $('#magasin-method').val('PUT');
        $('#magasin-title').html('<i class="fas fa-pen me-2"></i>Modifier le magasin');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('logistique/magasins') }}/${currentId}`
            : `{{ route('logistique.magasins.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#magasinModal').modal('hide');
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