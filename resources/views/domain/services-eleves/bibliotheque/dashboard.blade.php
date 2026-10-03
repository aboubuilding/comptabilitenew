@extends('admin.layouts.app')

@section('title', 'Dashboard Bibliothèque')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Dashboard Bibliothèque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.bibliotheque.livres.index') }}">Bibliothèque</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.bibliotheque.livres.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-book me-1"></i> Catalogue
    </a>
    <a href="{{ route('services-eleves.bibliotheque.prets.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-exchange-alt me-1"></i> Prêts
    </a>
    <a href="{{ route('services-eleves.bibliotheque.referentiels.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-cog me-1"></i> Référentiels
    </a>
@endsection

@section('css')
<style>
    .db-page { --db-bg-soft:#faf6ee; --db-border:#ece4d6; }
    .text-xs { font-size:.72rem; }

    .stat-card { background:#fff; border:1px solid var(--db-border); border-radius:12px; padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(122,15,28,.04); transition:transform .2s; }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:48px; height:48px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }
    .stat-info .stat-sub   { font-size:.7rem; color:var(--school-muted); margin-top:2px; }

    .chart-card { background:#fff; border:1px solid var(--db-border); border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(122,15,28,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--db-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color:var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; }
    .chart-wrapper { position:relative; height:280px; }

    .filters-card { background:#fff; border:1px solid var(--db-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--db-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-reset { background:#fff; border:1px solid var(--db-border); color:#5b6b7a; border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

    .table-top thead tr { background:var(--db-bg-soft); }
    .table-top thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--db-border); }
    .table-top tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--db-border); font-size:.85rem; vertical-align:middle; }
    .table-top tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.encours { background:#e0ecfc; color:#2563eb; }
    .badge-statut.rendu   { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.retard  { background:#fdecea; color:var(--school-red); font-weight:700; }
    .badge-statut.perdu   { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="db-page">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('services-eleves.bibliotheque.dashboard') }}" class="filters-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] }}">
            </div>
            <div class="col-md-4">
                <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Appliquer</button>
                <a href="{{ route('services-eleves.bibliotheque.dashboard') }}" class="btn btn-reset"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-book"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsCatalogue['total_livres'] }}</div>
                    <div class="stat-label">Titres au catalogue</div>
                    <div class="stat-sub">{{ $statsCatalogue['total_exemplaires'] }} exemplaires</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-hand-holding"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsPrets['en_cours'] }}</div>
                    <div class="stat-label">Prêts en cours</div>
                    <div class="stat-sub">{{ $statsPrets['total'] }} prêts sur la période</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $statsPrets['en_retard'] }}</div>
                    <div class="stat-label">Prêts en retard</div>
                    <div class="stat-sub">À relancer</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-percentage"></i></div>
                <div class="stat-info">
                    <div class="stat-value">
                        @php
                            $taux = $statsCatalogue['total_exemplaires'] > 0
                                ? round(($statsPrets['total'] / $statsCatalogue['total_exemplaires']) * 100, 1)
                                : 0;
                        @endphp
                        {{ number_format($taux, 1, ',', ' ') }} %
                    </div>
                    <div class="stat-label">Taux de rotation global</div>
                    <div class="stat-sub">Prêts / exemplaires</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 1 : top livres + répartition par statut --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-trophy"></i> Top 10 livres les plus empruntés</h6>
                </div>
                <div class="card-body">
                    @if ($topLivres->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-trophy"></i>
                                <p class="mb-0">Aucun prêt sur la période.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartTopLivres"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-pie"></i> Répartition par statut</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['par_statut']['labels']))
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-chart-pie"></i>
                                <p class="mb-0">Aucune donnée.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartParStatut"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : taux de rotation par catégorie + évolution mensuelle --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-redo"></i> Taux de rotation par catégorie</h6>
                    <span class="text-xs text-muted">Prêts / exemplaires</span>
                </div>
                <div class="card-body p-0">
                    @if ($rotationCategories->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-redo"></i>
                            <p class="mb-0">Aucune catégorie.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-top mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Catégorie</th>
                                        <th class="text-end">Livres</th>
                                        <th class="text-end">Prêts</th>
                                        <th class="text-end pe-4">Rotation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($rotationCategories as $cat)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $cat->libelle }}</td>
                                        <td class="num">{{ $cat->nb_livres }}</td>
                                        <td class="num">{{ $cat->nb_prets }}</td>
                                        <td class="num grand pe-4">{{ number_format($cat->taux_rotation, 2, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-line"></i> Évolution des prêts (12 derniers mois)</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['evolution']['labels']))
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <div class="empty-state">
                                <i class="fas fa-chart-line"></i>
                                <p class="mb-0">Aucun prêt enregistré.</p>
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
    </div>

    {{-- Ligne 3 : top emprunteurs + prêts en retard --}}
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-users"></i> Top 10 emprunteurs</h6>
                </div>
                <div class="card-body p-0">
                    @if ($topEmprunteurs->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p class="mb-0">Aucun emprunteur sur la période.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-top mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Élève</th>
                                        <th class="text-end pe-4">Prêts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($topEmprunteurs as $e)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $e->nom }} {{ $e->prenom }}</div>
                                            <div class="text-xs text-muted">{{ $e->matricule }}</div>
                                        </td>
                                        <td class="num grand pe-4">{{ $e->nb_prets }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6>
                        <i class="fas fa-exclamation-triangle" style="color:var(--school-red);"></i>
                        Prêts en retard
                    </h6>
                    <a href="{{ route('services-eleves.bibliotheque.prets.index', ['en_retard' => 1]) }}"
                       class="text-xs text-decoration-none" style="color:var(--school-red);">
                        Voir tout <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if ($pretsEnRetard->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-check-circle" style="color:#1c7a4d;"></i>
                            <p class="mb-0">Aucun prêt en retard.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-top mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Livre</th>
                                        <th>Élève</th>
                                        <th>Retour prévu</th>
                                        <th class="text-end pe-4">Retard</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($pretsEnRetard as $p)
                                    <tr>
                                        <td class="ps-4 text-sm">
                                            <div class="fw-semibold">{{ \Str::limit($p->livre?->titre, 30) }}</div>
                                        </td>
                                        <td class="text-sm">
                                            {{ $p->inscription?->eleve?->nom }}
                                            {{ $p->inscription?->eleve?->prenom }}
                                            <div class="text-xs text-muted">{{ $p->inscription?->classe?->libelle }}</div>
                                        </td>
                                        <td class="text-sm">
                                            {{ optional($p->date_retour_pret)->format('d/m/Y') }}
                                        </td>
                                        <td class="num pe-4" style="color:var(--school-red);font-weight:700;">
                                            {{ $p->jours_retard }} j
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
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const chartData = @json($chartData);
    const COLORS = { green: '#1c7a4d', red: '#C81E3A', gold: '#B8860B', blue: '#2563eb', ink: '#1f2d3a', muted: '#6f7e8c', border: '#ece4d6' };
    const STATUS_COLORS = {
        'En cours': '#2563eb',
        'Rendu':    '#1c7a4d',
        'En retard':'#C81E3A',
        'Perdu':    '#5b6b7a',
    };

    // Top livres (barres horizontales)
    const c1 = document.getElementById('chartTopLivres');
    if (c1 && chartData.top_livres.labels.length > 0) {
        new Chart(c1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.top_livres.labels,
                datasets: [{
                    label: 'Prêts',
                    data: chartData.top_livres.values,
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
                                const auteur = chartData.top_livres.auteurs[ctx.dataIndex];
                                return `${ctx.parsed.x} prêt(s) · ${auteur ?? '—'}`;
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

    // Répartition par statut (donut)
    const c2 = document.getElementById('chartParStatut');
    if (c2 && chartData.par_statut.labels.length > 0) {
        new Chart(c2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.par_statut.labels,
                datasets: [{
                    data: chartData.par_statut.values,
                    backgroundColor: chartData.par_statut.labels.map(l => STATUS_COLORS[l] || COLORS.muted),
                    borderWidth: 2, borderColor: '#fff',
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10, boxWidth: 12, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : '0.0';
                                return `${ctx.label} : ${ctx.parsed} (${pct} %)`;
                            },
                        },
                    },
                },
            },
        });
    }

    // Évolution mensuelle (barres empilées)
    const c3 = document.getElementById('chartEvolution');
    if (c3 && chartData.evolution.labels.length > 0) {
        new Chart(c3.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.evolution.labels,
                datasets: [
                    { label: 'En cours',  data: chartData.evolution.en_cours, backgroundColor: COLORS.blue,  borderRadius: 3, maxBarThickness: 22 },
                    { label: 'Rendus',    data: chartData.evolution.rendus,   backgroundColor: COLORS.green, borderRadius: 3, maxBarThickness: 22 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, usePointStyle: true } },
                    tooltip: { backgroundColor: COLORS.ink, mode: 'index', intersect: false },
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 9 } } },
                    y: { stacked: true, beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                },
            },
        });
    }
});
</script>
@endpush