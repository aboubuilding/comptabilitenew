@extends('admin.layouts.app')

@section('title', 'Bons de stock')
@section('page_icon', 'fa-file-alt')
@section('page_title', 'Bons d\'entrée, sortie & transfert')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Bons de stock</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.produits.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-boxes me-1"></i> Produits
    </x-bouton>
    @can('stock.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauBon" data-bs-toggle="modal" data-bs-target="#bonModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau bon
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    /* Réutiliser les mêmes styles que produits/index — non dupliqués ici
       pour rester concis. Reprendre le bloc CSS de produits/index. */
    .bon-page { --bp-bg-soft:var(--eim-cream); --bp-border:var(--eim-border); }
    .badge-bon { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-bon.entree    { background:#e3f7ec; color:var(--eim-success); }
    .badge-bon.sortie    { background:#fdecea; color:var(--school-red); }
    .badge-bon.transfert { background:#e0ecfc; color:#2563eb; }

    /* Reprendre les classes .main-card, .filtre-input, .btn-*, .table-* de
       produits/index */
</style>
@endsection

@section('contenu')
<div class="bon-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <x-stat icone="fas fa-arrow-down" couleur="green" libelle="Bons d'entrée">{{ $compteurs['entree'] }}</x-stat>
        </div>
        <div class="col-sm-4">
            <x-stat icone="fas fa-arrow-up" couleur="red" libelle="Bons de sortie">{{ $compteurs['sortie'] }}</x-stat>
        </div>
        <div class="col-sm-4">
            <x-stat icone="fas fa-exchange-alt" couleur="blue" libelle="Transferts">{{ $compteurs['transfert'] }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control filtre-input"
                           placeholder="Référence, motif..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type de bon —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="magasin_id" class="form-select filtre-input">
                        <option value="">— Magasin —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}" @selected(($filters['magasin_id'] ?? '') == $m->id)>{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('logistique.bons.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-stock align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Référence</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Magasin</th>
                            <th>Motif</th>
                            <th class="text-center">Lignes</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($bons as $bon)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.bons.show', $bon) }}" class="text-decoration-none">
                                    <span class="reference" style="font-family:monospace;font-size:.8rem;background:var(--eim-cream);color:var(--school-red);padding:2px 8px;border-radius:6px;font-weight:700;">{{ $bon->reference }}</span>
                                </a>
                            </td>
                            <td class="text-sm text-muted">{{ optional($bon->date_bon)->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge-bon {{ $bon->type?->css() }}">
                                    <span class="badge-dot"></span>{{ $bon->type?->label() }}
                                </span>
                            </td>
                            <td class="text-sm">
                                {{ $bon->magasin?->libelle ?? '—' }}
                                @if ($bon->type?->value === \App\Domain\Logistique\Types\TypeBon::TRANSFERT->value)
                                    <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                    {{ $bon->magasinDestination?->libelle ?? '—' }}
                                @endif
                            </td>
                            <td class="text-sm">{{ \Str::limit($bon->motif, 40) ?? '—' }}</td>
                            <td class="text-center"><x-compteur>{{ $bon->details_count ?? 0 }}</x-compteur></td>
                            <td class="text-center pe-4">
                                <a href="{{ route('logistique.bons.show', $bon) }}" class="btn-action" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-file-alt">Aucun bon enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$bons->appends(request()->query())" /></div>

    {{-- Modale nouvelle bon --}}
    @can('stock.gerer')
    <x-modale id="bonModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-stock" entete-class="modal-header-stock" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="bon-form" method="POST" action="{{ route('logistique.bons.store') }}">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-file-alt me-2"></i>Nouveau bon de stock</x-slot:titre>
        <x-section titre="Informations" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type" id="bon-type" class="form-select" required>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_bon" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-3">
                    <x-libelle requis>Magasin</x-libelle>
                    <select name="magasin_id" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3" id="destination-wrap" style="display:none;">
                    <x-libelle requis>Magasin destination</x-libelle>
                    <select name="magasin_destination_id" class="form-select">
                        <option value="">— Sélectionner —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Nom du responsable</x-libelle>
                    <input type="text" name="nom_responsable" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>Nom du magasinier</x-libelle>
                    <input type="text" name="nom_magasinier" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>Motif</x-libelle>
                    <input type="text" name="motif" class="form-control" maxlength="200">
                </div>
                <div class="col-md-6">
                    <x-libelle>Commentaire</x-libelle>
                    <input type="text" name="commentaire" class="form-control" maxlength="1000">
                </div>
            </div>
        </x-section>

        <x-section titre="Produits concernés" icone="fas fa-list">
            <div id="lignes-bon"></div>
            <x-bouton variante="contour" type="button" class="btn-sm mt-2" id="add-ligne-bon">
                <i class="fas fa-plus me-1"></i> Ajouter un produit
            </x-bouton>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Créer le bon</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>

<template id="ligne-bon-template">
    <div class="ligne-form" style="display:flex;gap:.5rem;align-items:center;background:#fff;border:1px dashed var(--eim-border);border-radius:10px;padding:.6rem .75rem;margin-bottom:.5rem;">
        <select name="lignes[__idx__][produit_id]" class="form-select" style="flex:1;" required>
            <option value="">— Produit —</option>
            @foreach ($produits as $p)
                <option value="{{ $p->id }}">{{ $p->libelle }}</option>
            @endforeach
        </select>
        <input type="number" name="lignes[__idx__][quantite]" step="0.01" min="0.01" placeholder="Qté" style="width:90px;" required>
        <input type="number" name="lignes[__idx__][prix_unitaire]" step="0.01" min="0" placeholder="Prix U." style="width:110px;">
        <button type="button" class="btn btn-sm btn-light remove-ligne"><i class="fas fa-times text-danger"></i></button>
    </div>
</template>
@endsection

@push('js')
<script>
$(function () {
    let idx = 0;
    const $container = $('#lignes-bon');
    const tpl = $('#bon-template')?.html() || $('#ligne-bon-template').html();

    function addLigne() {
        $container.append(tpl.replace(/__idx__/g, idx++));
    }

    $('#add-ligne-bon').on('click', addLigne);
    $(document).on('click', '.remove-ligne', function () {
        if ($container.children().length > 1) $(this).closest('.ligne-form').remove();
    });

    // Affiche/masque la destination selon le type
    $('#bon-type').on('change', function () {
        const isTransfert = $(this).val() === '3';
        $('#destination-wrap').toggle(isTransfert);
    });

    $('#btnNouveauBon').on('click', function () {
        $container.empty();
        addLigne();
        $('#destination-wrap').hide();
    });

    $('#bon-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#bonModal').modal('hide');
                window.showToastThenReload(res.message || 'Bon créé.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    // Init : une ligne
    addLigne();
});
</script>
@endpush