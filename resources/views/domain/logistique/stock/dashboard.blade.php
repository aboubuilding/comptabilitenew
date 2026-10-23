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
    <x-bouton variante="contour" href="{{ route('logistique.produits.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-boxes me-1"></i> Liste des produits
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.mouvements-stock.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-history me-1"></i> Journal
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.produits.exportPdf', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF catalogue
    </x-bouton>
@endsection

@section('css')
<style>
    .dash-page { --dp-bg-soft:var(--eim-cream); --dp-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    /* Cartes stats */
    .stat-card {
        background:#fff; border:1px solid var(--dp-border); border-radius:12px;
        padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem;
        box-shadow:0 2px 8px rgba(142,18,22,.04);
        transition: transform .2s;
    }
    .stat-icon {
        width:48px; height:48px; border-radius:12px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-size:1.15rem;
    }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    /* Cartes graphiques */
    .chart-card { background:#fff; border:1px solid var(--dp-border); border-radius:16px; overflow:hidden; box-shadow: 0 4px 18px rgba(142,18,22,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--dp-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color: var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; position:relative; }
    .chart-wrapper { position:relative; height:280px; }

    /* Filtres */
    .filters-card { background:#fff; border:1px solid var(--dp-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--dp-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-reset { background:#fff; border:1px solid var(--dp-border); color:var(--eim-ink-soft); border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

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
    .table-magasin tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
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
                <x-bouton variante="filtrer" type="submit" class="flex-fill">
                    <i class="fas fa-filter me-1"></i> Appliquer
                </x-bouton>
                <a href="{{ route('logistique.stock.dashboard') }}" class="btn btn-reset">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-boxes" couleur="blue" libelle="Produits référencés">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-coins" couleur="green" libelle="Valeur totale du stock">{{ number_format($stats['valeur_totale'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-exclamation-triangle" couleur="gold" libelle="Produits en alerte">{{ $stats['en_alerte'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-times-circle" couleur="red" libelle="Produits en rupture">{{ $stats['en_rupture'] }}</x-stat>
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
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;color:#7A6A6A;">
                            <x-vide icone="fas fa-chart-pie" :paragraphe="false"><p class="mb-0">Aucune donnée.</p></x-vide>
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
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;color:#7A6A6A;">
                            <x-vide icone="fas fa-chart-line" :paragraphe="false"><p class="mb-0">Aucun mouvement sur la période.</p></x-vide>
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
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;color:#7A6A6A;">
                            <x-vide icone="fas fa-trophy" :paragraphe="false"><p class="mb-0">Aucun produit.</p></x-vide>
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
                        <x-vide :paragraphe="false"><i class="fas fa-check-circle" style="color:var(--eim-success);"></i>
                            <p class="mb-0">Aucune alerte de stock.</p></x-vide>
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
                <x-vide icone="fas fa-warehouse" :paragraphe="false"><p class="mb-0">Aucun magasin enregistré.</p></x-vide>
            @else
                <x-tableau class="table-magasin mb-0">
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
                    </x-tableau>
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
        red:      '#E31E24',
        redDark:  '#B3171C',
        green:    '#1F7A4D',
        gold:     '#8A6516',
        goldSoft: '#D4A94D',
        blue:     '#2563eb',
        ink:      '#2B1E1E',
        muted:    '#5A4A4A',
        border:   '#EADFCB',
    };
    const DONUT_COLORS = [
        '#E31E24', '#8A6516', '#1F7A4D', '#2563eb',
        '#B3171C', '#D4A94D', '#8E1216', '#3b82f6',
        '#a64c1f', '#5A4A4A',
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