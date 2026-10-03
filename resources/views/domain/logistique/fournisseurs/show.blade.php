@extends('admin.layouts.app')

@section('title', 'Fournisseur — ' . $fournisseur->raison_social)
@section('page_icon', 'fa-truck')
@section('page_title', 'Détail du fournisseur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.fournisseurs.index') }}">Fournisseurs</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($fournisseur->raison_social, 40) }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.fournisseurs.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid #ece4d6 !important; }
    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value a { color:var(--school-red); text-decoration:none; }
    .info-value a:hover { text-decoration:underline; }

    .stat-card { background:#fff; border:1px solid #ece4d6; border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    .table-historique thead tr { background:#faf6ee; }
    .table-historique thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid #ece4d6; }
    .table-historique tbody td { padding:.7rem .9rem; border-bottom:1px solid #ece4d6; font-size:.85rem; vertical-align:middle; }
    .table-historique tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .empty-state { padding:3rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2.5rem; margin-bottom:.6rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="row g-4">

    {{-- Fiche fournisseur --}}
    <div class="col-md-5">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Identité</h6>
            </div>
            <div class="card-body p-4">
                <div class="info-row">
                    <span class="info-label">Raison sociale</span>
                    <span class="info-value">{{ $fournisseur->raison_social }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact</span>
                    <span class="info-value">{{ $fournisseur->nom_contact ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Téléphone</span>
                    <span class="info-value">
                        @if ($fournisseur->telephone_contact)
                            <a href="tel:{{ $fournisseur->telephone_contact }}">{{ $fournisseur->telephone_contact }}</a>
                        @else — @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value">
                        @if ($fournisseur->email_contact)
                            <a href="mailto:{{ $fournisseur->email_contact }}">{{ $fournisseur->email_contact }}</a>
                        @else — @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Adresse</span>
                    <span class="info-value">{{ $fournisseur->adresse ?? '—' }}</span>
                </div>
                @if ($fournisseur->nif || $fournisseur->rccm)
                    <div class="info-row">
                        <span class="info-label">NIF</span>
                        <span class="info-value">{{ $fournisseur->nif ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">RCCM</span>
                        <span class="info-value">{{ $fournisseur->rccm ?? '—' }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Statistiques + historique --}}
    <div class="col-md-7">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $fournisseur->nombre_achats }}</div>
                        <div class="stat-label">Achats effectués</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ number_format($fournisseur->montant_total_achats, 0, ',', ' ') }}</div>
                        <div class="stat-label">Montant total achats</div>
                    </div>
                </div>
            </div>
        </div>

        @if (isset($fournisseur->achats))
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history me-1" style="color:var(--school-gold-dark);"></i> Historique des achats</h6>
                    <span class="text-xs text-muted">{{ $fournisseur->achats->count() }} achat(s)</span>
                </div>
                <div class="card-body p-0">
                    @if ($fournisseur->achats->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0">Aucun achat enregistré pour ce fournisseur.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-historique mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Référence</th>
                                        <th>Date</th>
                                        <th class="text-end">Montant</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($fournisseur->achats as $achat)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $achat->reference ?? ('#' . $achat->id) }}</td>
                                        <td class="text-sm text-muted">{{ optional($achat->date_achat)->format('d/m/Y') }}</td>
                                        <td class="num grand">{{ number_format((float) $achat->montant_total, 2, ',', ' ') }}</td>
                                        <td class="text-sm">
                                            @if (isset($achat->statut_livraison))
                                                <span class="compteur-badge" style="font-size:.72rem;">{{ $achat->statut_livraison }}</span>
                                            @else — @endif
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-light border-0 small" style="background:#f0f6ff;color:#1e3a8a;border-left:3px solid #2563eb;">
                <i class="fas fa-info-circle me-1"></i>
                Le module <strong>Achats</strong> n'est pas encore disponible. L'historique apparaîtra automatiquement
                ici dès qu'il sera livré.
            </div>
        @endif
    </div>
</div>
@endsection