@extends('admin.layouts.app')

@section('title', 'Mouvements de stock')
@section('page_icon', 'fa-history')
@section('page_title', 'Journal des mouvements de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Mouvements de stock</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.mouvements-stock.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('stock.ajuster')
        <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#ajusterModal">
            <i class="fas fa-balance-scale me-1"></i> Ajuster un stock
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .badge-mv { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-mv.entree     { background:#e3f7ec; color:var(--eim-success); }
    .badge-mv.sortie     { background:#fdecea; color:var(--school-red); }
    .badge-mv.ajustement { background:#fdf4e0; color:var(--school-gold-dark); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.positif { color:var(--eim-success); }
    .num.negatif { color:var(--school-red); }
</style>
@endsection

@section('contenu')
<div class="container-fluid p-0">
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="produit_id" class="form-select filtre-input">
                        <option value="">— Produit —</option>
                        @foreach ($produits as $p)
                            <option value="{{ $p->id }}" @selected(($filters['produit_id'] ?? '') == $p->id)>{{ $p->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="magasin_id" class="form-select filtre-input">
                        <option value="">— Magasin —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}" @selected(($filters['magasin_id'] ?? '') == $m->id)>{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type_mouvement" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type_mouvement'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-stock align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Produit</th>
                            <th>Magasin</th>
                            <th>Type</th>
                            <th class="text-end">Quantité</th>
                            <th class="text-end">P.U.</th>
                            <th class="text-end">Valeur</th>
                            <th>Utilisateur</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($mouvements as $m)
                        @php $t = $m->type_mouvement; @endphp
                        <tr>
                            <td class="ps-4 text-sm">{{ optional($m->date_stock)->format('d/m/Y') }}</td>
                            <td class="text-sm">{{ $m->produit?->libelle ?? '—' }}</td>
                            <td class="text-sm">{{ $m->magasin?->libelle ?? '—' }}</td>
                            <td>
                                <span class="badge-mv {{ $t?->css() }}">
                                    <span class="badge-dot"></span>{{ $t?->label() ?? '—' }}
                                </span>
                            </td>
                            <td class="num {{ $t?->signe() === 1 ? 'positif' : 'negatif' }}">
                                {{ $t?->signe() === 1 ? '+' : '−' }}{{ number_format((float) $m->quantite, 2, ',', ' ') }}
                            </td>
                            <td class="num">{{ number_format((float) $m->prix_unitaire, 2, ',', ' ') }}</td>
                            <td class="num">{{ number_format((float) $m->valeur_mouvement, 2, ',', ' ') }}</td>
                            <td class="text-sm text-muted">{{ $m->utilisateur?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <x-vide colspan="8" icone="fas fa-history">Aucun mouvement.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$mouvements->appends(request()->query())" /></div>

    {{-- Modale ajustement --}}
    @can('stock.ajuster')
    <x-modale id="ajusterModal" data-bs-backdrop="static" entete-style="background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color:#fff; border-radius:16px 16px 0 0;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="ajuster-form" method="POST" action="{{ route('logistique.mouvements-stock.ajuster') }}">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-balance-scale me-2"></i>Ajustement d'inventaire</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Produit</x-libelle>
                <select name="produit_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($produits as $p)
                        <option value="{{ $p->id }}">{{ $p->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <x-libelle requis>Magasin</x-libelle>
                <select name="magasin_id" class="form-select" required>
                    <option value="">— Sélectionner —</option>
                    @foreach ($magasins as $m)
                        <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <x-libelle requis>Nouvelle quantité</x-libelle>
                <input type="number" name="nouvelle_quantite" step="0.01" min="0" class="form-control" required>
            </div>
            <div>
                <x-libelle requis>Motif</x-libelle>
                <textarea name="motif" rows="2" class="form-control" required minlength="5"></textarea>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Ajuster</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#ajuster-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#ajusterModal').modal('hide');
                window.showToastThenReload(res.message || 'Ajusté.', 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                window.showToast(msg, 'error');
            }
        });
    });
});
</script>
@endpush