@extends('admin.layouts.app')

@section('title', 'Journal de caisse — ' . $journal['caisse']->libelle)
@section('page_icon', 'fa-book')
@section('page_title', 'Journal de caisse')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.caisses.index') }}">Caisses</a></li>
    <li class="breadcrumb-item active">#{{ $journal['caisse']->id }} — {{ $journal['caisse']->libelle }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.caisses.export', $journal['caisse']->id) }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Excel
    </a>
    <button onclick="window.print()" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-print me-1"></i> Imprimer
    </button>

    @if ($journal['caisse']->isOuverte())
        @can('caisses.mouvements')
            <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#mouvementModal">
                <i class="fas fa-plus-circle me-1"></i> Mouvement
            </button>
        @endcan
        @can('caisses.cloturer')
            <button type="button" class="btn"
                    style="background: var(--school-red); color:#fff; border-radius:10px; padding:.6rem 1.2rem; font-weight:600;"
                    data-bs-toggle="modal" data-bs-target="#clotureSessionModal">
                <i class="fas fa-lock me-1"></i> Clôturer
            </button>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .synth-card {
        background: #fff; border: 1px solid #ece4d6; border-radius: 12px;
        padding: 1.1rem 1.25rem; box-shadow: 0 2px 8px rgba(122,15,28,.04);
        transition: transform .2s;
    }
    .synth-card:hover { transform: translateY(-2px); }
    .synth-card .label { font-size: .72rem; text-transform: uppercase; color: var(--school-muted); font-weight: 600; letter-spacing: .5px; }
    .synth-card .value { font-size: 1.35rem; font-weight: 700; color: var(--school-ink); margin-top: 6px; font-variant-numeric: tabular-nums; }
    .synth-card.accent   { border-left: 4px solid var(--school-red); }
    .synth-card.entrees  { border-left: 4px solid #1c7a4d; }
    .synth-card.sorties  { border-left: 4px solid var(--school-red); }
    .synth-card.theorique{ border-left: 4px solid var(--school-gold-dark); }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }

    .journal-table thead tr { background: var(--school-ink); color: #fff; }
    .journal-table thead th {
        color: #fff; font-size: .74rem; text-transform: uppercase; letter-spacing: .5px;
        font-weight: 600; border: none; padding: .9rem 1rem; white-space: nowrap;
    }
    .journal-table tbody td { padding: .75rem 1rem; vertical-align: middle; border-bottom: 1px solid #ece4d6; }
    .journal-table tbody tr:hover { background: #fdfaf5; }
    .journal-table .montant { font-variant-numeric: tabular-nums; font-weight: 600; }
    .journal-table .montant.entree { color: #1c7a4d; }
    .journal-table .montant.sortie { color: var(--school-red); }

    .type-badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:.68rem; font-weight:700; }
    .type-badge.entree { background:#e3f7ec; color:#1c7a4d; }
    .type-badge.sortie { background:#fdecea; color: var(--school-red); }

    @media print {
        .page-header-bar, #app-footer, .hbtp-root, #toast-container { display: none !important; }
        .content-area { padding: 0 !important; }
    }
</style>
@endsection

@section('contenu')
<div class="caisses-page">

    {{-- Bandeau --}}
    <div class="mb-3 d-flex align-items-center gap-2">
        @if ($journal['caisse']->isOuverte())
            <span class="badge-etat badge-etat-ouverte"><span class="badge-dot"></span>Ouverte</span>
        @else
            <span class="badge-etat badge-etat-cloturee"><span class="badge-dot"></span>Clôturée</span>
        @endif
        <span class="text-muted text-sm"><i class="fas fa-user me-1"></i>{{ $journal['caisse']->caissier?->nom }} {{ $journal['caisse']->caissier?->prenom }}</span>
        <span class="text-muted text-sm ms-3"><i class="fas fa-calendar me-1"></i>Ouverte le {{ optional($journal['caisse']->date_ouverture)->format('d/m/Y à H:i') }}</span>
    </div>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="synth-card">
                <div class="label">Solde initial</div>
                <div class="value">{{ number_format($journal['caisse']->solde_initial, 2, ',', ' ') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="synth-card entrees">
                <div class="label">Total entrées</div>
                <div class="value" style="color:#1c7a4d;">+{{ number_format($journal['totalEntrees'], 2, ',', ' ') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="synth-card sorties">
                <div class="label">Total sorties</div>
                <div class="value" style="color:var(--school-red);">−{{ number_format($journal['totalSorties'], 2, ',', ' ') }}</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="synth-card theorique">
                <div class="label">Solde théorique</div>
                <div class="value" style="color:var(--school-red-dark);">{{ number_format($journal['soldeTheorique'], 2, ',', ' ') }}</div>
            </div>
        </div>
    </div>

    {{-- Bloc clôture --}}
    @if (! $journal['caisse']->isOuverte())
        <div class="main-card mb-4">
            <div class="card-body">
                <h6 class="mb-3" style="color: var(--school-ink);">
                    <i class="fas fa-info-circle me-1" style="color: var(--school-gold-dark);"></i>
                    Informations de clôture
                </h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="label small text-muted text-xs">Solde compté</div>
                        <div class="fw-semibold">{{ number_format($journal['caisse']->solde_compte, 2, ',', ' ') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="label small text-muted text-xs">Écart</div>
                        <div class="fw-bold {{ abs($journal['caisse']->ecart) > 0.01 ? 'text-danger' : 'text-success' }}">
                            {{ number_format($journal['caisse']->ecart, 2, ',', ' ') }}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="label small text-muted text-xs">Clôturée le</div>
                        <div class="fw-semibold">{{ optional($journal['caisse']->date_cloture)->format('d/m/Y à H:i') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="label small text-muted text-xs">Validée par</div>
                        <div class="fw-semibold">{{ $journal['caisse']->valideur?->nom ?? '— en attente —' }}</div>
                    </div>
                </div>

                @if ($journal['caisse']->motif_ecart)
                    <div class="mt-3 p-3 rounded text-sm"
                         style="background: #fdf2f2; border-left: 3px solid var(--school-red); color: var(--school-red-dark);">
                        <strong>Motif de l'écart :</strong> {{ $journal['caisse']->motif_ecart }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Journal chronologique --}}
    <div class="main-card">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center"
             style="border-bottom: 1px solid #ece4d6;">
            <h6 class="mb-0" style="color: var(--school-ink);">
                <i class="fas fa-list-ol me-1" style="color: var(--school-gold-dark);"></i>
                Journal chronologique
            </h6>
            <span class="compteur-badge">{{ $journal['mouvements']->count() }} mouvement(s)</span>
        </div>

        <div class="table-responsive">
            <table class="table journal-table mb-0">
                <thead>
                    <tr>
                        <th style="width:100px;">Date</th>
                        <th>Libellé</th>
                        <th style="width:170px;">Type</th>
                        <th>Bénéficiaire</th>
                        <th class="text-end" style="width:130px;">Entrée</th>
                        <th class="text-end" style="width:130px;">Sortie</th>
                        <th style="width:150px;">Utilisateur</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($journal['mouvements'] as $m)
                    @php $t = $m->type_mouvement; @endphp
                    <tr>
                        <td class="text-sm">{{ optional($m->date_mouvement)->format('d/m/Y') }}</td>
                        <td>
                            <div class="fw-semibold" style="color: var(--school-ink);">{{ $m->libelle }}</div>
                            @if ($m->motif)
                                <div class="text-muted text-xs">{{ \Str::limit($m->motif, 90) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="type-badge {{ $t?->isEntree() ? 'entree' : 'sortie' }}">
                                {{ $t?->label() ?? '—' }}
                            </span>
                        </td>
                        <td class="text-sm text-muted">{{ $m->beneficiaire ?? '—' }}</td>
                        <td class="text-end montant entree">
                            {{ $t?->isEntree() ? number_format($m->montant, 2, ',', ' ') : '' }}
                        </td>
                        <td class="text-end montant sortie">
                            {{ $t && ! $t->isEntree() ? number_format($m->montant, 2, ',', ' ') : '' }}
                        </td>
                        <td class="text-sm text-muted">
                            {{ $m->utilisateur?->nom }} {{ $m->utilisateur?->prenom }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p class="mb-0 fw-medium">Aucun mouvement dans cette session.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
                @if ($journal['mouvements']->isNotEmpty())
                    <tfoot style="background: #faf6ee;">
                        <tr>
                            <td colspan="4" class="text-end fw-bold text-xs text-uppercase">Totaux</td>
                            <td class="text-end montant entree fw-bold">+{{ number_format($journal['totalEntrees'], 2, ',', ' ') }}</td>
                            <td class="text-end montant sortie fw-bold">−{{ number_format($journal['totalSorties'], 2, ',', ' ') }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end fw-bold text-uppercase" style="color: var(--school-red-dark);">Solde théorique</td>
                            <td class="fw-bold" style="color: var(--school-red-dark);">{{ number_format($journal['soldeTheorique'], 2, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Modales --}}
@if ($journal['caisse']->isOuverte())
    @can('caisses.mouvements')
        @include('domain.finances.caisses.partials._mouvement-modal', ['caisse' => $journal['caisse'], 'typesManuels' => $typesManuels])
    @endcan
    @can('caisses.cloturer')
        @include('domain.finances.caisses.partials._cloture-session-modal', ['caisse' => $journal['caisse']])
    @endcan
@endif
@endsection