@extends('admin.layouts.app')

@section('title', 'Zones')
@section('page_icon', 'fa-map-marked-alt')
@section('page_title', 'Zones de transport')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Zones</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.voitures.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-bus me-1"></i> Véhicules
    </a>
    <a href="{{ route('services-eleves.transport.lignes.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-route me-1"></i> Lignes
    </a>
    @can('transport.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleZone"
                data-bs-toggle="modal" data-bs-target="#zoneModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle zone
        </button>
    @endcan
@endsection

@section('css')
<style>
    .zone-page { --z-bg-soft:#faf6ee; --z-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--z-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--z-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--z-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--z-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--z-border); }

    .table-zones thead tr { background:var(--z-bg-soft); }
    .table-zones thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--z-border); }
    .table-zones tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--z-border); font-size:.9rem; vertical-align:middle; }
    .table-zones tbody tr:hover { background:#fdfaf5; }

    .zone-libelle { font-weight:700; color:var(--school-ink); }
    .zone-desc { font-size:.75rem; color:var(--school-muted); }

    .chip { display:inline-flex; align-items:center; gap:4px; background:var(--z-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:600; padding:.25em .7em; border-radius:999px; border:1px solid var(--z-border); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-zone { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-zone { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--z-bg-soft); border:1px solid var(--z-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--z-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--z-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="zone-page">
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Rechercher une zone..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-3 text-end">
                    <span class="compteur-badge">{{ $zones->total() }} zone(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-zones align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Zone</th>
                            <th>Chauffeur assigné</th>
                            <th>Véhicule assigné</th>
                            <th class="text-center">Abonnements</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($zones as $z)
                        <tr>
                            <td class="ps-4">
                                <div class="zone-libelle">{{ $z->libelle }}</div>
                                @if ($z->description)
                                    <div class="zone-desc">{{ \Str::limit($z->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($z->chauffeur)
                                    <i class="fas fa-user-tie me-1 text-muted"></i>{{ $z->chauffeur->nom_complet }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($z->voiture)
                                    <span class="chip"><i class="fas fa-bus"></i>{{ $z->voiture->plaque }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center"><span class="compteur-badge">{{ $z->abonnements_count ?? 0 }}</span></td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('transport.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-zone"
                                                    data-id="{{ $z->id }}"
                                                    data-libelle="{{ $z->libelle }}"
                                                    data-description="{{ $z->description }}"
                                                    data-chauffeur="{{ $z->chauffeur_id }}"
                                                    data-voiture="{{ $z->voiture_id }}"
                                                    data-bs-toggle="modal" data-bs-target="#zoneModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('transport.gerer')
                                        <li>
                                            <form action="{{ route('services-eleves.transport.zones.destroy', $z) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-map-marked-alt"></i><p>Aucune zone enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $zones->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('transport.gerer')
    <div class="modal fade" id="zoneModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-zone">
                <form id="zone-form">
                    @csrf
                    <input type="hidden" name="_method" id="zone-method" value="POST">
                    <div class="modal-header modal-header-zone">
                        <h5 class="modal-title" id="zone-title"><i class="fas fa-map-marked-alt me-2"></i>Nouvelle zone</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="z-libelle" class="form-control" required maxlength="150">
                                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="z-description" rows="2" class="form-control" maxlength="1000"></textarea>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Chauffeur</label>
                                    <select name="chauffeur_id" id="z-chauffeur" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($chauffeurs as $c)
                                            <option value="{{ $c->id }}">{{ $c->nom_complet }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Véhicule</label>
                                    <select name="voiture_id" id="z-voiture" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($voitures as $v)
                                            <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                                        @endforeach
                                    </select>
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
    const $form = $('#zone-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleZone').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#zone-method').val('POST');
        $('#zone-title').html('<i class="fas fa-map-marked-alt me-2"></i>Nouvelle zone');
    });

    $(document).on('click', '.btn-edit-zone', function () {
        currentId = $(this).data('id');
        $('#z-libelle').val($(this).data('libelle'));
        $('#z-description').val($(this).data('description'));
        $('#z-chauffeur').val($(this).data('chauffeur') ?? '');
        $('#z-voiture').val($(this).data('voiture') ?? '');
        $('#zone-method').val('PUT');
        $('#zone-title').html('<i class="fas fa-pen me-2"></i>Modifier la zone');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/zones') }}/${currentId}`
            : `{{ route('services-eleves.transport.zones.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#zoneModal').modal('hide');
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