@extends('admin.layouts.app')

@section('title', 'Achat — ' . $achat->reference)
@section('page_icon', 'fa-shopping-cart')
@section('page_title', 'Détail de l\'achat')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.achats.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">{{ $achat->reference }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('logistique.achats.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    @if (! $achat->isLivre() && ! $achat->isAnnule())
        @can('achats.receptionner')
            <form action="{{ route('logistique.achats.receptionner', $achat) }}" method="POST" class="d-inline form-confirm-delete"
                  data-confirm-title="Réceptionner cet achat ?"
                  data-confirm-text="Le stock sera mis à jour.">
                @csrf
                <button class="btn btn-nouveau">
                    <i class="fas fa-truck-loading me-1"></i> Réceptionner
                </button>
            </form>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid #ece4d6 !important; }
    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value.grand { font-size:1.15rem; color:var(--school-red-dark); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.4em .9em; border-radius:999px; font-size:.75rem; font-weight:600; }
    .badge-statut.attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.livre   { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule  { background:#eceff2; color:#5b6b7a; }
    .badge-statut.impaye  { background:#fdecea; color:var(--school-red); }
    .badge-statut.partiel { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.paye    { background:#e3f7ec; color:#1c7a4d; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .table-lignes thead tr { background:#faf6ee; }
    .table-lignes thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid #ece4d6; }
    .table-lignes tbody td { padding:.7rem .9rem; border-bottom:1px solid #ece4d6; font-size:.88rem; vertical-align:middle; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }
</style>
@endsection

@section('contenu')
<div class="row g-4">
    <div class="col-md-5">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Informations</h6>
            </div>
            <div class="card-body p-4">
                <div class="info-row">
                    <span class="info-label">Référence</span>
                    <span class="info-value"><span class="reference">{{ $achat->reference }}</span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Bon de commande</span>
                    <span class="info-value">{{ $achat->bon_commande ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-value">{{ optional($achat->date_achat)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fournisseur</span>
                    <span class="info-value">{{ $achat->fournisseur?->raison_social ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Acheteur</span>
                    <span class="info-value">{{ $achat->nom_acheteur ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Type</span>
                    <span class="info-value">{{ $achat->type_achat?->label() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Livraison</span>
                    <span class="info-value">
                        <span class="badge-statut {{ $achat->statut_livraison?->css() }}">
                            <span class="badge-dot"></span>{{ $achat->statut_livraison?->label() }}
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Paiement</span>
                    <span class="info-value">
                        <span class="badge-statut {{ $achat->statut_paiement?->css() }}">
                            <span class="badge-dot"></span>{{ $achat->statut_paiement?->label() }}
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant total</span>
                    <span class="info-value grand">{{ number_format((float) $achat->montant_total, 2, ',', ' ') }}</span>
                </div>
                @if ($achat->commentaire)
                    <div class="mt-3 p-2 rounded" style="background:#faf6ee;font-size:.85rem;color:#4a5560;">
                        <strong>Commentaire :</strong> {{ $achat->commentaire }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-1" style="color:var(--school-gold-dark);"></i> Lignes de l'achat</h6>
                <span class="text-xs text-muted">{{ $achat->details->count() }} ligne(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-lignes align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Produit</th>
                                <th class="text-end">Quantité</th>
                                <th class="text-end">Prix unitaire</th>
                                <th class="text-end pe-4">Montant</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($achat->details as $ligne)
                            <tr>
                                <td class="ps-4">
                                    @if ($ligne->produit ?? null)
                                        <div class="fw-semibold">{{ $ligne->produit->libelle }}</div>
                                        <div class="text-xs text-muted">{{ $ligne->produit->code }}</div>
                                    @else
                                        <span class="text-muted">Produit #{{ $ligne->produit_id }}</span>
                                    @endif
                                </td>
                                <td class="num">{{ number_format((float) $ligne->quantite, 2, ',', ' ') }}</td>
                                <td class="num">{{ number_format((float) $ligne->prix_unitaire, 2, ',', ' ') }}</td>
                                <td class="num grand pe-4">{{ number_format((float) $ligne->montant_achat, 2, ',', ' ') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">Aucune ligne.</td></tr>
                        @endforelse
                        </tbody>
                        @if ($achat->details->isNotEmpty())
                            <tfoot style="background:#faf6ee;">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold text-uppercase text-xs" style="color:var(--school-red-dark);">Total</td>
                                    <td class="num grand pe-4">{{ number_format((float) $achat->montant_total, 2, ',', ' ') }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection