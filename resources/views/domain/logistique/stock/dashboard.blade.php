@extends('admin.layouts.app')

@section('title', 'Dashboard Stock')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Dashboard Stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.produits.index') }}">Produits &amp; Stock</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.produits.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-boxes me-1"></i> Liste des produits
    </a>
    <a href="{{ route('logistique.mouvements-stock.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-history me-1"></i> Journal
    </a>
    <a href="{{ route('logistique.produits.exportPdf', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF catalogue
    </a>
@endsection

@section('css')
<style>
    .dash-page { --dp-bg-soft:#faf6ee; --dp-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    /* Cartes stats */
    .stat-card {
        background:#fff; border:1px solid var(--dp-border); border-radius:12px;
        padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem;
        box-shadow:0 2px 8px rgba(122,15,28,.04);
        transition: transform .2s;
    }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon {
        width:48px; height:48px; border-radius:12px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-size:1.15rem;
    }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }
    .stat-info .stat-sub   { font-size:.7rem; color:var(--school-muted); margin-top:2px; }

    /* Cartes graphiques */
    .chart-card { background:#fff; border:1px solid var(--dp-border); border-radius:16px; overflow:hidden; box-shadow: 0 4px 18px rgba(122,15,28,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--dp-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color: var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; position:relative; }
    .chart-wrapper { position:relative; height:280px; }

    /* Filtres */
    .filters-card { background:#fff; border:1px solid var(--dp-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--dp-border); padding:.5rem .75rem; font-size:.85rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .btn-reset { background:#fff; border:1px solid var(--dp-border); color:#5b6b7a; border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

    /* Liste alertes */
    .alerte-item {
        display:flex; align-items:center; gap:.75rem; padding:.65rem .9rem;
        border-bottom:1px solid #f1ece2;
    }
    .alerte-item:last-child { border-bottom:none; }
    .alerte-icon {
        width:32px; height:32px; border-radius:8px; flex-shrink:0;
        background:#fdecea; color:var(--school-red);
        display:flex; align-items:center; justify-content:center; font-size:.85rem;
    }
    .alerte-produit { font-size:.85rem; font-weight:600; color:var(--school-ink); }
    .alerte-magasin { font-size:.72rem; color:var(--school-muted); }
    .alerte-deficit { margin-left:auto; text-align:right; }
    .alerte-deficit .val { font-size:.9rem; font-weight:700; color:var(--school-red); }
    .alerte-deficit .label { font-size:.65rem; color:var(--school-muted); }

    /* Tableau magasins */
    .table-magasin thead tr { background:var(--dp-bg-soft); }
    .table-magasin thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--dp-border); }
    .table-magasin tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--dp-border); font-size:.85rem; vertical-align:middle; }
    .table-magasin tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="dash-page">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('logistique.stock.dashboard') }}" class="filters-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <select name="magasin_id" class="form-select filtre-input">
                    <option value="">— Tous les magasins —</option>
                    @foreach ($magasins as $m)
                        <option value="{{ $m->id }}" @selected(($filters['magasin_id'] ?? '') == $m->id)>
                            {{ $m->libelle }}
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
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-filtrer flex-fill">
                    <i class="fas fa-filter me-1"></i> Appliquer
                </button>
                <a href="{{ route('logistique.stock.dashboard') }}" class="btn btn-reset">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-boxes"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Produits référencés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($stats['valeur_totale'], 0, ',', ' ') }}</div>
                    <div class="stat-label">Valeur totale du stock</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['en_alerte'] }}</div>
                    <div class="stat-label">Produits en alerte</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $stats['en_rupture'] }}</div>
                    <div class="stat-label">Produits en rupture</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 1 : valeur par magasin + évolution mensuelle --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-warehouse"></i> Valeur du stock par magasin</h6>
                </div>
                <div class="card-body">
                    @if ($valeurParMagasin->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;color:#9aa7b2;">
                            <div class="empty-state">
                                <i class="fas fa-chart-pie"></i>
                                <p class="mb-0">Aucune donnée.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartValeurMagasin"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-line"></i> Évolution des mouvements (12 derniers mois)</h6>
                    <span class="text-xs text-muted">Valeurs en F</span>
                </div>
                <div class="card-body">
                    @if ($evolutionMensuelle->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;color:#9aa7b2;">
                            <div class="empty-state">
                                <i class="fas fa-chart-line"></i>
                                <p class="mb-0">Aucun mouvement sur la période.</p>
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

    {{-- Ligne 2 : top produits + alertes --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-trophy"></i> Top 10 produits par valeur de stock</h6>
                </div>
                <div class="card-body">
                    @if ($topProduits->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;color:#9aa7b2;">
                            <div class="empty-state">
                                <i class="fas fa-trophy"></i>
                                <p class="mb-0">Aucun produit.</p>
                            </div>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartTopProduits"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-bell"></i> Alertes de stock</h6>
                    <a href="{{ route('logistique.produits.index', ['alerte' => 1]) }}"
                       class="text-xs text-decoration-none" style="color:var(--school-red);">
                        Voir tout <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if ($alertes->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-check-circle" style="color:#1c7a4d;"></i>
                            <p class="mb-0">Aucune alerte de stock.</p>
                        </div>
                    @else
                        @foreach ($alertes as $alerte)
                            <div class="alerte-item">
                                <div class="alerte-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="alerte-produit">{{ $alerte->produit }}</div>
                                    <div class="alerte-magasin">
                                        <i class="fas fa-warehouse me-1"></i>{{ $alerte->magasin }}
                                    </div>
                                </div>
                                <div class="alerte-deficit">
                                    <div class="val">−{{ number_format((float) $alerte->deficit, 2, ',', ' ') }}</div>
                                    <div class="label">
                                        {{ number_format((float) $alerte->quantite, 2, ',', ' ') }} / seuil {{ number_format((float) $alerte->seuil_alerte, 2, ',', ' ') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 3 : détail par magasin --}}
    <div class="chart-card">
        <div class="card-header">
            <h6><i class="fas fa-list"></i> Détail par magasin</h6>
            <span class="text-xs text-muted">{{ $valeurParMagasin->count() }} magasin(s)</span>
        </div>
        <div class="card-body p-0">
            @if ($valeurParMagasin->isEmpty())
                <div class="empty-state">
                    <i class="fas fa-warehouse"></i>
                    <p class="mb-0">Aucun magasin enregistré.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-magasin mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Magasin</th>
                                <th>Type</th>
                                <th class="text-end">Produits</th>
                                <th class="text-end">Quantité totale</th>
                                <th class="text-end pe-4">Valeur</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($valeurParMagasin as $vm)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('logistique.magasins.show', $vm->id) }}" class="text-decoration-none">
                                        <div class="fw-semibold">{{ $vm->libelle }}</div>
                                    </a>
                                </td>
                                <td class="text-sm">
                                    @php
                                        $typeMagasin = \App\Domain\Logistique\Types\TypeMagasin::tryFrom((int) $vm->type);
                                    @endphp
                                    {{ $typeMagasin?->label() ?? '—' }}
                                </td>
                                <td class="num">{{ $vm->nb_produits }}</td>
                                <td class="num">{{ number_format((float) $vm->quantite, 2, ',', ' ') }}</td>
                                <td class="num grand pe-4">{{ number_format((float) $vm->valeur, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot style="background:var(--dp-bg-soft);">
                            <tr>
                                <td colspan="4" class="text-end fw-bold text-uppercase text-xs" style="color:var(--school-red-dark);">Total</td>
                                <td class="num grand pe-4">{{ number_format($valeurParMagasin->sum('valeur'), 2, ',', ' ') }}</td>
                            </tr>
                        </tfoot>
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
    const COLORS = {
        red:      '#C81E3A',
        redDark:  '#7A0F22',
        green:    '#1c7a4d',
        gold:     '#B8860B',
        goldSoft: '#D4A94D',
        blue:     '#2563eb',
        ink:      '#1f2d3a',
        muted:    '#6f7e8c',
        border:   '#ece4d6',
    };
    const DONUT_COLORS = [
        '#C81E3A', '#B8860B', '#1c7a4d', '#2563eb',
        '#7A0F22', '#D4A94D', '#4A0C19', '#3b82f6',
        '#a64c1f', '#5b6b7a',
    ];
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(v) + ' F';

    // ============ Chart 1 : Valeur par magasin (donut) ============
    const ctx1 = document.getElementById('chartValeurMagasin');
    if (ctx1 && chartData.valeur_par_magasin.labels.length > 0) {
        new Chart(ctx1.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.valeur_par_magasin.labels,
                datasets: [{
                    data: chartData.valeur_par_magasin.values,
                    backgroundColor: DONUT_COLORS.slice(0, chartData.valeur_par_magasin.labels.length),
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
                        labels: { font: { size: 11 }, padding: 10, boxWidth: 12, usePointStyle: true },
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

    // ============ Chart 2 : Évolution mensuelle (barres groupées) ============
    const ctx2 = document.getElementById('chartEvolution');
    if (ctx2 && chartData.evolution.labels.length > 0) {
        new Chart(ctx2.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.evolution.labels,
                datasets: [
                    {
                        label: 'Entrées',
                        data: chartData.evolution.entrees,
                        backgroundColor: COLORS.green,
                        borderRadius: 4,
                        maxBarThickness: 22,
                    },
                    {
                        label: 'Sorties',
                        data: chartData.evolution.sorties,
                        backgroundColor: COLORS.red,
                        borderRadius: 4,
                        maxBarThickness: 22,
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

    // ============ Chart 3 : Top produits (barres horizontales) ============
    const ctx3 = document.getElementById('chartTopProduits');
    if (ctx3 && chartData.top_produits.labels.length > 0) {
        new Chart(ctx3.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.top_produits.labels,
                datasets: [{
                    label: 'Valeur du stock',
                    data: chartData.top_produits.values,
                    backgroundColor: COLORS.gold,
                    borderRadius: 6,
                    maxBarThickness: 22,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: {
                            label: (ctx) => {
                                const qte = chartData.top_produits.quantites[ctx.dataIndex];
                                return `Valeur : ${fmtMoney(ctx.parsed.x)} · Qté : ${qte}`;
                            },
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
                        ticks: {
                            color: COLORS.ink,
                            font: { size: 10 },
                            callback: function (value, index) {
                                const label = this.getLabelForValue(value);
                                return label.length > 28 ? label.substring(0, 26) + '…' : label;
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