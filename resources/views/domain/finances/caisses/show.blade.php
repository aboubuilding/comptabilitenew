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

    <a href="{{ route('finances.caisses.exportPdf', $journal['caisse']->id) }}"
   class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> PDF
</a>
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

    .onglets-journal { border-bottom:2px solid #ece4d6; gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets-journal .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets-journal .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets-journal .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:#faf6ee; color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid #ece4d6; }
    .total-type { background:#fff; border:1px solid #ece4d6; border-radius:10px; padding:.6rem .8rem; height:100%; }
    .total-type .libelle { font-size:.72rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .total-type .valeur { font-size:1.05rem; font-weight:700; color:#1c7a4d; font-variant-numeric:tabular-nums; }
    .total-type.total-general { background:#e3f7ec; border-color:#bfe6d0; }

    @media print {
        .tab-content > .tab-pane { display:block !important; opacity:1 !important; margin-bottom:1rem; }
        .onglets-journal { display:none !important; }
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

    {{-- Journal en onglets --}}
    @php
        $fmt = fn ($v) => number_format((float) $v, 2, ',', ' ');
        $totalDecaisse = $decaissements->sum('montant');
        $totalManuel = $entreesManuelles->sum('montant');
    @endphp
    <div class="main-card">
        <ul class="nav onglets-journal px-3 pt-2" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link @if ($onglet === 'mouvements') active @endif" id="onglet-mouvements" data-bs-toggle="tab" data-bs-target="#panneau-mouvements" type="button" role="tab" data-onglet="mouvements">
                    <i class="fas fa-list-ol me-1"></i> Tous les mouvements <span class="compte">{{ $journal['mouvements']->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if ($onglet === 'encaissements') active @endif" id="onglet-encaissements" data-bs-toggle="tab" data-bs-target="#panneau-encaissements" type="button" role="tab" data-onglet="encaissements">
                    <i class="fas fa-arrow-down me-1"></i> Tous les encaissements <span class="compte">{{ $encaissements->count() + $entreesManuelles->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if ($onglet === 'decaissements') active @endif" id="onglet-decaissements" data-bs-toggle="tab" data-bs-target="#panneau-decaissements" type="button" role="tab" data-onglet="decaissements">
                    <i class="fas fa-arrow-up me-1"></i> Décaissements <span class="compte">{{ $decaissements->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- ========== Tous les mouvements : la chronologie ========== --}}
            <div class="tab-pane fade @if ($onglet === 'mouvements') show active @endif" id="panneau-mouvements" role="tabpanel">
                <div class="table-responsive">
                    <table class="table journal-table mb-0" id="table-mouvements">
                        <thead>
                            <tr>
                                <th style="width:130px;">Date</th>
                                <th>Libellé</th>
                                <th style="width:150px;">Type</th>
                                <th class="text-end" style="width:130px;">Entrée</th>
                                <th class="text-end" style="width:130px;">Sortie</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($journal['mouvements'] as $m)
                            @php $t = $m->type_mouvement; @endphp
                            <tr>
                                <td class="text-sm">
                                    {{ optional($m->date_mouvement)->format('d/m/Y') }}
                                    <div class="text-muted text-xs">{{ $m->utilisateur?->nom }} {{ $m->utilisateur?->prenom }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: var(--school-ink);">{{ $m->libelle }}</div>
                                    <div class="text-muted text-xs">
                                        @if ($m->beneficiaire){{ $m->beneficiaire }}@endif
                                        @if ($m->beneficiaire && $m->motif) · @endif
                                        @if ($m->motif){{ \Str::limit($m->motif, 90) }}@endif
                                    </div>
                                </td>
                                <td><span class="type-badge {{ $t?->isEntree() ? 'entree' : 'sortie' }}">{{ $t?->label() ?? '—' }}</span></td>
                                <td class="text-end montant entree">{{ $t?->isEntree() ? $fmt($m->montant) : '' }}</td>
                                <td class="text-end montant sortie">{{ $t && ! $t->isEntree() ? $fmt($m->montant) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i><p class="mb-0 fw-medium">Aucun mouvement dans cette session.</p></div></td></tr>
                        @endforelse
                        </tbody>
                        @if ($journal['mouvements']->isNotEmpty())
                            <tfoot style="background: #faf6ee;">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold text-xs text-uppercase">Totaux</td>
                                    <td class="text-end montant entree fw-bold">+{{ $fmt($journal['totalEntrees']) }}</td>
                                    <td class="text-end montant sortie fw-bold">−{{ $fmt($journal['totalSorties']) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-uppercase" style="color: var(--school-red-dark);">Solde théorique</td>
                                    <td class="text-end fw-bold" style="color: var(--school-red-dark);">{{ $fmt($journal['soldeTheorique']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- ========== Tous les encaissements, avec les totaux par nature ========== --}}
            <div class="tab-pane fade @if ($onglet === 'encaissements') show active @endif" id="panneau-encaissements" role="tabpanel">
                <div class="p-3 border-bottom" style="border-color:#ece4d6 !important;">
                    <div class="fw-bold text-xs text-uppercase mb-2" style="color: var(--school-red);">Totaux encaissés par type de paiement</div>
                    <div class="row g-2" id="totaux-par-type">
                        @forelse ($totauxParType as $total)
                            <div class="col-6 col-md-4 col-xl-3">
                                <div class="total-type">
                                    <div class="libelle">{{ $total['libelle'] }}</div>
                                    <div class="valeur">{{ $fmt($total['montant']) }}</div>
                                    <div class="text-xs text-muted">{{ $total['nb'] }} ligne(s)</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted small">Aucun paiement encaissé dans cette caisse.</div>
                        @endforelse
                        @if ($entreesManuelles->isNotEmpty())
                            <div class="col-6 col-md-4 col-xl-3">
                                <div class="total-type">
                                    <div class="libelle">Entrées manuelles</div>
                                    <div class="valeur">{{ $fmt($totalManuel) }}</div>
                                    <div class="text-xs text-muted">{{ $entreesManuelles->count() }} mouvement(s)</div>
                                </div>
                            </div>
                        @endif
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="total-type total-general">
                                <div class="libelle">Total encaissé</div>
                                <div class="valeur" id="total-encaisse">{{ $fmt($totalEncaisse + $totalManuel) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table journal-table mb-0" id="table-encaissements">
                        <thead>
                            <tr>
                                <th style="width:150px;">Encaissement</th>
                                <th>Élève / Payeur</th>
                                <th>Nature(s)</th>
                                <th class="text-end" style="width:140px;">Montant</th>
                                <th class="text-center" style="width:80px;">Reçu</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($encaissements as $e)
                            @php
                                $natures = collect(explode(',', (string) $e->natures))
                                    ->map(fn ($c) => \App\Domain\Finances\Types\TypeDetail::tryFrom((int) $c)?->label())->filter()->implode(', ');
                                $mode = \App\Domain\Finances\Types\ModePaiement::tryFrom((int) $e->mode_paiement);
                            @endphp
                            <tr>
                                <td class="text-sm">
                                    <span class="fw-semibold" style="font-family:monospace;color:var(--school-red);">{{ $e->reference }}</span>
                                    <div class="text-muted text-xs">{{ $e->date_encaissement ? \Illuminate\Support\Carbon::parse($e->date_encaissement)->format('d/m/Y') : '—' }} · {{ $e->caissier }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $e->eleve_nom ? $e->eleve_nom . ' ' . $e->eleve_prenom : $e->payeur }}</div>
                                    <div class="text-muted text-xs">{{ $e->matricule }}@if ($e->classe) · {{ $e->classe }}@endif · payé par {{ $e->payeur }}</div>
                                </td>
                                <td class="text-sm">
                                    {{ $natures ?: '—' }}
                                    <div class="text-muted text-xs">{{ $e->nb_lignes }} ligne(s)@if ($mode) · {{ $mode->label() }}@endif</div>
                                </td>
                                <td class="text-end montant entree">{{ $fmt($e->montant) }}</td>
                                <td class="text-center">
                                    <a href="{{ route('finances.encaissements.paiement.recu', $e->paiement_id) }}" target="_blank" title="Reçu"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        @foreach ($entreesManuelles as $m)
                            <tr>
                                <td class="text-sm">{{ optional($m->date_mouvement)->format('d/m/Y') }}<div class="text-muted text-xs">{{ $m->utilisateur?->nom }} {{ $m->utilisateur?->prenom }}</div></td>
                                <td><div class="fw-semibold">{{ $m->libelle }}</div><div class="text-muted text-xs">{{ $m->beneficiaire }}</div></td>
                                <td class="text-sm"><span class="type-badge entree">Entrée manuelle</span><div class="text-muted text-xs">{{ \Str::limit($m->motif, 60) }}</div></td>
                                <td class="text-end montant entree">{{ $fmt($m->montant) }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                        @if ($encaissements->isEmpty() && $entreesManuelles->isEmpty())
                            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i><p class="mb-0 fw-medium">Aucun encaissement dans cette session.</p></div></td></tr>
                        @endif
                        </tbody>
                        @if ($encaissements->isNotEmpty() || $entreesManuelles->isNotEmpty())
                            <tfoot style="background: #faf6ee;">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold text-xs text-uppercase">Total encaissé</td>
                                    <td class="text-end montant entree fw-bold">{{ $fmt($totalEncaisse + $totalManuel) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            {{-- ========== Décaissements ========== --}}
            <div class="tab-pane fade @if ($onglet === 'decaissements') show active @endif" id="panneau-decaissements" role="tabpanel">
                <div class="table-responsive">
                    <table class="table journal-table mb-0" id="table-decaissements">
                        <thead>
                            <tr>
                                <th style="width:130px;">Date</th>
                                <th>Libellé</th>
                                <th style="width:150px;">Type</th>
                                <th>Bénéficiaire</th>
                                <th class="text-end" style="width:140px;">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($decaissements as $m)
                            <tr>
                                <td class="text-sm">{{ optional($m->date_mouvement)->format('d/m/Y') }}<div class="text-muted text-xs">{{ $m->utilisateur?->nom }} {{ $m->utilisateur?->prenom }}</div></td>
                                <td><div class="fw-semibold">{{ $m->libelle }}</div>@if ($m->motif)<div class="text-muted text-xs">{{ \Str::limit($m->motif, 90) }}</div>@endif</td>
                                <td><span class="type-badge sortie">{{ $m->type_mouvement?->label() }}</span></td>
                                <td class="text-sm text-muted">{{ $m->beneficiaire ?? '—' }}</td>
                                <td class="text-end montant sortie">{{ $fmt($m->montant) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="fas fa-inbox"></i><p class="mb-0 fw-medium">Aucun décaissement dans cette session.</p></div></td></tr>
                        @endforelse
                        </tbody>
                        @if ($decaissements->isNotEmpty())
                            <tfoot style="background: #faf6ee;">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold text-xs text-uppercase">Total décaissé</td>
                                    <td class="text-end montant sortie fw-bold" id="total-decaisse">{{ $fmt($totalDecaisse) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
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

@push('js')
<script>
$(function () {
    $('.onglets-journal [data-bs-toggle=tab]').on('shown.bs.tab', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('onglet', $(this).data('onglet'));
        history.replaceState(null, '', url);
    });
});
</script>
@endpush
