@extends('admin.layouts.app')

@section('title', 'Vente — ' . $vente->reference)
@section('page_icon', 'fa-receipt')
@section('page_title', 'Détail de la vente')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.ventes.index') }}">Boutique</a></li>
    <li class="breadcrumb-item active">{{ $vente->reference }}</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.ventes.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.ventes.ticket', $vente) }}" target="_blank" class="btn-sm rounded-pill px-3">
        <i class="fas fa-receipt me-1"></i> Imprimer le ticket
    </x-bouton>
    @if (! $vente->isAnnulee())
        @can('ventes.annuler')
            <button type="button" class="btn btn-sm rounded-pill px-3"
                    style="background:var(--school-red);color:#fff;border:none;"
                    data-bs-toggle="modal" data-bs-target="#annulerVenteShowModal">
                <i class="fas fa-times me-1"></i> Annuler la vente
            </button>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--eim-border) !important; }

    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value.grand { font-size:1.15rem; color:var(--school-red-dark); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.4em .9em; border-radius:999px; font-size:.75rem; font-weight:600; }

    .table-lignes thead tr { background:var(--eim-cream); }
    .table-lignes thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--eim-border); }
    .table-lignes tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--eim-border); font-size:.85rem; vertical-align:middle; }
    .table-lignes tfoot td { padding:.75rem .9rem; font-size:.9rem; font-weight:700; background:var(--eim-cream); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; font-size:.95rem; }
</style>
@endsection

@section('contenu')
<div class="row g-4">
    <div class="col-md-5">
        <x-carte>
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i> Informations</h6>
            </div>
            <div class="card-body p-4">
                <div class="info-row">
                    <span class="info-label">Référence</span>
                    <span class="info-value" style="font-family:monospace;">{{ $vente->reference }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-value">{{ optional($vente->date_vente)->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Type</span>
                    <span class="info-value">{{ $vente->type_vente?->label() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Acheteur</span>
                    <span class="info-value">{{ $vente->acheteur_nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Magasin</span>
                    <span class="info-value">{{ $vente->magasin?->libelle ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="info-value">
                        <x-badge class="{{ $vente->statut_paiement?->css() }}" point>{{ $vente->statut_paiement?->label() }}
                        </x-badge>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Vendeur</span>
                    <span class="info-value">{{ $vente->utilisateur?->name ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total HT</span>
                    <span class="info-value">{{ number_format((float) $vente->total_ht, 2, ',', ' ') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total TTC</span>
                    <span class="info-value grand">{{ number_format((float) $vente->total_ttc, 2, ',', ' ') }}</span>
                </div>
            </div>
        </x-carte>
    </div>

    <div class="col-md-7">
        <x-carte>
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-1" style="color:var(--school-gold-dark);"></i> Lignes de vente</h6>
                <span class="text-xs text-muted">{{ $vente->details->count() }} produit(s)</span>
            </div>
            <div class="card-body p-0">
                <x-tableau class="table-lignes mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Produit</th>
                                <th class="text-end">Quantité</th>
                                <th class="text-end">P.U.</th>
                                <th class="text-end">Remise</th>
                                <th class="text-end pe-4">Montant HT</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($vente->details as $ligne)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold">{{ $ligne->produit?->libelle ?? 'Produit #' . $ligne->produit_id }}</div>
                                    <div class="text-xs text-muted">{{ $ligne->produit?->unite_stock }}</div>
                                </td>
                                <td class="num">{{ number_format((float) $ligne->quantite, 2, ',', ' ') }}</td>
                                <td class="num">{{ number_format((float) $ligne->prix_unitaire, 2, ',', ' ') }}</td>
                                <td class="num">
                                    @if ((float) $ligne->remise > 0)
                                        <x-badge style="background:#fdf4e0;color:var(--school-gold-dark);">
                                            −{{ number_format((float) $ligne->remise, 1, ',', ' ') }} %
                                        </x-badge>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="num grand pe-4">{{ number_format((float) $ligne->montant_ht, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end text-uppercase text-xs" style="color:var(--school-red-dark);">Total HT</td>
                                <td class="num grand pe-4">{{ number_format((float) $vente->total_ht, 2, ',', ' ') }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="text-end text-uppercase" style="color:var(--school-red-dark);font-size:.95rem;">Total TTC</td>
                                <td class="num grand pe-4" style="font-size:1.1rem;">{{ number_format((float) $vente->total_ttc, 2, ',', ' ') }}</td>
                            </tr>
                        </tfoot>
                    </x-tableau>
            </div>
        </x-carte>
    </div>
</div>

@can('ventes.annuler')
    @if (! $vente->isAnnulee())
    <x-modale id="annulerVenteShowModal" aria-hidden="true" contenu-style="border:none;border-radius:16px;overflow:hidden;" entete-style="background:linear-gradient(135deg,var(--school-red-deep),var(--school-red-dark));color:#fff;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire method="POST" action="{{ route('logistique.ventes.annuler', $vente) }}">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Annuler la vente</x-slot:titre>
        <p>Annuler la vente <strong>{{ $vente->reference }}</strong> ?</p>
        <p class="text-muted small">Le stock sera automatiquement réintégré.</p>
        <div class="mb-0">
            <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
        </div>
        <x-slot:pied class="bg-light px-4 py-3">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
        </x-slot:pied>
    </x-modale>
    @endif
@endcan
@endsection