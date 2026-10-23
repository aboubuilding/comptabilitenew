@extends('admin.layouts.app')

@section('title', 'Rapport analytique')
@section('page_icon', 'fa-chart-pie')
@section('page_title', 'Comptabilité analytique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Rapport analytique</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.analytique.centres.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-sitemap me-1"></i> Centres
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('finances.analytique.rapports.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Excel
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('finances.analytique.rapports.exportPdf', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF
    </x-bouton>
@endsection

@section('css')
<style>
    .rapport-page { --rp-bg-soft:var(--eim-cream); --rp-border:var(--eim-border); }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--rp-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--rp-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-outline-scolarite { border:1px solid var(--rp-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--rp-bg-soft); border-color:var(--school-red); color:var(--school-red); }
    .table-rapport thead tr { background:var(--rp-bg-soft); }
    .table-rapport thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--rp-border); white-space:nowrap; }
    .table-rapport tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--rp-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-rapport tbody tr:hover { background:var(--eim-hover); }
    .table-rapport .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .table-rapport .num.positif { color:var(--eim-success); }
    .table-rapport .num.negatif { color:var(--school-red); }
    .centre-code { font-family:monospace; background:var(--rp-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-size:.75rem; font-weight:700; }
    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
    .taux-bar { height:6px; background:#eceff2; border-radius:3px; overflow:hidden; margin-top:4px; }
    .taux-bar > div { height:100%; background:linear-gradient(90deg,var(--eim-success),#2ecc71); }
    .taux-bar.warn > div { background:linear-gradient(90deg,var(--eim-gold-deep),var(--eim-gold)); }
    .taux-bar.bad > div { background:linear-gradient(90deg,var(--school-red-dark),var(--school-red)); }
</style>
@endsection

@section('contenu')
<div class="rapport-page">

    <x-carte class="mb-3">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="annee_id" class="form-select filtre-input">
                        <option value="">— Année —</option>
                        @foreach ($annees as $a)
                            <option value="{{ $a->id }}" @selected(($filters['annee_id'] ?? '') == $a->id)>{{ $a->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}" placeholder="Du">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}" placeholder="Au">
                </div>
                <div class="col-md-3">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
            </form>
        </div>
    </x-carte>

    <x-carte>
        <div class="card-body p-0">
            <x-tableau class="table-rapport align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Centre analytique</th>
                            <th class="text-end">Recettes</th>
                            <th class="text-end">Dépenses</th>
                            <th class="text-end">Résultat</th>
                            <th class="text-end">Budget</th>
                            <th class="text-end">Écart budget</th>
                            <th style="width:160px;">Taux de réalisation</th>
                        </tr>
                    </thead>
                    <tbody>
                    @php $totR = $totD = $totRes = $totB = 0; @endphp
                    @forelse ($lignes as $l)
                        @php
                            $totR += $l['recettes']; $totD += $l['depenses'];
                            $totRes += $l['resultat']; $totB += $l['budget'];
                            $taux = $l['taux_realisation'];
                            $barClass = $taux >= 80 ? '' : ($taux >= 50 ? 'warn' : 'bad');
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <span class="centre-code">{{ $l['code'] }}</span>
                                <div class="fw-semibold mt-1">{{ $l['libelle'] }}</div>
                            </td>
                            <td class="num positif">+{{ number_format($l['recettes'], 2, ',', ' ') }}</td>
                            <td class="num negatif">−{{ number_format($l['depenses'], 2, ',', ' ') }}</td>
                            <td class="num {{ $l['resultat'] >= 0 ? 'positif' : 'negatif' }}">
                                {{ $l['resultat'] >= 0 ? '+' : '' }}{{ number_format($l['resultat'], 2, ',', ' ') }}
                            </td>
                            <td class="num">{{ number_format($l['budget'], 2, ',', ' ') }}</td>
                            <td class="num {{ $l['ecart_budget'] >= 0 ? 'positif' : 'negatif' }}">
                                {{ $l['ecart_budget'] >= 0 ? '+' : '' }}{{ number_format($l['ecart_budget'], 2, ',', ' ') }}
                            </td>
                            <td>
                                <span class="fw-bold">{{ number_format($taux, 1, ',', ' ') }} %</span>
                                <div class="taux-bar {{ $barClass }}"><div style="width:{{ min(100, max(0, $taux)) }}%"></div></div>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-chart-pie">Aucune donnée analytique sur la période.</x-vide>
                    @endforelse
                    </tbody>
                    @if ($lignes->isNotEmpty())
                        <tfoot style="background:var(--rp-bg-soft);">
                            <tr>
                                <td class="ps-4 fw-bold text-uppercase text-xs" style="color:var(--school-red-dark);">Totaux</td>
                                <td class="num positif fw-bold">+{{ number_format($totR, 2, ',', ' ') }}</td>
                                <td class="num negatif fw-bold">−{{ number_format($totD, 2, ',', ' ') }}</td>
                                <td class="num fw-bold {{ $totRes >= 0 ? 'positif' : 'negatif' }}">
                                    {{ $totRes >= 0 ? '+' : '' }}{{ number_format($totRes, 2, ',', ' ') }}
                                </td>
                                <td class="num fw-bold">{{ number_format($totB, 2, ',', ' ') }}</td>
                                <td class="num fw-bold {{ ($totRes - $totB) >= 0 ? 'positif' : 'negatif' }}">
                                    {{ ($totRes - $totB) >= 0 ? '+' : '' }}{{ number_format($totRes - $totB, 2, ',', ' ') }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </x-tableau>
        </div>
    </x-carte>
</div>
@endsection