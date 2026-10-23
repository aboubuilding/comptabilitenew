@extends('admin.layouts.app')

@section('title', 'Dashboard Boutique')
@section('page_icon', 'fa-chart-line')
@section('page_title', 'Dashboard Boutique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.ventes.index') }}">Boutique</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.ventes.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-list me-1"></i> Ventes
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.ventes.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Export
    </x-bouton>
    @can('ventes.creer')
        <x-bouton variante="nouveau" href="{{ route('logistique.ventes.index') }}#nouvelle">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle vente
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .dash-vt { --dv-bg-soft:var(--eim-cream); --dv-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }

    .stat-card { background:#fff; border:1px solid var(--dv-border); border-radius:12px; padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-icon { width:48px; height:48px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .chart-card { background:#fff; border:1px solid var(--dv-border); border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(142,18,22,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--dv-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color: var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; }
    .chart-wrapper { position:relative; height:280px; }

    .filters-card { background:#fff; border:1px solid var(--dv-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--dv-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-reset { background:#fff; border:1px solid var(--dv-border); color:var(--eim-ink-soft); border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

    .table-top thead tr { background:var(--dv-bg-soft); }
    .table-top thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--dv-border); }
    .table-top tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--dv-border); font-size:.85rem; vertical-align:middle; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="dash-vt">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('logistique.dashboard') }}" class="filters-card">
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
                <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Appliquer</x-bouton>
                <a href="{{ route('logistique.dashboard') }}" class="btn btn-reset"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-coins" couleur="green" libelle="CA aujourd'hui">{{ number_format($stats['ca_jour'], 0, ',', ' ') }}<x-slot:complement><div class="stat-sub">{{ $stats['nb_jour'] }} vente(s)</div></x-slot:complement></x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-chart-line" couleur="gold" libelle="CA période">{{ number_format($stats['ca_periode'], 0, ',', ' ') }}<x-slot:complement><div class="stat-sub">{{ $stats['nb_ventes'] }} vente(s)</div></x-slot:complement></x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-shopping-basket" couleur="blue" libelle="Panier moyen">{{ number_format($stats['panier_moyen'], 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-clock" couleur="red" libelle="Ventes impayées">{{ $statuts[0]->nb ?? 0 }}<x-slot:complement>@if (($statuts[0]->montant ?? 0) > 0)
                        <div class="stat-sub">{{ number_format((float) $statuts[0]->montant, 0, ',', ' ') }}</div>
                    @endif</x-slot:complement></x-stat>
        </div>
    </div>

    {{-- Ligne 1 : CA par jour + CA par magasin --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-area"></i> Chiffre d'affaires (30 derniers jours)</h6>
                </div>
                <div class="card-body">
                    @if ($caParJour->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <x-vide icone="fas fa-chart-area" :paragraphe="false"><p class="mb-0">Aucune vente sur la période.</p></x-vide>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartCaJour"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-store"></i> CA par magasin</h6>
                </div>
                <div class="card-body">
                    @if ($caParMagasin->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <x-vide icone="fas fa-store" :paragraphe="false"><p class="mb-0">Aucun magasin.</p></x-vide>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartCaMagasin"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : top produits + ventes du jour --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-trophy"></i> Top 10 produits vendus</h6>
                </div>
                <div class="card-body">
                    @if ($topProduits->isEmpty())
                        <x-vide icone="fas fa-trophy" :paragraphe="false"><p class="mb-0">Aucune vente de produits sur la période.</p></x-vide>
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
                    <h6><i class="fas fa-receipt"></i> Dernières ventes du jour</h6>
                    <a href="{{ route('logistique.ventes.index', ['jour' => 'today']) }}"
                       class="text-xs text-decoration-none" style="color:var(--school-red);">
                        Tout voir <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if ($ventesDuJour->isEmpty())
                        <x-vide icone="fas fa-receipt" :paragraphe="false"><p class="mb-0">Aucune vente aujourd'hui.</p></x-vide>
                    @else
                        <x-tableau class="table-top mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Référence</th>
                                        <th>Acheteur</th>
                                        <th class="text-end pe-4">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($ventesDuJour as $v)
                                    <tr>
                                        <td class="ps-4" style="font-family:monospace;font-size:.75rem;color:var(--school-red);font-weight:700;">
                                            {{ $v->reference }}
                                        </td>
                                        <td class="text-sm">{{ trim($v->acheteur) ?: 'Anonyme' }}</td>
                                        <td class="num grand pe-4">{{ number_format((float) $v->total_ttc, 0, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </x-tableau>
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
    const COLORS = { green: '#1F7A4D', red: '#E31E24', gold: '#8A6516', blue: '#2563eb', ink: '#2B1E1E', muted: '#5A4A4A', border: '#EADFCB' };
    const DONUT_COLORS = ['#E31E24', '#8A6516', '#1F7A4D', '#2563eb', '#B3171C', '#D4A94D', '#8E1216', '#3b82f6'];
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' F';

    // CA par jour
    const c1 = document.getElementById('chartCaJour');
    if (c1 && chartData.ca_par_jour.labels.length > 0) {
        new Chart(c1.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.ca_par_jour.labels,
                datasets: [{
                    label: 'CA',
                    data: chartData.ca_par_jour.values,
                    borderColor: COLORS.green,
                    backgroundColor: 'rgba(28,122,77,.10)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: COLORS.green,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: { label: (ctx) => `CA : ${fmtMoney(ctx.parsed.y)}` },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: {
                        beginAtZero: true, grid: { color: COLORS.border, drawBorder: false },
                        ticks: { color: COLORS.muted, font: { size: 10 }, callback: (v) => fmtMoney(v) },
                    },
                },
            },
        });
    }

    // CA par magasin
    const c2 = document.getElementById('chartCaMagasin');
    if (c2 && chartData.ca_par_magasin.labels.length > 0) {
        new Chart(c2.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.ca_par_magasin.labels,
                datasets: [{
                    data: chartData.ca_par_magasin.values,
                    backgroundColor: DONUT_COLORS.slice(0, chartData.ca_par_magasin.labels.length),
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

    // Top produits
    const c3 = document.getElementById('chartTopProduits');
    if (c3 && chartData.top_produits.labels.length > 0) {
        new Chart(c3.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.top_produits.labels,
                datasets: [{
                    label: 'Quantité vendue',
                    data: chartData.top_produits.quantite,
                    backgroundColor: COLORS.gold,
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
                                const ca = chartData.top_produits.ca[ctx.dataIndex];
                                return `Vendu : ${ctx.parsed.x} · CA : ${fmtMoney(ca)}`;
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
                                return lbl.length > 26 ? lbl.substring(0, 24) + '…' : lbl;
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