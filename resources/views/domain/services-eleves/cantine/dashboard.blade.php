@extends('admin.layouts.app')

@section('title', 'Dashboard Cantine')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Dashboard Cantine')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.cantine.inscriptions.index') }}">Cantine</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.cantine.inscriptions.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-users me-1"></i> Inscriptions
    </a>
    <a href="{{ route('services-eleves.cantine.menus.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-book-open me-1"></i> Menus
    </a>
    <a href="{{ route('services-eleves.cantine.preparations.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-fire me-1"></i> Préparations
    </a>
@endsection

@section('css')
<style>
    .dc-page { --dc-bg-soft:#faf6ee; --dc-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--dc-border); border-radius:12px; padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:48px; height:48px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }
    .stat-info .stat-sub   { font-size:.7rem; color:var(--school-muted); margin-top:2px; }

    .chart-card { background:#fff; border:1px solid var(--dc-border); border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(122,15,28,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--dc-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color:var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; }
    .chart-wrapper { position:relative; height:280px; }

    .filters-card { background:#fff; border:1px solid var(--dc-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--dc-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-reset { background:#fff; border:1px solid var(--dc-border); color:#5b6b7a; border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

    /* Rentabilité */
    .renta-card { background:linear-gradient(135deg,#1c7a4d,#2ecc71); color:#fff; border-radius:16px; padding:1.25rem 1.5rem; box-shadow:0 8px 24px rgba(28,122,77,.20); }
    .renta-card.deficit { background:linear-gradient(135deg,var(--school-red-deep),var(--school-red)); }
    .renta-card .label { font-size:.75rem; opacity:.85; text-transform:uppercase; letter-spacing:.5px; }
    .renta-card .value { font-size:1.6rem; font-weight:700; margin-top:4px; font-variant-numeric:tabular-nums; }
    .renta-card .sub { font-size:.8rem; opacity:.9; margin-top:4px; }

    .table-top thead tr { background:var(--dc-bg-soft); }
    .table-top thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--dc-border); }
    .table-top tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--dc-border); font-size:.85rem; vertical-align:middle; }
    .table-top tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="dc-page">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('services-eleves.cantine.dashboard') }}" class="filters-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] }}">
            </div>
            <div class="col-md-4">
                <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Appliquer</button>
                <a href="{{ route('services-eleves.cantine.dashboard') }}" class="btn btn-reset"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>

    {{-- Cartes stats --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsAbonnements['total_actifs'] }}</div>
                    <div class="stat-label">Abonnements actifs</div>
                    <div class="stat-sub">{{ number_format($statsAbonnements['revenus_mensuels'], 0, ',', ' ') }} F/mois</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-utensils"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsPreparations['parts'] }}</div>
                    <div class="stat-label">Parts servies</div>
                    <div class="stat-sub">{{ $statsPreparations['nb'] }} préparation(s)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($statsPreparations['cout_moyen_par_part'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Coût moyen / part</div>
                    <div class="stat-sub">Coût total : {{ number_format($statsPreparations['cout_total'], 0, ',', ' ') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon {{ $tauxOccupation >= 80 ? 'green' : ($tauxOccupation >= 50 ? 'gold' : 'red') }}">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($tauxOccupation, 1, ',', ' ') }} %</div>
                    <div class="stat-label">Taux d'occupation</div>
                    <div class="stat-sub">Parts servies / abonnés</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Carte rentabilité --}}
    <div class="row g-3 mb-4">
        <div class="col-md-8">
            <div class="renta-card {{ $rentabilite['marge'] < 0 ? 'deficit' : '' }}">
                <div class="row">
                    <div class="col-md-4">
                        <div class="label">Recettes encaissées</div>
                        <div class="value">{{ number_format($rentabilite['recettes'], 0, ',', ' ') }} F</div>
                    </div>
                    <div class="col-md-4">
                        <div class="label">Coûts d'exploitation</div>
                        <div class="value">{{ number_format($rentabilite['couts'], 0, ',', ' ') }} F</div>
                    </div>
                    <div class="col-md-4">
                        <div class="label">Marge</div>
                        <div class="value">
                            {{ $rentabilite['marge'] >= 0 ? '+' : '' }}{{ number_format($rentabilite['marge'], 0, ',', ' ') }} F
                        </div>
                        <div class="sub">{{ number_format($rentabilite['marge_pct'], 1, ',', ' ') }} % des recettes</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-balance-scale"></i> Rentabilité</h6>
                </div>
                <div class="card-body">
                    @if ($rentabilite['recettes'] <= 0 && $rentabilite['couts'] <= 0)
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-balance-scale"></i>
                                <p class="mb-0">Aucune donnée.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartRentabilite"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 1 : coût par jour + type repas --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-area"></i> Coût des préparations (30 derniers jours)</h6>
                </div>
                <div class="card-body">
                    @if ($coutParJour->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-chart-area"></i>
                                <p class="mb-0">Aucune préparation sur la période.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartCoutJour"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-utensils"></i> Répartition par type de repas</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['par_type_repas']['labels']))
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-utensils"></i>
                                <p class="mb-0">Aucune donnée.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartTypeRepas"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : top menus + évolution --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-trophy"></i> Top menus servis</h6>
                </div>
                <div class="card-body">
                    @if ($topMenus->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-trophy"></i>
                            <p class="mb-0">Aucun menu servi sur la période.</p>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartTopMenus"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-line"></i> Évolution mensuelle (12 mois)</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['evolution']['labels']))
                        <div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <p class="mb-0">Aucune donnée.</p>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartEvolution"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const chartData = @json($chartData);
    const COLORS = { green: '#1c7a4d', red: '#C81E3A', gold: '#B8860B', blue: '#2563eb', ink: '#1f2d3a', muted: '#6f7e8c', border: '#ece4d6' };
    const DONUT_COLORS = ['#B8860B', '#C81E3A', '#2563eb', '#7A0F22'];
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' F';

    // Rentabilité (donut)
    const c1 = document.getElementById('chartRentabilite');
    if (c1 && chartData.rentabilite.values.some(v => v > 0)) {
        new Chart(c1.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.rentabilite.labels,
                datasets: [{
                    data: chartData.rentabilite.values,
                    backgroundColor: [COLORS.green, COLORS.red],
                    borderWidth: 2, borderColor: '#fff',
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 10, boxWidth: 12, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: { label: (ctx) => `${ctx.label} : ${fmtMoney(ctx.parsed)}` },
                    },
                },
            },
        });
    }

    // Coût par jour (ligne)
    const c2 = document.getElementById('chartCoutJour');
    if (c2 && chartData.cout_par_jour.labels.length > 0) {
        new Chart(c2.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.cout_par_jour.labels,
                datasets: [{
                    label: 'Coût',
                    data: chartData.cout_par_jour.values,
                    borderColor: COLORS.gold,
                    backgroundColor: 'rgba(212,169,77,.15)',
                    fill: true, tension: 0.35,
                    pointRadius: 3, pointBackgroundColor: COLORS.gold,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const parts = chartData.cout_par_jour.parts[ctx.dataIndex];
                                return `Coût : ${fmtMoney(ctx.parsed.y)} · ${parts} parts`;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 }, callback: (v) => fmtMoney(v) } },
                },
            },
        });
    }

    // Type de repas (donut)
    const c3 = document.getElementById('chartTypeRepas');
    if (c3 && chartData.par_type_repas.labels.length > 0) {
        new Chart(c3.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.par_type_repas.labels,
                datasets: [{
                    data: chartData.par_type_repas.parts,
                    backgroundColor: DONUT_COLORS.slice(0, chartData.par_type_repas.labels.length),
                    borderWidth: 2, borderColor: '#fff',
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 10 }, padding: 10, boxWidth: 12, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const cout = chartData.par_type_repas.couts[ctx.dataIndex];
                                return `${ctx.label} : ${ctx.parsed} parts · ${fmtMoney(cout)}`;
                            },
                        },
                    },
                },
            },
        });
    }

    // Top menus (barres horizontales)
    const c4 = document.getElementById('chartTopMenus');
    if (c4 && chartData.top_menus.labels.length > 0) {
        new Chart(c4.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.top_menus.labels,
                datasets: [{
                    label: 'Parts servies',
                    data: chartData.top_menus.parts,
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
                                const cout = chartData.top_menus.couts[ctx.dataIndex];
                                return `${ctx.parsed.x} parts · ${fmtMoney(cout)}`;
                            },
                        },
                    },
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: {
                        grid: { display: false },
                        ticks: {
                            color: COLORS.ink, font: { size: 10 },
                            callback: function (v) {
                                const lbl = this.getLabelForValue(v);
                                return lbl.length > 24 ? lbl.substring(0, 22) + '…' : lbl;
                            },
                        },
                    },
                },
            },
        });
    }

    // Évolution mensuelle (barres)
    const c5 = document.getElementById('chartEvolution');
    if (c5 && chartData.evolution.labels.length > 0) {
        new Chart(c5.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.evolution.labels,
                datasets: [{
                    label: 'Coût',
                    data: chartData.evolution.couts,
                    backgroundColor: COLORS.blue,
                    borderRadius: 4, maxBarThickness: 22,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const parts = chartData.evolution.parts[ctx.dataIndex];
                                return `${fmtMoney(ctx.parsed.y)} · ${parts} parts`;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 9 } } },
                    y: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 }, callback: (v) => fmtMoney(v) } },
                },
            },
        });
    }
});
</script>
@endpush