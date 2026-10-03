@extends('admin.layouts.app')

@section('title', 'Inscriptions cantine')
@section('page_icon', 'fa-utensils')
@section('page_title', 'Cantine scolaire')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item active">Cantine</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.cantine.inscriptions.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('services-eleves.cantine.menus.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-book-open me-1"></i> Menus
    </a>
    <a href="{{ route('services-eleves.cantine.preparations.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-fire me-1"></i> Préparations
    </a>
    @can('cantine.inscrire')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleInscription"
                data-bs-toggle="modal" data-bs-target="#inscriptionModal">
            <i class="fas fa-plus-circle me-1"></i> Inscrire un élève
        </button>
    @endcan
@endsection

@section('css')
<style>
    .cantine-page { --c-bg-soft:#faf6ee; --c-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    /* Menu du jour */
    .menu-jour-card {
        background: linear-gradient(135deg, #1c7a4d, #2ecc71);
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        box-shadow: 0 8px 24px rgba(28,122,77,.18);
        margin-bottom:1.25rem;
    }
    .menu-jour-title { font-size:.85rem; opacity:.85; text-transform:uppercase; letter-spacing:1px; font-weight:600; }
    .menu-jour-libelle { font-size:1.3rem; font-weight:700; margin:4px 0 8px; }
    .menu-jour-produits { font-size:.85rem; opacity:.9; }
    .menu-jour-produits .item { display:inline-block; background:rgba(255,255,255,.18); padding:2px 10px; border-radius:999px; margin-right:6px; margin-top:4px; }

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

    .table-insc thead tr { background:var(--c-bg-soft); }
    .table-insc thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--c-border); white-space:nowrap; }
    .table-insc tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--c-border); font-size:.9rem; vertical-align:middle; }
    .table-insc tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.actif     { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.abandonne { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-cantine { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-cantine { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--c-bg-soft); border:1px solid var(--c-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--c-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--c-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="cantine-page">

    {{-- Menu du jour --}}
    @if ($menuDuJour->isNotEmpty())
        <div class="menu-jour-card">
            <div class="menu-jour-title"><i class="fas fa-utensils me-1"></i> Menu du jour — {{ now()->translatedFormat('l d F Y') }}</div>
            @foreach ($menuDuJour as $menu)
                <div class="menu-jour-libelle">{{ $menu->libelle }}</div>
                @if ($menu->menuProduits->isNotEmpty())
                    <div class="menu-jour-produits">
                        @foreach ($menu->menuProduits as $mp)
                            <span class="item">
                                {{ $mp->produit?->libelle }}
                                <strong>{{ number_format((float) $mp->quantite, 2, ',', ' ') }}</strong>
                                {{ $mp->produit?->unite_stock }}
                            </span>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['total_actifs'] }}</div>
                    <div class="stat-label">Élèves inscrits</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($compteurs['revenus_mensuels'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Revenus mensuels</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($compteurs['revenus_annuels'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Total annuel attendu</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $menuSemaine->count() }}</div>
                    <div class="stat-label">Menus cette semaine</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inscriptions --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Nom, matricule..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') === (string) $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('services-eleves.cantine.inscriptions.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-insc align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Classe</th>
                            <th>Période</th>
                            <th class="text-end">Mensuel</th>
                            <th class="text-end">Total dû</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($inscriptions as $insc)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">
                                    {{ $insc->inscription?->eleve?->nom }} {{ $insc->inscription?->eleve?->prenom }}
                                </div>
                                <div class="text-xs text-muted">{{ $insc->inscription?->eleve?->matricule }}</div>
                            </td>
                            <td class="text-sm">{{ $insc->inscription?->classe?->libelle ?? '—' }}</td>
                            <td class="text-sm">
                                {{ optional($insc->date_debut)->format('d/m/Y') }}
                                @if ($insc->date_fin)
                                    → {{ $insc->date_fin->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="num">{{ number_format((float) $insc->montant_mensuel, 0, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format((float) $insc->montant_total_du, 0, ',', ' ') }}</td>
                            <td>
                                <span class="badge-statut {{ $insc->statut?->css() }}">
                                    <span class="badge-dot"></span>{{ $insc->statut?->label() }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @if ($insc->isActif())
                                            @can('cantine.gerer')
                                                <li>
                                                    <button class="dropdown-item text-danger btn-abandonner"
                                                            data-id="{{ $insc->id }}"
                                                            data-eleve="{{ $insc->inscription?->eleve?->nom }} {{ $insc->inscription?->eleve?->prenom }}"
                                                            data-bs-toggle="modal" data-bs-target="#abandonModal">
                                                        <i class="fas fa-times"></i> Abandonner
                                                    </button>
                                                </li>
                                            @endcan
                                        @else
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i>
                                                    Abandonné le {{ optional($insc->date_abandon)->format('d/m/Y') }}
                                                </span>
                                            </li>
                                        @endif                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-utensils"></i><p>Aucune inscription cantine.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $inscriptions->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modal souscription --}}
    @can('cantine.inscrire')
    <div class="modal fade" id="inscriptionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cantine">
                <form id="inscription-form">
                    @csrf
                    <div class="modal-header modal-header-cantine">
                        <h5 class="modal-title"><i class="fas fa-utensils me-2"></i>Inscrire un élève</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action crée l'engagement <strong>indépendamment du paiement</strong>.
                            Le montant dû apparaîtra dans la situation financière de l'élève.
                        </div>
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Élève <span class="text-danger">*</span></label>
                                @include('admin.partials.select-eleve')
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Date de début <span class="text-danger">*</span></label>
                                    <input type="date" name="date_debut" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Montant mensuel <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_mensuel" step="0.01" min="0.01" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nombre de mois <span class="text-danger">*</span></label>
                                    <input type="number" name="nombre_mois" min="1" max="12" value="9" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Frais lié (optionnel)</label>
                                    <select name="frais_ecole_id" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($frais as $f)
                                            <option value="{{ $f->id }}">{{ $f->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
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

    {{-- Modal abandon --}}
    @can('cantine.gerer')
    <div class="modal fade" id="abandonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-cantine">
                <form id="abandon-form" method="POST" action="">
                    @csrf
                    <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Abandonner l'abonnement cantine</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Abandonner l'abonnement de <strong id="abandon-eleve">—</strong> ?</p>
                        <div class="form-section">
                            <label class="form-label small fw-semibold">Motif <span class="text-danger">*</span></label>
                            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
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
    $('#inscription-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.cantine.inscriptions.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#inscriptionModal').modal('hide');
                window.showToastThenReload(res.message || 'Inscription enregistrée.', 'success');
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

    $('.btn-abandonner').on('click', function () {
        $('#abandon-eleve').text($(this).data('eleve'));
        $('#abandon-form').attr('action', `{{ url('services-eleves/cantine/inscriptions') }}/${$(this).data('id')}/abandonner`);
    });

    $('#abandon-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#abandonModal').modal('hide');
                window.showToastThenReload(res.message || 'Abandon enregistré.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
            }
        });
    });
});
</script>
@endpush