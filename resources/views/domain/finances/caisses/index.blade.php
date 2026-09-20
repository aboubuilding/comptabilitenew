{{-- resources/views/domain/finances/caisses/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Caisses & Journaux de caisse')
@section('page_icon', 'fa-clipboard-list')
@section('page_title', 'Caisses & Journaux de caisse')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Caisses</li>
@endsection

@section('page_actions')
    @can('caisses.ouvrir')
        <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#caisseModal">
            <i class="fas fa-plus-circle me-1"></i> Ouvrir une caisse
        </button>
    @endcan
@endsection

@section('css')
<style>
    /* Jetons propres à cette page — school-red / gold / ink / muted / ff
       viennent de layouts/app.blade.php, non redéclarés ici. */
    .caisses-page {
        --caisses-bg-soft: #faf6ee;
        --caisses-border: #ece4d6;
    }

    .text-xs  { font-size: .72rem; }
    .text-sm  { font-size: .8rem; }
    .text-md  { font-size: .92rem; }

    /* Cartes stats */
    .stat-card {
        background: #fff;
        border: 1px solid var(--caisses-border);
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(122,15,28,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(122,15,28,.08); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; background: rgba(200,30,58,.08); color: var(--school-red);
        flex-shrink: 0;
    }
    .stat-icon.green { background: rgba(28,122,77,.08); color: #1c7a4d; }
    .stat-info .stat-value { font-size: 1.2rem; font-weight: 700; line-height: 1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size: .78rem; color: var(--school-muted); margin: 0; }

    .btn-outline-scolarite {
        border: 1px solid var(--caisses-border);
        color: var(--school-ink);
        background: #fff;
        font-size: .82rem;
    }
    .btn-outline-scolarite:hover { background: var(--caisses-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    /* Card & tableau principal */
    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--caisses-border) !important; }

    .filtre-input {
        border-radius: 10px; border: 1px solid var(--caisses-border);
        padding: .55rem .9rem; font-size: .88rem;
    }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .85rem; }
    .search-input { padding-left: 36px !important; }

    .btn-filtrer {
        background: var(--school-red); color: #fff; border: none; border-radius: 10px;
        padding: .55rem 1rem; font-weight: 600; font-size: .88rem; transition: all .2s ease;
    }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.25); }

    .compteur-badge {
        display: inline-block; background: var(--caisses-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
        border: 1px solid var(--caisses-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25); transition: all .2s ease;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    /* Tableau */
    .table-caisses thead tr { background: var(--caisses-bg-soft); }
    .table-caisses thead th {
        color: var(--school-red); font-size: .76rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--caisses-border); padding: .9rem 1rem;
        white-space: nowrap;
    }
    .table-caisses thead th i { color: var(--school-gold-dark); }
    .table-caisses tbody td { padding: .9rem 1rem; border-bottom: 1px solid var(--caisses-border); font-size: .9rem; color: var(--school-ink); vertical-align: middle; }
    .table-caisses tbody tr:hover { background: #fdfaf5; }
    .table-caisses tbody tr:last-child td { border-bottom: none; }

    .caisse-libelle { font-weight: 700; color: var(--school-ink); display: block; font-size: .94rem; }
    .caisse-id { font-size: .72rem; color: var(--school-muted); }

    .caissier-chip {
        display: inline-flex; align-items: center; gap: 8px;
    }
    .caissier-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(145deg, var(--school-gold-soft), var(--school-gold));
        color: var(--school-red-deep); font-size: .72rem; font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
    }

    .solde { font-variant-numeric: tabular-nums; font-weight: 600; }
    .solde.positif { color: #1c7a4d; }
    .solde.negatif { color: var(--school-red); }

    /* Badges statut, alignés sur les couleurs déjà utilisées pour
       "Active" / "Clôturée" dans l'écran Années scolaires. */
    .badge-etat {
        display: inline-flex; align-items: center; gap: 6px; font-size: .75rem;
        font-weight: 600; padding: .4em .85em; border-radius: 999px;
    }
    .badge-etat-ouverte  { background: #e3f7ec; color: #1c7a4d; }
    .badge-etat-cloturee { background: var(--caisses-bg-soft); color: var(--school-muted); }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; }
    .badge-etat-ouverte  .badge-dot { background: #1c7a4d; }
    .badge-etat-cloturee .badge-dot { background: var(--school-muted); }

    .empty-state { padding: 3.5rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }
    .empty-state p { font-size: .9rem; }

    /* Modale */
    .modal-caisses { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,.15); }
    .modal-header-caisses {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border-bottom: none; padding: 1.15rem 1.5rem;
    }
    .modal-header-caisses .modal-title { font-weight: 600; font-size: 1.05rem; }

    .form-section { background: var(--caisses-bg-soft); border: 1px solid var(--caisses-border); border-radius: 12px; padding: 1.1rem; }
    .form-section-title {
        font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        color: var(--school-red); margin-bottom: .75rem; display: flex; align-items: center; gap: 6px;
    }
    .form-section .form-label { font-size: .82rem; font-weight: 600; color: var(--school-ink); }
    .form-section .form-control,
    .form-section .form-select {
        border-radius: 8px; border: 1px solid var(--caisses-border); padding: .55rem .75rem; font-size: .88rem;
    }
    .form-section .form-control:focus,
    .form-section .form-select:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .btn-annuler { background: #fff; border: 1px solid var(--caisses-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .55rem 1.2rem; font-size: .88rem; }
    .btn-annuler:hover { background: var(--caisses-bg-soft); }
    .btn-enregistrer {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff;
        border: none; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;
    }
    .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }
</style>
@endsection

@section('contenu')
<div class="caisses-page">

    {{-- ===== Cartes statistiques ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $totalCaisses }}</div>
                    <div class="stat-label">Sessions enregistrées</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-lock-open"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $caissesOuvertes }}</div>
                    <div class="stat-label">Caisses ouvertes</div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6 d-flex align-items-center justify-content-md-end gap-2">
            <a href="{{ route('finances.caisses.export', 0) }}"
               class="btn btn-outline-scolarite btn-sm rounded-pill px-3 d-none"
               id="btnExportGlobal">
                <i class="fas fa-file-excel me-1"></i> Exporter
            </a>
        </div>
    </div>

    {{-- ===== Tableau principal ===== --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Rechercher une caisse..." value="{{ $filters['search'] ?? '' }}">
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
                    <select name="responsable_id" class="form-select filtre-input">
                        <option value="">— Caissier —</option>
                        @foreach ($caissiers as $c)
                            <option value="{{ $c->id }}" @selected(($filters['responsable_id'] ?? '') == $c->id)>
                                {{ $c->nom }} {{ $c->prenom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="annee_id" class="form-select filtre-input">
                        <option value="">— Année —</option>
                        @foreach ($annees as $a)
                            <option value="{{ $a->id }}" @selected(($filters['annee_id'] ?? '') == $a->id)>
                                {{ $a->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                    <a href="{{ route('finances.caisses.index') }}" class="btn btn-outline-scolarite" title="Réinitialiser">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-caisses align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-tag me-2"></i>Caisse</th>
                            <th><i class="fas fa-user me-2"></i>Caissier</th>
                            <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Solde initial</th>
                            <th class="text-end"><i class="fas fa-calculator me-2"></i>Solde théorique</th>
                            <th class="text-end"><i class="fas fa-balance-scale me-2"></i>Écart</th>
                            <th><i class="fas fa-clock me-2"></i>Ouverture</th>
                            <th><i class="fas fa-lock me-2"></i>Clôture</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($caisses as $caisse)
                        <tr>
                            <td class="ps-4">
                                <span class="caisse-libelle">{{ $caisse->libelle }}</span>
                                <span class="caisse-id">#{{ $caisse->id }}</span>
                            </td>
                            <td>
                                <div class="caissier-chip">
                                    <div class="caissier-avatar">
                                        {{ strtoupper(substr($caisse->caissier?->nom ?? '??', 0, 2)) }}
                                    </div>
                                    <span class="text-sm">
                                        {{ $caisse->caissier?->nom }} {{ $caisse->caissier?->prenom }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if ($caisse->isOuverte())
                                    <span class="badge-etat badge-etat-ouverte"><span class="badge-dot"></span>Ouverte</span>
                                @else
                                    <span class="badge-etat badge-etat-cloturee"><span class="badge-dot"></span>Clôturée</span>
                                @endif
                            </td>
                            <td class="text-end solde">
                                {{ number_format($caisse->solde_initial, 2, ',', ' ') }}
                            </td>
                            <td class="text-end solde">
                                {{ number_format($caisse->solde_theorique, 2, ',', ' ') }}
                            </td>
                            <td class="text-end">
                                @if (! is_null($caisse->ecart) && abs($caisse->ecart) > 0.01)
                                    <span class="solde negatif">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        {{ number_format($caisse->ecart, 2, ',', ' ') }}
                                    </span>
                                @elseif (! $caisse->isOuverte())
                                    <span class="solde positif">
                                        <i class="fas fa-check me-1"></i> 0,00
                                    </span>
                                @else
                                    <span class="text-muted text-sm">—</span>
                                @endif
                            </td>
                            <td class="text-sm text-muted">
                                {{ optional($caisse->date_ouverture)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-sm text-muted">
                                {{ optional($caisse->date_cloture)->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.caisses.show', $caisse->id) }}">
                                                <i class="fas fa-book"></i> Consulter le journal
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.caisses.export', $caisse->id) }}">
                                                <i class="fas fa-file-excel"></i> Exporter Excel
                                            </a>
                                        </li>
                                        @if ($caisse->isOuverte())
                                            @can('caisses.cloturer')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-cloturer"
                                                            data-id="{{ $caisse->id }}"
                                                            data-libelle="{{ $caisse->libelle }}"
                                                            data-solde="{{ number_format($caisse->solde_theorique, 2, ',', ' ') }}"
                                                            data-bs-toggle="modal" data-bs-target="#clotureModal">
                                                        <i class="fas fa-lock"></i> Clôturer la caisse
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-cash-register"></i>
                                    <p class="mb-0 fw-medium">Aucune session de caisse enregistrée.</p>
                                    <small class="text-muted">Commencez par ouvrir une nouvelle caisse.</small>
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
        {{ $caisses->appends(request()->query())->links() }}
    </div>

    {{-- ===== Modale Ouverture ===== --}}
    @can('caisses.ouvrir')
    <div class="modal fade" id="caisseModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-caisses">
                <form id="caisse-form" method="POST" action="{{ route('finances.caisses.store') }}">
                    @csrf
                    <div class="modal-header modal-header-caisses">
                        <h5 class="modal-title">
                            <i class="fas fa-cash-register me-2"></i>Ouvrir une nouvelle caisse
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations générales</div>
                            <div class="mb-3">
                                <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" class="form-control" maxlength="100"
                                       placeholder="Ex : Caisse principale, Caisse cantine...">
                                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                            </div>

                            <div class="row g-3">
                                <div class="col-7">
                                    <label class="form-label">Caissier responsable <span class="text-danger">*</span></label>
                                    <select name="responsable_id" class="form-select">
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($caissiers as $c)
                                            <option value="{{ $c->id }}">{{ $c->nom }} {{ $c->prenom }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger text-xs mt-1" id="responsable_id-error"></div>
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Solde initial <span class="text-danger">*</span></label>
                                    <input type="number" name="solde_initial" step="0.01" min="0"
                                           class="form-control" value="0.00">
                                    <div class="text-danger text-xs mt-1" id="solde_initial-error"></div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border-0 small mb-0"
                             style="background: var(--caisses-bg-soft); color: var(--school-muted);">
                            <i class="fas fa-info-circle me-1" style="color: var(--school-gold-dark);"></i>
                            Cette action démarre une nouvelle <strong>session</strong> (journal de caisse) sur l'année
                            scolaire active.
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer">
                            <i class="fas fa-lock-open me-1"></i> Ouvrir la caisse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- ===== Modale Clôture (une seule, réutilisée) ===== --}}
    @can('caisses.cloturer')
    <div class="modal fade" id="clotureModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-caisses">
                <form id="cloture-form" method="POST" action="">
                    @csrf
                    <div class="modal-header"
                         style="background: linear-gradient(135deg, var(--school-red-deep), var(--school-red-dark)); color:#fff; border-radius: 16px 16px 0 0;">
                        <h5 class="modal-title">
                            <i class="fas fa-lock me-2"></i>Clôturer la caisse
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            La clôture est <strong>définitive</strong> : le journal sera figé et aucune écriture ne
                            pourra plus y être ajoutée.
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3 p-3"
                             style="background: var(--caisses-bg-soft); border-radius: 10px;">
                            <div>
                                <div class="text-muted text-xs">Caisse</div>
                                <div class="fw-semibold" id="cloture-libelle">—</div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted text-xs">Solde théorique</div>
                                <div class="fw-bold fs-5" id="cloture-solde">—</div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Solde compté (espèces physiquement en caisse) <span class="text-danger">*</span></label>
                                <input type="number" name="solde_compte" step="0.01" min="0"
                                       class="form-control" id="cloture-solde-compte">
                                <div class="text-danger text-xs mt-1" id="solde_compte-error"></div>
                            </div>

                            <div id="ecart-bloc" class="d-none">
                                <div class="d-flex justify-content-between align-items-center p-2 rounded mb-2"
                                     style="background:#fdf2f2; border-left:3px solid var(--school-red);">
                                    <span class="fw-semibold" style="color: var(--school-red);">Écart constaté</span>
                                    <span class="fw-bold" style="color: var(--school-red);" id="ecart-valeur">0,00</span>
                                </div>
                                <label class="form-label">Motif de l'écart <span class="text-danger">*</span></label>
                                <textarea name="motif_ecart" rows="2" class="form-control" id="motif_ecart"></textarea>
                                <div class="text-danger text-xs mt-1" id="motif_ecart-error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn"
                                style="background: var(--school-red-dark); color: #fff; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;">
                            <i class="fas fa-check me-1"></i> Clôturer définitivement
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
    // ============ OUVERTURE ============
    const $openForm = $('#caisse-form');
    if ($openForm.length) {
        $openForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($openForm);

            $.ajax({
                url:  $openForm.attr('action'),
                type: 'POST',
                data: $openForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#caisseModal').modal('hide');
                    window.showToastThenReload(res.message || 'Caisse ouverte.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors($openForm, xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // ============ CLÔTURE ============
    const $clotureModal = $('#clotureModal');
    if ($clotureModal.length) {
        const $clotureForm = $('#cloture-form');

        $('.btn-cloturer').on('click', function () {
            const id      = $(this).data('id');
            const libelle = $(this).data('libelle');
            const solde   = $(this).data('solde');

            $('#cloture-libelle').text(libelle);
            $('#cloture-solde').text(solde);
            $('#cloture-solde-compte').val('').data('theorique', parseFloat(solde.replace(/\s/g, '').replace(',', '.')));
            $('#ecart-bloc').addClass('d-none');
            clearErrors($clotureForm);

            $clotureForm.attr('action', '{{ url("finances/caisses") }}/' + id + '/cloturer');
        });

        $('#cloture-solde-compte').on('input', function () {
            const theorique = parseFloat($(this).data('theorique') || 0);
            const compte    = parseFloat($(this).val() || 0);
            const ecart     = +(compte - theorique).toFixed(2);

            if (Math.abs(ecart) > 0.01) {
                $('#ecart-bloc').removeClass('d-none');
                $('#ecart-valeur').text(ecart.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
                }));
                $('#motif_ecart').prop('required', true);
            } else {
                $('#ecart-bloc').addClass('d-none');
                $('#motif_ecart').prop('required', false);
            }
        });

        $clotureForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($clotureForm);

            $.ajax({
                url:  $clotureForm.attr('action'),
                type: 'POST',
                data: $clotureForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#clotureModal').modal('hide');
                    window.showToastThenReload(res.message || 'Caisse clôturée.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors($clotureForm, xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // ============ Utilitaires ============
    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[id$="-error"]').text('');
    }

    function showErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            $form.find('[name="' + field + '"]').addClass('is-invalid');
            $('#' + field + '-error').text(errors[field][0]);
        });
    }
});
</script>
@endpush