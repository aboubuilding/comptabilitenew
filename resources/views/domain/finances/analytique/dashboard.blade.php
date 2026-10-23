@extends('admin.layouts.app')

@section('title', 'Dashboard analytique')
@section('page_icon', 'fa-chart-bar')
@section('page_title', 'Dashboard analytique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.analytique.rapports.index') }}">Comptabilité analytique</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.analytique.rapports.index', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-table me-1"></i> Vue tableau
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('finances.analytique.rapports.exportPdf', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF
    </x-bouton>

    <x-bouton variante="contour" href="{{ route('finances.analytique.exportExcel', request()->query()) }}" class="btn-sm rounded-pill px-3">
    <i class="fas fa-file-excel me-1"></i> Excel multi-feuilles
</x-bouton>
@endsection

@section('css')
<style>
    /* Jetons propres à cette page — les couleurs de marque restent celles
       de layouts/app.blade.php. */
    .dashboard-page { --dash-bg-soft: var(--eim-cream); --dash-border: var(--eim-border); }

    /* Cartes synthèse */
    .stat-card {
        background: #fff; border: 1px solid var(--dash-border);
        border-radius: 12px; padding: 1.15rem 1.25rem;
        display: flex; align-items: center; gap: 1rem;
        transition: transform .2s, box-shadow .2s;
        box-shadow: 0 2px 8px rgba(142,18,22,.04);
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(142,18,22,.08); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; flex-shrink: 0;
    }
    .stat-info .stat-value { font-size: 1.3rem; font-weight: 700; color: var(--school-ink); line-height: 1.2; }
    .stat-info .stat-label { font-size: .75rem; color: var(--school-muted); margin: 0; }

    /* Cartes graphiques */
    .chart-card {
        background: #fff; border: 1px solid var(--dash-border);
        border-radius: 16px; overflow: hidden;
        box-shadow: 0 4px 18px rgba(142,18,22,.05);
    }
    .chart-card .card-header {
        background: #fff; border-bottom: 1px solid var(--dash-border);
        padding: 1rem 1.25rem;
        display: flex; align-items: center; justify-content: space-between;
    }
    .chart-card .card-header h6 {
        margin: 0; font-size: .92rem; font-weight: 700; color: var(--school-ink);
        display: flex; align-items: center; gap: 8px;
    }
    .chart-card .card-header i { color: var(--school-gold-dark); }
    .chart-card .card-body { padding: 1.25rem; position: relative; }

    /* Zone graphique — hauteur fixe pour Chart.js */
    .chart-wrapper     { position: relative; height: 320px; }
    .chart-wrapper-sm  { position: relative; height: 280px; }

    /* Filtres */
    .filters-card {
        background: #fff; border: 1px solid var(--dash-border);
        border-radius: 12px; padding: .85rem 1rem; margin-bottom: 1.25rem;
    }
    .filtre-input {
        border-radius: 8px; border: 1px solid var(--dash-border);
        padding: .5rem .75rem; font-size: .85rem;
    }
    .btn-filtrer {
        background: var(--school-red); color: #fff; border: none;
        border-radius: 8px; padding: .5rem 1.1rem; font-weight: 600; font-size: .85rem;
    }
    .btn-reset {
        background: #fff; border: 1px solid var(--dash-border);
        color: var(--eim-ink-soft); border-radius: 8px; padding: .5rem 1rem; font-size: .85rem;
    }

    /* Liste compacte résultat par centre (sous les graphiques) */
    .mini-table thead tr { background: var(--dash-bg-soft); }
    .mini-table thead th {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .4px;
        color: var(--school-red); font-weight: 700;
        padding: .6rem .9rem; border-bottom: 1px solid var(--dash-border);
    }
    .mini-table tbody td {
        font-size: .85rem; padding: .55rem .9rem;
        border-bottom: 1px solid var(--dash-border);
    }
    .mini-table .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
    .mini-table .num.positif { color: var(--eim-success); }
    .mini-table .num.negatif { color: var(--school-red); }

    /* État vide */
    .empty-chart {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        height: 100%; color: #7A6A6A; text-align: center;
    }
    .empty-chart i { font-size: 2.5rem; color: #d8c8ae; margin-bottom: .75rem; }
</style>
@endsection

@section('contenu')
<div class="dashboard-page">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('finances.analytique.rapports.dashboard') }}"
          class="filters-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="annee_id" class="form-select filtre-input">
                    <option value="">— Toutes les années —</option>
                    @foreach ($annees as $a)
                        <option value="{{ $a->id }}" @selected(($filters['annee_id'] ?? '') == $a->id)>
                            {{ $a->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date_debut" class="form-control filtre-input"
                       value="{{ $filters['date_debut'] ?? '' }}" placeholder="Du">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_fin" class="form-control filtre-input"
                       value="{{ $filters['date_fin'] ?? '' }}" placeholder="Au">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <x-bouton variante="filtrer" type="submit" class="flex-fill">
                    <i class="fas fa-filter me-1"></i> Appliquer
                </x-bouton>
                <a href="{{ route('finances.analytique.rapports.dashboard') }}"
                   class="btn btn-reset" title="Réinitialiser">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-arrow-down" couleur="green" libelle="Recettes cumulées">{{ number_format($totaux['recettes'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-arrow-up" couleur="red" libelle="Dépenses cumulées">{{ number_format($totaux['depenses'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-balance-scale" couleur="{{ $totaux['resultat'] >= 0 ? 'green' : 'red' }}" valeur-class="{{ $totaux['resultat'] >= 0 ? 'text-success' : 'text-danger' }}" libelle="Résultat net">{{ $totaux['resultat'] >= 0 ? '+' : '' }}{{ number_format($totaux['resultat'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-bullseye" couleur="gold" libelle="Taux de réalisation">{{ number_format($totaux['taux_realisation'], 1, ',', ' ') }} %<x-slot:complement><div class="stat-sub">
                        Budget : {{ number_format($totaux['budget'], 0, ',', ' ') }}
                    </div></x-slot:complement></x-stat>
        </div>
    </div>

    {{-- Ligne 1 : barres groupées + donut --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar"></i> Recettes vs Dépenses par centre</h6>
                    <span class="text-muted text-xs">Valeurs brutes</span>
                </div>
                <div class="card-body">
                    @if ($lignes->isEmpty())
                        <div class="chart-wrapper">
                            <div class="empty-chart">
                                <i class="fas fa-chart-bar"></i>
                                <p class="mb-0">Aucune donnée analytique sur la période.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartRecettesDepenses"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-pie"></i> Répartition des recettes</h6>
                </div>
                <div class="card-body">
                    @if ($lignes->isEmpty() || $totaux['recettes'] <= 0)
                        <div class="chart-wrapper-sm">
                            <div class="empty-chart">
                                <i class="fas fa-chart-pie"></i>
                                <p class="mb-0">Pas de recettes à répartir.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper-sm">
                            <canvas id="chartRepartition"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : résultat par centre + mini-tableau --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar"></i> Résultat net par centre</h6>
                    <span class="text-muted text-xs">
                        <span style="color:var(--eim-success);">●</span> Excédentaire &nbsp;
                        <span style="color:var(--school-red);">●</span> Déficitaire
                    </span>
                </div>
                <div class="card-body">
                    @if ($lignes->isEmpty())
                        <div class="chart-wrapper">
                            <div class="empty-chart">
                                <i class="fas fa-chart-bar"></i>
                                <p class="mb-0">Aucun résultat à afficher.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartResultats"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-list"></i> Détail par centre</h6>
                    <a href="{{ route('finances.analytique.rapports.index', request()->query()) }}"
                       class="text-xs text-decoration-none" style="color: var(--school-red);">
                        Vue complète <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <x-tableau class="mini-table mb-0">
                            <thead>
                                <tr>
                                    <th>Centre</th>
                                    <th class="text-end">Résultat</th>
                                    <th class="text-end">Taux</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($lignes as $l)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $l['libelle'] }}</span>
                                        <div class="text-xs text-muted">{{ $l['code'] }}</div>
                                    </td>
                                    <td class="num {{ $l['resultat'] >= 0 ? 'positif' : 'negatif' }}">
                                        {{ $l['resultat'] >= 0 ? '+' : '' }}{{ number_format($l['resultat'], 0, ',', ' ') }}
                                    </td>
                                    <td class="num text-muted">
                                        {{ number_format($l['taux_realisation'], 0, ',', ' ') }} %
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Aucune donnée.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </x-tableau>
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
    const totaux    = @json($totaux);

    if (!chartData.labels || chartData.labels.length === 0) return;

    // ========== Palette cohérente avec la charte ==========
    const COLORS = {
        recettes: '#1F7A4D',
        depenses: '#E31E24',
        positif:  '#1F7A4D',
        negatif:  '#E31E24',
        border:   '#EADFCB',
        ink:      '#2B1E1E',
        muted:    '#5A4A4A',
        gold:     '#8A6516',
    };

    // Palette pour le donut (répartition des recettes)
    const DOUGHNUT_COLORS = [
        '#E31E24', '#8A6516', '#1F7A4D', '#1d4ed8',
        '#B3171C', '#D4A94D', '#8E1216', '#2563eb',
        '#a64c1f', '#5A4A4A',
    ];

    // Formatage monétaire FR
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(v) + ' F';

    // ========================================================================
    // 1) Barres groupées : recettes vs dépenses par centre
    // ========================================================================
    const ctx1 = document.getElementById('chartRecettesDepenses');
    if (ctx1) {
        new Chart(ctx1.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: 'Recettes',
                        data: chartData.recettes,
                        backgroundColor: COLORS.recettes,
                        borderRadius: 6,
                        maxBarThickness: 42,
                    },
                    {
                        label: 'Dépenses',
                        data: chartData.depenses,
                        backgroundColor: COLORS.depenses,
                        borderRadius: 6,
                        maxBarThickness: 42,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { size: 11 }, padding: 14, usePointStyle: true },
                    },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        padding: 10,
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
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

    // ========================================================================
    // 2) Donut : répartition des recettes par centre
    // ========================================================================
    const ctx2 = document.getElementById('chartRepartition');
    if (ctx2) {
        // Filtre les centres sans recettes (sinon on a des parts vides)
        const pairs = chartData.labels
            .map((l, i) => ({ label: l, value: chartData.recettes[i] }))
            .filter(p => p.value > 0);

        new Chart(ctx2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: pairs.map(p => p.label),
                datasets: [{
                    data: pairs.map(p => p.value),
                    backgroundColor: DOUGHNUT_COLORS.slice(0, pairs.length),
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
                        labels: { font: { size: 10 }, padding: 10, boxWidth: 12, usePointStyle: true },
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

    // ========================================================================
    // 3) Barres horizontales : résultat net par centre
    //      Vert = excédentaire, Rouge = déficitaire
    // ========================================================================
    const ctx3 = document.getElementById('chartResultats');
    if (ctx3) {
        new Chart(ctx3.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Résultat net',
                    data: chartData.resultats,
                    backgroundColor: chartData.resultats.map(v =>
                        v >= 0 ? COLORS.positif : COLORS.negatif
                    ),
                    borderRadius: 6,
                    maxBarThickness: 26,
                }],
            },
            options: {
                indexAxis: 'y', // barres horizontales
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => `Résultat : ${fmtMoney(ctx.parsed.x)}`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: COLORS.border, drawBorder: false },
                        ticks: {
                            color: COLORS.muted,
                            font: { size: 10 },
                            callback: (v) => fmtMoney(v),
                        },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: COLORS.ink, font: { size: 11 } },
                    },
                },
            },
        });
    }
});
</script>
@endpush