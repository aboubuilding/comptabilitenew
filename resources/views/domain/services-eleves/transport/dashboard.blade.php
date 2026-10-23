@extends('admin.layouts.app')

@section('title', 'Dashboard Transport')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Dashboard Transport scolaire')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.voitures.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-bus me-1"></i> Véhicules
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.abonnements.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-id-card me-1"></i> Abonnements
    </x-bouton>
@endsection

@section('css')
<style>
    .dt-page { --dt-bg-soft:var(--eim-cream); --dt-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }

    .stat-card { background:#fff; border:1px solid var(--dt-border); border-radius:12px; padding:1.15rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-icon { width:48px; height:48px; border-radius:12px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:1.15rem; }
    .stat-info .stat-value { font-size:1.3rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .chart-card { background:#fff; border:1px solid var(--dt-border); border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(142,18,22,.05); }
    .chart-card .card-header { background:#fff; border-bottom:1px solid var(--dt-border); padding:1rem 1.25rem; display:flex; align-items:center; justify-content:space-between; }
    .chart-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .chart-card .card-header i { color: var(--school-gold-dark); }
    .chart-card .card-body { padding:1.25rem; }
    .chart-wrapper { position:relative; height:280px; }

    .filters-card { background:#fff; border:1px solid var(--dt-border); border-radius:12px; padding:.85rem 1rem; margin-bottom:1.25rem; }
    .filtre-input { border-radius:8px; border:1px solid var(--dt-border); padding:.5rem .75rem; font-size:.85rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:8px; padding:.5rem 1.1rem; font-weight:600; font-size:.85rem; }
    .btn-reset { background:#fff; border:1px solid var(--dt-border); color:var(--eim-ink-soft); border-radius:8px; padding:.5rem 1rem; font-size:.85rem; }

    .table-top thead tr { background:var(--dt-bg-soft); }
    .table-top thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--dt-border); }
    .table-top tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--dt-border); font-size:.85rem; vertical-align:middle; }
    .table-top tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="dt-page">

    {{-- Filtres --}}
    <form method="GET" action="{{ route('services-eleves.transport.dashboard') }}" class="filters-card">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] }}">
            </div>
            <div class="col-md-4">
                <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Appliquer</x-bouton>
                <a href="{{ route('services-eleves.transport.dashboard') }}" class="btn btn-reset"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>

    {{-- Cartes synthèse --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-bus" couleur="blue" libelle="Véhicules">{{ $statsParc['total'] }}<x-slot:complement><div class="stat-sub">
                        <span style="color:var(--eim-success);">{{ $statsParc['disponible'] }} dispo</span> ·
                        <span style="color:var(--school-gold-dark);">{{ $statsParc['maintenance'] }} maint.</span>
                    </div></x-slot:complement></x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-id-card" couleur="green" libelle="Abonnements actifs">{{ $statsAbonnements['total_actifs'] }}<x-slot:complement><div class="stat-sub">{{ number_format($statsAbonnements['revenus_mensuels'], 0, ',', ' ') }} F/mois</div></x-slot:complement></x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-gas-pump" couleur="gold" libelle="Carburant période">{{ number_format($couts['carburant'], 0, ',', ' ') }}<x-slot:complement><div class="stat-sub">{{ number_format($couts['litres'], 0, ',', ' ') }} litres</div></x-slot:complement></x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-coins" couleur="red" libelle="Coût total d'exploitation">{{ number_format($couts['total'], 0, ',', ' ') }}<x-slot:complement><div class="stat-sub">
                        Entretien : {{ number_format($couts['entretien'], 0, ',', ' ') }} ·
                        Assurance : {{ number_format($couts['assurance'], 0, ',', ' ') }}
                    </div></x-slot:complement></x-stat>
        </div>
    </div>

    {{-- Ligne 1 : répartition coûts + coûts par véhicule --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-pie"></i> Répartition des coûts</h6>
                </div>
                <div class="card-body">
                    @if ($couts['total'] <= 0)
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <x-vide icone="fas fa-chart-pie" :paragraphe="false"><p class="mb-0">Aucun coût enregistré.</p></x-vide>
                        </div>
                    @else                        <div class="chart-wrapper">
                            <canvas id="chartRepartition"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-bar"></i> Coûts par véhicule</h6>
                </div>
                <div class="card-body">
                    @if ($coutsParVehicule->isEmpty())
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <x-vide icone="fas fa-chart-bar" :paragraphe="false"><p class="mb-0">Aucune donnée.</p></x-vide>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartCoutsVehicules"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Ligne 2 : abonnements par mois + zones --}}
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-line"></i> Évolution des abonnements (12 derniers mois)</h6>
                </div>
                <div class="card-body">
                    @if (empty($chartData['abo_par_mois']['labels']))
                        <div class="chart-wrapper" style="display:flex;align-items:center;justify-content:center;">
                            <x-vide icone="fas fa-chart-line" :paragraphe="false"><p class="mb-0">Aucun abonnement créé sur la période.</p></x-vide>
                        </div>
                    @else
                        <div class="chart-wrapper">
                            <canvas id="chartAboMois"></canvas>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="chart-card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-map-marked-alt"></i> Abonnements actifs par zone</h6>
                    <a href="{{ route('services-eleves.transport.abonnements.index') }}" class="text-xs text-decoration-none" style="color:var(--school-red);">
                        Voir tout <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @if ($zonesActives->isEmpty())
                        <x-vide icone="fas fa-map-marked-alt" :paragraphe="false"><p class="mb-0">Aucune zone active.</p></x-vide>
                    @else
                        <x-tableau class="table-top mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Zone</th>
                                        <th class="text-end pe-4">Abonnés</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($zonesActives as $z)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $z->libelle }}</td>
                                        <td class="num grand pe-4">{{ $z->nb_abonnes }}</td>
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
    const DONUT_COLORS = ['#8A6516', '#E31E24', '#2563eb'];
    const fmtMoney = (v) => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(v) + ' F';

    // Répartition coûts (donut)
    const c1 = document.getElementById('chartRepartition');
    if (c1 && chartData.couts_repartition.values.some(v => v > 0)) {
        new Chart(c1.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.couts_repartition.labels,
                datasets: [{
                    data: chartData.couts_repartition.values,
                    backgroundColor: DONUT_COLORS,
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
                                return `${ctx.label} : ${fmtMoney(ctx.parsed)} (${pct} %)`;
                            },
                        },
                    },
                },
            },
        });
    }

    // Coûts par véhicule (barres groupées)
    const c2 = document.getElementById('chartCoutsVehicules');
    if (c2 && chartData.couts_vehicules.labels.length > 0) {
        new Chart(c2.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.couts_vehicules.labels,
                datasets: [
                    { label: 'Carburant', data: chartData.couts_vehicules.carburant, backgroundColor: COLORS.gold, borderRadius: 4, maxBarThickness: 22 },
                    { label: 'Entretien', data: chartData.couts_vehicules.entretien, backgroundColor: COLORS.blue, borderRadius: 4, maxBarThickness: 22 },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 14, usePointStyle: true } },
                    tooltip: {
                        backgroundColor: COLORS.ink,
                        callbacks: { label: (ctx) => `${ctx.dataset.label} : ${fmtMoney(ctx.parsed.y)}` },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 }, callback: (v) => fmtMoney(v) } },
                },
            },
        });
    }

    // Abonnements par mois (ligne)
    const c3 = document.getElementById('chartAboMois');
    if (c3 && chartData.abo_par_mois.labels.length > 0) {
        new Chart(c3.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.abo_par_mois.labels,
                datasets: [{
                    label: 'Abonnements',
                    data: chartData.abo_par_mois.values,
                    borderColor: COLORS.green,
                    backgroundColor: 'rgba(28,122,77,.10)',
                    fill: true, tension: 0.35,
                    pointRadius: 3, pointBackgroundColor: COLORS.green,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: COLORS.ink, callbacks: { label: (ctx) => `Abonnements : ${ctx.parsed.y}` } },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                    y: { beginAtZero: true, grid: { color: COLORS.border, drawBorder: false }, ticks: { color: COLORS.muted, font: { size: 10 } } },
                },
            },
        });
    }
});
</script>
@endpush