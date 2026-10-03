@extends('admin.layouts.app')

@section('title', 'Bulletins de paie')
@section('page_icon', 'fa-money-check-alt')
@section('page_title', 'Paie & Rémunérations')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item active">Paie</li>
@endsection

@section('page_actions')
    <a href="{{ route('rh.paies.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    <a href="{{ route('rh.paies.dashboard', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-chart-pie me-1"></i> Dashboard
    </a>
    <a href="{{ route('rh.periodes.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-alt me-1"></i> Périodes
    </a>
    <a href="{{ route('rh.remunerations.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-sliders-h me-1"></i> Rémunérations
    </a>
    @can('paie.gerer')
        <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#paieModal">
            <i class="fas fa-plus-circle me-1"></i> Générer un bulletin
        </button>
    @endcan
@endsection

@section('css')
<style>
    .paie-page { --p-bg-soft:#faf6ee; --p-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--p-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--p-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--p-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--p-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--p-bg-soft); border-color:var(--school-red); color:var(--school-red); }
    .compteur-badge { display:inline-block; background:var(--p-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--p-border); }

    .table-paies thead tr { background:var(--p-bg-soft); }
    .table-paies thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--p-border); white-space:nowrap; }
    .table-paies thead th i { color:var(--school-gold-dark); }
    .table-paies tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--p-border); font-size:.9rem; vertical-align:middle; }
    .table-paies tbody tr:hover { background:#fdfaf5; }

    .reference { font-family:monospace; font-size:.75rem; background:var(--p-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }
    .num.net { color:#1c7a4d; font-weight:800; font-size:1rem; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.brouillon { background:#eceff2; color:#5b6b7a; }
    .badge-statut.valide    { background:#e0ecfc; color:#2563eb; }
    .badge-statut.paye      { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule    { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    /* Badges mode paiement */
    .badge-mode {
        display:inline-flex; align-items:center; gap:5px;
        padding:.3em .75em; border-radius:999px;
        font-size:.7rem; font-weight:700;
    }
    .badge-mode.banque   { background:#e0ecfc; color:#2563eb; }
    .badge-mode.billette { background:#fdf4e0; color:var(--school-gold-dark); }

    /* Badges retrait */
    .badge-retrait {
        display:inline-flex; align-items:center; gap:5px;
        padding:.3em .75em; border-radius:999px;
        font-size:.7rem; font-weight:700;
    }
    .badge-retrait.attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-retrait.retire  { background:#e3f7ec; color:#1c7a4d; }
    .badge-retrait.vire    { background:#e0ecfc; color:#2563eb; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-rh { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-rh { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--p-bg-soft); border:1px solid var(--p-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--p-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--p-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="paie-page">

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Bulletins</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['masse_brute'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Masse brute</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['masse_nette'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Masse nette</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['brouillons'] }}</div>
                    <div class="stat-label">En brouillon</div>
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
                               placeholder="Employé..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="periode_id" class="form-select filtre-input">
                        <option value="">— Période —</option>
                        @foreach ($periodes as $p)
                            <option value="{{ $p->id }}" @selected(($filters['periode_id'] ?? '') == $p->id)>
                                {{ $p->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut'] ?? '') === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-filtrer flex-fill">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                    <a href="{{ route('rh.paies.index') }}" class="btn btn-outline-scolarite">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-paies align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Référence</th>
                            <th>Employé</th>
                            <th>Période</th>
                            <th class="text-end">Brut</th>
                            <th class="text-end">Déductions</th>
                            <th class="text-end">Retenues</th>
                            <th class="text-end">Net à payer</th>
                            <th>Statut</th>
                            <th>Mode / Retrait</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($paies as $paie)
                        <tr>
                            <td class="ps-4">
                                <span class="reference">{{ $paie->reference ?? ('#' . $paie->id) }}</span>
                            </td>
                            <td class="text-sm">
                                <a href="{{ route('rh.employes.show', $paie->personnel_id) }}"
                                   class="text-decoration-none">
                                    <div class="fw-semibold">{{ $paie->employe?->nom_prenom }}</div>
                                    <div class="text-xs text-muted">{{ $paie->employe?->profession?->libelle }}</div>
                                </a>
                            </td>
                            <td class="text-sm">{{ $paie->periode?->libelle ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $paie->total_brut, 0, ',', ' ') }}</td>
                            <td class="num">{{ number_format((float) $paie->total_deductions, 0, ',', ' ') }}</td>
                            <td class="num">{{ number_format((float) $paie->total_retenues, 0, ',', ' ') }}</td>
                            <td class="num net">{{ number_format((float) $paie->net_a_payer, 0, ',', ' ') }}</td>
                            <td>
                                <span class="badge-statut {{ $paie->statut?->css() }}">
                                    <span class="badge-dot"></span>{{ $paie->statut?->label() }}
                                </span>
                            </td>
                            <td>
                                {{-- Mode de paiement --}}
                                @if ($paie->isPayeParBanque())
                                    <span class="badge-mode banque">
                                        <i class="fas fa-university"></i> Banque
                                    </span>
                                @else
                                    <span class="badge-mode billette">
                                        <i class="fas fa-money-bill-wave"></i> Billetterie
                                    </span>
                                @endif

                                {{-- État du retrait (billetterie) --}}
                                @if ($paie->isPaye() && $paie->isPayeABilletterie())
                                    @if ($paie->retire_a_la_billetterie)
                                        <div class="mt-1">
                                            <span class="badge-retrait retire">
                                                <i class="fas fa-check"></i> Retiré le
                                                {{ optional($paie->date_retrait)->format('d/m/Y') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <span class="badge-retrait attente">
                                                <i class="fas fa-hourglass-half"></i>
                                                En attente ({{ $paie->jours_depuis_paiement }} j)
                                            </span>
                                        </div>
                                    @endif
                                @elseif ($paie->isPaye() && $paie->isPayeParBanque())
                                    <div class="mt-1">
                                        <span class="badge-retrait vire">
                                            <i class="fas fa-check"></i> Virement effectué
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button"
                                            data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">

                                        {{-- Toujours disponible : voir le bulletin --}}
                                        <li>
                                            <a class="dropdown-item" href="{{ route('rh.paies.show', $paie) }}">
                                                <i class="fas fa-eye"></i> Voir le bulletin
                                            </a>
                                        </li>

                                        {{-- Toujours disponible : PDF du bulletin --}}
                                        <li>
                                            <a class="dropdown-item" href="{{ route('rh.paies.exportPdf', $paie) }}">
                                                <i class="fas fa-file-pdf"></i> Télécharger PDF
                                            </a>
                                        </li>

                                        @can('paie.gerer')
                                            {{-- Valider (brouillon uniquement) --}}
                                            @if ($paie->isBrouillon())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('rh.paies.valider', $paie) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Valider ce bulletin ?">
                                                        @csrf
                                                        <button class="dropdown-item">
                                                            <i class="fas fa-check"></i> Valider
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        @endcan

                                        {{-- Le règlement des bulletins revient au Comptable (cahier des charges 5.2) --}}
                                        @can('paie.payer')
                                            {{-- Payer (brouillon ou validé) --}}
                                            @if (! $paie->isPaye() && $paie->statut?->value !== 'annule')
                                                <li>
                                                    <form action="{{ route('rh.paies.payer', $paie) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Marquer comme payé ?">
                                                        @csrf
                                                        <button class="dropdown-item">
                                                            <i class="fas fa-money-bill-wave"></i> Marquer payé
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif

                                            {{-- Confirmer retrait billetterie (uniquement si payé + billetterie + non retiré) --}}
                                            @if ($paie->isEnAttenteRetrait())
                                                <li>
                                                    <form action="{{ route('rh.paies.retrait', $paie) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Confirmer le retrait en espèces ?"
                                                          data-confirm-text="L'employé a bien récupéré sa paie à la billetterie.">
                                                        @csrf
                                                        <button class="dropdown-item" style="color:#1c7a4d;">
                                                            <i class="fas fa-hand-holding-usd"></i>
                                                            Confirmer le retrait
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        @endcan

                                        @can('paie.gerer')
                                            {{-- Annuler (brouillon ou validé uniquement) --}}
                                            @if (! $paie->statut?->isVerrouille())
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button"
                                                            class="dropdown-item text-danger btn-annuler-paie"
                                                            data-id="{{ $paie->id }}"
                                                            data-reference="{{ $paie->reference }}"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#annulerPaieModal">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                </li>
                                            @endif
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="fas fa-money-check-alt"></i>
                                    <p class="mb-0">Aucun bulletin de paie.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $paies->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale génération bulletin --}}
    @can('paie.gerer')
    <div class="modal fade" id="paieModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-rh">
                <form id="paie-form">
                    @csrf
                    <div class="modal-header modal-header-rh">
                        <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i>Générer un bulletin</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-light border-0 small mb-3" style="background:#f0f6ff;color:#1e3a8a;">
                            <i class="fas fa-info-circle me-1"></i>
                            Le bulletin est calculé automatiquement depuis la rémunération, les déductions
                            et les retenues de l'employé (IRPP calculé selon son mode). Il est créé en
                            <strong>brouillon</strong>.
                        </div>
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Employé <span class="text-danger">*</span></label>
                                <select name="employe_id" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($employes as $e)
                                        <option value="{{ $e->id }}">{{ $e->nom_prenom }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Période <span class="text-danger">*</span></label>
                                <select name="periode_id" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($periodes as $p)
                                        <option value="{{ $p->id }}">{{ $p->libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer">
                            <i class="fas fa-calculator me-1"></i> Générer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    {{-- Modale annulation --}}
    @can('paie.gerer')
    <div class="modal fade" id="annulerPaieModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-rh">
                <form id="annuler-paie-form" method="POST" action="">
                    @csrf
                    <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Annuler le bulletin</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Annuler le bulletin <strong id="annuler-ref">—</strong> ?</p>
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
    // ============ FORMULAIRE GÉNÉRATION ============
    $('#paie-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("rh.paies.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#paieModal').modal('hide');
                window.showToastThenReload(res.message || 'Bulletin généré.', 'success');
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

    // ============ FORMULAIRE ANNULATION ============
    $('.btn-annuler-paie').on('click', function () {
        const id = $(this).data('id');
        const ref = $(this).data('reference') ?? ('#' + id);
        $('#annuler-ref').text(ref);
        $('#annuler-paie-form').attr('action', `{{ url('rh/paies') }}/${id}/annuler`);
    });

    $('#annuler-paie-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#annulerPaieModal').modal('hide');
                window.showToastThenReload(res.message || 'Bulletin annulé.', 'success');
            },
            error: function (xhr) {
                window.showToast(xhr.responseJSON?.message || 'Erreur.', 'error');
            }
        });
    });
});
</script>
@endpush