@extends('admin.layouts.app')

@section('title', 'Produit — ' . $produit->libelle)
@section('page_icon', 'fa-box')
@section('page_title', 'Détail du produit')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.produits.index') }}">Produits &amp; Stock</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($produit->libelle, 40) }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.produits.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    <a href="{{ route('logistique.mouvements-stock.index', ['produit_id' => $produit->id]) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-history me-1"></i> Tous les mouvements
    </a>
    @can('stock.gerer')
        <button type="button" class="btn btn-nouveau"
                data-bs-toggle="modal" data-bs-target="#stockAdjustModal">
            <i class="fas fa-balance-scale me-1"></i> Ajuster un stock
        </button>
    @endcan
@endsection

@section('css')
<style>
    .show-page { --sp-bg-soft:#faf6ee; --sp-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--sp-border) !important; }

    /* Bandeau produit */
    .produit-banner {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(122,15,28,.18);
        margin-bottom:1.25rem;
    }
    .produit-photo {
        width:80px; height:80px; border-radius:12px;
        background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.30);
        display:flex; align-items:center; justify-content:center;
        font-size:2rem; color: rgba(255,255,255,.7);
        flex-shrink:0; overflow:hidden;
    }
    .produit-photo img { width:100%; height:100%; object-fit:cover; }
    .produit-banner h2 { margin:0; font-size:1.3rem; font-weight:700; }
    .produit-banner .meta { font-size:.85rem; opacity:.9; margin-top:4px; }

    /* Info-rangées */
    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value.grand { font-size:1.15rem; color:var(--school-red-dark); }

    /* Cartes stats */
    .stat-card { background:#fff; border:1px solid var(--sp-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    /* Badges */
    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-type.vendable    { background:#e0ecfc; color:#2563eb; }
    .badge-type.consommable { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.mixte       { background:#e3f7ec; color:#1c7a4d; }

    .badge-stock { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:700; }
    .badge-stock.ok      { background:#e3f7ec; color:#1c7a4d; }
    .badge-stock.alerte  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-stock.rupture { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    /* Tableau stock par magasin */
    .table-stock-magasin thead tr { background:var(--sp-bg-soft); }
    .table-stock-magasin thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); }
    .table-stock-magasin tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); font-size:.85rem; vertical-align:middle; }
    .table-stock-magasin tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    /* Mouvements récents */
    .mouvement-ligne { display:flex; align-items:center; gap:.9rem; padding:.65rem .9rem; border-bottom:1px solid #f1ece2; }
    .mouvement-ligne:last-child { border-bottom:none; }
    .mouvement-icon {
        width:32px; height:32px; border-radius:8px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-size:.85rem;
    }
    .mouvement-icon.entree     { background:#e3f7ec; color:#1c7a4d; }
    .mouvement-icon.sortie     { background:#fdecea; color:var(--school-red); }
    .mouvement-icon.ajustement { background:#fdf4e0; color:var(--school-gold-dark); }
    .mouvement-date { font-size:.72rem; color:var(--school-muted); }
    .mouvement-libelle { font-size:.85rem; font-weight:600; color:var(--school-ink); }
    .mouvement-qte { font-size:.9rem; font-weight:700; font-variant-numeric:tabular-nums; }
    .mouvement-qte.positif { color:#1c7a4d; }
    .mouvement-qte.negatif { color:var(--school-red); }

    .empty-state { padding:2rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="show-page">

    {{-- Bandeau --}}
    <div class="produit-banner">
        <div class="produit-photo">
            @if ($produit->photo)
                <img src="{{ asset('storage/' . $produit->photo) }}" alt="{{ $produit->libelle }}">
            @else
                <i class="fas fa-box"></i>
            @endif
        </div>
        <div class="flex-grow-1">
            <h2>{{ $produit->libelle }}</h2>
            <div class="meta">
                <span class="badge-type {{ $produit->type_produit?->css() }}">
                    {{ $produit->type_produit?->label() }}
                </span>
                &nbsp;·&nbsp;
                <i class="fas fa-balance-scale me-1"></i>{{ $produit->unite_stock }}
                @if ($produit->unite_achat && $produit->equivalence)
                    &nbsp;·&nbsp;
                    <i class="fas fa-box-open me-1"></i>
                    1 {{ $produit->unite_achat }} = {{ $produit->equivalence }} {{ $produit->unite_stock }}
                @endif
            </div>
        </div>
        <div class="text-end">
            <div class="text-xs" style="opacity:.75;">Prix unitaire</div>
            <div style="font-size:1.4rem;font-weight:700;">
                {{ number_format((float) $produit->prix_unitaire, 2, ',', ' ') }}
            </div>
        </div>
    </div>

    {{-- Cartes stats --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-cubes"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($produit->stock_total, 2, ',', ' ') }}</div>
                    <div class="stat-label">Stock total (tous magasins)</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ number_format($produit->valeur_stock_total, 0, ',', ' ') }}</div>
                    <div class="stat-label">Valeur du stock</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon {{ $produit->est_en_rupture ? 'red' : ($produit->est_en_alerte ? 'gold' : 'green') }}">
                    <i class="fas {{ $produit->est_en_rupture ? 'fa-times-circle' : ($produit->est_en_alerte ? 'fa-exclamation-triangle' : 'fa-check-circle') }}"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value">
                        @if ($produit->est_en_rupture) Rupture
                        @elseif ($produit->est_en_alerte) Stock bas
                        @else Disponible
                        @endif
                    </div>
                    <div class="stat-label">État global</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-map-marked-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $produit->stocksActuels->count() }}</div>
                    <div class="stat-label">Magasin(s) avec stock</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Colonne gauche : informations + stock par magasin --}}
        <div class="col-lg-6">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                        Informations produit
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Libellé</span>
                        <span class="info-value">{{ $produit->libelle }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type</span>
                        <span class="info-value">
                            <span class="badge-type {{ $produit->type_produit?->css() }}">
                                {{ $produit->type_produit?->label() }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Unité de stock</span>
                        <span class="info-value">{{ $produit->unite_stock }}</span>
                    </div>
                    @if ($produit->unite_achat)
                        <div class="info-row">
                            <span class="info-label">Unité d'achat</span>
                            <span class="info-value">{{ $produit->unite_achat }}</span>
                        </div>
                    @endif
                    @if ($produit->equivalence)
                        <div class="info-row">
                            <span class="info-label">Équivalence</span>
                            <span class="info-value">1 {{ $produit->unite_achat }} = {{ $produit->equivalence }} {{ $produit->unite_stock }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Prix unitaire</span>
                        <span class="info-value grand">{{ number_format((float) $produit->prix_unitaire, 2, ',', ' ') }}</span>
                    </div>
                </div>
            </div>

            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-warehouse me-1" style="color:var(--school-gold-dark);"></i>
                        Stock par magasin
                    </h6>
                    <span class="text-xs text-muted">{{ $produit->stocksActuels->count() }} magasin(s)</span>
                </div>
                <div class="card-body p-0">
                    @if ($produit->stocksActuels->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0">Aucun stock enregistré pour ce produit.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-stock-magasin mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Magasin</th>
                                        <th class="text-end">Quantité</th>
                                        <th class="text-end">Seuil</th>
                                        <th class="text-end pe-4">Valeur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach ($produit->stocksActuels as $stock)
                                    @php
                                        $alerte = $stock->seuil_alerte !== null && $stock->quantite <= $stock->seuil_alerte;
                                        $rupture = $stock->quantite <= 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $stock->magasin?->libelle ?? '—' }}</div>
                                            <div class="text-xs text-muted">
                                                {{ $stock->magasin?->type?->label() ?? '' }}
                                            </div>
                                        </td>
                                        <td class="num {{ $rupture ? 'text-danger' : ($alerte ? 'text-warning' : 'grand') }}">
                                            {{ number_format((float) $stock->quantite, 2, ',', ' ') }}
                                        </td>
                                        <td class="num text-muted">
                                            {{ $stock->seuil_alerte !== null ? number_format((float) $stock->seuil_alerte, 2, ',', ' ') : '—' }}
                                        </td>
                                        <td class="num pe-4">{{ number_format((float) $stock->valeur_stock, 2, ',', ' ') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Colonne droite : historique des mouvements --}}
        <div class="col-lg-6">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-1" style="color:var(--school-gold-dark);"></i>
                        Mouvements récents
                    </h6>
                    <a href="{{ route('logistique.mouvements-stock.index', ['produit_id' => $produit->id]) }}"
                       class="text-xs text-decoration-none" style="color:var(--school-red);">
                        Tout voir <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    @php $mouvementsRecents = $historique->take(15); @endphp
                    @if ($mouvementsRecents->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0">Aucun mouvement enregistré.</p>
                        </div>
                    @else
                        @foreach ($mouvementsRecents as $m)
                            @php $t = $m->type_mouvement; @endphp
                            <div class="mouvement-ligne">
                                <div class="mouvement-icon {{ $t?->css() }}">
                                    <i class="fas {{ $t?->signe() === 1 ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="mouvement-libelle">{{ $t?->label() }}</div>
                                    <div class="mouvement-date">
                                        <i class="far fa-calendar me-1"></i>{{ optional($m->date_stock)->format('d/m/Y') }}
                                        · {{ $m->magasin?->libelle }}
                                    </div>
                                </div>
                                <div class="mouvement-qte {{ $t?->signe() === 1 ? 'positif' : 'negatif' }}">
                                    {{ $t?->signe() === 1 ? '+' : '−' }}{{ number_format((float) $m->quantite, 2, ',', ' ') }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modale ajustement rapide --}}
@can('stock.gerer')
<div class="modal fade" id="stockAdjustModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;">
            <form method="POST" action="{{ route('logistique.mouvements-stock.ajuster') }}">
                @csrf
                <input type="hidden" name="produit_id" value="{{ $produit->id }}">
                <div class="modal-header" style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;">
                    <h5 class="modal-title"><i class="fas fa-balance-scale me-2"></i>Ajuster le stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Magasin <span class="text-danger">*</span></label>
                        <select name="magasin_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            @foreach ($produit->stocksActuels as $stock)
                                <option value="{{ $stock->magasin_id }}">
                                    {{ $stock->magasin?->libelle }} (actuel : {{ number_format((float) $stock->quantite, 2, ',', ' ') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nouvelle quantité <span class="text-danger">*</span></label>
                        <input type="number" name="nouvelle_quantite" step="0.01" min="0" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label small fw-semibold">Motif <span class="text-danger">*</span></label>
                        <textarea name="motif" rows="2" class="form-control" required minlength="5"
                                  placeholder="Inventaire physique, correction d'erreur..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-enregistrer" style="background:linear-gradient(135deg,var(--school-red-dark),var(--school-red));color:#fff;border:none;border-radius:8px;font-weight:600;padding:.55rem 1.4rem;">
                        <i class="fas fa-save me-1"></i> Ajuster
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection