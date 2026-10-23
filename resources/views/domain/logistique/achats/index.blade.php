@extends('admin.layouts.app')

@section('title', 'Achats')
@section('page_icon', 'fa-shopping-cart')
@section('page_title', 'Achats & Bons de commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Achats</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.achats.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('achats.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouvelAchat" data-bs-toggle="modal" data-bs-target="#achatModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvel achat
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .achats-page { --ac-bg-soft:var(--eim-cream); --ac-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--ac-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition:transform .2s; box-shadow:0 2px 8px rgba(142,18,22,.04); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--ac-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--ac-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--ac-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--ac-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--ac-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--ac-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-achats thead tr { background:var(--ac-bg-soft); }
    .table-achats thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--ac-border); white-space:nowrap; }
    .table-achats tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--ac-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-achats tbody tr:hover { background:var(--eim-hover); }

    .reference { font-family:monospace; font-size:.8rem; background:var(--ac-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .badge-statut.livre     { background:#e3f7ec; color:var(--eim-success); }
    .badge-statut.partiel   { background:#fdf4e0; color:var(--school-gold-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-achats { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-achats { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--ac-bg-soft); border:1px solid var(--ac-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--ac-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--ac-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }

    .ligne-form { display:flex; gap:.5rem; align-items:center; background:#fff; border:1px dashed var(--ac-border); border-radius:10px; padding:.6rem .75rem; margin-bottom:.5rem; }
    .ligne-form input, .ligne-form select { border-radius:6px; border:1px solid var(--ac-border); padding:.4rem .6rem; font-size:.85rem; }
    .ligne-total { font-weight:700; color:var(--school-red-dark); min-width:90px; text-align:right; }
</style>
@endsection

@section('contenu')
<div class="achats-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-clock" couleur="gold">{{ $compteurs['attente'] }}<x-slot:libelle>En attente — {{ number_format($montants['attente'], 0, ',', ' ') }}</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-check-circle" couleur="green">{{ $compteurs['livre'] }}<x-slot:libelle>Livrés — {{ number_format($montants['livre'], 0, ',', ' ') }}</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-times-circle" couleur="red" libelle="Annulés">{{ $compteurs['annule'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-coins" couleur="blue" libelle="Volume total">{{ number_format($montants['attente'] + $montants['livre'], 0, ',', ' ') }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Référence, bon de commande..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="fournisseur_id" class="form-select filtre-input">
                        <option value="">— Fournisseur —</option>
                        @foreach ($fournisseurs as $f)
                            <option value="{{ $f->id }}" @selected(($filters['fournisseur_id'] ?? '') == $f->id)>{{ $f->raison_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut_livraison" class="form-select filtre-input">
                        <option value="">— Livraison —</option>
                        @foreach ($statutsLivraison as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut_livraison'] ?? '') === (string) $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="statut_paiement" class="form-select filtre-input">
                        <option value="">— Paiement —</option>
                        @foreach ($statutsPaiement as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut_paiement'] ?? '') === (string) $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-achats align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Référence</th>
                            <th>Fournisseur</th>
                            <th>Date</th>
                            <th class="text-end">Montant</th>
                            <th>Livraison</th>
                            <th>Paiement</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($achats as $achat)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.achats.show', $achat) }}" class="text-decoration-none">
                                    <span class="reference">{{ $achat->reference }}</span>
                                </a>
                                @if ($achat->bon_commande)
                                    <div class="text-xs text-muted mt-1">BC : {{ $achat->bon_commande }}</div>
                                @endif
                            </td>
                            <td class="text-sm">{{ $achat->fournisseur?->raison_social ?? '—' }}</td>
                            <td class="text-sm text-muted">{{ optional($achat->date_achat)->format('d/m/Y') }}</td>
                            <td class="num grand">{{ number_format((float) $achat->montant_total, 2, ',', ' ') }}</td>
                            <td>
                                <x-badge class="{{ $achat->statut_livraison?->css() }}" point>{{ $achat->statut_livraison?->label() }}
                                </x-badge>
                            </td>
                            <td>
                                <x-badge class="{{ $achat->statut_paiement?->css() }}" point>{{ $achat->statut_paiement?->label() }}
                                </x-badge>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.achats.show', $achat) }}">
                                                <i class="fas fa-eye"></i> Voir le détail
                                            </a>
                                        </li>
                                        @if (! $achat->isLivre() && ! $achat->isAnnule())
                                            @can('achats.receptionner')
                                                <li>
                                                    <form action="{{ route('logistique.achats.receptionner', $achat) }}" method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Réceptionner cet achat ?"
                                                          data-confirm-text="Le stock du magasin de réception sera mis à jour.">
                                                        @csrf
                                                        <button class="dropdown-item">
                                                            <i class="fas fa-truck-loading"></i> Réceptionner
                                                        </button>
                                                    </form>
                                                </li>
                                            @endcan
                                        @endif
                                        @if (! $achat->isAnnule())
                                            @can('achats.gerer')
                                                <li>
                                                    <button class="dropdown-item text-danger btn-annuler-achat"
                                                            data-id="{{ $achat->id }}"
                                                            data-reference="{{ $achat->reference }}"
                                                            data-bs-toggle="modal" data-bs-target="#annulerModal">
                                                        <i class="fas fa-times"></i> Annuler
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="7" icone="fas fa-shopping-cart">Aucun achat enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$achats->appends(request()->query())" /></div>

    {{-- Modale création --}}
    @can('achats.gerer')
    <x-modale id="achatModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-achats" entete-class="modal-header-achats" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="achat-form">@csrf
                    <input type="hidden" name="_method" id="achat-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="achat-title"><i class="fas fa-shopping-cart me-2"></i>Nouvel achat</x-slot:titre>
        <x-section titre="Informations générales" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_achat" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Fournisseur</x-libelle>
                    <select name="fournisseur_id" class="form-select" required>
                        <option value="">— Sélectionner —</option>
                        @foreach ($fournisseurs as $f)
                            <option value="{{ $f->id }}">{{ $f->raison_social }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type_achat" class="form-select" required>
                        @foreach ($typesAchat as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <x-libelle>Nom de l'acheteur</x-libelle>
                    <input type="text" name="nom_acheteur" class="form-control" maxlength="150">
                </div>
                <div class="col-md-4">
                    <x-libelle>Bon de commande</x-libelle>
                    <input type="text" name="bon_commande" class="form-control" maxlength="150">
                </div>
                <div class="col-md-4">
                    <x-libelle>Commentaire</x-libelle>
                    <input type="text" name="commentaire" class="form-control" maxlength="1000">
                </div>
            </div>
        </x-section>

        <x-section titre="Lignes de l'achat" icone="fas fa-list">
            <div id="lignes-container"></div>
            <x-bouton variante="contour" type="button" class="btn-sm mt-2" id="add-ligne">
                <i class="fas fa-plus me-1"></i> Ajouter une ligne
            </x-bouton>
            <div class="alert alert-light border-0 small mt-3 mb-0" style="background:#fff;color:var(--school-muted);">
                <strong>Total achat :</strong> <span id="total-achat" style="color:var(--school-red-dark);font-weight:700;">0,00</span>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- Modale annulation --}}
    @can('achats.gerer')
    <x-modale id="annulerModal" aria-hidden="true" contenu-class="modal-achats" entete-style="background: linear-gradient(135deg, var(--school-red-deep), var(--school-red-dark)); color:#fff; border-radius:16px 16px 0 0;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="annuler-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exclamation-triangle me-2"></i>Annuler l'achat</x-slot:titre>
        <p class="mb-3">Annuler l'achat <strong id="annuler-reference">—</strong> ?</p>
        <x-section>
            <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
            <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn" style="background:var(--school-red-dark);color:#fff;">Confirmer</button>
        </x-slot:pied>
    </x-modale>
    @endcan
</div>

<template id="ligne-achat-template">
    <div class="ligne-form">
        <select name="lignes[__idx__][produit_id]" class="form-select" style="flex:1;" required>
            <option value="">— Produit —</option>
            @foreach ($produitsDispo as $p)
                <option value="{{ $p->id }}">{{ $p->libelle }}{{ $p->code ? ' (' . $p->code . ')' : '' }}</option>
            @endforeach
        </select>
        <input type="number" name="lignes[__idx__][quantite]" step="0.01" min="0.01" placeholder="Qté" style="width:90px;" required>
        <input type="number" name="lignes[__idx__][prix_unitaire]" step="0.01" min="0" placeholder="Prix U." style="width:110px;" required>
        <span class="ligne-total">0,00</span>
        <button type="button" class="btn btn-sm btn-light remove-ligne"><i class="fas fa-times text-danger"></i></button>
    </div>
</template>
@endsection

@push('js')
<script>
$(function () {
    const $form = $('#achat-form');
    if (!$form.length) return;

    const $container = $('#lignes-container');
    const tpl = $('#ligne-achat-template').html();
    let idx = 0;
    let currentId = null;

    function addLigne() {
        const html = tpl.replace(/__idx__/g, idx++);
        const $row = $(html);
        $container.append($row);
        bindRow($row);
    }

    function bindRow($row) {
        const $qte   = $row.find('input[name$="[quantite]"]');
        const $pu    = $row.find('input[name$="[prix_unitaire]"]');
        const $total = $row.find('.ligne-total');

        function recalc() {
            const qte = parseFloat($qte.val() || 0);
            const pu  = parseFloat($pu.val() || 0);
            const tot = qte * pu;
            $total.text(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(tot));
            recalcGlobal();
        }

        $qte.on('input', recalc);
        $pu.on('input', recalc);
    }

    function recalcGlobal() {
        let total = 0;
        $container.find('.ligne-form').each(function () {
            const qte = parseFloat($(this).find('input[name$="[quantite]"]').val() || 0);
            const pu  = parseFloat($(this).find('input[name$="[prix_unitaire]"]').val() || 0);
            total += qte * pu;
        });
        $('#total-achat').text(new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(total));
    }

    $('#add-ligne').on('click', () => addLigne());
    $(document).on('click', '.remove-ligne', function () {
        if ($container.children().length > 1) {
            $(this).closest('.ligne-form').remove();
            recalcGlobal();
        }
    });

    $('#btnNouvelAchat').on('click', function () {
        currentId = null;
        $form[0].reset();
        $container.empty();
        addLigne();
        $('#achat-method').val('POST');
        $('#achat-title').html('<i class="fas fa-shopping-cart me-2"></i>Nouvel achat');
    });

    $form.on('submit', function (e) {
        e.preventDefault();

        const url = currentId
            ? `{{ url('logistique/achats') }}/${currentId}`
            : `{{ route('logistique.achats.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#achatModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
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

    // Annulation
    $('.btn-annuler-achat').on('click', function () {
        const id = $(this).data('id');
        $('#annuler-reference').text($(this).data('reference'));
        $('#annuler-form').attr('action', `{{ url('logistique/achats') }}/${id}/annuler`);
    });

    $('#annuler-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#annulerModal').modal('hide');
                window.showToastThenReload(res.message || 'Achat annulé.', 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                window.showToast(msg, 'error');
            }
        });
    });

    // Init : une ligne vide
    addLigne();
});
</script>
@endpush