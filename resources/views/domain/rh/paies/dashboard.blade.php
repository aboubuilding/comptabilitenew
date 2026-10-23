@extends('admin.layouts.app')

@section('title', 'Dashboard Paie')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Dashboard Paie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item"><a href="{{ route('rh.paies.index') }}">Paie</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('rh.paies.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-money-check-alt me-1"></i> Bulletins
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('rh.periodes.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-alt me-1"></i> Périodes
    </x-bouton>
    @can('paie.gerer')
        <x-bouton variante="nouveau" href="{{ route('rh.periodes.index') }}">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle période
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .dash-paie { --d-bg-soft:var(--eim-cream); --d-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .filters-card { background:#fff; border:1px solid var(--d-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--d-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-nouveau {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600;
    }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--d-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--d-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .stat-card { background:#fff; border:1px solid var(--d-border); border-radius:12px; padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-icon { width:48px; height:48px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .chart-card { background:#fff; border:1px solid var(--d-border); border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(142,18,22,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--d-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color:var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; }
    .chart-wrapper { position:relative; height:280px; }

    /* Carte billetterie / banque */
    .mode-card { border-radius:12px; padding:1.25rem; color:#fff; }
    .mode-card.billette { background:linear-gradient(135deg,var(--eim-gold-deep),var(--eim-gold)); }
    .mode-card.banque   { background:linear-gradient(135deg,#2563eb,#3b82f6); }
    .mode-card .label { font-size:.75rem; opacity:.85; text-transform:uppercase; letter-spacing:.5px; }
    .mode-card .value { font-size:1.5rem; font-weight:700; margin-top:4px; font-variant-numeric:tabular-nums; }
    .mode-card .sub { font-size:.8rem; opacity:.9; margin-top:4px; }
    .mode-card .detail { display:flex; justify-content:space-between; font-size:.75rem; opacity:.85; margin-top:8px; padding-top:8px; border-top:1px solid rgba(255,255,255,.2); }

    /* Tableau retards */
    .table-top thead tr { background:var(--d-bg-soft); }
    .table-top thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--d-border); white-space:nowrap; }
    .table-top tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--d-border); font-size:.85rem; vertical-align:middle; }
    .table-top tbody tr:hover { background:var(--eim-hover); }
    .table-top tfoot td { padding:.75rem .9rem; font-size:.9rem; font-weight:700; border-top:2px solid var(--school-red); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .badge-retard { display:inline-flex; align-items:center; padding:.3em .75em; border-radius:999px; font-size:.72rem; font-weight:700; }
    .badge-retard.ok   { background:#e3f7ec; color:var(--eim-success); }
    .badge-retard.warn { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-retard.bad  { background:#fdecea; color:var(--school-red); }

    .empty-state { padding:3rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2.5rem; margin-bottom:.65rem; display:block; color:#d8c8ae; }

    /* Bloc "aucune période" */
    .no-periode-block {
        background:#f0f6ff; border-left:3px solid #2563eb;
        border-radius:10px; padding:1.25rem 1.5rem;
        color:#1e3a8a; margin-bottom:1.5rem;
    }
    .no-periode-block h5 { font-size:1rem; font-weight:700; margin-bottom:.5rem; }
    .no-periode-block p { font-size:.88rem; margin-bottom:.75rem; }
</style>
@endsection

@section('contenu')
<div class="dash-paie">

    {{-- Sélection de la période --}}
    @if ($periodes->isNotEmpty())
        <form method="GET" action="{{ route('rh.paies.dashboard') }}" class="filters-card">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <x-libelle class="small text-muted mb-1">Période analysée</x-libelle>
                    <select name="periode_id" class="form-select filtre-input" onchange="this.form.submit()">
                        <option value="">— Sélectionner une période —</option>
                        @foreach ($periodes as $p)
                            <option value="{{ $p->id }}" @selected($periodeId == $p->id)>
                                {{ $p->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <x-bouton variante="filtrer" type="submit" class="w-100">
                        <i class="fas fa-filter me-1"></i> Appliquer
                    </x-bouton>
                </div>
                <div class="col-md-3 d-flex align-items-end justify-content-md-end">
                    <span class="text-muted small">
                        {{ $periodes->count() }} période(s) disponible(s)
                    </span>
                </div>
            </div>
        </form>
    @else
        <div class="no-periode-block">
            <h5><i class="fas fa-info-circle me-1"></i> Aucune période de paie configurée</h5>
            <p class="mb-0">
                Commencez par créer une période (ex. « Janvier 2026 ») pour pouvoir générer
                les bulletins et consulter le récapitulatif.
            </p>
            @can('paie.gerer')
                <a href="{{ route('rh.periodes.index') }}" class="btn btn-sm"
                   style="background:#2563eb;color:#fff;border-radius:8px;font-weight:600;">
                    <i class="fas fa-plus-circle me-1"></i> Créer une période
                </a>
            @endcan
        </div>
    @endif

    {{-- Récapitulatif de la période sélectionnée --}}
    @if ($recap)

        {{-- Cartes synthèse --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <x-stat icone="fas fa-file-invoice" couleur="blue" libelle="Bulletins de la période">{{ $recap['total'] }}<x-slot:complement><div class="stat-sub">{{ $recap['payes'] }} payé(s)</div></x-slot:complement></x-stat>
            </div>
            <div class="col-sm-6 col-lg-3">
                <x-stat icone="fas fa-coins" couleur="gold" libelle="Masse brute">{{ number_format($recap['total_brut'], 0, ',', ' ') }}</x-stat>
            </div>
            <div class="col-sm-6 col-lg-3">
                <x-stat icone="fas fa-money-bill-wave" couleur="green" libelle="Masse nette à payer">{{ number_format($recap['total_net'], 0, ',', ' ') }}</x-stat>
            </div>
            <div class="col-sm-6 col-lg-3">
                <x-stat icone="fas fa-minus-circle" couleur="red">{{ number_format($recap['total_deductions'] + $recap['total_retenues'], 0, ',', ' ') }}<x-slot:libelle>Retenues &amp; déductions</x-slot:libelle><x-slot:complement><div class="stat-sub">
                            IRPP : {{ number_format($recap['total_retenues'], 0, ',', ' ') }}
                        </div></x-slot:complement></x-stat>
            </div>
        </div>

        {{-- Cartes billetterie / banque --}}
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="mode-card billette">
                    <div class="label"><i class="fas fa-money-bill-wave me-1"></i>Billetterie (espèces)</div>
                    <div class="value">{{ number_format($recap['billette']['prevu'], 0, ',', ' ') }} F</div>
                    <div class="sub">
                        {{ $recap['billette']['nb'] }} employé(s) —
                        {{ number_format($recap['billette']['montant'], 0, ',', ' ') }} F déjà payés
                    </div>
                    <div class="detail">
                        <span><i class="fas fa-check-circle me-1"></i>Retirés : {{ $recap['billette']['retires'] }}</span>
                        <span><i class="fas fa-hourglass-half me-1"></i>En attente : {{ $recap['billette']['en_attente_retrait'] }}</span>
                    </div>
                    @if (($recap['billette']['montant_en_attente'] ?? 0) > 0)
                        <div class="detail" style="border-top:none;padding-top:4px;margin-top:4px;">
                            <span>Montant en attente de retrait</span>
                            <span>{{ number_format($recap['billette']['montant_en_attente'], 0, ',', ' ') }} F</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="mode-card banque">
                    <div class="label"><i class="fas fa-university me-1"></i>Banque (virement)</div>
                    <div class="value">{{ number_format($recap['banque']['prevu'], 0, ',', ' ') }} F</div>
                    <div class="sub">
                        {{ $recap['banque']['nb'] }} employé(s) —
                        {{ number_format($recap['banque']['montant'], 0, ',', ' ') }} F virement(s) effectué(s)
                    </div>
                    <div class="detail">
                        <span><i class="fas fa-check-circle me-1"></i>Virés</span>
                        <span>{{ $recap['banque']['nb'] }} / {{ $recap['banque']['nb'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Graphiques --}}
        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="chart-card h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-line"></i> Évolution de la masse salariale (12 mois)</h6>
                    </div>
                    <div class="card-body">
                        @if (empty($chartData['evolution']['labels']))
                            <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                                <x-vide icone="fas fa-chart-line" :paragraphe="false"><p class="mb-0">Aucune donnée sur les 12 derniers mois.</p></x-vide>
                            </div>
                        @else
                            <div class="chart-wrapper">
                                <canvas id="chartEvolution"></canvas>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="chart-card h-100">
                    <div class="card-header">
                        <h6><i class="fas fa-chart-pie"></i> Répartition billetterie / banque</h6>
                    </div>
                    <div class="card-body">
                        @if (array_sum($chartData['repartition']['values'] ?? []) <= 0)
                            <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                                <x-vide icone="fas fa-chart-pie" :paragraphe="false"><p class="mb-0">Aucune donnée.</p></x-vide>
                            </div>
                        @else
                            <div class="chart-wrapper">
                                <canvas id="chartRepartition"></canvas>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Tableau des retraits en attente --}}
    <div class="chart-card">
        <div class="card-header">
            <h6>
                <i class="fas fa-clock" style="color:var(--school-red);"></i>
                États des retraits en espèces en attente
            </h6>
            <span class="text-xs text-muted">{{ $enAttente->count() }} employé(s)</span>
        </div>
        <div class="card-body p-0">
            @if ($enAttente->isEmpty())
                <x-vide :paragraphe="false"><i class="fas fa-check-circle" style="color:var(--eim-success);"></i>
                    <p class="mb-0">Aucun retrait en attente. Tout le monde a récupéré sa paie.</p></x-vide>
            @else
                <x-tableau class="table-top mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Employé</th>
                                <th>Période</th>
                                <th>Payé le</th>
                                <th class="text-end">Montant net</th>
                                <th class="text-end">Jours écoulés</th>
                                @can('paie.gerer')
                                    <th class="text-center pe-4" style="width:120px;">Action</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($enAttente as $paie)
                            @php $jours = $paie->jours_depuis_paiement; @endphp
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('rh.paies.show', $paie) }}"
                                       class="text-decoration-none">
                                        <div class="fw-semibold">{{ $paie->employe?->nom_prenom }}</div>
                                        <div class="text-xs text-muted">{{ $paie->reference }}</div>
                                    </a>
                                </td>
                                <td class="text-sm">{{ $paie->periode?->libelle ?? '—' }}</td>
                                <td class="text-sm">
                                    {{ optional($paie->date_paiement)->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="num grand">
                                    {{ number_format((float) $paie->net_a_payer, 0, ',', ' ') }}
                                </td>
                                <td class="text-end">
                                    @php
                                        $badge = $jours < 7 ? 'ok' : ($jours < 30 ? 'warn' : 'bad');
                                    @endphp
                                    <span class="badge-retard {{ $badge }}">
                                        {{ $jours }} j
                                    </span>
                                </td>
                                @can('paie.gerer')
                                    <td class="text-center pe-4">
                                        <form action="{{ route('rh.paies.retrait', $paie) }}"
                                              method="POST" class="d-inline form-confirm-delete"
                                              data-confirm-title="Confirmer le retrait en espèces ?"
                                              data-confirm-text="L'employé a bien récupéré sa paie à la billetterie.">
                                            @csrf
                                            <button type="submit" class="btn btn-sm"
                                                    style="background:var(--eim-success);color:#fff;border-radius:8px;font-size:.75rem;">
                                                <i class="fas fa-hand-holding-usd me-1"></i>Retiré
                                            </button>
                                        </form>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="{{ auth()->user()->can('paie.gerer') ? 3 : 3 }}"
                                    class="text-end fw-bold text-uppercase text-xs"
                                    style="color:var(--school-red-dark);">
                                    Total en attente
                                </td>
                                <td class="num grand">
                                    {{ number_format($enAttente->sum('net_a_payer'), 0, ',', ' ') }}
                                </td>
                                <td></td>
                                @can('paie.gerer')
                                    <td></td>
                                @endcan
                            </tr>
                        </tfoot>
                    </x-tableau>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const chartData = @json($chartData ?? []);
    const COLORS = {
        green: '#1F7A4D', red: '#E31E24', gold: '#8A6516',
        blue: '#2563eb', ink: '#2B1E1E', muted: '#5A4A4A', border: '#EADFCB',
    };
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v) + ' F';

    // ============ ÉVOLUTION (lignes) ============
    const c1 = document.getElementById('chartEvolution');
    if (c1 && chartData.evolution && chartData.evolution.labels.length > 0) {
        new Chart(c1.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.evolution.labels,
                datasets: [
                    {
                        label: 'Masse brute',
                        data: chartData.evolution.brut,
                        borderColor: COLORS.gold,
                        backgroundColor: 'rgba(212,169,77,.10)',
                        fill: true, tension: 0.35,
                        pointRadius: 3, pointBackgroundColor: COLORS.gold,
                    },
                    {
                        label: 'Masse nette',
                        data: chartData.evolution.net,
                        borderColor: COLORS.green,
                        backgroundColor: 'rgba(28,122,77,.10)',
                        fill: true, tension: 0.35,
                        pointRadius: 3, pointBackgroundColor: COLORS.green,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, padding: 14, usePointStyle: true },
                    },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: (ctx) => `${ctx.dataset.label} : ${fmtMoney(ctx.parsed.y)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: COLORS.muted, font: { size: 10 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: COLORS.border, drawBorder: false },
                        ticks: {
                            color: COLORS.muted,
                            font: { size: 10 },
                            callback: (v) => fmtMoney(v),
                        },
                    },
                },
            },
        });
    }

    // ============ RÉPARTITION (donut) ============
    const c2 = document.getElementById('chartRepartition');
    if (c2 && chartData.repartition && chartData.repartition.values.some(v => v > 0)) {
        new Chart(c2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.repartition.labels,
                datasets: [{
                    data: chartData.repartition.values,
                    backgroundColor: [COLORS.gold, COLORS.blue],
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11 },
                            padding: 10,
                            usePointStyle: true, // ✅ correction : usePointStyle, pas useBoxStyle
                        },
                    },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
                                return `${ctx.label} : ${fmtMoney(ctx.parsed)} (${pct} %)`;
                            },
                        },
                    },
                },
            },
        });
    }
});
</script>
@endpush