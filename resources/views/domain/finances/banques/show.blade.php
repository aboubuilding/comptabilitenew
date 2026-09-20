{{-- resources/views/domain/finances/banques/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Banque — ' . $banque->nom)
@section('page_icon', 'fa-university')
@section('page_title', 'Détail de la banque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.index') }}">Banques</a></li>
    <li class="breadcrumb-item active">{{ $banque->nom }}</li>


@endsection

@section('page_actions')
    <a href="{{ route('finances.banques.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>

    <a href="{{ route('finances.banques.exportPdf', $banque) }}"
   class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> Exporter PDF
</a>
@endsection

@section('css')
<style>
    .banque-show-page { --bp-bg-soft: #faf6ee; --bp-border: #ece4d6; }
    .text-xs { font-size: .72rem; }
    .text-sm { font-size: .8rem; }

    /* Bandeau d'identité */
    .banque-header {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff;
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: 0 10px 30px rgba(122,15,28,.18);
    }
    .banque-header-icon {
        width: 60px; height: 60px;
        border-radius: 14px;
        background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }
    .banque-header h2 { margin: 0; font-weight: 700; font-size: 1.35rem; }
    .banque-header small { opacity: .8; font-size: .85rem; }

    /* Cartes stats */
    .stat-card { background:#fff; border:1px solid var(--bp-border); border-radius:12px; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition: transform .2s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-icon { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0; }
    .stat-icon.blue   { background: rgba(37,99,235,.10); color:#2563eb; }
    .stat-icon.green  { background: rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.red    { background: rgba(200,30,58,.10); color: var(--school-red); }
    .stat-icon.gold   { background: rgba(212,169,77,.18); color: var(--school-gold-dark); }
    .stat-info .stat-value { font-size:1.2rem; font-weight:700; line-height:1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size:.78rem; color: var(--school-muted); margin:0; }

    /* Tableau chèques */
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--bp-border) !important; }

    .table-cheques thead tr { background: var(--bp-bg-soft); }
    .table-cheques thead th { color: var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--bp-border); padding:.9rem 1rem; white-space:nowrap; }
    .table-cheques thead th i { color: var(--school-gold-dark); }
    .table-cheques tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--bp-border); font-size:.9rem; color: var(--school-ink); vertical-align:middle; }
    .table-cheques tbody tr:hover { background:#fdfaf5; }

    .badge-cheque { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-cheque-emis     { background:#dbeafe; color:#1d4ed8; }
    .badge-cheque-encaisse { background:#e3f7ec; color:#1c7a4d; }
    .badge-cheque-rejete   { background:#fdecea; color: var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; }
    .badge-cheque-emis .badge-dot     { background:#1d4ed8; }
    .badge-cheque-encaisse .badge-dot { background:#1c7a4d; }
    .badge-cheque-rejete .badge-dot   { background: var(--school-red); }

    .cheque-montant { font-variant-numeric: tabular-nums; font-weight:700; text-align:right; }

    .empty-state { padding: 3rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="banque-show-page">

    {{-- Bandeau --}}
    <div class="banque-header mb-4">
        <div class="banque-header-icon"><i class="fas fa-university"></i></div>
        <div>
            <h2>{{ $banque->nom }}</h2>
            <small>
                <i class="fas fa-hashtag me-1"></i>Banque #{{ $banque->id }}
                &nbsp;·&nbsp;
                <i class="fas fa-money-check me-1"></i>{{ $cheques->count() }} chèque(s) au total
            </small>
        </div>
    </div>

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['emis'] }}</div>
                    <div class="stat-label">En attente — {{ number_format($montants['emis'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['encaisse'] }}</div>
                    <div class="stat-label">Encaissés — {{ number_format($montants['encaisse'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['rejete'] }}</div>
                    <div class="stat-label">Rejetés — {{ number_format($montants['rejete'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">
                        {{ number_format($montants['emis'] + $montants['encaisse'], 0, ',', ' ') }}
                    </div>
                    <div class="stat-label">Volume total traité</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Liste des chèques --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0" style="color: var(--school-ink);">
                <i class="fas fa-list-ol me-1" style="color: var(--school-gold-dark);"></i>
                Chèques rattachés
            </h6>
            <span class="text-muted text-xs">{{ $cheques->count() }} chèque(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table table-cheques align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4"><i class="fas fa-hashtag me-2"></i>Numéro</th>
                        <th><i class="fas fa-user me-2"></i>Émetteur</th>
                        <th><i class="fas fa-calendar me-2"></i>Émission</th>
                        <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                        <th class="text-end"><i class="fas fa-coins me-2"></i>Montant</th>
                        <th><i class="fas fa-check-double me-2"></i>Rapprochement</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($cheques as $cheque)
                    @php $s = $cheque->statut; @endphp
                    <tr>
                        <td class="ps-4">
                            <span class="fw-semibold">{{ $cheque->numero }}</span>
                            <div class="text-xs text-muted">#{{ $cheque->id }}</div>
                        </td>
                        <td class="text-sm">{{ $cheque->emetteur }}</td>
                        <td class="text-sm">{{ optional($cheque->date_emission)->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <span class="badge-cheque badge-cheque-{{ $s?->css() }}">
                                <span class="badge-dot"></span>{{ $s?->label() ?? '—' }}
                            </span>
                        </td>
                        <td class="cheque-montant">
                            {{ number_format((float) $cheque->montant, 2, ',', ' ') }}
                        </td>
                        <td class="text-sm">
                            @if ($cheque->date_rapprochement)
                                <i class="fas fa-check text-success me-1"></i>
                                {{ $cheque->date_rapprochement->format('d/m/Y') }}
                                @if ($cheque->rapprocheur)
                                    <div class="text-xs text-muted">par {{ $cheque->rapprocheur->name }}</div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-money-check-alt"></i>
                                <p class="mb-0 fw-medium">Aucun chèque rattaché à cette banque.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection