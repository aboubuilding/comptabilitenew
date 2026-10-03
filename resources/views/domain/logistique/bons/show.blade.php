@extends('admin.layouts.app')

@section('title', 'Bon — ' . $bon->reference)
@section('page_icon', 'fa-file-alt')
@section('page_title', 'Détail du bon de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.bons.index') }}">Bons de stock</a></li>
    <li class="breadcrumb-item active">{{ $bon->reference }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.bons.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    <button onclick="window.print()" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-print me-1"></i> Imprimer
    </button>
@endsection

@section('css')
<style>
    .show-page { --sp-bg-soft:#faf6ee; --sp-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--sp-border) !important; }

    /* Bandeau bon */
    .bon-banner {
        border-radius:16px; padding:1.25rem 1.5rem;
        display:flex; align-items:center; gap:1.25rem;
        box-shadow: 0 8px 24px rgba(122,15,28,.15);
        margin-bottom:1.25rem;
        color:#fff;
    }
    .bon-banner.entree    { background: linear-gradient(135deg, #1c7a4d, #2ecc71); }
    .bon-banner.sortie    { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); }
    .bon-banner.transfert { background: linear-gradient(135deg, #2563eb, #3b82f6); }

    .bon-icon {
        width:64px; height:64px; border-radius:14px;
        background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.30);
        display:flex; align-items:center; justify-content:center;
        font-size:1.7rem; color:rgba(255,255,255,.9);
        flex-shrink:0;
    }
    .bon-banner h2 { margin:0; font-size:1.3rem; font-weight:700; }
    .bon-banner .ref { font-size:.85rem; opacity:.9; font-family:monospace; letter-spacing:.5px; margin-top:4px; }

    .info-row { display:flex; justify-content:space-between; padding:.55rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value.grand { font-size:1.1rem; color:var(--school-red-dark); }

    .badge-magasin { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.75rem; font-weight:600; }
    .badge-magasin.entrepot { background:#e0ecfc; color:#2563eb; }
    .badge-magasin.boutique { background:#fdf4e0; color:var(--school-gold-dark); }

    .table-lignes thead tr { background:var(--sp-bg-soft); }
    .table-lignes thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); }
    .table-lignes tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); font-size:.85rem; vertical-align:middle; }
    .table-lignes tbody tr:hover { background:#fdfaf5; }
    .table-lignes tfoot td { padding:.75rem .9rem; font-size:.9rem; font-weight:700; background:var(--sp-bg-soft); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; font-size:.95rem; }

    /* Mouvements générés */
    .mouvement-item {
        display:flex; align-items:center; gap:.75rem;
        padding:.65rem .9rem; border-bottom:1px solid #f1ece2;
    }
    .mouvement-item:last-child { border-bottom:none; }
    .mouvement-icon {
        width:30px; height:30px; border-radius:8px;
        display:flex; align-items:center; justify-content:center;
        font-size:.8rem; flex-shrink:0;
    }
    .mouvement-icon.entree     { background:#e3f7ec; color:#1c7a4d; }
    .mouvement-icon.sortie     { background:#fdecea; color:var(--school-red); }
    .mouvement-icon.ajustement { background:#fdf4e0; color:var(--school-gold-dark); }
    .mouvement-libelle { font-size:.85rem; font-weight:600; color:var(--school-ink); }
    .mouvement-date { font-size:.72rem; color:var(--school-muted); }
    .mouvement-qte { font-size:.9rem; font-weight:700; font-variant-numeric:tabular-nums; margin-left:auto; }
    .mouvement-qte.positif { color:#1c7a4d; }
    .mouvement-qte.negatif { color:var(--school-red); }

    .empty-state { padding:2.5rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }

    @media print {
        .page-header-bar, #app-footer, .hbtp-root, #toast-container,
        .btn, .btn-outline-scolarite, .btn-nouveau { display: none !important; }
        .content-area { padding: 0 !important; }
        .main-card { box-shadow: none; border: 1px solid #ddd; }
    }
</style>
@endsection

@section('contenu')
<div class="show-page">

    @php
        $type = $bon->type;
        $classeType = $type?->css() ?? 'entree';
        $iconeType  = $type?->icone() ?? 'fa-file-alt';
    @endphp

    {{-- Bandeau --}}
    <div class="bon-banner {{ $classeType }}">
        <div class="bon-icon"><i class="fas {{ $iconeType }}"></i></div>
        <div class="flex-grow-1">
            <h2>{{ $type?->label() ?? 'Bon' }}</h2>
            <div class="ref">{{ $bon->reference }}</div>
        </div>
        <div class="text-end">
            <div class="text-xs" style="opacity:.75;">Date</div>
            <div style="font-size:1.15rem;font-weight:700;">
                {{ optional($bon->date_bon)->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Colonne gauche : informations du bon --}}
        <div class="col-lg-5">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                        Informations du bon
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Référence</span>
                        <span class="info-value" style="font-family:monospace;">{{ $bon->reference }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type</span>
                        <span class="info-value">{{ $type?->label() }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date</span>
                        <span class="info-value">{{ optional($bon->date_bon)->format('d/m/Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">
                            {{ $type?->value === \App\Domain\Logistique\Types\TypeBon::ENTREE->value ? 'Magasin destination' : 'Magasin source' }}
                        </span>
                        <span class="info-value">
                            <span class="badge-magasin {{ $bon->magasin?->type?->css() ?? 'entrepot' }}">
                                {{ $bon->magasin?->libelle ?? '—' }}
                            </span>
                        </span>
                    </div>
                    @if ($type?->value === \App\Domain\Logistique\Types\TypeBon::TRANSFERT->value)
                        <div class="info-row">
                            <span class="info-label">Magasin destination</span>
                            <span class="info-value">
                                <span class="badge-magasin {{ $bon->magasinDestination?->type?->css() ?? 'boutique' }}">
                                    {{ $bon->magasinDestination?->libelle ?? '—' }}
                                </span>
                            </span>
                        </div>
                    @endif
                    @if ($bon->nom_responsable)
                        <div class="info-row">
                            <span class="info-label">Responsable</span>
                            <span class="info-value">{{ $bon->nom_responsable }}</span>
                        </div>
                    @endif
                    @if ($bon->nom_magasinier)
                        <div class="info-row">
                            <span class="info-label">Magasinier</span>
                            <span class="info-value">{{ $bon->nom_magasinier }}</span>
                        </div>
                    @endif
                    @if ($bon->motif)
                        <div class="info-row">
                            <span class="info-label">Motif</span>
                            <span class="info-value">{{ $bon->motif }}</span>
                        </div>
                    @endif
                    @if ($bon->utilisateur)
                        <div class="info-row">
                            <span class="info-label">Créé par</span>
                            <span class="info-value">{{ $bon->utilisateur->name }}</span>
                        </div>
                    @endif
                    @if ($bon->commentaire)
                        <div class="mt-3 p-2 rounded" style="background:#faf6ee;font-size:.85rem;color:#4a5560;">
                            <strong>Commentaire :</strong> {{ $bon->commentaire }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Mouvements générés --}}
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-exchange-alt me-1" style="color:var(--school-gold-dark);"></i>
                        Mouvements de stock générés
                    </h6>
                    <span class="text-xs text-muted">{{ $bon->mouvements->count() }} mouvement(s)</span>
                </div>
                <div class="card-body p-0">
                    @if ($bon->mouvements->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="mb-0">Aucun mouvement généré.</p>
                        </div>
                    @else
                        @foreach ($bon->mouvements as $m)
                            @php $t = $m->type_mouvement; @endphp
                            <div class="mouvement-item">
                                <div class="mouvement-icon {{ $t?->css() }}">
                                    <i class="fas {{ $t?->signe() === 1 ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="mouvement-libelle">{{ $m->produit?->libelle ?? '—' }}</div>
                                    <div class="mouvement-date">
                                        {{ $t?->label() }} · {{ $m->magasin?->libelle }}
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

        {{-- Colonne droite : lignes du bon --}}
        <div class="col-lg-7">
            <div class="card main-card">
                <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-1" style="color:var(--school-gold-dark);"></i>
                        Lignes du bon
                    </h6>
                    <span class="text-xs text-muted">{{ $bon->details->count() }} produit(s)</span>
                </div>
                <div class="card-body p-0">
                    @if ($bon->details->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <p class="mb-0">Aucune ligne.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-lignes mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Produit</th>
                                        <th class="text-end">Quantité</th>
                                        <th class="text-end">Prix unitaire</th>
                                        <th class="text-end pe-4">Montant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php $totalMontant = 0; @endphp
                                @foreach ($bon->details as $ligne)
                                    @php
                                        $montant = (float) ($ligne->montant ?? 0);
                                        $totalMontant += $montant;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            @if ($ligne->produit)
                                                <div class="fw-semibold">{{ $ligne->produit->libelle }}</div>
                                                <div class="text-xs text-muted">{{ $ligne->produit->unite_stock }}</div>
                                            @else
                                                <span class="text-muted">{{ $ligne->libelle ?? 'Produit #' . $ligne->produit_id }}</span>
                                            @endif
                                        </td>
                                        <td class="num grand">{{ number_format((float) $ligne->quantite, 2, ',', ' ') }}</td>
                                        <td class="num text-muted">
                                            {{ $ligne->prix_unitaire !== null ? number_format((float) $ligne->prix_unitaire, 2, ',', ' ') : '—' }}
                                        </td>
                                        <td class="num pe-4">
                                            {{ $ligne->montant !== null ? number_format($montant, 2, ',', ' ') : '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                @if ($totalMontant > 0)
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end text-uppercase text-xs pe-3" style="color:var(--school-red-dark);">Total</td>
                                            <td class="num grand pe-4">{{ number_format($totalMontant, 2, ',', ' ') }}</td>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection