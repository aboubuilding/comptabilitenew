@extends('admin.layouts.app')

@section('title', 'Menus de la cantine')
@section('page_icon', 'fa-book-open')
@section('page_title', 'Menus de la cantine')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Services aux Élèves</li>
    <li class="breadcrumb-item"><a href="{{ route('services-eleves.cantine.inscriptions.index') }}">Cantine</a></li>
    <li class="breadcrumb-item active">Menus</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.inscriptions.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-users me-1"></i> Inscriptions
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.preparations.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-fire me-1"></i> Préparations
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.dashboard') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-chart-pie me-1"></i> Dashboard
    </x-bouton>
    @can('cantine.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauMenu" data-bs-toggle="modal" data-bs-target="#menuModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau menu
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .menus-page { --m-bg-soft:var(--eim-cream); --m-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    /* Cartes statistiques */
    .stat-card {
        background:#fff; border:1px solid var(--m-border); border-radius:12px;
        padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem;
        box-shadow:0 2px 8px rgba(142,18,22,.04);
        transition: transform .2s;
    }
    .stat-icon {
        width:46px; height:46px; border-radius:10px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-size:1.1rem;
    }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    /* Carte principale */
    .main-card {
        border:none; border-radius:16px;
        box-shadow: 0 10px 30px rgba(142,18,22,.06);
        background:#fff; overflow:hidden;
    }
    .main-card .card-header { border-bottom:1px solid var(--m-border) !important; }

    /* Filtres */
    .filtre-input {
        border-radius:10px; border:1px solid var(--m-border);
        padding:.55rem .9rem; font-size:.88rem;
    }
    .btn-filtrer { background: var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge {
        display:inline-block; background: var(--m-bg-soft); color: var(--school-red);
        font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px;
        border:1px solid var(--m-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600;
    }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite {
        border:1px solid var(--m-border); color:var(--school-ink); background:#fff; font-size:.82rem;
    }
    .btn-outline-scolarite:hover { background: var(--m-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    /* Tableau */
    .table-menus thead tr { background: var(--m-bg-soft); }
    .table-menus thead th {
        color: var(--school-red); font-size:.76rem; font-weight:700;
        text-transform:uppercase; padding:.9rem 1rem;
        border-bottom:2px solid var(--m-border); white-space:nowrap;
    }
    .table-menus tbody td {
        padding:.9rem 1rem; border-bottom:1px solid var(--m-border);
        font-size:.9rem; color: var(--school-ink); vertical-align:middle;
    }
    .table-menus tbody tr:hover { background:var(--eim-hover); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color: var(--school-red-dark); }

    .menu-libelle { font-weight:700; color: var(--school-ink); display:block; }
    .menu-sub { font-size:.72rem; color: var(--school-muted); }

    .badge-type {
        display:inline-flex; padding:.3em .8em; border-radius:999px;
        font-size:.72rem; font-weight:600;
        background: var(--m-bg-soft); color: var(--school-red);
    }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    /* Modales */
    .modal-menus {
        border:none; border-radius:16px; overflow:hidden;
        box-shadow: 0 20px 40px rgba(0,0,0,.15);
    }
    .modal-header-menus {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border-bottom:none; padding:1.15rem 1.5rem;
    }
    .form-section {
        background: var(--m-bg-soft); border:1px solid var(--m-border);
        border-radius:12px; padding:1.1rem;
    }
    .form-section-title {
        font-size:.76rem; font-weight:700; text-transform:uppercase;
        color: var(--school-red); margin-bottom:.75rem;
        display:flex; align-items:center; gap:6px;
    }
    .form-section .form-label { font-size:.82rem; font-weight:600; color: var(--school-ink); }
    .form-section .form-control,
    .form-section .form-select {
        border-radius:8px; border:1px solid var(--m-border);
        padding:.55rem .75rem; font-size:.88rem;
    }

    /* Lignes de composition */
    .ligne-produit {
        display:flex; gap:.5rem; align-items:center;
        background:#fff; border:1px dashed var(--m-border);
        border-radius:10px; padding:.6rem .75rem; margin-bottom:.5rem;
    }
    .ligne-produit input, .ligne-produit select {
        border-radius:6px; border:1px solid var(--m-border);
        padding:.4rem .6rem; font-size:.85rem;
    }
    .ligne-total {
        font-weight:700; color: var(--school-red-dark);
        min-width:90px; text-align:right; font-variant-numeric:tabular-nums;
    }

    .btn-annuler {
        background:#fff; border:1px solid var(--m-border); color:var(--eim-ink-soft);
        border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem;
    }
    .btn-enregistrer {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color:#fff; border:none; border-radius:8px; font-weight:600;
        padding:.55rem 1.4rem; font-size:.88rem;
    }

    /* Détail menu (composition) */
    .detail-produit {
        display:flex; justify-content:space-between; padding:.5rem .75rem;
        border-bottom:1px solid #f1ece2; font-size:.88rem;
    }
    .detail-produit:last-child { border-bottom:none; }
    .detail-produit .label { color: var(--school-ink); }
    .detail-produit .value { font-weight:700; color: var(--school-red-dark); font-variant-numeric:tabular-nums; }
</style>
@endsection

@section('contenu')
<div class="menus-page">

    {{-- Cartes statistiques --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-book-open" couleur="blue" libelle="Menus enregistrés">{{ $menus->total() }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-calendar-day" couleur="gold" libelle="Menus aujourd'hui">{{ $menus->where('date_service', now()->toDateString())->count() }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Cette semaine">{{ $menus->where('date_service', '>=', now()->startOfWeek())->where('date_service', '<=', now()->endOfWeek())->count() }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="red" libelle="Coût prévu cumulé">{{ number_format((float) $menus->sum('cout_total_prevu'), 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    {{-- Tableau principal --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <x-recherche name="search" placeholder="Rechercher un menu..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="type_repas" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type_repas'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input"
                           value="{{ $filters['date_debut'] ?? '' }}" placeholder="Du">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input"
                           value="{{ $filters['date_fin'] ?? '' }}" placeholder="Au">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </x-bouton>
                    <x-bouton variante="contour" href="{{ route('services-eleves.cantine.menus.index') }}" title="Réinitialiser">
                        <i class="fas fa-undo"></i>
                    </x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-menus align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Libellé</th>
                            <th>Date de service</th>
                            <th>Type</th>
                            <th class="text-end">Prévu</th>
                            <th class="text-end">Réel</th>
                            <th class="text-end">Coût prévu</th>
                            <th class="text-end">Coût réel</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($menus as $menu)
                        <tr>
                            <td class="ps-4">
                                <span class="menu-libelle">{{ $menu->libelle }}</span>
                                <span class="menu-sub">
                                    {{ $menu->menu_produits_count ?? 0 }} produit(s)
                                    · {{ $menu->preparations_count ?? 0 }} préparation(s)
                                    @if ($menu->magasin)
                                        · <i class="fas fa-warehouse"></i> {{ $menu->magasin->libelle }}
                                    @endif
                                </span>
                            </td>
                            <td class="text-sm">
                                {{ optional($menu->date_service)->format('d/m/Y') }}
                            </td>
                            <td>
                                <span class="badge-type">{{ $menu->type_repas?->label() }}</span>
                            </td>
                            <td class="num">{{ $menu->quantite_prevue }}</td>
                            <td class="num">{{ $menu->quantite_reellement ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $menu->cout_total_prevu, 0, ',', ' ') }}</td>
                            <td class="num grand">
                                {{ $menu->cout_total_reel
                                    ? number_format((float) $menu->cout_total_reel, 0, ',', ' ')
                                    : '—' }}
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <button type="button" class="dropdown-item btn-detail-menu"
                                                    data-id="{{ $menu->id }}"
                                                    data-bs-toggle="modal" data-bs-target="#menuDetailModal">
                                                <i class="fas fa-eye"></i> Voir la composition
                                            </button>
                                        </li>
                                        @can('cantine.gerer')
                                            <li>
                                                <button type="button" class="dropdown-item btn-edit-menu"
                                                        data-id="{{ $menu->id }}"
                                                        data-bs-toggle="modal" data-bs-target="#menuModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <x-actions.supprimer :action="route('services-eleves.cantine.menus.destroy', $menu)" data-confirm-title="Supprimer ce menu ?" data-confirm-text="Les préparations liées empêcheront la suppression.">Supprimer</x-actions.supprimer>
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="8" icone="fas fa-book-open" :paragraphe="false"><p class="mb-0">Aucun menu enregistré.</p>
                                    @can('cantine.gerer')
                                        <p class="text-muted small mb-0">Cliquez sur « Nouveau menu » pour en créer un.</p>
                                    @endcan</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4">
        <x-pagination :liste="$menus->appends(request()->query())" />
    </div>

    {{-- Modal création / édition --}}
    @can('cantine.gerer')
    <x-modale id="menuModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-menus" entete-class="modal-header-menus" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="menu-form">@csrf
                    <input type="hidden" name="_method" id="menu-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="menu-title"><i class="fas fa-book-open me-2"></i>Nouveau menu</x-slot:titre>
        <x-section titre="Informations générales" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="menu-libelle" class="form-control" maxlength="200" required>
                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Date de service</x-libelle>
                    <input type="date" name="date_service" id="menu-date" class="form-control"
                           value="{{ now()->toDateString() }}" required>
                    <div class="text-danger text-xs mt-1" id="date_service-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Type de repas</x-libelle>
                    <select name="type_repas" id="menu-type" class="form-select">
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Magasin source</x-libelle>
                    <select name="magasin_id" id="menu-magasin" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        @foreach ($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                        @endforeach
                    </select>
                    <div class="text-danger text-xs mt-1" id="magasin_id-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Quantité prévue</x-libelle>
                    <input type="number" name="quantite_prevue" id="menu-quantite" min="0" value="0" class="form-control">
                </div>
                <div class="col-12">
                    <x-libelle>Description</x-libelle>
                    <textarea name="description" id="menu-description" rows="2" class="form-control" maxlength="2000"></textarea>
                </div>
            </div>
        </x-section>

        <x-section titre="Composition du menu" icone="fas fa-list">
            <div id="menu-produits"></div>
            <x-bouton variante="contour" type="button" class="btn-sm mt-2" id="add-produit-menu">
                <i class="fas fa-plus me-1"></i> Ajouter un produit
            </x-bouton>
            <div class="alert alert-light border-0 small mt-3 mb-0" style="background:#fff;">
                <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                <strong>Coût prévisionnel :</strong>
                <span id="menu-cout-prevu" style="color:var(--school-red-dark);font-weight:700;">0,00</span>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-save me-1"></i> Enregistrer
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modal détail / composition --}}
    <x-modale id="menuDetailModal" aria-hidden="true" contenu-class="modal-menus" entete-class="modal-header-menus" corps-class="p-4" corps-id="menu-detail-body" croix-blanche>
        <x-slot:titre><i class="fas fa-list me-2"></i>Composition du menu</x-slot:titre>
        <div class="text-center text-muted py-3">
            <i class="fas fa-spinner fa-spin"></i> Chargement...
        </div>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
        </x-slot:pied>
    </x-modale>
</div>

{{-- Template ligne produit --}}
<template id="ligne-produit-template">
    <div class="ligne-produit">
        <select name="produits[__idx__][produit_id]" class="form-select produit-select" style="flex:1;" required>
            <option value="">— Produit —</option>
            @foreach ($produits as $p)
                <option value="{{ $p->id }}" data-prix="{{ $p->prix_unitaire }}">{{ $p->libelle }}</option>
            @endforeach
        </select>
        <input type="number" name="produits[__idx__][quantite]" step="0.001" min="0.001"
               placeholder="Qté" style="width:90px;" required>
        <input type="number" name="produits[__idx__][cout_unitaire]" step="0.01" min="0"
               placeholder="C.U." style="width:110px;" required>
        <span class="ligne-total">0,00</span>
        <button type="button" class="btn btn-sm btn-light remove-ligne" title="Retirer">
            <i class="fas fa-times text-danger"></i>
        </button>
    </div>
</template>
@endsection

@push('js')
<script>
$(function () {
    // ================================================================
    // FORMULAIRE MENU
    // ================================================================
    const $form = $('#menu-form');
    const $container = $('#menu-produits');
    const tpl = $('#ligne-produit-template').html();
    let idx = 0;
    let currentId = null;

    function addLigne(preset = null) {
        const $row = $(tpl.replace(/__idx__/g, idx++));
        if (preset) {
            $row.find('.produit-select').val(preset.produit_id);
            $row.find('input[name$="[quantite]"]').val(preset.quantite);
            $row.find('input[name$="[cout_unitaire]"]').val(preset.cout_unitaire);
        }
        $container.append($row);
        bindRow($row);
        if (preset) {
            recalcRow($row);
        }
    }

    function bindRow($row) {
        const $prod = $row.find('.produit-select');
        const $qte  = $row.find('input[name$="[quantite]"]');
        const $cu   = $row.find('input[name$="[cout_unitaire]"]');
        const $tot  = $row.find('.ligne-total');

        $prod.on('change', function () {
            const prix = parseFloat($(this).find('option:selected').data('prix') || 0);
            if (prix > 0 && ! $cu.val()) $cu.val(prix);
            recalcRow($row);
        });

        $qte.on('input', function () { recalcRow($row); });
        $cu.on('input',  function () { recalcRow($row); });
    }

    function recalcRow($row) {
        const q = parseFloat($row.find('input[name$="[quantite]"]').val() || 0);
        const c = parseFloat($row.find('input[name$="[cout_unitaire]"]').val() || 0);
        const tot = q * c;
        $row.find('.ligne-total').text(new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(tot));
        recalcGlobal();
    }

    function recalcGlobal() {
        let total = 0;
        $container.find('.ligne-produit').each(function () {
            const q = parseFloat($(this).find('input[name$="[quantite]"]').val() || 0);
            const c = parseFloat($(this).find('input[name$="[cout_unitaire]"]').val() || 0);
            total += q * c;
        });
        $('#menu-cout-prevu').text(new Intl.NumberFormat('fr-FR', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        }).format(total));
    }

    $('#add-produit-menu').on('click', function () { addLigne(); });

    $(document).on('click', '.remove-ligne', function () {
        if ($container.children().length > 1) {
            $(this).closest('.ligne-produit').remove();
            recalcGlobal();
        }
    });

    // Bouton nouveau menu
    $('#btnNouveauMenu').on('click', function () {
        currentId = null;
        $form[0].reset();
        $container.empty();
        addLigne();
        $('#menu-method').val('POST');
        $('#menu-title').html('<i class="fas fa-book-open me-2"></i>Nouveau menu');
        $('#menu-cout-prevu').text('0,00');
        clearErrors();
    });

    // Édition
    $(document).on('click', '.btn-edit-menu', function () {
        currentId = $(this).data('id');
        $('#menu-method').val('PUT');
        $('#menu-title').html('<i class="fas fa-pen me-2"></i>Modifier le menu');
        clearErrors();

        $.getJSON(`{{ url('services-eleves/cantine/menus') }}/${currentId}`, function (res) {
            const menu = res.menu;
            $('#menu-libelle').val(menu.libelle);
            $('#menu-date').val(menu.date_service);
            $('#menu-type').val(menu.type_repas);
            $('#menu-magasin').val(menu.magasin_id);
            $('#menu-quantite').val(menu.quantite_prevue);
            $('#menu-description').val(menu.description);

            $container.empty();
            (menu.menu_produits || []).forEach(mp => {
                addLigne({
                    produit_id:    mp.produit_id,
                    quantite:      mp.quantite,
                    cout_unitaire: mp.cout_unitaire,
                });
            });
            if ((menu.menu_produits || []).length === 0) addLigne();
        });
    });

    // Soumission
    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('services-eleves/cantine/menus') }}/${currentId}`
            : `{{ route('services-eleves.cantine.menus.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#menuModal').modal('hide');
                window.showToastThenReload(res.message || 'Menu enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => {
                        const key = f.replace(/\./g, '-');
                        $('#' + key + '-error').text(xhr.responseJSON.errors[f][0]);
                    });
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });

    function clearErrors() { $form.find('[id$="-error"]').text(''); }

    // ================================================================
    // MODAL DÉTAIL MENU
    // ================================================================
    $('#menuDetailModal').on('show.bs.modal', function (e) {
        const id = $(e.relatedTarget).data('id');
        const $body = $('#menu-detail-body').html(
            '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>'
        );

        $.getJSON(`{{ url('services-eleves/cantine/menus') }}/${id}`, function (res) {
            const menu = res.menu;
            const fmt = (v) => new Intl.NumberFormat('fr-FR', {
                minimumFractionDigits: 0, maximumFractionDigits: 0,
            }).format(v);

            let html = `<div class="mb-3">
                <div class="fw-bold" style="color:var(--school-ink);">${menu.libelle}</div>
                <div class="text-xs text-muted">
                    ${menu.date_service} ·
                    ${menu.menu_produits?.length || 0} produit(s)
                </div>
            </div>`;

            if (! menu.menu_produits || menu.menu_produits.length === 0) {
                html += '<div class="text-center text-muted py-3">Aucun produit composé.</div>';
            } else {
                html += '<div style="border:1px solid var(--eim-border);border-radius:10px;overflow:hidden;">';
                menu.menu_produits.forEach(mp => {
                    html += `<div class="detail-produit">
                        <span class="label">
                            ${mp.produit?.libelle ?? 'Produit #' + mp.produit_id}
                            <span class="text-xs text-muted ms-2">${mp.quantite} ${mp.produit?.unite_stock ?? ''} × ${fmt(mp.cout_unitaire || 0)}</span>
                        </span>
                        <span class="value">${fmt(mp.cout_total || 0)}</span>
                    </div>`;
                });
                html += `<div class="detail-produit" style="background:var(--eim-cream);">
                    <span class="label" style="font-weight:700;text-transform:uppercase;font-size:.78rem;color:var(--school-red-dark);">Coût prévisionnel</span>
                    <span class="value">${fmt(menu.cout_total_prevu || 0)}</span>
                </div>`;
                html += '</div>';
            }

            $body.html(html);
        }).fail(function () {
            $body.html('<div class="text-center text-muted py-3">Impossible de charger la composition du menu.</div>');
        });
    });

    // Init : une ligne vide au chargement de la page
    addLigne();
});
</script>
@endpush