@extends('admin.layouts.app')

@section('title', 'Activités parascolaires')
@section('page_icon', 'fa-futbol')
@section('page_title', 'Activités parascolaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Activités &amp; Événements</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.activites-evenements.activites.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.activites-evenements.inscriptions.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-users me-1"></i> Inscriptions
    </a>
    <a href="{{ route('services-eleves.activites-evenements.evenements.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-check me-1"></i> Événements
    </a>
    @can('activites.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleActivite"
                data-bs-toggle="modal" data-bs-target="#activiteModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle activité
        </button>
    @endcan
@endsection

@section('css')
<style>
    .act-page { --a-bg-soft:#faf6ee; --a-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--a-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--a-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--a-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--a-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--a-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--a-border); }

    .table-activites thead tr { background:var(--a-bg-soft); }
    .table-activites thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--a-border); white-space:nowrap; }
    .table-activites tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--a-border); font-size:.9rem; vertical-align:middle; }
    .table-activites tbody tr:hover { background:#fdfaf5; }

    .activite-libelle { font-weight:700; color:var(--school-ink); display:block; }
    .activite-sub { font-size:.72rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-type.sportive      { background:#e3f7ec; color:#1c7a4d; }
    .badge-type.culturelle    { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.artistique    { background:#fce4ec; color:#c2185b; }
    .badge-type.scientifique  { background:#e0ecfc; color:#2563eb; }
    .badge-type.linguistique  { background:#f3e5f5; color:#7b1fa2; }
    .badge-type.technologique { background:#e0f7fa; color:#00838f; }
    .badge-type.sociale       { background:#fff3e0; color:#e65100; }
    .badge-type.autre         { background:#eceff2; color:#5b6b7a; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-act { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-act { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--a-bg-soft); border:1px solid var(--a-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--a-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--a-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="act-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-futbol"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Activités totales</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-running"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['sportives'] }}</div>
                    <div class="stat-label">Sportives</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-palette"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['culturelles'] }}</div>
                    <div class="stat-label">Culturelles</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['autres'] }}</div>
                    <div class="stat-label">Autres</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tableau --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Rechercher une activité..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="niveau_id" class="form-select filtre-input">
                        <option value="">— Niveau —</option>
                        @foreach ($niveaux as $n)
                            <option value="{{ $n->id }}" @selected(($filters['niveau_id'] ?? '') == $n->id)>{{ $n->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('services-eleves.activites-evenements.activites.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-activites align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Activité</th>
                            <th>Type</th>
                            <th>Niveau</th>
                            <th>Encadreur</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center">Inscrits</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($activites as $activite)
                        <tr>
                            <td class="ps-4">
                                <span class="activite-libelle">{{ $activite->libelle }}</span>
                                @if ($activite->description)
                                    <span class="activite-sub">{{ \Str::limit($activite->description, 70) }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($activite->type)
                                    <span class="badge-type {{ $activite->type->css() }}">
                                        {{ $activite->type->label() }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-sm">{{ $activite->niveau?->libelle ?? 'Tous niveaux' }}</td>
                            <td class="text-sm">
                                {{ $activite->encadreur ?? '—' }}
                                @if ($activite->contact_encadreur)
                                    <div class="text-xs text-muted">{{ $activite->contact_encadreur }}</div>
                                @endif
                            </td>
                            <td class="num grand">{{ number_format((float) $activite->montant, 0, ',', ' ') }}</td>
                            <td class="text-center">
                                <span class="compteur-badge">{{ $activite->inscriptions_count ?? 0 }}</span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('activites.inscrire')
                                            <li>
                                                <button class="dropdown-item btn-souscrire"
                                                        data-id="{{ $activite->id }}"
                                                        data-libelle="{{ $activite->libelle }}"
                                                        data-montant="{{ $activite->montant }}"
                                                        data-bs-toggle="modal" data-bs-target="#souscriptionModal">
                                                    <i class="fas fa-user-plus"></i> Inscrire un élève
                                                </button>
                                            </li>
                                        @endcan
                                        @can('activites.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-activite"
                                                        data-id="{{ $activite->id }}"
                                                        data-libelle="{{ $activite->libelle }}"
                                                        data-description="{{ $activite->description }}"
                                                        data-montant="{{ $activite->montant }}"
                                                        data-type="{{ $activite->type?->value }}"
                                                        data-encadreur="{{ $activite->encadreur }}"
                                                        data-contact="{{ $activite->contact_encadreur }}"
                                                        data-niveau="{{ $activite->niveau_id }}"
                                                        data-bs-toggle="modal" data-bs-target="#activiteModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('services-eleves.activites-evenements.activites.destroy', $activite) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-futbol"></i><p>Aucune activité enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $activites->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale activité --}}
    @can('activites.gerer')
    <div class="modal fade" id="activiteModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-act">
                <form id="activite-form">
                    @csrf
                    <input type="hidden" name="_method" id="act-method" value="POST">
                    <div class="modal-header modal-header-act">
                        <h5 class="modal-title" id="act-title"><i class="fas fa-futbol me-2"></i>Nouvelle activité</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="ac-libelle" class="form-control" maxlength="200" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant <span class="text-danger">*</span></label>
                                    <input type="number" name="montant" id="ac-montant" step="0.01" min="0" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type</label>
                                    <select name="type" id="ac-type" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($types as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Niveau</label>
                                    <select name="niveau_id" id="ac-niveau" class="form-select">
                                        <option value="">— Tous niveaux —</option>
                                        @foreach ($niveaux as $n)
                                            <option value="{{ $n->id }}">{{ $n->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Encadreur</label>
                                    <input type="text" name="encadreur" id="ac-encadreur" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact encadreur</label>
                                    <input type="text" name="contact_encadreur" id="ac-contact" class="form-control" maxlength="50">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="ac-description" rows="2" class="form-control" maxlength="2000"></textarea>
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

    {{-- Modale souscription --}}
    @can('activites.inscrire')
    <div class="modal fade" id="souscriptionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-act">
                <form id="souscription-form">
                    @csrf
                    <input type="hidden" name="activite_id" id="sc-activite-id">
                    <div class="modal-header modal-header-act">
                        <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Inscrire un élève</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action crée l'engagement <strong>indépendamment du paiement</strong>.
                            Le montant dû apparaîtra dans la situation financière de l'élève.
                        </div>
                        <p class="mb-3">Activité : <strong id="sc-activite-libelle">—</strong></p>
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Élève <span class="text-danger">*</span></label>
                                @include('admin.partials.select-eleve', ['name' => 'eleve_id', 'valeur' => 'eleve'])
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Montant dû (laisser vide = montant activité)</label>
                                <input type="number" name="montant_du" id="sc-montant" step="0.01" min="0" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Date d'inscription</label>
                                <input type="date" name="date_inscription" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Inscrire</button>
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
    // ========== FORMULAIRE ACTIVITÉ ==========
    const $form = $('#activite-form');
    if ($form.length) {
        let currentId = null;
        $('#btnNouvelleActivite').on('click', function () {
            currentId = null;
            $form[0].reset();
            $('#act-method').val('POST');
            $('#act-title').html('<i class="fas fa-futbol me-2"></i>Nouvelle activité');
        });
        $(document).on('click', '.btn-edit-activite', function () {
            currentId = $(this).data('id');
            $('#ac-libelle').val($(this).data('libelle'));
            $('#ac-description').val($(this).data('description'));
            $('#ac-montant').val($(this).data('montant'));
            $('#ac-type').val($(this).data('type'));
            $('#ac-encadreur').val($(this).data('encadreur'));
            $('#ac-contact').val($(this).data('contact'));
            $('#ac-niveau').val($(this).data('niveau') ?? '');
            $('#act-method').val('PUT');
            $('#act-title').html('<i class="fas fa-pen me-2"></i>Modifier l\'activité');
        });
        $form.on('submit', function (e) {
            e.preventDefault();
            const url = currentId
                ? `{{ url('services-eleves/activites-evenements/activites') }}/${currentId}`
                : `{{ route('services-eleves.activites-evenements.activites.store') }}`;
            $.ajax({
                url, method: 'POST', data: $form.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#activiteModal').modal('hide');
                    window.showToastThenReload(res.message || 'Enregistré.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                    } else {
                        window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
                    }
                }
            });
        });
    }

    // ========== SOUSCRIPTION ==========
    $('.btn-souscrire').on('click', function () {
        $('#sc-activite-id').val($(this).data('id'));
        $('#sc-activite-libelle').text($(this).data('libelle'));
        $('#sc-montant').attr('placeholder', $(this).data('montant'));
    });

    $('#souscription-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.activites-evenements.activites.souscrire") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#souscriptionModal').modal('hide');
                window.showToastThenReload(res.message || 'Élève inscrit.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
                }
            }
        });
    });
});
</script>
@endpush