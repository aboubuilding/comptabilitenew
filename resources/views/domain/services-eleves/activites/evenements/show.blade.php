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
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.evenements.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.activites-evenements.evenements.export', ['search' => $evenement->nom]) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
@endsection

@section('css')
<style>
    .show-page { --s-bg-soft:var(--eim-cream); --s-border:var(--eim-border); }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--s-border) !important; }

    .evt-banner {
        background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(142,18,22,.18);
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
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    .table-part thead tr { background:var(--s-bg-soft); }
    .table-part thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--s-border); }
    .table-part tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--s-border); font-size:.85rem; vertical-align:middle; }
    .table-part tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }

    .badge-statut.pre-inscrit { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.confirme    { background:#e3f7ec; color:var(--eim-success); }

    .empty-state { padding:2rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
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
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Confirmés">{{ $conf }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-hourglass-half" couleur="gold" libelle="Pré-inscrits en attente">{{ $pre }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-users" couleur="blue" libelle="Capacité">{{ $evenement->capacite ?? '∞' }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-percentage" couleur="{{ $taux >= 90 ? 'red' : 'green' }}" libelle="Remplissage">{{ number_format($taux, 0, ',', ' ') }} %</x-stat>
        </div>
    </div>

    <div class="row g-3">
        {{-- Infos --}}
        <div class="col-lg-5">
            <x-carte>
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
                                    <x-badge class="annule ms-1" style="background:#fdecea;color:var(--school-red);">
                                        Dépassée
                                    </x-badge>
                                @endif
                            </span>
                        </div>
                    @endif
                    @if ($evenement->description)
                        <div class="mt-3 p-2 rounded"
                             style="background:var(--eim-cream);font-size:.85rem;color:var(--eim-ink-soft);">
                            <strong>Description :</strong> {{ $evenement->description }}
                        </div>
                    @endif
                </div>
            </x-carte>
        </div>

        {{-- Participations --}}
        <div class="col-lg-7">
            <x-carte>
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
                        <x-vide icone="fas fa-users" :paragraphe="false"><p class="mb-0">Aucune participation enregistrée.</p></x-vide>
                    @else
                        <x-tableau class="table-part mb-0">
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
                                            <x-badge class="{{ $p->statut?->css() }}" point>{{ $p->statut?->label() }}
                                            </x-badge>
                                        </td>
                                        <td class="text-sm text-muted">
                                            {{ optional($p->date_inscription)->format('d/m/Y') }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </x-tableau>
                    @endif
                </div>
            </x-carte>
        </div>
    </div>
</div>
@endsection