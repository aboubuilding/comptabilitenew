@extends('admin.layouts.app')

@section('title', 'Produits & Stock')
@section('page_icon', 'fa-boxes')
@section('page_title', 'Produits & Stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Produits &amp; Stock</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.produits.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.bons.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-alt me-1"></i> Bons
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.mouvements-stock.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-history me-1"></i> Mouvements
    </x-bouton>
    @can('stock.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauProduit" data-bs-toggle="modal" data-bs-target="#produitModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau produit
        </x-bouton>
    @endcan

    <x-bouton variante="contour" href="{{ route('logistique.produits.exportPdf', request()->query()) }}" class="btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> PDF
</x-bouton>
@endsection

@section('css')
<style>
    .stock-page { --st-bg-soft:var(--eim-cream); --st-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--st-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--st-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--st-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--st-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--st-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--st-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--st-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-stock thead tr { background:var(--st-bg-soft); }
    .table-stock thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--st-border); white-space:nowrap; }
    .table-stock tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--st-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-stock tbody tr:hover { background:var(--eim-hover); }

    .produit-libelle { font-weight:700; color:var(--school-ink); display:block; }
    .produit-unite { font-size:.75rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-type { display:inline-flex; padding:.3em .8em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-type.vendable    { background:#e0ecfc; color:#2563eb; }
    .badge-type.consommable { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.mixte       { background:#e3f7ec; color:var(--eim-success); }

    .badge-stock { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:700; }
    .badge-stock.ok      { background:#e3f7ec; color:var(--eim-success); }
    .badge-stock.alerte  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-stock.rupture { background:#fdecea; color:var(--school-red); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-stock { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-stock { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--st-bg-soft); border:1px solid var(--st-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--st-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--st-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="stock-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-boxes" couleur="gold" libelle="Produits référencés">{{ $stats['total'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-exclamation-triangle" couleur="red" libelle="En alerte de stock">{{ $stats['en_alerte'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-times-circle" couleur="blue" libelle="En rupture">{{ $stats['en_rupture'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="green" libelle="Valeur du stock">{{ number_format($stats['valeur_totale'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Rechercher un produit..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="type_produit" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type_produit'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('logistique.produits.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-stock align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Produit</th>
                            <th>Type</th>
                            <th class="text-end">Prix unitaire</th>
                            <th class="text-end">Stock total</th>
                            <th class="text-end">Valeur</th>
                            <th>État</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($produits as $p)
                        @php
                            $stockTotal = $p->stock_total;
                            $enAlerte   = $p->est_en_alerte;
                            $enRupture  = $p->est_en_rupture;
                            $badgeClass = $enRupture ? 'rupture' : ($enAlerte ? 'alerte' : 'ok');
                            $badgeLabel = $enRupture ? 'Rupture' : ($enAlerte ? 'Stock bas' : 'Disponible');
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.produits.show', $p) }}" class="text-decoration-none">
                                    <span class="produit-libelle">{{ $p->libelle }}</span>
                                    <span class="produit-unite">{{ $p->unite_stock }}</span>
                                </a>
                            </td>
                            <td>
                                <span class="badge-type {{ $p->type_produit?->css() }}">{{ $p->type_produit?->label() }}</span>
                            </td>
                            <td class="num">{{ number_format((float) $p->prix_unitaire, 2, ',', ' ') }}</td>
                            <td class="num grand">{{ number_format($stockTotal, 2, ',', ' ') }}</td>
                            <td class="num">{{ number_format($p->valeur_stock_total, 2, ',', ' ') }}</td>
                            <td>
                                <span class="badge-stock {{ $badgeClass }}">
                                    <span class="badge-dot"></span>{{ $badgeLabel }}
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.produits.show', $p) }}">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </a>
                                        </li>
                                        @can('stock.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-produit"
                                                    data-id="{{ $p->id }}"
                                                    data-libelle="{{ $p->libelle }}"
                                                    data-prix="{{ $p->prix_unitaire }}"
                                                    data-unite-stock="{{ $p->unite_stock }}"
                                                    data-unite-achat="{{ $p->unite_achat }}"
                                                    data-equivalence="{{ $p->equivalence }}"
                                                    data-type="{{ $p->type_produit?->value }}"
                                                    data-bs-toggle="modal" data-bs-target="#produitModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('stock.gerer')
                                        <x-actions.supprimer :action="route('logistique.produits.destroy', $p)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-boxes">Aucun produit enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$produits->appends(request()->query())" /></div>

    {{-- Modale --}}
    @can('stock.gerer')
    <x-modale id="produitModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-stock" entete-class="modal-header-stock" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="produit-form" enctype="multipart/form-data">@csrf
                    <input type="hidden" name="_method" id="produit-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="produit-title"><i class="fas fa-box me-2"></i>Nouveau produit</x-slot:titre>
        <x-section titre="Identité produit" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="p-libelle" class="form-control" maxlength="200" required>
                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type_produit" id="p-type" class="form-select" required>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-section>

        <x-section class="mb-3">
            <div class="form-section-title"><i class="fas fa-balance-scale"></i> Unités &amp; prix</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <x-libelle requis>Unité stock</x-libelle>
                    <input type="text" name="unite_stock" id="p-unite-stock" class="form-control" maxlength="20" placeholder="unité, kg, L" required>
                </div>
                <div class="col-md-3">
                    <x-libelle>Unité achat</x-libelle>
                    <input type="text" name="unite_achat" id="p-unite-achat" class="form-control" maxlength="20" placeholder="carton, sac">
                </div>
                <div class="col-md-3">
                    <x-libelle>Équivalence</x-libelle>
                    <input type="number" name="equivalence" id="p-equivalence" step="0.0001" min="0" class="form-control" placeholder="1 carton = X unités">
                </div>
                <div class="col-md-3">
                    <x-libelle requis>Prix unitaire</x-libelle>
                    <input type="number" name="prix_unitaire" id="p-prix" step="0.01" min="0" class="form-control" required>
                </div>
            </div>
        </x-section>

        <x-section titre="Photo (facultatif)" icone="fas fa-image">
            <input type="file" name="photo" class="form-control" accept="image/*">
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>
@endsection

@push('js')
<script>
$(function () {
    const $form = $('#produit-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauProduit').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#produit-method').val('POST');
        $('#produit-title').html('<i class="fas fa-box me-2"></i>Nouveau produit');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-produit', function () {
        currentId = $(this).data('id');
        $('#p-libelle').val($(this).data('libelle'));
        $('#p-prix').val($(this).data('prix'));
        $('#p-unite-stock').val($(this).data('unite-stock'));
        $('#p-unite-achat').val($(this).data('unite-achat'));
        $('#p-equivalence').val($(this).data('equivalence'));
        $('#p-type').val($(this).data('type'));
        $('#produit-method').val('PUT');
        $('#produit-title').html('<i class="fas fa-pen me-2"></i>Modifier le produit');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('logistique/produits') }}/${currentId}`
            : `{{ route('logistique.produits.store') }}`;

        const fd = new FormData(this);

        $.ajax({
            url, method: 'POST', data: fd,
            processData: false, contentType: false,
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#produitModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() { $form.find('[id$="-error"]').text(''); }
});
</script>
@endpush