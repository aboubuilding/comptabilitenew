@extends('admin.layouts.app')

@section('title', 'Magasin — ' . $magasin->libelle)
@section('page_icon', 'fa-warehouse')
@section('page_title', 'Détail du magasin')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.magasins.index') }}">Magasins</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($magasin->libelle, 40) }}</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.magasins.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.bons.index', ['magasin_id' => $magasin->id]) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-alt me-1"></i> Bons du magasin
    </x-bouton>
    @can('stock.gerer')
        <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#bonRapideModal">
            <i class="fas fa-plus-circle me-1"></i> Créer un bon
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .show-page { --sp-bg-soft:var(--eim-cream); --sp-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--sp-border) !important; }

    /* Bandeau magasin */
    .magasin-banner {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(142,18,22,.18);
        margin-bottom:1.25rem;
    }
    .magasin-icon {
        width:64px; height:64px; border-radius:14px;
        background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.30);
        display:flex; align-items:center; justify-content:center;
        font-size:1.6rem; color:rgba(255,255,255,.85);
        flex-shrink:0;
    }
    .magasin-banner h2 { margin:0; font-size:1.3rem; font-weight:700; }
    .magasin-banner .meta { font-size:.85rem; opacity:.9; margin-top:4px; }
    .badge-magasin {
        display:inline-flex; align-items:center; gap:6px;
        padding:.35em .9em; border-radius:999px;
        font-size:.75rem; font-weight:600;
        background: rgba(255,255,255,.18); color:#fff;
    }

    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }

    .stat-card { background:#fff; border:1px solid var(--sp-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    .table-stocks thead tr { background:var(--sp-bg-soft); }
    .table-stocks thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); white-space:nowrap; }
    .table-stocks tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); font-size:.85rem; vertical-align:middle; }
    .table-stocks tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .badge-stock { display:inline-flex; align-items:center; gap:6px; padding:.3em .75em; border-radius:999px; font-size:.7rem; font-weight:700; }
    .badge-stock.ok      { background:#e3f7ec; color:var(--eim-success); }
    .badge-stock.alerte  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-stock.rupture { background:#fdecea; color:var(--school-red); }

    .empty-state { padding:3rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2.5rem; margin-bottom:.6rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="show-page">

    @php
        $quantiteTotale = $magasin->stocksActuels->sum('quantite');
        $valeurTotale   = $magasin->stocksActuels->sum('valeur_stock');
        $nbAlertes      = $magasin->stocksActuels->filter(fn ($s) => $s->seuil_alerte !== null && $s->quantite <= $s->seuil_alerte)->count();
    @endphp

    {{-- Bandeau --}}
    <div class="magasin-banner">
        <div class="magasin-icon"><i class="fas fa-warehouse"></i></div>
        <div class="flex-grow-1">
            <h2>{{ $magasin->libelle }}</h2>
            <div class="meta">
                <span class="badge-magasin">
                    <i class="fas fa-tag me-1"></i>{{ $magasin->type?->label() }}
                </span>
                @if ($magasin->responsable)
                    &nbsp;·&nbsp;<i class="fas fa-user me-1"></i>{{ $magasin->responsable }}
                @endif
                @if ($magasin->telephone)
                    &nbsp;·&nbsp;<i class="fas fa-phone me-1"></i>{{ $magasin->telephone }}
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="text-xs" style="opacity:.75;">Valeur du stock</div>
            <div style="font-size:1.4rem;font-weight:700;">
                {{ number_format($valeurTotale, 0, ',', ' ') }}
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-boxes" couleur="blue" libelle="Produits référencés">{{ $magasin->stocksActuels->count() }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-cubes" couleur="gold" libelle="Quantité totale">{{ number_format($quantiteTotale, 0, ',', ' ') }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-exclamation-triangle" couleur="{{ $nbAlertes > 0 ? 'red' : 'green' }}" libelle="Produits en alerte">{{ $nbAlertes }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="green" libelle="Valeur du stock">{{ number_format($valeurTotale, 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    <div class="row g-3">
        {{-- Info magasin --}}
        <div class="col-lg-4">
            <x-carte>
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                        Informations magasin
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Libellé</span>
                        <span class="info-value">{{ $magasin->libelle }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type</span>
                        <span class="info-value">{{ $magasin->type?->label() }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Responsable</span>
                        <span class="info-value">{{ $magasin->responsable ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Téléphone</span>
                        <span class="info-value">{{ $magasin->telephone ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Adresse</span>
                        <span class="info-value">{{ $magasin->adresse ?? '—' }}</span>
                    </div>
                    @if ($magasin->description)
                        <div class="mt-3 p-2 rounded" style="background:var(--eim-cream);font-size:.85rem;color:var(--eim-ink-soft);">
                            <strong>Description :</strong> {{ $magasin->description }}
                        </div>
                    @endif
                </div>
            </x-carte>
        </div>

        {{-- Stocks du magasin --}}
        <div class="col-lg-8">
            <x-carte>
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-cubes me-1" style="color:var(--school-gold-dark);"></i>
                        Stocks actuels
                    </h6>
                    <span class="text-xs text-muted">{{ $magasin->stocksActuels->count() }} produit(s)</span>
                </div>
                <div class="card-body p-0">
                    @if ($magasin->stocksActuels->isEmpty())
                        <x-vide icone="fas fa-box-open" :paragraphe="false"><p class="mb-0">Aucun produit en stock dans ce magasin.</p></x-vide>
                    @else
                        <x-tableau class="table-stocks mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Produit</th>
                                        <th class="text-end">Quantité</th>
                                        <th class="text-end">Seuil</th>
                                        <th class="text-end">Valeur</th>
                                        <th class="text-center pe-4">État</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($magasin->stocksActuels as $stock)
                                    @php
                                        $enRupture = $stock->quantite <= 0;
                                        $enAlerte  = ! $enRupture && $stock->seuil_alerte !== null && $stock->quantite <= $stock->seuil_alerte;
                                        $badgeClass = $enRupture ? 'rupture' : ($enAlerte ? 'alerte' : 'ok');
                                        $badgeLabel = $enRupture ? 'Rupture' : ($enAlerte ? 'Stock bas' : 'OK');
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            @if ($stock->produit)
                                                <a href="{{ route('logistique.produits.show', $stock->produit_id) }}" class="text-decoration-none">
                                                    <div class="fw-semibold">{{ $stock->produit->libelle }}</div>
                                                    <div class="text-xs text-muted">{{ $stock->produit->unite_stock }}</div>
                                                </a>
                                            @else
                                                <span class="text-muted">Produit #{{ $stock->produit_id }}</span>
                                            @endif
                                        </td>
                                        <td class="num grand">{{ number_format((float) $stock->quantite, 2, ',', ' ') }}</td>
                                        <td class="num text-muted">
                                            {{ $stock->seuil_alerte !== null ? number_format((float) $stock->seuil_alerte, 2, ',', ' ') : '—' }}
                                        </td>
                                        <td class="num">{{ number_format((float) $stock->valeur_stock, 2, ',', ' ') }}</td>
                                        <td class="text-center pe-4">
                                            <span class="badge-stock {{ $badgeClass }}">
                                                <span class="badge-dot"></span>{{ $badgeLabel }}
                                            </span>
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

{{-- Modale bon rapide --}}
@can('stock.gerer')
<x-modale id="bonRapideModal" data-bs-backdrop="static" contenu-style="border-radius:16px;overflow:hidden;border:none;" entete-style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;" corps-class="p-4" croix-blanche>
    <x-slot:titre><i class="fas fa-file-alt me-2"></i>Créer un bon</x-slot:titre>
    <p class="small text-muted mb-3">
        Vous allez être redirigé vers l'écran de création de bon avec ce magasin pré-sélectionné.
    </p>
    <a href="{{ route('logistique.bons.index', ['magasin_id' => $magasin->id]) }}"
       class="btn w-100"
       style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;border:none;border-radius:8px;padding:.7rem 1.4rem;font-weight:600;">
        <i class="fas fa-arrow-right me-1"></i> Aller aux bons de stock
    </a>
</x-modale>
@endcan
@endsection