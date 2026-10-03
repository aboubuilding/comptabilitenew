@extends('admin.layouts.app')

@section('title', 'Abonnements bus')
@section('page_icon', 'fa-id-card')
@section('page_title', 'Abonnements bus')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Abonnements</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.abonnements.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('transport.abonner')
        <button type="button" class="btn btn-nouveau" id="btnNouvelAbonnement"
                data-bs-toggle="modal" data-bs-target="#abonnementModal">
            <i class="fas fa-plus-circle me-1"></i> Souscrire un élève
        </button>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes voitures/index */
    .abon-page { --ab-bg-soft:#faf6ee; --ab-border:#ece4d6; }

    .stat-card { background:#fff; border:1px solid var(--ab-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--ab-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--ab-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--ab-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--ab-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--ab-border); }

    .table-ab thead tr { background:var(--ab-bg-soft); }
    .table-ab thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--ab-border); white-space:nowrap; }
    .table-ab tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--ab-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-ab tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-sens { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-sens.aller         { background:#e3f7ec; color:#1c7a4d; }
    .badge-sens.retour        { background:#e0ecfc; color:#2563eb; }
    .badge-sens.aller-retour  { background:#fdf4e0; color:var(--school-gold-dark); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.actif     { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.abandonne { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-ab { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-ab { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--ab-bg-soft); border:1px solid var(--ab-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; color:var(--school-red); margin-bottom:.75rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--ab-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid var(--ab-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="abon-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-id-card"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['total_actifs'] }}</div>
                    <div class="stat-label">Abonnements actifs</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($compteurs['revenus_mensuels'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Revenus mensuels</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($compteurs['revenus_annuels'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Total annuel attendu</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-map-marked-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ count($parZone) }}</div>
                    <div class="stat-label">Zones desservies</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Répartition par zone --}}
    @if (! empty($parZone))
        <div class="card main-card mb-4">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-map-marked-alt me-1" style="color:var(--school-gold-dark);"></i> Abonnements actifs par zone</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($parZone as $zone => $nb)
                        <span class="compteur-badge">
                            <i class="fas fa-map-marker-alt me-1"></i>{{ $zone }} : <strong>{{ $nb }}</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Tableau --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Nom, matricule..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="zone_id" class="form-select filtre-input">
                        <option value="">— Zone —</option>
                        @foreach ($zones as $z)
                            <option value="{{ $z->id }}" @selected(($filters['zone_id'] ?? '') == $z->id)>{{ $z->libelle }}</option>
                        @endforeach
                    </select>
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
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        <option value="1" @selected(($filters['statut'] ?? '') == 1)>Actif</option>
                        <option value="0" @selected(($filters['statut'] ?? '') === '0')>Abandonné</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('services-eleves.transport.abonnements.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ab align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Zone / Véhicule</th>
                            <th>Sens</th>
                            <th class="text-end">Mensuel</th>
                            <th class="text-end">Total dû</th>
                            <th>Statut</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($abonnements as $a)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">
                                    {{ $a->inscription?->eleve?->nom }} {{ $a->inscription?->eleve?->prenom }}
                                </div>
                                <div class="text-xs text-muted">{{ $a->inscription?->eleve?->matricule }}</div>
                            </td>
                            <td class="text-sm">
                                @if ($a->zone)
                                    <div><i class="fas fa-map-marker-alt me-1 text-muted"></i>{{ $a->zone->libelle }}</div>
                                @endif
                                @if ($a->voiture)
                                    <div class="text-xs text-muted"><i class="fas fa-bus me-1"></i>{{ $a->voiture->plaque }}</div>
                                @endif
                                @if ($a->point_arret)
                                    <div class="text-xs text-muted"><i class="fas fa-map-pin me-1"></i>{{ $a->point_arret }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge-sens {{ $a->sens?->css() }}">
                                    <span class="badge-dot"></span>{{ $a->sens?->label() }}
                                </span>
                            </td>
                            <td class="num">{{ number_format((float) $a->montant_mensuel, 0, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format((float) $a->montant_total_du, 0, ',', ' ') }}</td>
                            <td>
                                <span class="badge-statut {{ $a->statut == 1 ? 'actif' : 'abandonne' }}">
                                    <span class="badge-dot"></span>{{ $a->statut == 1 ? 'Actif' : 'Abandonné' }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('transport.gerer')
                                            <li>
                                                <button class="dropdown-item btn-assigner"
                                                        data-id="{{ $a->id }}"
                                                        data-zone="{{ $a->zone_id }}"
                                                        data-voiture="{{ $a->voiture_id }}"
                                                        data-ligne="{{ $a->ligne_id }}"
                                                        data-sens="{{ $a->sens?->value }}"
                                                        data-arret="{{ $a->point_arret }}"
                                                        data-bs-toggle="modal" data-bs-target="#assignerModal">
                                                    <i class="fas fa-edit"></i> Assigner
                                                </button>
                                            </li>
                                        @endcan
                                        @if ($a->statut == 1)
                                            @can('transport.gerer')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger btn-abandonner"
                                                            data-id="{{ $a->id }}"
                                                            data-eleve="{{ $a->inscription?->eleve?->nom }} {{ $a->inscription?->eleve?->prenom }}"
                                                            data-bs-toggle="modal" data-bs-target="#abandonModal">
                                                        <i class="fas fa-times"></i> Abandonner
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-id-card"></i><p>Aucun abonnement.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $abonnements->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale souscription --}}
    @can('transport.abonner')
    <div class="modal fade" id="abonnementModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-ab">
                <form id="abonnement-form">
                    @csrf
                    <div class="modal-header modal-header-ab">
                        <h5 class="modal-title"><i class="fas fa-id-card me-2"></i>Souscrire un élève au bus</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
                            <i class="fas fa-info-circle me-1"></i>
                            Cette action crée l'engagement <strong>indépendamment du paiement</strong>.
                            Le montant dû apparaîtra immédiatement dans la situation financière de l'élève.
                        </div>

                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-user-graduate"></i> Élève</div>
                            <div>
                                <label class="form-label">Élève <span class="text-danger">*</span></label>
                                @include('admin.partials.select-eleve')
                            </div>
                        </div>

                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-money-bill-wave"></i> Abonnement</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date de début <span class="text-danger">*</span></label>
                                    <input type="date" name="date_debut" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Montant mensuel <span class="text-danger">*</span></label>
                                    <input type="number" name="montant_mensuel" step="0.01" min="0.01" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nombre de mois <span class="text-danger">*</span></label>
                                    <input type="number" name="nombre_mois" min="1" max="12" value="9" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-route"></i> Trajet (optionnel)</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Zone</label>
                                    <select name="zone_id" class="form-select">
                                        <option value="">— Aucune —</option>
                                        @foreach ($zones as $z)
                                            <option value="{{ $z->id }}">{{ $z->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Véhicule</label>
                                    <select name="voiture_id" class="form-select">
                                        <option value="">— Aucun —</option>
                                        @foreach ($voitures as $v)
                                            <option value="{{ $v->id }}">{{ $v->plaque }} ({{ $v->marque }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ligne</label>
                                    <select name="ligne_id" class="form-select">
                                        <option value="">— Aucune —</option>
                                        @foreach ($lignes as $l)
                                            <option value="{{ $l->id }}">{{ $l->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sens <span class="text-danger">*</span></label>
                                    <select name="sens" class="form-select" required>
                                        @foreach ($sens as $v => $l)
                                            <option value="{{ $v }}" @selected($v === 'aller_retour')>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Point d'arrêt</label>
                                    <input type="text" name="point_arret" class="form-control" maxlength="200" placeholder="Ex : Carrefour Marché">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Souscrire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- Modale assignation --}}
    @can('transport.gerer')
    <div class="modal fade" id="assignerModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-ab">
                <form id="assigner-form">
                    @csrf
                    <div class="modal-header modal-header-ab">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Assigner l'abonnement</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Zone</label>
                                <select name="zone_id" id="assign-zone" class="form-select">
                                    <option value="">— Aucune —</option>
                                    @foreach ($zones as $z)
                                        <option value="{{ $z->id }}">{{ $z->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Véhicule</label>
                                <select name="voiture_id" id="assign-voiture" class="form-select">
                                    <option value="">— Aucun —</option>
                                    @foreach ($voitures as $v)
                                        <option value="{{ $v->id }}">{{ $v->plaque }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ligne</label>
                                <select name="ligne_id" id="assign-ligne" class="form-select">
                                    <option value="">— Aucune —</option>
                                    @foreach ($lignes as $l)
                                        <option value="{{ $l->id }}">{{ $l->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sens</label>
                                <select name="sens" id="assign-sens" class="form-select">
                                    @foreach ($sens as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Point d'arrêt</label>
                                <input type="text" name="point_arret" id="assign-arret" class="form-control" maxlength="200">
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

    {{-- Modale abandon --}}
    @can('transport.gerer')
    <div class="modal fade" id="abandonModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-ab">
                <form id="abandon-form" method="POST" action="">
                    @csrf
                    <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Abandonner l'abonnement</h5>
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
    // Souscription
    $('#abonnement-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("services-eleves.transport.abonnements.store") }}',
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#abonnementModal').modal('hide');
                window.showToastThenReload(res.message || 'Abonnement créé.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    // Assignation
    $('.btn-assigner').on('click', function () {
        $('#assign-zone').val($(this).data('zone') ?? '');
        $('#assign-voiture').val($(this).data('voiture') ?? '');
        $('#assign-ligne').val($(this).data('ligne') ?? '');
        $('#assign-sens').val($(this).data('sens') ?? 'aller_retour');
        $('#assign-arret').val($(this).data('arret') ?? '');
        $('#assigner-form').data('id', $(this).data('id'));
    });

    $('#assigner-form').on('submit', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            url: `{{ url('services-eleves/transport/abonnements') }}/${id}/assigner`,
            method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#assignerModal').modal('hide');
                window.showToastThenReload(res.message || 'Assignation mise à jour.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });

    // Abandon
    $('.btn-abandonner').on('click', function () {
        const id = $(this).data('id');
        $('#abandon-eleve').text($(this).data('eleve'));
        $('#abandon-form').attr('action', `{{ url('services-eleves/transport/abonnements') }}/${id}/abandonner`);
    });

    $('#abandon-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#abandonModal').modal('hide');
                window.showToastThenReload(res.message || 'Abonnement abandonné.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush