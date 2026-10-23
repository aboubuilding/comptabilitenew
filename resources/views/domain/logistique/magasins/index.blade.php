@extends('admin.layouts.app')

@section('title', 'Magasins')
@section('page_icon', 'fa-warehouse')
@section('page_title', 'Magasins & Entrepôts')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Magasins</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.produits.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-boxes me-1"></i> Produits
    </x-bouton>
    @can('stock.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauMagasin" data-bs-toggle="modal" data-bs-target="#magasinModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau magasin
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .mag-page { --mg-bg-soft:var(--eim-cream); --mg-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--mg-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition:transform .2s; }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--mg-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--mg-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--mg-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--mg-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--mg-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--mg-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-magasins thead tr { background:var(--mg-bg-soft); }
    .table-magasins thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--mg-border); white-space:nowrap; }
    .table-magasins tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--mg-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-magasins tbody tr:hover { background:var(--eim-hover); }

    .magasin-libelle { font-weight:700; color:var(--school-ink); display:block; }
    .magasin-desc { font-size:.75rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }

    .badge-magasin { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-magasin.entrepot { background:#e0ecfc; color:#2563eb; }
    .badge-magasin.boutique { background:#fdf4e0; color:var(--school-gold-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-magasin { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-magasin { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--mg-bg-soft); border:1px solid var(--mg-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--mg-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--mg-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="mag-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <x-stat icone="fas fa-warehouse" couleur="blue" libelle="Magasins actifs">{{ $magasins->total() }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-4">
            <x-stat icone="fas fa-map-marker-alt" couleur="gold" libelle="Produits en stock (total)">{{ $magasins->sum('stocks_actuels_count') }}</x-stat>
        </div>
        <div class="col-sm-12 col-md-4 d-flex align-items-end justify-content-md-end">
            <x-compteur>{{ $magasins->total() }} magasin(s)</x-compteur>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Rechercher un magasin..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select filtre-input">
                        <option value="">— Type —</option>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['type'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                    <x-bouton variante="contour" href="{{ route('logistique.magasins.index') }}"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-magasins align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Magasin</th>
                            <th>Type</th>
                            <th>Responsable</th>
                            <th>Contact</th>
                            <th class="text-center">Produits en stock</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($magasins as $m)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.magasins.show', $m) }}" class="text-decoration-none">
                                    <span class="magasin-libelle">{{ $m->libelle }}</span>
                                    @if ($m->description)
                                        <span class="magasin-desc">{{ \Str::limit($m->description, 60) }}</span>
                                    @endif
                                </a>
                            </td>
                            <td>
                                <span class="badge-magasin {{ $m->type?->css() }}">
                                    <span class="badge-dot"></span>{{ $m->type?->label() }}
                                </span>
                            </td>
                            <td class="text-sm">{{ $m->responsable ?? '—' }}</td>
                            <td class="text-sm">
                                @if ($m->telephone)
                                    <div><i class="fas fa-phone me-1 text-muted"></i>{{ $m->telephone }}</div>
                                @endif
                                @if ($m->adresse)
                                    <div class="text-xs text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>{{ \Str::limit($m->adresse, 40) }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                <x-compteur>{{ $m->stocks_actuels_count ?? 0 }}</x-compteur>
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.magasins.show', $m) }}">
                                                <i class="fas fa-eye"></i> Voir détail
                                            </a>
                                        </li>
                                        @can('stock.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-magasin"
                                                    data-id="{{ $m->id }}"
                                                    data-libelle="{{ $m->libelle }}"
                                                    data-responsable="{{ $m->responsable }}"
                                                    data-description="{{ $m->description }}"
                                                    data-adresse="{{ $m->adresse }}"
                                                    data-telephone="{{ $m->telephone }}"
                                                    data-type="{{ $m->type?->value }}"
                                                    data-bs-toggle="modal" data-bs-target="#magasinModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('stock.gerer')
                                        <x-actions.supprimer :action="route('logistique.magasins.destroy', $m)" data-confirm-title="Désactiver ce magasin ?" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="6" icone="fas fa-warehouse">Aucun magasin enregistré.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$magasins->appends(request()->query())" /></div>

    @can('stock.gerer')
    <x-modale id="magasinModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-magasin" entete-class="modal-header-magasin" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="magasin-form">@csrf
                    <input type="hidden" name="_method" id="magasin-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="magasin-title"><i class="fas fa-warehouse me-2"></i>Nouveau magasin</x-slot:titre>
        <x-section titre="Identité" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="m-libelle" class="form-control" maxlength="150" required>
                    <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type" id="m-type" class="form-select" required>
                        @foreach ($types as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Responsable</x-libelle>
                    <input type="text" name="responsable" id="m-responsable" class="form-control" maxlength="150">
                </div>
                <div class="col-md-6">
                    <x-libelle>Téléphone</x-libelle>
                    <input type="text" name="telephone" id="m-telephone" class="form-control" maxlength="30">
                </div>
                <div class="col-12">
                    <x-libelle>Adresse</x-libelle>
                    <input type="text" name="adresse" id="m-adresse" class="form-control" maxlength="500">
                </div>
                <div class="col-12">
                    <x-libelle>Description</x-libelle>
                    <textarea name="description" id="m-description" rows="2" class="form-control" maxlength="1000"></textarea>
                </div>
            </div>
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
    const $form = $('#magasin-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauMagasin').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#magasin-method').val('POST');
        $('#magasin-title').html('<i class="fas fa-warehouse me-2"></i>Nouveau magasin');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-magasin', function () {
        currentId = $(this).data('id');
        $('#m-libelle').val($(this).data('libelle'));
        $('#m-responsable').val($(this).data('responsable'));
        $('#m-description').val($(this).data('description'));
        $('#m-adresse').val($(this).data('adresse'));
        $('#m-telephone').val($(this).data('telephone'));
        $('#m-type').val($(this).data('type'));
        $('#magasin-method').val('PUT');
        $('#magasin-title').html('<i class="fas fa-pen me-2"></i>Modifier le magasin');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('logistique/magasins') }}/${currentId}`
            : `{{ route('logistique.magasins.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#magasinModal').modal('hide');
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