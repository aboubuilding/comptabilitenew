@extends('admin.layouts.app')

@section('title', 'Tableau de bord · École Mariam')

@section('page_title', 'Tableau de bord')
@section('page_icon', 'fa-chart-pie')

@section('breadcrumb')
    <li class="breadcrumb-item active">Accueil</li>
@endsection

@section('contenu')
    <style>
        .mariam-dashboard{
            font-family: 'Kumbh Sans', sans-serif;
            color: var(--school-ink);
            /* Jetons propres à cette page uniquement — school-red,
               school-red-dark, school-gold, school-ink et school-muted
               viennent déjà de layouts/app.blade.php et ne sont pas
               redéclarés ici, pour ne jamais risquer de diverger des
               valeurs établies ailleurs. */
            --school-red-light: #E14A54;
            --school-gold-light: #EFC876;
            --school-green: #2F7D5E;
            --school-green-dark: #1F5C44;
            --school-blue: #2b6cb0;
            --school-blue-dark: #1f4f85;
            --school-border: #F0DCC9;
        }

        /* ===== En-tête ===== */
        .dashboard-header{
            background: linear-gradient(120deg, var(--school-red-dark) 0%, var(--school-red) 55%, var(--school-red-light) 100%);
            border-radius: 18px;
            padding: 2.25rem 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 28px -12px rgba(122, 15, 34, 0.5);
        }
        .dashboard-header::before{
            content: "";
            position: absolute;
            top: -60%;
            right: -8%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(212,169,77,0.28) 0%, transparent 70%);
            pointer-events: none;
        }
        .dashboard-header::after{
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--school-gold) 0%, transparent 60%);
        }

        .dashboard-welcome{
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 1.85rem;
            margin-bottom: .35rem;
            letter-spacing: .2px;
        }
        .dashboard-welcome .fw-bold{ color: var(--school-gold-light); }
        .dashboard-subtitle{
            color: rgba(255,255,255,0.72);
            font-size: .95rem;
            text-transform: capitalize;
        }
        .badge-role{
            display: inline-flex;
            align-items: center;
            background: rgba(212,169,77,0.14);
            border: 1px solid rgba(212,169,77,0.45);
            color: var(--school-gold-light);
            font-weight: 600;
            font-size: .85rem;
            padding: .55rem 1.1rem;
            border-radius: 999px;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        /* ===== Cartes statistiques ===== */
        .stat-card{
            border-radius: 16px !important;
            background: #fff;
            position: relative;
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
            border: none !important;
            cursor: pointer;
        }
        .stat-card::before{
            content: "";
            position: absolute;
            top: 0; left: 0;
            width: 4px; height: 100%;
            background: var(--school-gold);
            opacity: 0;
            transition: opacity .25s ease;
        }
        .stat-card:hover{
            transform: translateY(-4px);
            box-shadow: 0 16px 30px -14px rgba(122,15,34,0.28) !important;
        }
        .stat-card:hover::before{ opacity: 1; }

        .stat-icon-wrapper{
            width: 52px; height: 52px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            color: #fff;
        }
        .bg-mariam-primary{ background: linear-gradient(135deg, var(--school-red), var(--school-red-dark)); }
        .bg-mariam-accent{ background: linear-gradient(135deg, var(--school-gold), #B7822B); }
        .bg-mariam-info{ background: linear-gradient(135deg, var(--school-blue), var(--school-blue-dark)); }
        .bg-mariam-green{ background: linear-gradient(135deg, var(--school-green), var(--school-green-dark)); }

        .stat-label{
            color: var(--school-muted);
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .6px;
            font-weight: 600;
            margin-top: 12px;
        }
        .stat-value{
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--school-red-dark);
            margin-bottom: .3rem;
            font-size: 2rem;
        }
        .stat-value.stat-value-sm{ font-size: 1.2rem; }

        /* ===== Carte activités ===== */
        .summary-card{ border-radius: 16px !important; border: none !important; }
        .summary-card .card-header{
            border-bottom: 1px solid var(--school-border) !important;
            background: #fff !important;
        }
        .card-title{
            font-family: 'Playfair Display', serif;
            color: var(--school-red-dark);
            font-size: 1.15rem;
        }
        .text-mariam-primary{ color: var(--school-red) !important; }
        .text-mariam-accent{ color: var(--school-gold) !important; }

        .summary-table thead{
            background: linear-gradient(120deg, var(--school-red-dark), var(--school-red)) !important;
        }
        .summary-table thead th{
            border: none;
            font-weight: 600;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: .9rem 1.25rem;
            color: #fff;
        }
        .summary-table tbody td{
            padding: .9rem 1.25rem;
            border-color: var(--school-border);
            font-size: .92rem;
        }
        .summary-table tbody tr{
            transition: background .2s ease;
        }
        .summary-table tbody tr:hover{
            background: rgba(212,169,77,0.06);
        }
        .summary-table tbody td:last-child{
            color: var(--school-muted);
            font-size: .82rem;
            white-space: nowrap;
        }

        .empty-state{
            text-align: center;
            padding: 3rem 1rem;
            color: var(--school-muted);
        }
        .empty-state i{
            font-size: 2.5rem;
            color: var(--school-gold);
            margin-bottom: .75rem;
            display: block;
        }

        /* ===== Section "Actions rapides" ===== */
        .section-heading{
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .9rem;
        }
        .section-heading h4{ margin: 0; }

        /* ===== Widgets rapides ===== */
        .quick-action{
            transition: all 0.3s ease;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            border: 1px solid var(--school-border);
            text-decoration: none;
            color: var(--school-ink);
            display: block;
            text-align: center;
        }
        .quick-action:hover{
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(122,15,34,0.12);
            border-color: var(--school-gold);
        }
        .quick-action i{
            font-size: 2rem;
            color: var(--school-red);
            margin-bottom: 8px;
            display: block;
            transition: transform 0.25s ease;
        }
        .quick-action:hover i{ transform: scale(1.15); }
        .quick-action span{
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* ===== Animation d'entrée — un seul geste, au chargement ===== */
        @media (prefers-reduced-motion: no-preference){
            .animate-on-load{ animation: eimFadeUp .5s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(1) .stat-card{ animation: eimFadeUp .45s .05s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(2) .stat-card{ animation: eimFadeUp .45s .12s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(3) .stat-card{ animation: eimFadeUp .45s .19s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(4) .stat-card{ animation: eimFadeUp .45s .26s ease both; }
            .quick-action{ animation: eimFadeUp .4s ease both; }
            .row.g-3.mb-4 .col-md-3:nth-child(1) .quick-action{ animation-delay: .08s; }
            .row.g-3.mb-4 .col-md-3:nth-child(2) .quick-action{ animation-delay: .14s; }
            .row.g-3.mb-4 .col-md-3:nth-child(3) .quick-action{ animation-delay: .20s; }
            .row.g-3.mb-4 .col-md-3:nth-child(4) .quick-action{ animation-delay: .26s; }
        }
        @keyframes eimFadeUp{
            from{ opacity: 0; transform: translateY(10px); }
            to{ opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 767px){
            .dashboard-header{ padding: 1.5rem; text-align: center; }
            .badge-role{ margin-top: .5rem; }
        }
    </style>

    <div class="mariam-dashboard">
        {{-- En-tête --}}
        <div class="dashboard-header mb-4 animate-on-load">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="dashboard-welcome">
                        {{ now()->hour < 18 ? 'Bonjour' : 'Bonsoir' }}, <span class="fw-bold">{{ $nomComplet ?? 'Utilisateur' }}</span>
                    </h2>
                    <p class="dashboard-subtitle mb-0">
                        <i class="fas fa-calendar-day me-2" aria-hidden="true"></i>{{ now()->locale('fr')->isoFormat('LLLL') }}
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex flex-wrap justify-content-md-end gap-2">
                        <div class="badge-role">
                            <i class="fas fa-user-shield me-2" aria-hidden="true"></i>{{ $roleLabel ?? 'Rôle' }}
                        </div>
                        @if($anneeActive)
                            <div class="badge-role">
                                <i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>{{ $anneeActive->libelle }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Cartes statistiques — recentrées sur le périmètre Administration --}}
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-primary"><i class="fas fa-users" aria-hidden="true"></i></div>
                        <h6 class="stat-label">Utilisateurs</h6>
                        <h2 class="stat-value">{{ $stats['total_users'] ?? 0 }}</h2>
                        <span class="text-muted small">Dont <strong>{{ $stats['users_actifs'] ?? 0 }}</strong> actifs</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-accent"><i class="fas fa-user-tie" aria-hidden="true"></i></div>
                        <h6 class="stat-label">Rôles configurés</h6>
                        <h2 class="stat-value">{{ $stats['total_roles'] ?? 0 }}</h2>
                        <span class="text-muted small">{{ $stats['role_admin'] ?? 0 }} admin · {{ $stats['role_directeur'] ?? 0 }} direction</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-info"><i class="fas fa-calendar-alt" aria-hidden="true"></i></div>
                        <h6 class="stat-label">Année scolaire active</h6>
                        <h2 class="stat-value stat-value-sm">{{ $anneeActive->libelle ?? 'Non définie' }}</h2>
                        <span class="text-muted small">
                            @if($anneeActive)
                                <i class="fas fa-circle text-success me-1" style="font-size: 8px;" aria-hidden="true"></i>Inscriptions ouvertes
                            @else
                                <i class="fas fa-exclamation-triangle text-warning me-1" aria-hidden="true"></i>À paramétrer
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-green"><i class="fas fa-history" aria-hidden="true"></i></div>
                        <h6 class="stat-label">Journal d'activités</h6>
                        <h2 class="stat-value">{{ $stats['activites_7j'] ?? count($recentActivities ?? []) }}</h2>
                        <span class="text-muted small">Actions journalisées (7 derniers jours)</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions rapides — les gestes courants du module Administration --}}
        <div class="section-heading">
            <h4 class="card-title fw-bold">
                <i class="fas fa-bolt me-2 text-mariam-accent" aria-hidden="true"></i>Actions rapides
            </h4>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <a href="{{ url('/users') }}" class="quick-action">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    <span>Nouvel utilisateur</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="{{ url('/annees') }}" class="quick-action">
                    <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                    <span>Nouvelle année scolaire</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="{{ url('/communications') }}" class="quick-action">
                    <i class="fas fa-paper-plane" aria-hidden="true"></i>
                    <span>Nouvelle communication</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="#" class="quick-action">
                    <i class="fas fa-history" aria-hidden="true"></i>
                    <span>Journal d'activités</span>
                </a>
            </div>
        </div>

        {{-- Dernières activités --}}
        <div class="card summary-card mb-5 shadow-sm animate-on-load">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0 fw-bold">
                    <i class="fas fa-list-alt me-2 text-mariam-primary" aria-hidden="true"></i>Dernières activités système
                </h4>
                <a href="#" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-eye me-1" aria-hidden="true"></i>Voir tout
                </a>
            </div>
            <div class="card-body p-0">
                @if(count($recentActivities ?? []) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover summary-table mb-0 align-middle">
                            <thead class="text-white">
                            <tr>
                                <th><i class="fas fa-tasks me-2" aria-hidden="true"></i>Action</th>
                                <th><i class="fas fa-user me-2" aria-hidden="true"></i>Utilisateur</th>
                                <th><i class="fas fa-clock me-2" aria-hidden="true"></i>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recentActivities as $activity)
                                <tr>
                                    <td>
                                        <i class="fas fa-circle text-mariam-accent me-2" style="font-size: 8px;" aria-hidden="true"></i>
                                        {{ $activity['action'] ?? 'Action système' }}
                                    </td>
                                    <td>{{ $activity['user'] ?? $nomComplet }}</td>
                                    <td>{{ $activity['date'] ?? now()->format('d/m/Y H:i') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="fas fa-inbox" aria-hidden="true"></i>
                        <p class="mb-0">Aucune activité récente pour le moment.</p>
                        <small class="text-muted">Les actions des utilisateurs apparaîtront ici.</small>
                    </div>
                @endif
            </div>
        </div>

        {{-- Informations système &amp; liens rapides du module Administration --}}
        <div class="row">
            <div class="col-md-6">
                <div class="card summary-card shadow-sm">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2 text-mariam-primary" aria-hidden="true"></i>Informations système
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width: 50%;">Version application</td>
                                <td><strong>v{{ config('app.version', '1.0.0') }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">PHP Version</td>
                                <td><strong>{{ phpversion() }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Environnement</td>
                                <td><strong>{{ app()->environment() }}</strong></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Base de données</td>
                                <td><strong>{{ config('database.default') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card summary-card shadow-sm">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-link me-2 text-mariam-primary" aria-hidden="true"></i>Module Administration
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="{{ url('/users') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-users-cog text-mariam-primary me-2" aria-hidden="true"></i>Utilisateurs &amp; Profils</span>
                                <i class="fas fa-chevron-right text-muted" aria-hidden="true"></i>
                            </a>
                            <a href="{{ url('/referentiels') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-sliders-h text-mariam-primary me-2" aria-hidden="true"></i>Référentiels &amp; Paramètres généraux</span>
                                <i class="fas fa-chevron-right text-muted" aria-hidden="true"></i>
                            </a>
                            <a href="{{ url('/communications') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-envelope text-mariam-primary me-2" aria-hidden="true"></i>Communications</span>
                                <i class="fas fa-chevron-right text-muted" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-history text-mariam-primary me-2" aria-hidden="true"></i>Journal &amp; Traçabilité</span>
                                <i class="fas fa-chevron-right text-muted" aria-hidden="true"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            // Note : les cartes/statistiques s'animent une seule fois au
            // chargement (voir @keyframes eimFadeUp ci-dessus) — pas de
            // ré-animation au scroll, pour rester sur un seul geste orchestré.
        });
    </script>
@endpush