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
    <a href="{{ route('logistique.produits.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-boxes me-1"></i> Produits
    </a>
    @can('stock.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauBon"
                data-bs-toggle="modal" data-bs-target="#bonModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau bon
        </button>
    @endcan
@endsection

@section('css')
<style>
    /* Réutiliser les mêmes styles que produits/index — non dupliqués ici
       pour rester concis. Reprendre le bloc CSS de produits/index. */
    .bon-page { --bp-bg-soft:#faf6ee; --bp-border:#ece4d6; }
    .badge-bon { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-bon.entree    { background:#e3f7ec; color:#1c7a4d; }
    .badge-bon.sortie    { background:#fdecea; color:var(--school-red); }
    .badge-bon.transfert { background:#e0ecfc; color:#2563eb; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    /* Reprendre les classes .main-card, .filtre-input, .btn-*, .table-* de
       produits/index */
</style>
@endsection

@section('contenu')
<div class="bon-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-arrow-down"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['entree'] }}</div>
                    <div class="stat-label">Bons d'entrée</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['sortie'] }}</div>
                    <div class="stat-label">Bons de sortie</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-exchange-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['transfert'] }}</div>
                    <div class="stat-label">Transferts</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card">
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
                    <button type="submit" class="btn btn-filtrer flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</button>
                    <a href="{{ route('logistique.bons.index') }}" class="btn btn-outline-scolarite"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-stock align-middle mb-0">
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
                                    <span class="reference" style="font-family:monospace;font-size:.8rem;background:#faf6ee;color:var(--school-red);padding:2px 8px;border-radius:6px;font-weight:700;">{{ $bon->reference }}</span>
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
                            <td class="text-center"><span class="compteur-badge">{{ $bon->details_count ?? 0 }}</span></td>
                            <td class="text-center pe-4">
                                <a href="{{ route('logistique.bons.show', $bon) }}" class="btn-action" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-alt"></i><p>Aucun bon enregistré.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $bons->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale nouvelle bon --}}
    @can('stock.gerer')
    <div class="modal fade" id="bonModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content modal-stock">
                <form id="bon-form" method="POST" action="{{ route('logistique.bons.store') }}">
                    @csrf
                    <div class="modal-header modal-header-stock">
                        <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Nouveau bon de stock</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section mb-3">
                            <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations</div>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type" id="bon-type" class="form-select" required>
                                        @foreach ($types as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_bon" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Magasin <span class="text-danger">*</span></label>
                                    <select name="magasin_id" class="form-select" required>
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($magasins as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3" id="destination-wrap" style="display:none;">
                                    <label class="form-label">Magasin destination <span class="text-danger">*</span></label>
                                    <select name="magasin_destination_id" class="form-select">
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($magasins as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nom du responsable</label>
                                    <input type="text" name="nom_responsable" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nom du magasinier</label>
                                    <input type="text" name="nom_magasinier" class="form-control" maxlength="150">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Motif</label>
                                    <input type="text" name="motif" class="form-control" maxlength="200">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Commentaire</label>
                                    <input type="text" name="commentaire" class="form-control" maxlength="1000">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <div class="form-section-title"><i class="fas fa-list"></i> Produits concernés</div>
                            <div id="lignes-bon"></div>
                            <button type="button" class="btn btn-outline-scolarite btn-sm mt-2" id="add-ligne-bon">
                                <i class="fas fa-plus me-1"></i> Ajouter un produit
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Créer le bon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
</div>

<template id="ligne-bon-template">
    <div class="ligne-form" style="display:flex;gap:.5rem;align-items:center;background:#fff;border:1px dashed #ece4d6;border-radius:10px;padding:.6rem .75rem;margin-bottom:.5rem;">
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