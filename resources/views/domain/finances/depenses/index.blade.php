{{-- resources/views/domain/finances/depenses/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Dépenses')
@section('page_icon', 'fa-file-invoice-dollar')
@section('page_title', 'Dépenses')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Dépenses</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.depenses.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('depenses.creer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleDepense"
                data-bs-toggle="modal" data-bs-target="#depenseModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle dépense
        </button>
    @endcan

    <a href="{{ route('finances.depenses.exportPdf', request()->query()) }}"
   class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> PDF
</a>
@endsection

@section('css')
<style>
    .depenses-page { --dp-bg-soft:#faf6ee; --dp-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--dp-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition: transform .2s; box-shadow: 0 2px 8px rgba(122,15,28,.04); }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background: rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background: rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.red   { background: rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-icon.blue  { background: rgba(37,99,235,.10);  color:#2563eb; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; line-height:1.2; color:var(--school-ink); }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--dp-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--dp-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }

    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color:#fff; }

    .compteur-badge { display:inline-block; background:var(--dp-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--dp-border); }

    .btn-nouveau { background: linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; box-shadow:0 4px 14px rgba(200,30,58,.25); }
    .btn-nouveau:hover { color:#fff; transform: translateY(-2px); }
    .btn-outline-scolarite { border:1px solid var(--dp-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--dp-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-depenses thead tr { background: var(--dp-bg-soft); }
    .table-depenses thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--dp-border); padding:.9rem 1rem; white-space:nowrap; }
    .table-depenses thead th i { color: var(--school-gold-dark); }
    .table-depenses tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--dp-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-depenses tbody tr:hover { background:#fdfaf5; }

    .depense-libelle { font-weight:700; color: var(--school-ink); display:block; }
    .depense-benef   { font-size:.75rem; color: var(--school-muted); }
    .depense-montant { font-variant-numeric: tabular-nums; font-weight:700; text-align:right; }

    .badge-depense { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-depense-attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-depense-validee { background:#e3f7ec; color:#1c7a4d; }
    .badge-depense-rejetee { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; }
    .badge-depense-attente .badge-dot { background: var(--school-gold-dark); }
    .badge-depense-validee .badge-dot { background:#1c7a4d; }
    .badge-depense-rejetee .badge-dot { background:var(--school-red); }

    .empty-state { padding: 3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-depenses { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-depenses { background: linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background: var(--dp-bg-soft); border:1px solid var(--dp-border); border-radius:12px; padding:1.1rem; }
    .form-section-title { font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--school-red); margin-bottom:.75rem; display:flex; align-items:center; gap:6px; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--dp-border); padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }

    .btn-annuler { background:#fff; border:1px solid var(--dp-border); color:#5b6b7a; border-radius:8px; font-weight:500; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-annuler:hover { background: var(--dp-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
    .btn-enregistrer:hover { color:#fff; }
</style>
@endsection

@section('contenu')
<div class="depenses-page">

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['attente'] }}</div>
                    <div class="stat-label">En attente — {{ number_format($montants['attente'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['validee'] }}</div>
                    <div class="stat-label">Validées — {{ number_format($montants['validee'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['rejetee'] }}</div>
                    <div class="stat-label">Rejetées — {{ number_format($montants['rejetee'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">
                        {{ number_format($montants['attente'] + $montants['validee'], 0, ',', ' ') }}
                    </div>
                    <div class="stat-label">Total engagé (hors rejets)</div>
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
                               placeholder="Libellé, bénéficiaire..." value="{{ $filters['search'] ?? '' }}">
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
                    <select name="caisse_id" class="form-select filtre-input">
                        <option value="">— Caisse —</option>
                        @foreach ($caisses as $c)
                            <option value="{{ $c->id }}" @selected(($filters['caisse_id'] ?? '') == $c->id)>
                                {{ $c->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input"
                           value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input"
                           value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-depenses align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-tag me-2"></i>Dépense</th>
                            <th><i class="fas fa-calendar me-2"></i>Date</th>
                            <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                            <th><i class="fas fa-cash-register me-2"></i>Caisse de règlement</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Montant</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($depenses as $depense)
                        <tr>
                            <td class="ps-4">
                                <span class="depense-libelle">{{ $depense->libelle }}</span>
                                <span class="depense-benef">
                                    <i class="fas fa-user me-1"></i>{{ $depense->beneficiaire ?? '—' }}
                                </span>
                            </td>
                            <td class="text-sm">{{ optional($depense->date_depense)->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @php $s = $depense->statut_depense; @endphp
                                <span class="badge-depense badge-depense-{{ $s?->css() }}">
                                    <span class="badge-dot"></span>{{ $s?->label() ?? '—' }}
                                </span>
                            </td>
                            <td class="text-sm">
                                @if ($depense->caisse)
                                    <i class="fas fa-cash-register me-1 text-muted"></i>{{ $depense->caisse->libelle }}
                                    <div class="text-xs text-muted">
                                        le {{ optional($depense->date_reglement)->format('d/m/Y') }}
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="depense-montant">
                                {{ number_format((float) $depense->montant, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.depenses.show', $depense) }}">
                                                <i class="fas fa-eye"></i> Détail &amp; ventilation
                                            </a>
                                        </li>
                                        @if ($depense->justificatif_path)
                                            <li>
                                                <a class="dropdown-item"
                                                   href="{{ asset('storage/' . $depense->justificatif_path) }}"
                                                   target="_blank">
                                                    <i class="fas fa-paperclip"></i> Voir le justificatif
                                                </a>
                                            </li>
                                        @endif

                                        @if ($depense->isEnAttente())
                                            @can('depenses.valider')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-valider"
                                                            data-id="{{ $depense->id }}"
                                                            data-libelle="{{ $depense->libelle }}"
                                                            data-bs-toggle="modal" data-bs-target="#validerModal">
                                                        <i class="fas fa-check"></i> Valider
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-rejeter"
                                                            data-id="{{ $depense->id }}"
                                                            data-libelle="{{ $depense->libelle }}"
                                                            data-bs-toggle="modal" data-bs-target="#rejeterModal">
                                                        <i class="fas fa-times"></i> Rejeter
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif

                                        @if ($depense->peutEtreReglee())
                                            @can('depenses.regler')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-regler"
                                                            data-id="{{ $depense->id }}"
                                                            data-libelle="{{ $depense->libelle }}"
                                                            data-montant="{{ number_format((float) $depense->montant, 2, ',', ' ') }}"
                                                            data-bs-toggle="modal" data-bs-target="#reglerModal">
                                                        <i class="fas fa-cash-register"></i> Régler via caisse
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif

                                        @if ($depense->isRejetee() && $depense->motif_rejet)
                                            <li>
                                                <span class="dropdown-item text-muted small" style="cursor:default;">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ \Str::limit($depense->motif_rejet, 40) }}
                                                </span>
                                            </li>
                                        @endif

                                        @if (! $depense->isReglee() || ($depense->caisse && $depense->caisse->isOuverte()))
    @can('depenses.creer')
        <li><hr class="dropdown-divider"></li>
        <li>
            <form action="{{ route('finances.depenses.destroy', $depense) }}"
                  method="POST" class="form-confirm-delete"
                  data-confirm-title="Supprimer cette dépense ?"
                  data-confirm-text="Le mouvement de caisse associé sera également retiré. Cette action peut être annulée.">
                @csrf
                @method('DELETE')
                <button type="submit" class="dropdown-item text-danger">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
            </form>
        </li>
    @endcan
@endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                    <p class="mb-0 fw-medium">Aucune dépense enregistrée.</p>
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
        {{ $depenses->appends(request()->query())->links('vendor.pagination.custom') }}
    </div>

    {{-- ========= Modale nouvelle dépense ========= --}}
    @can('depenses.creer')
    <div class="modal fade" id="depenseModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-depenses">
                <form id="depense-form" method="POST" enctype="multipart/form-data"
                      action="{{ route('finances.depenses.store') }}">
                    @csrf
                    <div class="modal-header modal-header-depenses">
                        <h5 class="modal-title"><i class="fas fa-file-invoice-dollar me-2"></i>Nouvelle dépense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Bénéficiaire <span class="text-danger">*</span></label>
                                    <input type="text" name="beneficiaire" class="form-control" maxlength="150">
                                    <div class="text-danger text-xs mt-1" id="beneficiaire-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_depense" class="form-control"
                                           value="{{ now()->toDateString() }}">
                                    <div class="text-danger text-xs mt-1" id="date_depense-error"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Montant <span class="text-danger">*</span></label>
                                    <input type="number" name="montant" step="0.01" min="0.01" class="form-control">
                                    <div class="text-danger text-xs mt-1" id="montant-error"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Motif</label>
                                    <textarea name="motif_depense" rows="2" class="form-control"
                                              maxlength="1000"></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Justificatif (PDF, image, doc — 5 Mo max)</label>
                                    <input type="file" name="justificatif" class="form-control"
                                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                                </div>

                                <div class="col-md-6">
    <label class="form-label">Nature analytique (optionnel)</label>
    <input type="text" name="nature_analytique" class="form-control"
           placeholder="ex : fournitures, entretien, cantine...">
    <div class="form-text small">Si une règle existe pour cette nature, la ventilation sera appliquée automatiquement.</div>
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

    {{-- ========= Modale validation ========= --}}
    @can('depenses.valider')
    <div class="modal fade" id="validerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-depenses">
                <form id="valider-form" method="POST" action="">
                    @csrf
                    <div class="modal-header modal-header-depenses">
                        <h5 class="modal-title"><i class="fas fa-check me-2"></i>Valider la dépense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Confirmer la validation de la dépense <strong id="valider-libelle"></strong> ?</p>
                        <p class="text-muted small mb-0">
                            Une fois validée, la dépense pourra être rattachée à une caisse pour règlement.
                        </p>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer">Valider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========= Modale rejet ========= --}}
    <div class="modal fade" id="rejeterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-depenses">
                <form id="rejeter-form" method="POST" action="">
                    @csrf
                    <div class="modal-header" style="background: linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark)); color:#fff; border-radius:16px 16px 0 0;">
                        <h5 class="modal-title"><i class="fas fa-times me-2"></i>Rejeter la dépense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Rejeter la dépense <strong id="rejeter-libelle"></strong> ?</p>
                        <div class="form-section">
                            <label class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                            <textarea name="motif_rejet" rows="3" class="form-control" required minlength="5"></textarea>
                            <div class="text-danger text-xs mt-1" id="motif_rejet-error"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn" style="background: var(--school-red-dark); color:#fff; border-radius:8px; font-weight:600; padding:.55rem 1.4rem;">
                            <i class="fas fa-times me-1"></i> Rejeter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- ========= Modale règlement ========= --}}
    @can('depenses.regler')
    <div class="modal fade" id="reglerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-depenses">
                <form id="regler-form" method="POST" action="">
                    @csrf
                    <div class="modal-header modal-header-depenses">
                        <h5 class="modal-title"><i class="fas fa-cash-register me-2"></i>Régler la dépense</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3"
                             style="background: var(--dp-bg-soft); border-radius:10px;">
                            <div>
                                <div class="text-muted text-xs">Dépense</div>
                                <div class="fw-semibold" id="regler-libelle">—</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted text-xs">Montant</div>
                                <div class="fw-bold fs-6" id="regler-montant">—</div>
                            </div>
                        </div>

                        <div class="form-section">
                            <label class="form-label">Caisse de règlement <span class="text-danger">*</span></label>
                            <select name="caisse_id" class="form-select" required>
                                <option value="">— Sélectionner une caisse ouverte —</option>
                                @foreach ($caisses as $c)
                                    @if ($c->statut === \App\Domain\Finances\Types\CaisseStatut::OUVERTE)
                                        <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div class="text-danger text-xs mt-1" id="caisse_id-error"></div>
                            <div class="form-text small mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                Un mouvement de sortie sera automatiquement ajouté au journal de la caisse.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer">
                            <i class="fas fa-check me-1"></i> Régler
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
    // ========= Saisie =========
    const $form = $('#depense-form');
    if ($form.length) {
        $form.on('submit', function (e) {
            e.preventDefault();
            clearErrors($form);

            const fd = new FormData(this);
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#depenseModal').modal('hide');
                    window.showToastThenReload(res.message || 'Dépense enregistrée.', 'success');
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

    // ========= Validation =========
    $('.btn-valider').on('click', function () {
        const id = $(this).data('id');
        $('#valider-libelle').text($(this).data('libelle'));
        $('#valider-form').attr('action', `{{ url('finances/depenses') }}/${id}/valider`);
    });
    $('#valider-form').on('submit', function (e) {
        e.preventDefault();
        ajaxForm($(this), 'validerModal');
    });

    // ========= Rejet =========
    $('.btn-rejeter').on('click', function () {
        const id = $(this).data('id');
        $('#rejeter-libelle').text($(this).data('libelle'));
        $('#rejeter-form').attr('action', `{{ url('finances/depenses') }}/${id}/rejeter`);
    });
    $('#rejeter-form').on('submit', function (e) {
        e.preventDefault();
        ajaxForm($(this), 'rejeterModal');
    });

    // ========= Règlement =========
    $('.btn-regler').on('click', function () {
        const id = $(this).data('id');
        $('#regler-libelle').text($(this).data('libelle'));
        $('#regler-montant').text($(this).data('montant'));
        $('#regler-form').attr('action', `{{ url('finances/depenses') }}/${id}/regler`);
    });
    $('#regler-form').on('submit', function (e) {
        e.preventDefault();
        ajaxForm($(this), 'reglerModal');
    });

    // ========= Helpers =========
    function ajaxForm($f, modalId) {
        clearErrors($f);
        $.ajax({
            url: $f.attr('action'),
            method: 'POST',
            data: $f.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#' + modalId).modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    }

    function clearErrors($f) {
        $f.find('.is-invalid').removeClass('is-invalid');
        $f.find('[id$="-error"]').text('');
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