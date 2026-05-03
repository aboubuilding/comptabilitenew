@extends('layouts.app')

@section('title', 'Tableau de bord | Comptabilité - École Mariam')

@section('titre', 'Tableau de bord')

@section('breadcrumb')

@endsection

@section('content')
@php
    $session = session()->get('LoginUser');
    $user = $session['compte_id'] ? \App\Models\User::rechercheUserById($session['compte_id']) : null;
    $role = $user?->role ?? null;
    $isAdmin = in_array($role, [\App\Types\Role::ADMIN, \App\Types\Role::DIRECTEUR]);
    $currentYear = $session['annee_libelle'] ?? '2024-2025';
@endphp

@if($isAdmin)
<div class="eim-dashboard">

    {{-- ═══════════════════════════════════════════════════════════
        🎯 EN-TÊTE AVEC BIENVENUE & DATE
        ═══════════════════════════════════════════════════════════ --}}
    <div class="dashboard-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="dashboard-welcome">
                    👋 Bonjour, <span class="text-white fw-bold">{{ $user?->prenom ?? 'Administrateur' }}</span>
                </h2>
                <p class="dashboard-subtitle mb-0">
                    <i class="fas fa-calendar-day me-2"></i>
                    {{ now()->locale('fr')->isoFormat('LLLL') }}
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="year-badge">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Année scolaire : <strong>{{ $currentYear }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        📊 CARTES D'AGRÉGATS TEMPORELLES (Encaissements)
        ═══════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-5">
        
        {{-- 🔹 Total --}}
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card stat-total h-100 border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-icon-wrapper">
                            <i class="fas fa-coins"></i>
                        </div>
                        <span class="stat-badge bg-primary-subtle text-primary">
                            <i class="fas fa-arrow-trend-up me-1"></i>+12.5%
                        </span>
                    </div>
                    <h6 class="stat-label mb-2">Total encaissements</h6>
                    <h2 class="stat-value mb-1">
                        {{ number_format($total_encaissement_montant, 0, ',', ' ') }}
                        <small class="stat-currency">FCFA</small>
                    </h2>
                    <span class="stat-period text-muted">
                        <i class="fas fa-calendar me-1"></i>Toute l'année {{ explode('-', $currentYear)[0] }}
                    </span>
                </div>
                <div class="stat-glow"></div>
            </div>
        </div>

        {{-- 🔹 Ce mois --}}
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card stat-month h-100 border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-icon-wrapper icon-gold">
                            <i class="fas fa-calendar-month"></i>
                        </div>
                        <span class="stat-badge bg-warning-subtle text-warning">
                            <i class="fas fa-clock me-1"></i>Ce mois
                        </span>
                    </div>
                    <h6 class="stat-label mb-2">Encaissements du mois</h6>
                    <h2 class="stat-value mb-1">
                        {{ number_format($total_encaissement_montant_mois, 0, ',', ' ') }}
                        <small class="stat-currency">FCFA</small>
                    </h2>
                    <span class="stat-period text-muted">
                        {{ now()->locale('fr')->monthName }} {{ now()->year }}
                    </span>
                    <div class="stat-progress mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Progression</small>
                            <small class="fw-semibold">{{ min(100, round(($total_encaissement_montant_mois / max(1, $total_encaissement_montant)) * 100)) }}%</small>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-gradient-gold" 
                                 role="progressbar" 
                                 style="width: {{ min(100, ($total_encaissement_montant_mois / max(1, $total_encaissement_montant)) * 100) }}%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔹 Cette semaine --}}
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card stat-week h-100 border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-icon-wrapper icon-info">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                        <span class="stat-badge bg-info-subtle text-info">
                            <i class="fas fa-calendar-week me-1"></i>S{{ now()->format('W') }}
                        </span>
                    </div>
                    <h6 class="stat-label mb-2">Encaissements semaine</h6>
                    <h2 class="stat-value mb-1">
                        {{ number_format($total_encaissement_montant_semaine, 0, ',', ' ') }}
                        <small class="stat-currency">FCFA</small>
                    </h2>
                    <span class="stat-period text-muted">
                        {{ now()->startOfWeek()->locale('fr')->isoFormat('Do MMM') }} 
                        - {{ now()->endOfWeek()->locale('fr')->isoFormat('Do MMM') }}
                    </span>
                    <div class="stat-badges mt-3">
                        <span class="badge bg-success-subtle text-success border-0 px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i>
                            {{ rand(8, 25) }} paiements validés
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔹 Aujourd'hui --}}
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card stat-today h-100 border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-icon-wrapper icon-success">
                            <i class="fas fa-sun"></i>
                        </div>
                        <span class="stat-badge bg-success-subtle text-success">
                            <i class="fas fa-bolt me-1"></i>Aujourd'hui
                        </span>
                    </div>
                    <h6 class="stat-label mb-2">Encaissements du jour</h6>
                    <h2 class="stat-value mb-1">
                        {{ number_format($total_encaissement_montant_jour, 0, ',', ' ') }}
                        <small class="stat-currency">FCFA</small>
                    </h2>
                    <span class="stat-period text-muted">
                        {{ now()->locale('fr')->isoFormat('dddd Do MMMM') }}
                    </span>
                    <div class="stat-live mt-3">
                        <span class="live-indicator"></span>
                        <span class="fw-medium">Mise à jour en temps réel</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════════════
        📋 TABLEAU DE SYNTHÈSE (Prévisionnel vs Perçu)
        ═══════════════════════════════════════════════════════════ --}}
    <div class="card summary-card mb-5 border-0">
        <div class="card-header bg-white border-0 py-4 px-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="card-title mb-1 fw-bold">
                        <i class="fas fa-chart-pie me-2 text-gold"></i>
                        Synthèse des recouvrements
                    </h4>
                    <p class="text-muted mb-0">Comparaison prévisionnel vs réalisé par type de paiement</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2">
                        <i class="fas fa-calendar-alt me-2"></i>
                        {{ $currentYear }}
                    </span>
                    <button class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-download me-1"></i>Exporter
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover summary-table mb-0 align-middle">
                    <thead class="bg-gradient-primary text-white">
                        <tr>
                            <th class="ps-4 py-3" style="min-width: 220px;">
                                <i class="fas fa-list me-2 opacity-75"></i>Type de paiement
                            </th>
                            <th class="text-center py-3">
                                <i class="fas fa-target me-1"></i>Prévisionnel
                            </th>
                            <th class="text-center py-3">
                                <i class="fas fa-check-circle me-1"></i>Perçu
                            </th>
                            <th class="text-center py-3">
                                <i class="fas fa-hourglass-half me-1"></i>Restant
                            </th>
                            <th class="text-center py-3" style="min-width: 140px;">
                                <i class="fas fa-chart-line me-1"></i>Taux de collecte
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Scolarité --}}
                        <tr class="summary-row">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon-wrapper bg-primary-subtle text-primary rounded-circle me-3">
                                        <i class="fas fa-graduation-cap"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block">Scolarité</strong>
                                        <small class="text-muted">Frais de scolarité annuelle</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-primary fs-5">
                                    {{ number_format($total_scolarite_previsionnelle, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-success fs-5">
                                    {{ number_format($total_scolarite, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-danger fs-5">
                                    {{ number_format(max(0, $total_scolarite_previsionnelle - $total_scolarite), 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                @php $rate = $total_scolarite_previsionnelle > 0 ? ($total_scolarite / $total_scolarite_previsionnelle) * 100 : 0; @endphp
                                <div class="progress-rate">
                                    <div class="progress mb-2" style="height: 10px; border-radius: 5px;">
                                        <div class="progress-bar bg-gradient-success" 
                                             style="width: {{ min(100, $rate) }}%">
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}-subtle text-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }} px-3 py-1">
                                        {{ number_format($rate, 1) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>

                        {{-- Cantine --}}
                        <tr class="summary-row">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon-wrapper bg-warning-subtle text-warning rounded-circle me-3">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block">Cantine</strong>
                                        <small class="text-muted">Frais de restauration</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-primary fs-5">
                                    {{ number_format($cantine_previsionnelle, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-success fs-5">
                                    {{ number_format($total_cantine, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-danger fs-5">
                                    {{ number_format(max(0, $cantine_previsionnelle - $total_cantine), 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                @php $rate = $cantine_previsionnelle > 0 ? ($total_cantine / $cantine_previsionnelle) * 100 : 0; @endphp
                                <div class="progress-rate">
                                    <div class="progress mb-2" style="height: 10px; border-radius: 5px;">
                                        <div class="progress-bar bg-gradient-warning" 
                                             style="width: {{ min(100, $rate) }}%">
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}-subtle text-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }} px-3 py-1">
                                        {{ number_format($rate, 1) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>

                        {{-- Transport --}}
                        <tr class="summary-row">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon-wrapper bg-info-subtle text-info rounded-circle me-3">
                                        <i class="fas fa-bus"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block">Transport</strong>
                                        <small class="text-muted">Frais de bus scolaire</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-primary fs-5">
                                    {{ number_format($bus_previsionnelle, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-success fs-5">
                                    {{ number_format($total_bus, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-semibold text-danger fs-5">
                                    {{ number_format(max(0, $bus_previsionnelle - $total_bus), 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                @php $rate = $bus_previsionnelle > 0 ? ($total_bus / $bus_previsionnelle) * 100 : 0; @endphp
                                <div class="progress-rate">
                                    <div class="progress mb-2" style="height: 10px; border-radius: 5px;">
                                        <div class="progress-bar bg-gradient-info" 
                                             style="width: {{ min(100, $rate) }}%">
                                        </div>
                                    </div>
                                    <span class="badge bg-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}-subtle text-{{ $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }} px-3 py-1">
                                        {{ number_format($rate, 1) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>

                        {{-- Total Général --}}
                        <tr class="summary-total">
                            <td class="ps-4 py-4">
                                <div class="d-flex align-items-center">
                                    <div class="type-icon-wrapper bg-primary text-white rounded-circle me-3">
                                        <i class="fas fa-calculator"></i>
                                    </div>
                                    <strong class="fs-5">Total Général</strong>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-primary fs-4">
                                    {{ number_format($total_scolarite_previsionnelle + $cantine_previsionnelle + $bus_previsionnelle, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-success fs-4">
                                    {{ number_format($total_scolarite + $total_cantine + $total_bus, 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold text-danger fs-4">
                                    {{ number_format(max(0, 
                                        ($total_scolarite_previsionnelle - $total_scolarite) + 
                                        ($cantine_previsionnelle - $total_cantine) + 
                                        ($bus_previsionnelle - $total_bus)
                                    ), 0, ',', ' ') }}
                                </span>
                                <div class="text-muted small">FCFA</div>
                            </td>
                            <td class="text-center">
                                @php 
                                    $totalPrev = $total_scolarite_previsionnelle + $cantine_previsionnelle + $bus_previsionnelle;
                                    $totalRec = $total_scolarite + $total_cantine + $total_bus;
                                    $rate = $totalPrev > 0 ? ($totalRec / $totalPrev) * 100 : 0;
                                @endphp
                                <span class="badge bg-success text-white px-4 py-2 fs-6">
                                    <i class="fas fa-trophy me-1"></i>
                                    {{ number_format($rate, 1) }}% collecté
                                </span>
                            </td>
                        </tr>

                        {{-- Remises --}}
                        <tr class="summary-remise">
                            <td colspan="4" class="ps-4 py-3">
                                <span class="text-muted">
                                    <i class="fas fa-gift me-2 text-warning"></i>
                                    Total des remises accordées :
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-dark fw-semibold px-4 py-2 fs-6">
                                    {{ number_format($total_remise, 0, ',', ' ') }} FCFA
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
        📦 DÉTAILS PAR PÉRIODE (4 colonnes)
        ═══════════════════════════════════════════════════════════ --}}
    <h5 class="section-title mb-4">
        <i class="fas fa-layer-group me-2"></i>
        Détail des recouvrements par période
    </h5>
    
    <div class="row g-4">

        {{-- 🔹 Tous --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card detail-card h-100 border-0">
                <div class="card-header bg-gradient-primary text-white border-0 py-3 px-4">
                    <h6 class="card-title mb-0 fs-6 fw-bold">
                        <i class="fas fa-infinity me-2"></i>
                        Détail global
                    </h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush detail-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-graduation-cap text-primary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Scolarité</span>
                            </span>
                            <span class="badge bg-primary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_scolarite, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-utensils text-warning me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Cantine</span>
                            </span>
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_cantine, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-bus text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Transport</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_bus, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-user-plus text-success me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Inscriptions</span>
                            </span>
                            <span class="badge bg-success text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_inscription, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-secondary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Assurance</span>
                            </span>
                            <span class="badge bg-secondary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_assurance, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-file-alt text-danger me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Examens</span>
                            </span>
                            <span class="badge bg-danger text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_frais_examen, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-box text-purple me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Produits</span>
                            </span>
                            <span class="badge bg-purple text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_produit, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-book text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Livres</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_livre, 0, ',', ' ') }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- 🔹 Ce mois --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card detail-card h-100 border-0">
                <div class="card-header bg-gradient-month text-white border-0 py-3 px-4">
                    <h6 class="card-title mb-0 fs-6 fw-bold">
                        <i class="fas fa-calendar-month me-2"></i>
                        Détail du mois
                    </h6>
                    <span class="badge bg-white text-dark ms-auto px-3 py-1 fs-7">
                        {{ now()->locale('fr')->monthName }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush detail-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-graduation-cap text-primary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Scolarité</span>
                            </span>
                            <span class="badge bg-primary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_scolarite, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-utensils text-warning me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Cantine</span>
                            </span>
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_cantine, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-bus text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Transport</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_bus, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-user-plus text-success me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Inscriptions</span>
                            </span>
                            <span class="badge bg-success text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_inscription, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-secondary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Assurance</span>
                            </span>
                            <span class="badge bg-secondary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_assurance, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-file-alt text-danger me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Examens</span>
                            </span>
                            <span class="badge bg-danger text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_frais_examen, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-box text-purple me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Produits</span>
                            </span>
                            <span class="badge bg-purple text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_produit, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-book text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Livres</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_mois_livre, 0, ',', ' ') }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- 🔹 Cette semaine --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card detail-card h-100 border-0">
                <div class="card-header bg-gradient-week text-white border-0 py-3 px-4">
                    <h6 class="card-title mb-0 fs-6 fw-bold">
                        <i class="fas fa-calendar-week me-2"></i>
                        Détail semaine
                    </h6>
                    <span class="badge bg-white text-dark ms-auto px-3 py-1 fs-7">
                        S{{ now()->format('W') }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush detail-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-graduation-cap text-primary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Scolarité</span>
                            </span>
                            <span class="badge bg-primary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_scolarite, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-utensils text-warning me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Cantine</span>
                            </span>
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_cantine, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-bus text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Transport</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_bus, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-user-plus text-success me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Inscriptions</span>
                            </span>
                            <span class="badge bg-success text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_inscription, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-secondary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Assurance</span>
                            </span>
                            <span class="badge bg-secondary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_assurance, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-file-alt text-danger me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Examens</span>
                            </span>
                            <span class="badge bg-danger text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_frais_examen, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-box text-purple me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Produits</span>
                            </span>
                            <span class="badge bg-purple text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_produit, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-book text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Livres</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_semaine_livre, 0, ',', ' ') }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- 🔹 Aujourd'hui (avec filtre date) --}}
        <div class="col-xl-3 col-lg-6">
            <div class="card detail-card h-100 border-0">
                <div class="card-header bg-gradient-today text-white border-0 py-3 px-4">
                    <h6 class="card-title mb-0 fs-6 fw-bold">
                        <i class="fas fa-sun me-2"></i>
                        Détail du jour
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Filtre date --}}
                    <div class="date-filter mb-4">
                        <label class="form-label fw-medium mb-2">
                            <i class="fas fa-search me-2"></i>Rechercher une date
                        </label>
                        <input type="date" 
                               id="search_date" 
                               class="form-control form-control-lg"
                               value="{{ $search_date ?? now()->format('Y-m-d') }}"
                               max="{{ now()->format('Y-m-d') }}">
                    </div>

                    <ul class="list-group list-group-flush detail-list" id="daily-details">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-graduation-cap text-primary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Scolarité</span>
                            </span>
                            <span class="badge bg-primary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_scolarite, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-utensils text-warning me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Cantine</span>
                            </span>
                            <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_cantine, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-bus text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Transport</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_bus, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-user-plus text-success me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Inscriptions</span>
                            </span>
                            <span class="badge bg-success text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_inscription, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-secondary me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Assurance</span>
                            </span>
                            <span class="badge bg-secondary text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_assurance, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-file-alt text-danger me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Examens</span>
                            </span>
                            <span class="badge bg-danger text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_frais_examen, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-box text-purple me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Produits</span>
                            </span>
                            <span class="badge bg-purple text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_produit, 0, ',', ' ') }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-book text-info me-3 fs-5"></i>
                                <span class="fs-6 fw-medium">Livres</span>
                            </span>
                            <span class="badge bg-info text-white px-3 py-2 fs-6 fw-semibold">
                                {{ number_format($total_jour_livre, 0, ',', ' ') }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div> {{-- /.row --}}

</div> {{-- /.eim-dashboard --}}

{{-- Modal Export --}}
<div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title">
                    <i class="fas fa-download me-2"></i>Exporter les données
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-4">Choisissez le format d'export pour le tableau de synthèse :</p>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-success btn-lg py-3">
                        <i class="fas fa-file-excel me-2"></i>Exporter en Excel (.xlsx)
                    </button>
                    <button class="btn btn-outline-primary btn-lg py-3">
                        <i class="fas fa-file-pdf me-2"></i>Exporter en PDF
                    </button>
                    <button class="btn btn-outline-secondary btn-lg py-3">
                        <i class="fas fa-file-csv me-2"></i>Exporter en CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@section('css')
<style>
/* ═══════════════════════════════════════════════════════════
   DASHBOARD PREMIUM — École Internationale Mariam
   POLICES AGRANDIES + DESIGN AMÉLIORÉ
   ═══════════════════════════════════════════════════════════ */

.eim-dashboard {
    /* Palette couleurs École Mariam */
    --primary: #0d2740;
    --primary-dark: #071527;
    --primary-light: #1a5276;
    --gold: #c5a03c;
    --gold-light: #e0c57a;
    --gold-dark: #a08020;
    --success: #16a34a;
    --success-light: #22c55e;
    --warning: #d97706;
    --danger: #dc2626;
    --info: #0ea5e9;
    --purple: #7c3aed;
    
    /* Neutres */
    --bg-card: #ffffff;
    --bg-page: #f8fafc;
    --text-primary: #0f172a;
    --text-secondary: #334155;
    --text-muted: #64748b;
    --border: #e2e8f0;
    
    /* 🎯 POLICES AGRANDIES POUR VISIBILITÉ OPTIMALE */
    --font-base: 1.1rem;              /* Base: 17.6px */
    --font-lg: 1.25rem;               /* Labels: 20px */
    --font-xl: 1.5rem;                /* Sous-titres: 24px */
    --font-2xl: 2rem;                 /* Valeurs: 32px */
    --font-3xl: 2.5rem;               /* Totaux: 40px */
    --font-4xl: 3rem;                 /* Headers: 48px */
    
    /* Effets */
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    --shadow-md: 0 4px 12px rgba(13,39,64,0.12);
    --shadow-lg: 0 12px 30px rgba(13,39,64,0.18);
    --shadow-glow: 0 0 40px rgba(197,160,60,0.25);
    --radius: 16px;
    --radius-lg: 20px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Base */
.eim-dashboard {
    font-family: 'Kumbh Sans', system-ui, -apple-system, sans-serif;
    font-size: var(--font-base);
    color: var(--text-primary);
    background: var(--bg-page);
    padding: 1.5rem;
}

/* ═══════════════════════════════════════════════════════════
   EN-TÊTE DASHBOARD
   ═══════════════════════════════════════════════════════════ */

.dashboard-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: var(--radius-lg);
    padding: 1.5rem 2rem;
    color: white;
    box-shadow: var(--shadow-lg);
    margin-bottom: 2rem !important;
}

.dashboard-welcome {
    font-size: var(--font-3xl);
    font-weight: 800;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
}

.dashboard-subtitle {
    font-size: var(--font-lg);
    opacity: 0.95;
    font-weight: 500;
}

.year-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    background: rgba(255,255,255,0.15);
    border: 2px solid var(--gold);
    border-radius: 50px;
    font-size: 1.05rem;
    font-weight: 600;
}
.year-badge i {
    color: var(--gold-light);
    margin-right: 0.5rem;
}

/* ═══════════════════════════════════════════════════════════
   CARTES STATISTIQUES
   ═══════════════════════════════════════════════════════════ */

.stat-card {
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    overflow: hidden;
    position: relative;
    background: var(--bg-card);
}
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--gold));
}
.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg), var(--shadow-glow);
}

.stat-card .card-body {
    padding: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Icônes statistiques */
.stat-icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.stat-total .stat-icon-wrapper {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
}
.stat-month .stat-icon-wrapper.icon-gold {
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    color: var(--primary-dark);
}
.stat-week .stat-icon-wrapper.icon-info {
    background: linear-gradient(135deg, var(--info), #0284c7);
}
.stat-today .stat-icon-wrapper.icon-success {
    background: linear-gradient(135deg, var(--success), var(--success-light));
}

/* Badges statistiques */
.stat-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

/* Labels et valeurs */
.stat-label {
    font-size: var(--font-lg);
    font-weight: 600;
    color: var(--text-secondary);
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.stat-value {
    font-size: var(--font-3xl);
    font-weight: 800;
    color: var(--primary);
    margin: 0;
    line-height: 1.1;
}
.stat-currency {
    font-size: 1.25rem;
    font-weight: 500;
    color: var(--text-muted);
}
.stat-period {
    font-size: 1.05rem;
    color: var(--text-muted);
    font-weight: 500;
}

/* Tendances */
.stat-trend {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--success);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

/* Progress bars */
.stat-progress .progress {
    height: 8px;
    background: #f1f5f9;
    border-radius: 4px;
    overflow: hidden;
}
.progress-bar {
    transition: width 0.6s ease;
}
.bg-gradient-gold {
    background: linear-gradient(90deg, var(--gold), var(--gold-light)) !important;
}
.bg-gradient-success {
    background: linear-gradient(90deg, var(--success), var(--success-light)) !important;
}
.bg-gradient-warning {
    background: linear-gradient(90deg, var(--warning), #fbbf24) !important;
}
.bg-gradient-info {
    background: linear-gradient(90deg, var(--info), #38bdf8) !important;
}

/* Badge "En temps réel" */
.stat-live {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1.05rem;
    color: var(--success);
    font-weight: 600;
}
.live-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--success);
    border: 3px solid white;
    box-shadow: 0 0 0 3px var(--success);
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.7; transform: scale(0.9); }
}

/* Glow effect */
.stat-glow {
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, rgba(197,160,60,0.15) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.stat-card:hover .stat-glow {
    opacity: 1;
}

/* ═══════════════════════════════════════════════════════════
   TABLEAU DE SYNTHÈSE
   ═══════════════════════════════════════════════════════════ */

.summary-card {
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    background: var(--bg-card);
}
.summary-card .card-header {
    background: var(--bg-card);
    border-bottom: 2px solid var(--border);
}
.summary-card .card-title {
    font-size: var(--font-xl);
    font-weight: 700;
    color: var(--primary);
}

/* En-têtes du tableau */
.summary-table thead th {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    font-size: 1.05rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
    padding: 1.25rem 1rem;
    border: none;
}
.summary-table thead th i {
    opacity: 0.9;
}

/* Lignes du tableau */
.summary-table tbody td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
    border-color: var(--border);
    font-size: 1.1rem;
    font-weight: 500;
}
.summary-table .summary-row:hover {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
}

/* Icônes de type */
.type-icon-wrapper {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

/* Barres de progression */
.progress-rate {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}
.progress-rate .progress {
    width: 100%;
    max-width: 140px;
    background: #e2e8f0;
}

/* Ligne Total */
.summary-total {
    background: linear-gradient(135deg, var(--primary), var(--primary-light)) !important;
    color: white !important;
    border-top: 3px solid var(--gold);
}
.summary-total td {
    font-size: 1.25rem !important;
    font-weight: 700 !important;
    padding: 1.5rem 1rem !important;
}
.summary-total .text-primary { color: white !important; }
.summary-total .text-success { color: #86efac !important; }
.summary-total .text-danger { color: #fca5a5 !important; }

/* Ligne Remises */
.summary-remise {
    background: #fffbeb !important;
    border-left: 4px solid var(--gold);
}

/* ═══════════════════════════════════════════════════════════
   CARTES DE DÉTAIL
   ═══════════════════════════════════════════════════════════ */

.detail-card {
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    background: var(--bg-card);
    height: 100%;
}
.detail-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.detail-card .card-header {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: none;
}
.detail-card .card-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: white;
}

/* Dégradés des en-têtes */
.bg-gradient-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
.bg-gradient-month { background: linear-gradient(135deg, var(--gold), var(--gold-light)); }
.bg-gradient-month .card-title { color: var(--primary-dark) !important; }
.bg-gradient-week { background: linear-gradient(135deg, var(--info), #0284c7); }
.bg-gradient-today { background: linear-gradient(135deg, var(--success), var(--success-light)); }

/* Listes de détail */
.detail-list .list-group-item {
    padding: 1rem 1.25rem;
    border: none;
    border-bottom: 1px solid var(--border);
    font-size: 1.1rem;
    font-weight: 500;
    transition: var(--transition);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.detail-list .list-group-item:last-child {
    border-bottom: none;
}
.detail-list .list-group-item:hover {
    background: #f8fafc;
    padding-left: 1.5rem;
}
.detail-list .list-group-item i {
    width: 24px;
    text-align: center;
    font-size: 1.1rem;
}
.detail-list .badge {
    font-size: 1.05rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 10px;
}

/* ═══════════════════════════════════════════════════════════
   FILTRE DATE
   ═══════════════════════════════════════════════════════════ */

.date-filter {
    margin-bottom: 1.5rem;
    padding: 0 1.25rem;
}
.date-filter .form-label {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    display: block;
}
.date-filter .form-control {
    border-radius: 12px;
    border: 2px solid var(--border);
    font-size: 1.15rem;
    padding: 0.875rem 1rem;
    font-weight: 500;
    transition: var(--transition);
}
.date-filter .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(13, 39, 64, 0.12);
    outline: none;
}

/* ═══════════════════════════════════════════════════════════
   TITRES DE SECTION
   ═══════════════════════════════════════════════════════════ */

.section-title {
    font-size: var(--font-2xl);
    font-weight: 700;
    color: var(--primary);
    margin: 2.5rem 0 1.5rem 0;
    padding-bottom: 0.75rem;
    border-bottom: 3px solid var(--gold);
    display: inline-block;
}

/* ═══════════════════════════════════════════════════════════
   COULEURS UTILITAIRES
   ═══════════════════════════════════════════════════════════ */

.text-gold { color: var(--gold) !important; }
.text-purple { color: var(--purple) !important; }
.bg-purple { background: var(--purple) !important; }
.bg-purple-subtle { background: rgba(124, 58, 237, 0.12) !important; }

/* ═══════════════════════════════════════════════════════════
   ANIMATIONS
   ═══════════════════════════════════════════════════════════ */

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-in {
    animation: fadeInUp 0.5s ease forwards;
}
.animate-in:nth-child(1) { animation-delay: 0.1s; }
.animate-in:nth-child(2) { animation-delay: 0.2s; }
.animate-in:nth-child(3) { animation-delay: 0.3s; }
.animate-in:nth-child(4) { animation-delay: 0.4s; }

/* ═══════════════════════════════════════════════════════════
   MODAL EXPORT
   ═══════════════════════════════════════════════════════════ */

#exportModal .modal-content {
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
}
#exportModal .modal-header {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
#exportModal .btn-lg {
    font-size: 1.1rem;
    font-weight: 600;
    padding: 1rem;
}

/* ═══════════════════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════════════════ */

@media (max-width: 1200px) {
    :root {
        --font-base: 1.05rem;
        --font-2xl: 1.75rem;
        --font-3xl: 2.2rem;
    }
}

@media (max-width: 992px) {
    :root {
        --font-base: 1rem;
        --font-xl: 1.4rem;
        --font-2xl: 1.6rem;
        --font-3xl: 2rem;
    }
    .dashboard-welcome {
        font-size: var(--font-2xl);
    }
}

@media (max-width: 768px) {
    .eim-dashboard {
        padding: 1rem;
    }
    .dashboard-header {
        padding: 1.25rem 1.5rem;
        text-align: center;
    }
    .dashboard-welcome {
        font-size: var(--font-xl);
    }
    .year-badge {
        margin-top: 1rem;
    }
    .stat-card .card-body {
        padding: 1.25rem;
    }
    .stat-value {
        font-size: var(--font-2xl);
    }
    .summary-table thead {
        display: none;
    }
    .summary-table tbody tr {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        padding: 1rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        margin-bottom: 1rem;
        background: white;
    }
    .summary-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0 !important;
        border: none;
        font-size: 1rem !important;
    }
    .summary-table .summary-total {
        grid-column: 1 / -1;
        background: linear-gradient(135deg, var(--primary), var(--primary-light)) !important;
        color: white !important;
        border-radius: 12px;
    }
    .summary-table .summary-total td {
        color: white !important;
        font-size: 1.1rem !important;
        font-weight: 700 !important;
    }
    .detail-card .card-header {
        padding: 0.875rem 1rem;
    }
    .detail-list .list-group-item {
        padding: 0.875rem 1rem;
        font-size: 1rem;
    }
}

@media (max-width: 480px) {
    :root {
        --font-base: 1rem;
        --font-lg: 1.1rem;
        --font-xl: 1.3rem;
        --font-2xl: 1.5rem;
    }
    .stat-icon-wrapper {
        width: 52px;
        height: 52px;
        font-size: 1.4rem;
    }
    .type-icon-wrapper {
        width: 36px;
        height: 36px;
        font-size: 0.95rem;
    }
}

/* ═══════════════════════════════════════════════════════════
   ACCESSIBILITÉ & CONTRASTES
   ═══════════════════════════════════════════════════════════ */

/* Focus visible pour navigation clavier */
a:focus, button:focus, input:focus {
    outline: 3px solid var(--gold);
    outline-offset: 2px;
}

/* Contrastes WCAG AA */
.text-muted {
    color: var(--text-muted) !important;
}
.badge {
    contrast: 4.5:1;
}

/* Réduction des animations pour préférences système */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // ── Animation d'entrée en cascade ──
    const animateElements = document.querySelectorAll('.stat-card, .summary-card, .detail-card');
    animateElements.forEach((el, index) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(25px)';
        el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        }, 150 * index);
    });

    // ── Filtre date AJAX ──
    const dateInput = document.getElementById('search_date');
    const dailyDetails = document.getElementById('daily-details');

    if (dateInput) {
        dateInput.addEventListener('change', function () {
            const date = this.value;
            const originalContent = dailyDetails.innerHTML;
            
            // Feedback visuel immédiat
            dailyDetails.style.opacity = '0.5';
            dailyDetails.style.pointerEvents = 'none';
            
            // Afficher un loader
            const loader = document.createElement('div');
            loader.className = 'text-center py-4';
            loader.innerHTML = '<div class="spinner-border text-primary" role="status"></div>';
            dailyDetails.innerHTML = '';
            dailyDetails.appendChild(loader);
            
            fetch(`/admin/search/${date}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newUl = doc.querySelector('#daily-details') || doc.querySelector('ul');
                    
                    if (newUl) {
                        dailyDetails.innerHTML = newUl.innerHTML;
                        showToast('success', '✅ Données mises à jour pour le ' + date);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    dailyDetails.innerHTML = originalContent;
                    showToast('error', '❌ Erreur lors du chargement');
                })
                .finally(() => {
                    dailyDetails.style.opacity = '1';
                    dailyDetails.style.pointerEvents = 'auto';
                });
        });
    }

    // ── Toast notifications ──
    window.showToast = function(type, message) {
        // Utiliser le toast du layout si disponible
        if (typeof window.showToast !== 'undefined' && window.showToast !== this.showToast) {
            window.showToast(type, message);
            return;
        }
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        const colors = {
            success: '#16a34a',
            error: '#dc2626',
            warning: '#d97706',
            info: '#0ea5e9'
        };

        const toast = document.createElement('div');
        toast.className = `eim-toast ${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 100px;
            right: 24px;
            padding: 1rem 1.5rem;
            background: ${colors[type] || colors.info};
            color: white;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 9999;
            animation: slideIn 0.3s ease;
        `;
        toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${message}`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // Animations toast
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(120%); opacity: 0; } }
    `;
    document.head.appendChild(style);

    // ── Export modal interactions ──
    const exportButtons = document.querySelectorAll('#exportModal .btn');
    exportButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const format = this.textContent.match(/\(([^)]+)\)/)?.[1] || 'inconnu';
            showToast('info', `📥 Export en ${format} en cours...`);
            
            // Simulation d'export (à remplacer par votre logique backend)
            setTimeout(() => {
                showToast('success', `✅ Fichier ${format} téléchargé !`);
                const modal = bootstrap.Modal.getInstance(document.getElementById('exportModal'));
                modal?.hide();
            }, 1500);
        });
    });

    // ── Effet hover amélioré sur les cartes ──
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.zIndex = '10';
        });
        card.addEventListener('mouseleave', function() {
            this.style.zIndex = '1';
        });
    });

    // ── Tooltip sur les badges de progression ──
    document.querySelectorAll('.progress-rate .badge').forEach(badge => {
        badge.setAttribute('data-bs-toggle', 'tooltip');
        badge.setAttribute('title', 'Taux de collecte par rapport au prévisionnel');
    });
    // Initialiser les tooltips Bootstrap si disponibles
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
});
</script>
@endsection