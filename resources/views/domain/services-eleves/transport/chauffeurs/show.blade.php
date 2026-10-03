@extends('admin.layouts.app')

@section('title', 'Chauffeur — ' . $chauffeur->nom_complet)
@section('page_icon', 'fa-user-tie')
@section('page_title', 'Fiche chauffeur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.chauffeurs.index') }}">Chauffeurs</a></li>
    <li class="breadcrumb-item active">{{ $chauffeur->nom_complet }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('services-eleves.transport.chauffeurs.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid #ece4d6 !important; }

    .chauffeur-banner {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(122,15,28,.18);
        margin-bottom:1.25rem;
    }
    .chauffeur-avatar-lg {
        width:72px; height:72px; border-radius:50%;
        background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.35);
        display:flex; align-items:center; justify-content:center;
        font-weight:800; font-size:1.5rem; letter-spacing:.5px;
        flex-shrink:0;
    }
    .chauffeur-banner h2 { margin:0; font-size:1.3rem; font-weight:700; }
    .chauffeur-banner .meta { font-size:.85rem; opacity:.9; margin-top:4px; }

    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }

    .table-affectations thead tr { background:#faf6ee; }
    .table-affectations thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid #ece4d6; }
    .table-affectations tbody td { padding:.7rem .9rem; border-bottom:1px solid #ece4d6; font-size:.85rem; vertical-align:middle; }
    .table-affectations tbody tr:hover { background:#fdfaf5; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.actif     { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.inactif   { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .empty-state { padding:2.5rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div>
    {{-- Bandeau --}}
    <div class="chauffeur-banner">
        <div class="chauffeur-avatar-lg">
            {{ strtoupper(substr($chauffeur->prenom ?? '', 0, 1)) }}{{ strtoupper(substr($chauffeur->nom ?? '', 0, 1)) }}
        </div>
        <div class="flex-grow-1">
            <h2>{{ $chauffeur->nom_complet }}</h2>
            <div class="meta">
                @if ($chauffeur->permis_conduire)
                    <i class="fas fa-id-card me-1"></i>{{ $chauffeur->permis_conduire }}
                    @if ($chauffeur->date_validite_permis)
                        · valide jusqu'au {{ $chauffeur->date_validite_permis->format('d/m/Y') }}
                    @endif
                @endif
            </div>
        </div>
        <div class="text-end">
            <span class="badge-statut {{ $chauffeur->isActif() ? 'actif' : 'inactif' }}" style="background:rgba(255,255,255,.2);color:#fff;">
                <span class="badge-dot"></span>{{ $chauffeur->isActif() ? 'Actif' : 'Inactif' }}
            </span>
        </div>
    </div>

    <div class="row g-3">
        {{-- Infos --}}
        <div class="col-lg-5">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Informations</h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Nom</span>
                        <span class="info-value">{{ $chauffeur->nom }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Prénom</span>
                        <span class="info-value">{{ $chauffeur->prenom }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Permis</span>
                        <span class="info-value">{{ $chauffeur->permis_conduire ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Validité permis</span>
                        <span class="info-value">
                            {{ $chauffeur->date_validite_permis?->format('d/m/Y') ?? '—' }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Téléphone</span>
                        <span class="info-value">{{ $chauffeur->telephone ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">{{ $chauffeur->email ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Adresse</span>
                        <span class="info-value">{{ $chauffeur->adresse ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Affectations --}}
        <div class="col-lg-7">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history me-1" style="color:var(--school-gold-dark);"></i> Affectations passées et en cours</h6>
                    <span class="text-xs text-muted">{{ $chauffeur->affectations->count() }} affectation(s)</span>
                </div>
                <div class="card-body p-0">
                    @if ($chauffeur->affectations->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-bus"></i>
                            <p class="mb-0">Aucune affectation enregistrée.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-affectations mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Véhicule</th>
                                        <th>Période</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($chauffeur->affectations as $a)
                                    <tr>
                                        <td class="ps-4">
                                            @if ($a->voiture)
                                                <a href="{{ route('services-eleves.transport.voitures.show', $a->voiture) }}" class="text-decoration-none">
                                                    <div class="fw-semibold">{{ $a->voiture->plaque }}</div>
                                                    <div class="text-xs text-muted">{{ $a->voiture->marque }} {{ $a->voiture->modele }}</div>
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-sm">
                                            {{ optional($a->date_debut)->format('d/m/Y') }}
                                            @if ($a->date_fin)
                                                → {{ $a->date_fin->format('d/m/Y') }}
                                            @else
                                                → en cours
                                            @endif
                                        </td>
                                        <td class="text-sm">{{ $a->type_affectation?->label() }}</td>
                                        <td>
                                            <span class="badge-statut {{ $a->isActive() ? 'actif' : 'inactif' }}">
                                                <span class="badge-dot"></span>{{ $a->isActive() ? 'Active' : 'Terminée' }}
                                            </span>
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