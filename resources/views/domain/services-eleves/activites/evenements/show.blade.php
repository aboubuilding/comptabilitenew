@extends('admin.layouts.app')

@section('title', 'Événement — ' . $evenement->nom)
@section('page_icon', 'fa-calendar-check')
@section('page_title', 'Détail de l\'événement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.activites-evenements.activites.index') }}">Activités</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.activites-evenements.evenements.index') }}">Événements</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($evenement->nom, 40) }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.activites-evenements.evenements.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    <a href="{{ route('services-eleves.activites-evenements.evenements.export', ['search' => $evenement->nom]) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
@endsection

@section('css')
<style>
    .show-page { --s-bg-soft:#faf6ee; --s-border:#ece4d6; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--s-border) !important; }

    .evt-banner {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(122,15,28,.18);
        margin-bottom:1.25rem;
    }
    .evt-banner-icon {
        width:64px; height:64px; border-radius:14px;
        background: rgba(255,255,255,.18);
        border:2px solid rgba(255,255,255,.35);
        display:flex; align-items:center; justify-content:center;
        font-size:1.7rem; flex-shrink:0;
    }
    .evt-banner h2 { margin:0; font-size:1.3rem; font-weight:700; }
    .evt-banner .meta { font-size:.85rem; opacity:.9; margin-top:4px; }

    .info-row { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }

    .stat-card { background:#fff; border:1px solid var(--s-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-icon.green { background:rgba(28,122,77,.10); color:#1c7a4d; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10); color:#2563eb; }
    .stat-icon.red   { background:rgba(200,30,58,.10); color:var(--school-red); }
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    .table-part thead tr { background:var(--s-bg-soft); }
    .table-part thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--s-border); }
    .table-part tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--s-border); font-size:.85rem; vertical-align:middle; }
    .table-part tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.pre-inscrit { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.confirme    { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule      { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:2rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="show-page">

    @php
        $conf = $evenement->nombre_confirmes;
        $pre  = $evenement->nombre_pre_inscrits;
        $taux = $evenement->taux_remplissage;
    @endphp

    {{-- Bandeau --}}
    <div class="evt-banner">
        <div class="evt-banner-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="flex-grow-1">
            <h2>{{ $evenement->nom }}</h2>
            <div class="meta">
                <i class="fas fa-tag me-1"></i>{{ $evenement->type?->label() }}
                &nbsp;·&nbsp;
                <i class="fas fa-calendar me-1"></i>{{ optional($evenement->date_evenement)->format('d/m/Y') }}
                @if ($evenement->isCapaciteLimitee())
                    &nbsp;·&nbsp;
                    <i class="fas fa-users me-1"></i>Capacité limitée ({{ $evenement->capacite }})
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="text-xs" style="opacity:.75;">Participation</div>
            <div style="font-size:1.4rem;font-weight:700;">
                {{ number_format((float) $evenement->participation, 0, ',', ' ') }} F
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $conf }}</div>
                    <div class="stat-label">Confirmés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $pre }}</div>
                    <div class="stat-label">Pré-inscrits en attente</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $evenement->capacite ?? '∞' }}</div>
                    <div class="stat-label">Capacité</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon {{ $taux >= 90 ? 'red' : 'green' }}">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($taux, 0, ',', ' ') }} %</div>
                    <div class="stat-label">Remplissage</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Infos --}}
        <div class="col-lg-5">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                        Informations
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Nom</span>
                        <span class="info-value">{{ $evenement->nom }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type</span>
                        <span class="info-value">{{ $evenement->type?->label() }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date</span>
                        <span class="info-value">
                            {{ optional($evenement->date_evenement)->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Participation</span>
                        <span class="info-value">
                            {{ number_format((float) $evenement->participation, 2, ',', ' ') }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Capacité limitée</span>
                        <span class="info-value">{{ $evenement->isCapaciteLimitee() ? 'Oui' : 'Non' }}</span>
                    </div>
                    @if ($evenement->isCapaciteLimitee())
                        <div class="info-row">
                            <span class="info-label">Places restantes</span>
                            <span class="info-value">{{ $evenement->places_restantes }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date limite</span>
                            <span class="info-value">
                                {{ optional($evenement->date_limite_confirmation)->format('d/m/Y') ?? '—' }}
                                @if ($evenement->isDateLimitePassee())
                                    <span class="badge-statut annule ms-1"
                                          style="background:#fdecea;color:var(--school-red);">
                                        Dépassée
                                    </span>
                                @endif
                            </span>
                        </div>
                    @endif
                    @if ($evenement->description)
                        <div class="mt-3 p-2 rounded"
                             style="background:#faf6ee;font-size:.85rem;color:#4a5560;">
                            <strong>Description :</strong> {{ $evenement->description }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Participations --}}
        <div class="col-lg-7">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-users me-1" style="color:var(--school-gold-dark);"></i>
                        Participations
                    </h6>
                    <span class="text-xs text-muted">
                        {{ $evenement->participations->count() }} participant(s)
                    </span>
                </div>
                <div class="card-body p-0">
                    @if ($evenement->participations->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <p class="mb-0">Aucune participation enregistrée.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-part mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Élève</th>
                                        <th>Classe</th>
                                        <th class="text-end">Montant</th>
                                        <th>Statut</th>
                                        <th>Pré-inscrit le</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($evenement->participations as $p)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">
                                                {{ $p->inscription?->eleve?->nom }}
                                                {{ $p->inscription?->eleve?->prenom }}
                                            </div>
                                            <div class="text-xs text-muted">
                                                {{ $p->inscription?->eleve?->matricule }}
                                            </div>
                                        </td>
                                        <td class="text-sm">
                                            {{ $p->inscription?->classe?->libelle ?? '—' }}
                                        </td>
                                        <td class="num">
                                            {{ number_format((float) $p->montant_facture, 0, ',', ' ') }}
                                        </td>
                                        <td>
                                            <span class="badge-statut {{ $p->statut?->css() }}">
                                                <span class="badge-dot"></span>{{ $p->statut?->label() }}
                                            </span>
                                        </td>
                                        <td class="text-sm text-muted">
                                            {{ optional($p->date_inscription)->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection