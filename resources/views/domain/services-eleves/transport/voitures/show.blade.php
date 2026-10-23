@extends('admin.layouts.app')

@section('title', 'Véhicule — ' . $voiture->plaque)
@section('page_icon', 'fa-bus')
@section('page_title', 'Fiche véhicule')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.transport.voitures.index') }}">Transport</a></li>
    <li class="breadcrumb-item active">{{ $voiture->plaque }}</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.transport.voitures.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    @can('transport.gerer')
        <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#affecterModal">
            <i class="fas fa-user-plus me-1"></i> Affecter un chauffeur
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .show-page { --sp-bg-soft:var(--eim-cream); --sp-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .vehicule-banner {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(142,18,22,.18);
        margin-bottom:1.25rem;
    }
    .vehicule-icon {
        width:72px; height:72px; border-radius:14px;
        background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.30);
        display:flex; align-items:center; justify-content:center;
        font-size:2rem; color:rgba(255,255,255,.85);
        flex-shrink:0;
    }
    .vehicule-banner h2 { margin:0; font-size:1.4rem; font-weight:700; font-family:monospace; letter-spacing:1px; }
    .vehicule-banner .meta { font-size:.85rem; opacity:.9; margin-top:4px; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.4em .9em; border-radius:999px; font-size:.75rem; font-weight:600; }
    .badge-statut.disponible  { background:rgba(255,255,255,.2); color:#fff; }
    .badge-statut.maintenance { background:rgba(255,255,255,.2); color:#fff; }
    .badge-statut.sorti       { background:rgba(255,255,255,.2); color:#fff; }
    .badge-statut.reforme     { background:rgba(255,255,255,.2); color:#fff; }

    .stat-card { background:#fff; border:1px solid var(--sp-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--sp-border) !important; padding:.9rem 1.25rem; }
    .main-card .card-header h6 { margin:0; font-size:.92rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .main-card .card-header i { color:var(--school-gold-dark); }

    .info-row { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }

    .table-detail thead tr { background:var(--sp-bg-soft); }
    .table-detail thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.65rem .9rem; border-bottom:1px solid var(--sp-border); }
    .table-detail tbody td { padding:.65rem .9rem; border-bottom:1px solid var(--sp-border); font-size:.85rem; vertical-align:middle; }
    .table-detail tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    /* Tabs */
    .nav-tabs-custom { border-bottom:1px solid var(--sp-border); }
    .nav-tabs-custom .nav-link { border:none; color:var(--school-muted); font-size:.88rem; font-weight:600; padding:.65rem 1rem; border-bottom:2px solid transparent; }
    .nav-tabs-custom .nav-link:hover { color:var(--school-ink); }
    .nav-tabs-custom .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }

    .empty-state { padding:2rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="show-page">

    @php
        $chauffeur = $voiture->chauffeurActuel();
        $assuranceActive = $voiture->assuranceActive();
        $totalCarburant = $voiture->cout_carburant;
        $totalEntretien = $voiture->cout_entretien;
    @endphp

    {{-- Bandeau --}}
    <div class="vehicule-banner">
        <div class="vehicule-icon"><i class="fas fa-bus"></i></div>
        <div class="flex-grow-1">
            <h2>{{ $voiture->plaque }}</h2>
            <div class="meta">
                <i class="fas fa-car me-1"></i>{{ $voiture->marque }} {{ $voiture->modele }}
                @if ($voiture->annee_fabrication)
                    · {{ $voiture->annee_fabrication }}
                @endif
                @if ($voiture->nombre_place)
                    · {{ $voiture->nombre_place }} places
                @endif
            </div>
        </div>
        <div class="text-end">
            <x-badge class="{{ $voiture->statut?->css() }}" point>{{ $voiture->statut?->label() }}
            </x-badge>
            <div style="font-size:1.1rem;font-weight:700;margin-top:.4rem;font-variant-numeric:tabular-nums;">
                {{ number_format((float) $voiture->kilometrage_actuel, 0, ',', ' ') }} km
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-gas-pump" couleur="gold" libelle="Coût carburant total">{{ number_format($totalCarburant, 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-tools" couleur="blue" libelle="Coût entretien total">{{ number_format($totalEntretien, 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-shield-alt" couleur="{{ $assuranceActive ? 'green' : 'red' }}" libelle="Assurance">@if ($assuranceActive)
                            {{ $assuranceActive->date_fin->format('d/m/Y') }}
                        @else
                            Expirée
                        @endif</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-user-tie" couleur="{{ $chauffeur ? 'green' : 'red' }}" libelle="Chauffeur actuel">{{ $chauffeur?->nom_complet ?? 'Aucun' }}</x-stat>
        </div>
    </div>

    <div class="row g-3">
        {{-- Infos générales --}}
        <div class="col-lg-4">
            <x-carte>
                <div class="card-header">
                    <h6><i class="fas fa-info-circle"></i> Informations générales</h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Marque</span>
                        <span class="info-value">{{ $voiture->marque }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Modèle</span>
                        <span class="info-value">{{ $voiture->modele ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Année fab.</span>
                        <span class="info-value">{{ $voiture->annee_fabrication ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Places</span>
                        <span class="info-value">{{ $voiture->nombre_place ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">N° châssis</span>
                        <span class="info-value" style="font-family:monospace;font-size:.8rem;">{{ $voiture->numero_chassis ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date achat</span>
                        <span class="info-value">{{ $voiture->date_achat?->format('d/m/Y') ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Prix achat</span>
                        <span class="info-value">{{ $voiture->prix_achat ? number_format((float) $voiture->prix_achat, 2, ',', ' ') : '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fournisseur</span>
                        <span class="info-value">{{ $voiture->fournisseur ?? '—' }}</span>
                    </div>
                </div>
            </x-carte>
        </div>

        {{-- Détails tabulés --}}
        <div class="col-lg-8">
            <x-carte>
                <div class="card-header p-0 border-bottom-0">
                    <ul class="nav nav-tabs nav-tabs-custom px-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-carburant" type="button">
                                <i class="fas fa-gas-pump me-1"></i> Carburant ({{ $voiture->carburants->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-entretien" type="button">
                                <i class="fas fa-tools me-1"></i> Entretiens ({{ $voiture->entretiens->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-assurances" type="button">
                                <i class="fas fa-shield-alt me-1"></i> Assurances ({{ $voiture->assurances->count() }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-affectations" type="button">
                                <i class="fas fa-user-tie me-1"></i> Affectations ({{ $voiture->affectations->count() }})
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content">

                        {{-- Carburant --}}
                        <div class="tab-pane fade show active" id="tab-carburant">
                            @if ($voiture->carburants->isEmpty())
                                <x-vide icone="fas fa-gas-pump">Aucun plein enregistré.</x-vide>
                            @else
                                <x-tableau class="table-detail mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Date</th>
                                                <th class="text-end">Litres</th>
                                                <th class="text-end">P.U.</th>
                                                <th class="text-end">Montant</th>
                                                <th>Kilométrage</th>
                                                <th>Station</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($voiture->carburants as $c)
                                            <tr>
                                                <td class="ps-4 text-sm">{{ optional($c->date_plein)->format('d/m/Y') }}</td>
                                                <td class="num">{{ number_format((float) $c->quantite_litres, 2, ',', ' ') }}</td>
                                                <td class="num">{{ number_format((float) $c->prix_unitaire, 2, ',', ' ') }}</td>
                                                <td class="num grand">{{ number_format((float) $c->montant_total, 2, ',', ' ') }}</td>
                                                <td class="text-sm">{{ number_format((float) $c->kilometrage, 0, ',', ' ') }} km</td>
                                                <td class="text-sm text-muted">{{ $c->station_service ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </x-tableau>
                            @endif
                        </div>

                        {{-- Entretiens --}}
                        <div class="tab-pane fade" id="tab-entretien">
                            @if ($voiture->entretiens->isEmpty())
                                <x-vide icone="fas fa-tools">Aucun entretien enregistré.</x-vide>
                            @else
                                <x-tableau class="table-detail mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Date</th>
                                                <th>Type</th>
                                                <th class="text-end">Coût</th>
                                                <th>Kilométrage</th>
                                                <th>Effectué par</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($voiture->entretiens as $e)
                                            <tr>
                                                <td class="ps-4 text-sm">{{ optional($e->date_entretien)->format('d/m/Y') }}</td>
                                                <td class="text-sm">{{ $e->type_entretien }}</td>
                                                <td class="num grand">{{ number_format((float) $e->cout, 2, ',', ' ') }}</td>
                                                <td class="text-sm">{{ number_format((float) $e->kilometrage, 0, ',', ' ') }} km</td>
                                                <td class="text-sm text-muted">{{ $e->chauffeur?->nom_complet ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </x-tableau>
                            @endif
                        </div>

                        {{-- Assurances --}}
                        <div class="tab-pane fade" id="tab-assurances">
                            @if ($voiture->assurances->isEmpty())
                                <x-vide icone="fas fa-shield-alt">Aucune assurance enregistrée.</x-vide>
                            @else
                                <x-tableau class="table-detail mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Compagnie</th>
                                                <th>N° contrat</th>
                                                <th>Type</th>
                                                <th>Période</th>
                                                <th class="text-end">Prime</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($voiture->assurances as $a)
                                            <tr>
                                                <td class="ps-4 text-sm fw-semibold">{{ $a->compagnie_assurance }}</td>
                                                <td class="text-sm" style="font-family:monospace;">{{ $a->numero_contrat }}</td>
                                                <td class="text-sm">{{ $a->type_assurance }}</td>
                                                <td class="text-sm">
                                                    {{ $a->date_debut->format('d/m/Y') }} → {{ $a->date_fin->format('d/m/Y') }}
                                                    @if ($a->isActive())
                                                        <x-badge class="activ" style="background:#e3f7ec;color:var(--eim-success);font-size:.65rem;padding:.15em .5em;">Active</x-badge>
                                                    @endif
                                                </td>
                                                <td class="num grand">{{ number_format((float) $a->prime, 2, ',', ' ') }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </x-tableau>
                            @endif
                        </div>

                        {{-- Affectations --}}
                        <div class="tab-pane fade" id="tab-affectations">
                            @if ($voiture->affectations->isEmpty())
                                <x-vide icone="fas fa-user-tie">Aucune affectation.</x-vide>
                            @else
                                <x-tableau class="table-detail mb-0">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Chauffeur</th>
                                                <th>Période</th>
                                                <th>Type</th>
                                                <th>Motif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($voiture->affectations as $aff)
                                            <tr>
                                                <td class="ps-4 text-sm fw-semibold">{{ $aff->chauffeur?->nom_complet ?? '—' }}</td>
                                                <td class="text-sm">
                                                    {{ optional($aff->date_debut)->format('d/m/Y') }}
                                                    @if ($aff->date_fin) → {{ $aff->date_fin->format('d/m/Y') }}
                                                    @else → <span style="color:var(--eim-success);font-weight:600;">en cours</span>
                                                    @endif
                                                </td>
                                                <td class="text-sm">{{ $aff->type_affectation?->label() }}</td>
                                                <td class="text-sm text-muted">{{ \Str::limit($aff->motif, 40) ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </x-tableau>
                            @endif
                        </div>
                    </div>
                </div>
            </x-carte>
        </div>
    </div>
</div>

@can('transport.gerer')
<x-modale id="affecterModal" data-bs-backdrop="static" contenu-style="border:none;border-radius:16px;overflow:hidden;" entete-style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;" corps-class="p-4" croix-blanche>
    <x-slot:formulaire method="POST" action="{{ route('services-eleves.transport.voitures.affecter', $voiture) }}">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-user-plus me-2"></i>Affecter un chauffeur</x-slot:titre>
    <div class="mb-3">
        <x-libelle class="small fw-semibold" requis>Chauffeur</x-libelle>
        <select name="chauffeur_id" class="form-select" required>
            <option value="">— Sélectionner —</option>
            @foreach ($chauffeurs as $c)
                <option value="{{ $c->id }}">{{ $c->nom_complet }}</option>
            @endforeach
        </select>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <x-libelle class="small fw-semibold" requis>Date début</x-libelle>
            <input type="date" name="date_debut" class="form-control" value="{{ now()->toDateString() }}" required>
        </div>
        <div class="col-md-6">
            <x-libelle class="small fw-semibold">Date fin</x-libelle>
            <input type="date" name="date_fin" class="form-control">
        </div>
    </div>
    <div class="mt-3">
        <x-libelle class="small fw-semibold">Type</x-libelle>
        <select name="type_affectation" class="form-select">
            <option value="1">Permanente</option>
            <option value="2">Temporaire</option>
        </select>
    </div>
    <div class="mt-3">
        <x-libelle class="small fw-semibold">Motif</x-libelle>
        <textarea name="motif" rows="2" class="form-control" maxlength="500"></textarea>
    </div>
    <x-slot:pied class="bg-light px-4 py-3">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
        <button type="submit" class="btn" style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;border:none;border-radius:8px;font-weight:600;padding:.55rem 1.4rem;">
            <i class="fas fa-save me-1"></i> Enregistrer
        </button>
    </x-slot:pied>
</x-modale>
@endcan
@endsection