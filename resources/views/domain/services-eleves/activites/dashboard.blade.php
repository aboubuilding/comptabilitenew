@extends('admin.layouts.app')

@section('title', 'Dashboard Activités & Événements')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Dashboard Activités & Événements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.activites-evenements.activites.index') }}">Activités</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.activites-evenements.activites.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-futbol me-1"></i> Activités
    </a>
    <a href="{{ route('services-eleves.activites-evenements.evenements.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-calendar-check me-1"></i> Événements
    </a>
    <a href="{{ route('services-eleves.activites-evenements.inscriptions.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-user-check me-1"></i> Inscriptions
    </a>
@endsection

@section('css')
<style>
    .dash-ae { --d-bg-soft:#faf6ee; --d-border:#ece4d6; }
    .text-xs { font-size:.72rem; }

    .stat-card { background:#fff; border:1px solid var(--d-border); border-radius:12px; padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:48px; height:48px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }
    .stat-info .stat-sub   { font-size:.7rem; color:var(--school-muted); margin-top:2px; }

    .chart-card { background:#fff; border:1px solid var(--d-border); border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(122,15,28,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--d-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color:var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; }
    .chart-wrapper { position:relative; height:280px; }

    .filters-card { background:#fff; border:1px solid var(--d-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--d-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-reset { background:#fff; border:1px solid var(--d-border); color:#5b6b7a; border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

    .table-top thead tr { background:var(--d-bg-soft); }
    .table-top thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--d-border); }
    .table-top tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--d-border); font-size:.85rem; vertical-align:middle; }
    .table-top tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .progress-bar-custom {
        height:6px; background:#eceff2; border-radius:3px; overflow:hidden; margin-top:4px;
    }
    .progress-bar-custom > div {
        height:100%; background:linear-gradient(90deg, #1c7a4d, #2ecc71);
    }
    .progress-bar-custom.warn > div { background:linear-gradient(90deg, #B8860B, #D4A94D); }
    .progress-bar-custom.full > div { background:linear-gradient(90deg, var(--school-red-dark), var(--school-red)); }

    .badge-type { display:inline-flex; padding:.25em .7em; border-radius:999px; font-size:.7rem; font-weight:600; }
    .badge-type.excursion   { background:#e3f7ec; color:#1c7a4d; }
    .badge-type.voyage      { background:#e0ecfc; color:#2563eb; }
    .badge-type.sortie_pedagogique { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.competition { background:#fce4ec; color:#c2185b; }
    .badge-type.autre       { background:#eceff2; color:#5b6b7a; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="dash-ae">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('services-eleves.activites-evenements.dashboard') }}" class="filters-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] }}">
            </div>
            <div class="col-md-4">
                <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Appliquer</button>
                <a href="{{ route('services-eleves.activites-evenements.dashboard') }}" class="btn btn-reset"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-futbol"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsActivites['total'] }}</div>
                    <div class="stat-label">Activités</div>
                    <div class="stat-sub">{{ $statsActivites['inscrits'] }} inscription(s)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($revenusActivites, 0, ',', ' ') }}</div>
                    <div class="stat-label">Revenus activités</div>
                    <div class="stat-sub">Dû : {{ number_format($statsActivites['montant_du'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsEvenements['total'] }}</div>
                    <div class="stat-label">Événements</div>
                    <div class="stat-sub">{{ $statsEvenements['a_venir'] }} à venir</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsEvenements['confirmes'] }}</div>
                    <div class="stat-label">Confirmés événements</div>
                    <div class="stat-sub">{{ $statsEvenements['pre_inscrits'] }} en attente</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 1 : top activités + répartition par type --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-trophy"></i> Top 10 activités par inscrits</h6>
                </div>
                <div class="card-body">
                    @if ($topActivites->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-trophy"></i>
                                <p class="mb-0">Aucune activité.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartTopActivites"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-pie"></i> Répartition par type</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['par_type']['labels']))
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-chart-pie"></i>
                                <p class="mb-0">Aucune donnée.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartParType"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : évolution + revenus --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-line"></i> Évolution mensuelle (12 derniers mois)</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['evolution']['labels']))
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-chart-line"></i>
                                <p class="mb-0">Aucune donnée.</p>
                            </div>
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
                    <h6><i class="fas fa-balance-scale"></i> Revenus encaissés</h6>
                </div>
                <div class="card-body">
                    @if (array_sum($chartData['revenus']['values']) <= 0)
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-balance-scale"></i>
                                <p class="mb-0">Aucun revenu.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartRevenus"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 3 : prochains événements avec taux de remplissage --}}
    <div class="chart-card">
        <div class="card-header">
            <h6><i class="fas fa-calendar-alt"></i> Prochains événements</h6>
            <a href="{{ route('services-eleves.activites-evenements.evenements.index', ['a_venir' => 1]) }}"
               class="text-xs text-decoration-none" style="color:var(--school-red);">
                Voir tous <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            @if ($prochainsEvenements->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-calendar-check"></i>
                    <p class="mb-0">Aucun événement à venir.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-top mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Nom</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th class="text-end">Participation</th>
                                <th class="text-center">Confirmés</th>
                                <th class="text-center pe-4" style="min-width:140px;">Remplissage</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($prochainsEvenements as $evt)
                            @php
                                $taux = $evt->capacite_limitee && $evt->capacite > 0
                                    ? round(($evt->nb_confirmes / $evt->capacite) * 100, 1)
                                    : 0;
                                $barClass = $taux >= 90 ? 'full' : ($taux >= 60 ? 'warn' : '');
                            @endphp
                            <tr>
                                <td class="ps-4 fw-semibold">{{ $evt->nom }}</td>
                                <td>
                                    <span class="badge-type {{ $evt->type }}">
                                        {{ \App\Domain\ServicesEleves\Types\TypeEvenement::tryFrom($evt->type)?->label() ?? $evt->type }}
                                    </span>
                                </td>
                                <td class="text-sm">{{ \Carbon\Carbon::parse($evt->date_evenement)->format('d/m/Y') }}</td>
                                <td class="num grand">{{ number_format((float) $evt->participation, 0, ',', ' ') }}</td>
                                <td class="text-center">
                                    @if ($evt->capacite_limitee)
                                        <strong>{{ $evt->nb_confirmes }}</strong> / {{ $evt->capacite }}
                                    @else
                                        <strong>{{ $evt->nb_confirmes }}</strong>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    @if ($evt->capacite_limitee && $evt->capacite > 0)
                                        <span class="fw-bold">{{ number_format($taux, 0, ',', ' ') }} %</span>
                                        <div class="progress-bar-custom {{ $barClass }}">
                                            <div style="width:{{ min(100, max(0, $taux)) }}%"></div>
                                        </div>
                                    @else
                                        <span class="text-muted small">Non limité</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const chartData = @json($chartData);
    const COLORS = { green: '#1c7a4d', red: '#C81E3A', gold: '#B8860B', blue: '#2563eb', ink: '#1f2d3a', muted: '#6f7e8c', border: '#ece4d6' };
    const DONUT_COLORS = ['#C81E3A', '#B8860B', '#1c7a4d', '#2563eb', '#7A0F22', '#D4A94D', '#4A0C19', '#3b82f6'];
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' F';

    // Top activités (barres horizontales)
    const c1 = document.getElementById('chartTopActivites');
    if (c1 && chartData.top_activites.labels.length > 0) {
        new Chart(c1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.top_activites.labels,
                datasets: [{
                    label: 'Inscrits',
                    data: chartData.top_activites.inscrits,
                    backgroundColor: COLORS.green,
                    borderRadius: 6, maxBarThickness: 22,
                }],
            },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const montant = chartData.top_activites.montant[ctx.dataIndex];
                                return `${ctx.parsed.x} inscrit(s) · ${fmtMoney(montant)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: { grid: { display: false }, ticks: { color: COLORS.ink, font: { size: 10 } } },
                },
            },
        });
    }

    // Répartition par type (donut)
    const c2 = document.getElementById('chartParType');
    if (c2 && chartData.par_type.labels.length > 0) {
        new Chart(c2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.par_type.labels,
                datasets: [{
                    data: chartData.par_type.inscrits,
                    backgroundColor: DONUT_COLORS.slice(0, chartData.par_type.labels.length),
                    borderWidth: 2, borderColor: '#fff',
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 8, boxWidth: 12, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const nbAct = chartData.par_type.activites[ctx.dataIndex];
                                return `${ctx.label} : ${ctx.parsed} inscrit(s) · ${nbAct} activité(s)`;
                            },
                        },
                    },
                },
            },
        });
    }

    // Évolution mensuelle (2 lignes)
    const c3 = document.getElementById('chartEvolution');
    if (c3 && chartData.evolution.labels.length > 0) {
        new Chart(c3.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.evolution.labels.map(l => {
                    const [y, m] = l.split('-');
                    return `${m}/${y.substring(2)}`;
                }),
                datasets: [
                    {
                        label: 'Inscriptions activités',
                        data: chartData.evolution.inscriptions,
                        borderColor: COLORS.green,
                        backgroundColor: 'rgba(28,122,77,.10)',
                        fill: true, tension: 0.35,
                        pointRadius: 3, pointBackgroundColor: COLORS.green,
                    },
                    {
                        label: 'Participations événements',
                        data: chartData.evolution.participations,
                        borderColor: COLORS.gold,
                        backgroundColor: 'rgba(212,169,77,.10)',
                        fill: true, tension: 0.35,
                        pointRadius: 3, pointBackgroundColor: COLORS.gold,
                    },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, usePointStyle: true } },
                    tooltip: { backgroundColor: COLORS.ink, mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                },
            },
        });
    }

    // Revenus (barres)
    const c4 = document.getElementById('chartRevenus');
    if (c4 && chartData.revenus.values.some(v => v > 0)) {
        new Chart(c4.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.revenus.labels,
                datasets: [{
                    data: chartData.revenus.values,
                    backgroundColor: [COLORS.green, COLORS.gold],
                    borderRadius: 6, maxBarThickness: 60,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: { label: (ctx) => fmtMoney(ctx.parsed.y) },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 11 } } },
                    y: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 }, callback: (v) => fmtMoney(v) } },
                },
            },
        });
    }
});
</script>
@endpush