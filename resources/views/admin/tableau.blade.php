@extends('admin.layouts.app')

@section('title', 'Tableau de bord · École Mariam')

@section('page_title', 'Tableau de bord')
@section('page_icon', 'fa-chart-pie')

@section('breadcrumb')
    <li class="breadcrumb-item active">Accueil</li>
@endsection

@section('contenu')
    <style>
        :root{
            --eim-red: #C21E2A;
            --eim-red-dark: #8F1620;
            --eim-red-light: #E14A54;
            --eim-gold: #D9A441;
            --eim-gold-light: #EFC876;
            --eim-cream: #FFF9F2;
            --eim-ink: #2B1416;
            --eim-muted: #7A6A6A;
            --eim-green: #2F7D5E;
            --eim-border: #F0DCC9;
        }

        .mariam-dashboard{
            font-family: 'Kumbh Sans', sans-serif;
            color: var(--eim-ink);
        }

        /* ===== En-tête ===== */
        .dashboard-header{
            background: linear-gradient(120deg, var(--eim-red-dark) 0%, var(--eim-red) 55%, var(--eim-red-light) 100%);
            border-radius: 18px;
            padding: 2.25rem 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 28px -12px rgba(140, 22, 32, 0.5);
        }
        .dashboard-header::before{
            content: "";
            position: absolute;
            top: -60%;
            right: -8%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(217,164,65,0.28) 0%, transparent 70%);
            pointer-events: none;
        }
        .dashboard-header::after{
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--eim-gold) 0%, transparent 60%);
        }

        .dashboard-welcome{
            font-family: 'Playfair Display', serif;
            color: #fff;
            font-size: 1.85rem;
            margin-bottom: .35rem;
            letter-spacing: .2px;
        }
        .dashboard-welcome .fw-bold{ color: var(--eim-gold-light); }
        .dashboard-subtitle{
            color: rgba(255,255,255,0.72);
            font-size: .95rem;
            text-transform: capitalize;
        }
        .badge-role{
            display: inline-flex;
            align-items: center;
            background: rgba(201,162,75,0.14);
            border: 1px solid rgba(201,162,75,0.45);
            color: var(--eim-gold-light);
            font-weight: 600;
            font-size: .85rem;
            padding: .55rem 1.1rem;
            border-radius: 999px;
            letter-spacing: .3px;
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
            background: var(--eim-gold);
            opacity: 0;
            transition: opacity .25s ease;
        }
        .stat-card:hover{
            transform: translateY(-4px);
            box-shadow: 0 16px 30px -14px rgba(140,22,32,0.28) !important;
        }
        .stat-card:hover::before{ opacity: 1; }

        .stat-icon-wrapper{
            width: 52px; height: 52px;
            border-radius: 13px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            color: #fff;
        }
        .bg-mariam-primary{ background: linear-gradient(135deg, var(--eim-red), var(--eim-red-dark)); }
        .bg-mariam-accent{ background: linear-gradient(135deg, var(--eim-gold), #B7822B); }
        .bg-mariam-blue{ background: linear-gradient(135deg, var(--eim-red-light), var(--eim-red)); }
        .bg-mariam-green{ background: linear-gradient(135deg, var(--eim-green), #1F5C44); }

        .stat-label{
            color: var(--eim-muted);
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .6px;
            font-weight: 600;
            margin-top: 12px;
        }
        .stat-value{
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--eim-red-dark);
            margin-bottom: .3rem;
            font-size: 2rem;
        }

        /* ===== Carte activités ===== */
        .summary-card{ border-radius: 16px !important; border: none !important; }
        .summary-card .card-header{
            border-bottom: 1px solid var(--eim-border) !important;
            background: #fff !important;
        }
        .card-title{
            font-family: 'Playfair Display', serif;
            color: var(--eim-red-dark);
            font-size: 1.15rem;
        }
        .text-mariam-primary{ color: var(--eim-red) !important; }
        .text-mariam-accent{ color: var(--eim-gold) !important; }

        .summary-table thead{
            background: linear-gradient(120deg, var(--eim-red-dark), var(--eim-red)) !important;
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
            border-color: var(--eim-border);
            font-size: .92rem;
        }
        .summary-table tbody tr{
            transition: background .2s ease;
        }
        .summary-table tbody tr:hover{
            background: rgba(201,162,75,0.06);
        }
        .summary-table tbody td:last-child{
            color: var(--eim-muted);
            font-size: .82rem;
            white-space: nowrap;
        }

        .empty-state{
            text-align: center;
            padding: 3rem 1rem;
            color: var(--eim-muted);
        }
        .empty-state i{
            font-size: 2.5rem;
            color: var(--eim-gold);
            margin-bottom: .75rem;
            display: block;
        }

        /* ===== Widgets rapides ===== */
        .quick-action{
            transition: all 0.3s ease;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            border: 1px solid var(--eim-border);
            text-decoration: none;
            color: var(--eim-ink);
            display: block;
            text-align: center;
        }
        .quick-action:hover{
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(140,22,32,0.12);
            border-color: var(--eim-gold);
        }
        .quick-action i{
            font-size: 2rem;
            color: var(--eim-red);
            margin-bottom: 8px;
            display: block;
        }
        .quick-action span{
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* ===== Animation d'entrée ===== */
        @media (prefers-reduced-motion: no-preference){
            .animate-on-load{
                animation: eimFadeUp .5s ease both;
            }
            .row.g-4.mb-5 .col-xl-3:nth-child(1) .stat-card{ animation: eimFadeUp .45s .05s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(2) .stat-card{ animation: eimFadeUp .45s .12s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(3) .stat-card{ animation: eimFadeUp .45s .19s ease both; }
            .row.g-4.mb-5 .col-xl-3:nth-child(4) .stat-card{ animation: eimFadeUp .45s .26s ease both; }
        }
        @keyframes eimFadeUp{
            from{ opacity: 0; transform: translateY(10px); }
            to{ opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 767px){
            .dashboard-header{ padding: 1.5rem; text-align: center; }
            .badge-role{ margin-top: 1rem; }
        }
    </style>

    <div class="mariam-dashboard">
        {{-- En-tête --}}
        <div class="dashboard-header mb-4 animate-on-load">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="dashboard-welcome">
                        👋 Bonjour, <span class="fw-bold">{{ $nomComplet ?? 'Utilisateur' }}</span>
                    </h2>
                    <p class="dashboard-subtitle mb-0">
                        <i class="fas fa-calendar-day me-2"></i> {{ now()->locale('fr')->isoFormat('LLLL') }}
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="badge-role">
                        <i class="fas fa-user-shield me-2"></i>
                        {{ $roleLabel ?? 'Rôle' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Cartes statistiques --}}
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-primary"><i class="fas fa-users"></i></div>
                        <h6 class="stat-label">Total utilisateurs</h6>
                        <h2 class="stat-value">{{ $stats['total_users'] ?? 0 }}</h2>
                        <span class="text-muted small">Dont <strong>{{ $stats['users_actifs'] ?? 0 }}</strong> actifs</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-accent"><i class="fas fa-user-tie"></i></div>
                        <h6 class="stat-label">Rôles</h6>
                        <h2 class="stat-value">{{ $stats['total_roles'] ?? 0 }}</h2>
                        <span class="text-muted small">{{ $stats['role_admin'] ?? 0 }} admin · {{ $stats['role_directeur'] ?? 0 }} directeur</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-blue"><i class="fas fa-clock"></i></div>
                        <h6 class="stat-label">Session</h6>
                        <h2 class="stat-value" style="font-size:1.2rem;">En ligne</h2>
                        <span class="text-muted small">
                        <i class="fas fa-circle text-success me-1" style="font-size: 8px;"></i>
                        Connecté depuis {{ $user->updated_at ? $user->updated_at->diffForHumans() : 'aujourd\'hui' }}
                    </span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="stat-icon-wrapper bg-mariam-green"><i class="fas fa-shield-alt"></i></div>
                        <h6 class="stat-label">Sécurité</h6>
                        <h2 class="stat-value" style="font-size:1.2rem;">SSL/TLS</h2>
                        <span class="text-muted small">
                        <i class="fas fa-lock text-success me-1"></i>
                        Connexion sécurisée
                    </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions rapides --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <a href="#" class="quick-action">
                    <i class="fas fa-user-plus"></i>
                    <span>Nouvel utilisateur</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="#" class="quick-action">
                    <i class="fas fa-file-invoice"></i>
                    <span>Nouvelle facture</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="#" class="quick-action">
                    <i class="fas fa-coins"></i>
                    <span>Enregistrer paiement</span>
                </a>
            </div>
            <div class="col-md-3 col-6">
                <a href="#" class="quick-action">
                    <i class="fas fa-chart-bar"></i>
                    <span>Rapports</span>
                </a>
            </div>
        </div>

        {{-- Dernières activités --}}
        <div class="card summary-card mb-5 shadow-sm animate-on-load">
            <div class="card-header bg-white border-0 py-4 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0 fw-bold">
                    <i class="fas fa-list-alt me-2 text-mariam-primary"></i>Dernières activités système
                </h4>
                <a href="#" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-eye me-1"></i> Voir tout
                </a>
            </div>
            <div class="card-body p-0">
                @if(count($recentActivities ?? []) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover summary-table mb-0 align-middle">
                            <thead class="text-white">
                            <tr>
                                <th><i class="fas fa-tasks me-2"></i>Action</th>
                                <th><i class="fas fa-user me-2"></i>Utilisateur</th>
                                <th><i class="fas fa-clock me-2"></i>Date</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($recentActivities as $activity)
                                <tr>
                                    <td>
                                        <i class="fas fa-circle text-mariam-accent me-2" style="font-size: 8px;"></i>
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
                        <i class="fas fa-inbox"></i>
                        <p class="mb-0">Aucune activité récente pour le moment.</p>
                        <small class="text-muted">Les actions des utilisateurs apparaîtront ici.</small>
                    </div>
                @endif
            </div>
        </div>

        {{-- Informations système --}}
        <div class="row">
            <div class="col-md-6">
                <div class="card summary-card shadow-sm">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-info-circle me-2 text-mariam-primary"></i>Informations système
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width: 50%;">Version application</td>
                                <td><strong>v1.0.0</strong></td>
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
                            <tr>
                                <td class="text-muted">Dernière mise à jour</td>
                                <td><strong>{{ now()->format('d/m/Y H:i:s') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card summary-card shadow-sm">
                    <div class="card-header bg-white border-0 py-3 px-4">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-link me-2 text-mariam-primary"></i>Liens rapides
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-users text-mariam-primary me-2"></i> Gestion des utilisateurs</span>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-file-invoice text-mariam-primary me-2"></i> Facturation</span>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-wallet text-mariam-primary me-2"></i> Trésorerie</span>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-cog text-mariam-primary me-2"></i> Paramètres</span>
                                <i class="fas fa-chevron-right text-muted"></i>
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
            // Animation des cartes au scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            document.querySelectorAll('.stat-card, .summary-card, .quick-action').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });

            // Effet de survol sur les actions rapides
            document.querySelectorAll('.quick-action').forEach(el => {
                el.addEventListener('mouseenter', function() {
                    this.querySelector('i').style.transform = 'scale(1.2)';
                    this.querySelector('i').style.transition = 'transform 0.3s ease';
                });
                el.addEventListener('mouseleave', function() {
                    this.querySelector('i').style.transform = 'scale(1)';
                });
            });

            // Actualiser les stats (optionnel)
            // setInterval(function() {
            //     location.reload();
            // }, 60000);
        });
    </script>
@endpush
