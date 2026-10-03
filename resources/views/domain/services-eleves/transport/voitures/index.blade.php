@extends('admin.layouts.app')

@section('title', 'Véhicules')
@section('page_icon', 'fa-bus')
@section('page_title', 'Parc de véhicules')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Transport scolaire</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.voitures.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.transport.chauffeurs.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-user-tie me-1"></i> Chauffeurs
    </a>
    <a href="{{ route('services-eleves.transport.zones.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-map-marked-alt me-1"></i> Zones
    </a>
    <a href="{{ route('services-eleves.transport.abonnements.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-id-card me-1"></i> Abonnements
    </a>
    @can('transport.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleVoiture"
                data-bs-toggle="modal" data-bs-target="#voitureModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau véhicule
        </button>
    @endcan
@endsection

@section('css')
<style>
    .transport-page { --tr-bg-soft:#faf6ee; --tr-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--tr-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--tr-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--tr-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--tr-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--tr-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--tr-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--tr-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-vehicules thead tr { background:var(--tr-bg-soft); }
    .table-vehicules thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--tr-border); white-space:nowrap; }
    .table-vehicules tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--tr-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-vehicules tbody tr:hover { background:#fdfaf5; }

    .plaque { font-family:monospace; font-weight:700; color:var(--school-red-dark); font-size:.95rem; }
    .vehicule-modele { font-size:.8rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.disponible  { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.maintenance { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.sorti       { background:#e0ecfc; color:#2563eb; }
    .badge-statut.reforme     { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-transport { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-transport { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--tr-bg-soft); border:1px solid var(--tr-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--tr-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--tr-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="transport-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-bus"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['total'] }}</div>
                    <div class="stat-label">Véhicules total</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['disponible'] }}</div>
                    <div class="stat-label">Disponibles</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-tools"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['maintenance'] }}</div>
                    <div class="stat-label">En maintenance</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-archive"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['reforme'] }}</div>
                    <div class="stat-label">Réformés</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Plaque, marque, modèle..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('services-eleves.transport.voitures.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-vehicules align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Plaque / Modèle</th>
                            <th>Chauffeur affecté</th>
                            <th>Zones desservies</th>
                            <th class="text-end">Kilométrage</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($voitures as $v)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('services-eleves.transport.voitures.show', $v) }}" class="text-decoration-none">
                                    <div class="plaque">{{ $v->plaque }}</div>
                                    <div class="vehicule-modele">{{ $v->marque }} {{ $v->modele }}</div>
                                </a>
                            </td>
                            <td class="text-sm">
                                @php $chauffeur = $v->chauffeurActuel(); @endphp
                                @if ($chauffeur)
                                    <i class="fas fa-user-tie me-1 text-muted"></i>{{ $chauffeur->nom_complet }}
                                    <div class="text-xs text-muted">{{ $chauffeur->telephone }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($v->zones->isNotEmpty())
                                    @foreach ($v->zones->take(2) as $zone)
                                        <span class="compteur-badge" style="font-size:.7rem;">{{ $zone->libelle }}</span>
                                    @endforeach
                                    @if ($v->zones->count() > 2)
                                        <span class="text-xs text-muted">+{{ $v->zones->count() - 2 }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $v->kilometrage_actuel, 0, ',', ' ') }} km</td>
                            <td>
                                <span class="badge-statut {{ $v->statut?->css() }}">
                                    <span class="badge-dot"></span>{{ $v->statut?->label() }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('services-eleves.transport.voitures.show', $v) }}"><i class="fas fa-eye"></i> Détail</a></li>
                                        @can('transport.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-voiture"
                                                        data-id="{{ $v->id }}"
                                                        data-marque="{{ $v->marque }}"
                                                        data-modele="{{ $v->modele }}"
                                                        data-plaque="{{ $v->plaque }}"
                                                        data-places="{{ $v->nombre_place }}"
                                                        data-annee-fab="{{ $v->annee_fabrication }}"
                                                        data-chassis="{{ $v->numero_chassis }}"
                                                        data-date-achat="{{ optional($v->date_achat)->toDateString() }}"
                                                        data-prix="{{ $v->prix_achat }}"
                                                        data-fournisseur="{{ $v->fournisseur }}"
                                                        data-km="{{ $v->kilometrage_actuel }}"
                                                        data-statut="{{ $v->statut?->value }}"
                                                        data-bs-toggle="modal" data-bs-target="#voitureModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('services-eleves.transport.voitures.destroy', $v) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="6"><div class="empty-state"><i class="fas fa-bus"></i><p>Aucun véhicule enregistré.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $voitures->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('transport.gerer')
    <div class="modal fade" id="voitureModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-transport">
                <form id="voiture-form">
                    @csrf
                    <input type="hidden" name="_method" id="voiture-method" value="POST">
                    <div class="modal-header modal-header-transport">
                        <h5 class="modal-title" id="voiture-title"><i class="fas fa-bus me-2"></i>Nouveau véhicule</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Identification</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Plaque <span class="text-danger">*</span></label>
                                    <input type="text" name="plaque" id="v-plaque" class="form-control" maxlength="30" required>
                                    <div class="text-danger text-xs mt-1" id="plaque-error"></div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Marque <span class="text-danger">*</span></label>
                                    <input type="text" name="marque" id="v-marque" class="form-control" maxlength="100" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Modèle</label>
                                    <input type="text" name="modele" id="v-modele" class="form-control" maxlength="100">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Année de fabrication</label>
                                    <input type="number" name="annee_fabrication" id="v-annee" class="form-control" min="1950" max="{{ date('Y') + 1 }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre de places</label>
                                    <input type="number" name="nombre_place" id="v-places" class="form-control" min="1" max="200">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">N° châssis</label>
                                    <input type="text" name="numero_chassis" id="v-chassis" class="form-control" maxlength="100">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-file-invoice-dollar"></i> Achat & suivi</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date d'achat</label>
                                    <input type="date" name="date_achat" id="v-date" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Prix d'achat</label>
                                    <input type="number" name="prix_achat" id="v-prix" step="0.01" min="0" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Fournisseur</label>
                                    <input type="text" name="fournisseur" id="v-fournisseur" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kilométrage actuel</label>
                                    <input type="number" name="kilometrage_actuel" id="v-km" step="1" min="0" value="0" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Statut <span class="text-danger">*</span></label>
                                    <select name="statut" id="v-statut" class="form-select" required>
                                        @foreach ($statuts as $val => $lbl)
                                            <option value="{{ $val }}">{{ $lbl }}</option>
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
    const $form = $('#voiture-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleVoiture').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#voiture-method').val('POST');
        $('#voiture-title').html('<i class="fas fa-bus me-2"></i>Nouveau véhicule');
    });

    $(document).on('click', '.btn-edit-voiture', function () {
        currentId = $(this).data('id');
        $('#v-plaque').val($(this).data('plaque'));
        $('#v-marque').val($(this).data('marque'));
        $('#v-modele').val($(this).data('modele'));
        $('#v-annee').val($(this).data('annee-fab'));
        $('#v-places').val($(this).data('places'));
        $('#v-chassis').val($(this).data('chassis'));
        $('#v-date').val($(this).data('date-achat'));
        $('#v-prix').val($(this).data('prix'));
        $('#v-fournisseur').val($(this).data('fournisseur'));
        $('#v-km').val($(this).data('km'));
        $('#v-statut').val($(this).data('statut'));
        $('#voiture-method').val('PUT');
        $('#voiture-title').html('<i class="fas fa-pen me-2"></i>Modifier le véhicule');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('services-eleves/transport/voitures') }}/${currentId}`
            : `{{ route('services-eleves.transport.voitures.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#voitureModal').modal('hide');
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
});
</script>
@endpush