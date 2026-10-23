@extends('admin.layouts.app')

@section('title', 'Fournisseurs')
@section('page_icon', 'fa-truck')
@section('page_title', 'Fournisseurs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Logistique &amp; Achats</li>
    <li class="breadcrumb-item active">Fournisseurs</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.fournisseurs.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Excel
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('logistique.fournisseurs.exportPdf', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-pdf me-1"></i> PDF
    </x-bouton>
    @can('fournisseurs.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauFournisseur" data-bs-toggle="modal" data-bs-target="#fournisseurModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau fournisseur
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .fourn-page { --fr-bg-soft:var(--eim-cream); --fr-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--fr-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition:transform .2s; box-shadow:0 2px 8px rgba(142,18,22,.04); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--fr-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--fr-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--fr-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--fr-border); }

    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-nouveau:hover { color:#fff; }
    .btn-outline-scolarite { border:1px solid var(--fr-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--fr-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-fourn thead tr { background:var(--fr-bg-soft); }
    .table-fourn thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--fr-border); white-space:nowrap; }
    .table-fourn tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--fr-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-fourn tbody tr:hover { background:var(--eim-hover); }

    .raison-sociale { font-weight:700; color:var(--school-ink); display:block; }
    .contact-sub { font-size:.75rem; color:var(--school-muted); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }
    .badge-count { display:inline-flex; align-items:center; justify-content:center; background:var(--fr-bg-soft); color:var(--school-red); font-weight:700; font-size:.75rem; padding:.35em .8em; border-radius:999px; min-width:36px; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-fourn { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-fourn { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--fr-bg-soft); border:1px solid var(--fr-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--fr-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--fr-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="fourn-page">

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-truck" couleur="gold" libelle="Fournisseurs actifs">{{ $fournisseurs->total() }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-3">
            <x-stat icone="fas fa-shopping-cart" couleur="blue" libelle="Achats cumulés">{{ $fournisseurs->sum('nombre_achats') }}</x-stat>
        </div>
        <div class="col-sm-12 col-md-6 text-md-end d-flex align-items-end justify-content-md-end">
            <x-compteur>{{ $fournisseurs->total() }} fiche(s)</x-compteur>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <x-recherche name="search" placeholder="Raison sociale, contact, téléphone, email..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-2">
                    <x-bouton variante="contour" href="{{ route('logistique.fournisseurs.index') }}" class="w-100">
                        <i class="fas fa-undo me-1"></i> Reset
                    </x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-fourn align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Raison sociale</th>
                            <th>Contact</th>
                            <th>Téléphone / Email</th>
                            <th class="text-center">Achats</th>
                            <th class="text-end">Montant total</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($fournisseurs as $f)
                        <tr>
                            <td class="ps-4">
                                <a href="{{ route('logistique.fournisseurs.show', $f) }}" class="text-decoration-none">
                                    <span class="raison-sociale">{{ $f->raison_social }}</span>
                                </a>
                            </td>
                            <td class="text-sm">
                                {{ $f->nom_contact ?? '—' }}
                                @if ($f->adresse)
                                    <div class="contact-sub"><i class="fas fa-map-marker-alt me-1"></i>{{ \Str::limit($f->adresse, 40) }}</div>
                                @endif
                            </td>
                            <td class="text-sm">
                                @if ($f->telephone_contact)
                                    <div><i class="fas fa-phone me-1 text-muted"></i>{{ $f->telephone_contact }}</div>
                                @endif
                                @if ($f->email_contact)
                                    <div><i class="fas fa-envelope me-1 text-muted"></i>{{ $f->email_contact }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-count">{{ $f->nombre_achats }}</span>
                            </td>
                            <td class="num grand">
                                {{ number_format($f->montant_total_achats, 2, ',', ' ') }}
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('logistique.fournisseurs.show', $f) }}">
                                                <i class="fas fa-eye"></i> Voir le détail
                                            </a>
                                        </li>
                                        @can('fournisseurs.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-fourn"
                                                    data-id="{{ $f->id }}"
                                                    data-raison="{{ $f->raison_social }}"
                                                    data-contact="{{ $f->nom_contact }}"
                                                    data-telephone="{{ $f->telephone_contact }}"
                                                    data-email="{{ $f->email_contact }}"
                                                    data-adresse="{{ $f->adresse }}"
                                                    data-nif="{{ $f->nif }}"
                                                    data-rccm="{{ $f->rccm }}"
                                                    data-bs-toggle="modal" data-bs-target="#fournisseurModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('fournisseurs.gerer')
                                        <x-actions.supprimer :action="route('logistique.fournisseurs.destroy', $f)" data-confirm-title="Désactiver ce fournisseur ?" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="6" icone="fas fa-truck">Aucun fournisseur.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$fournisseurs->appends(request()->query())" /></div>

    {{-- Modale création / édition --}}
    @can('fournisseurs.gerer')
    <x-modale id="fournisseurModal" data-bs-backdrop="static" dialogue="modal-dialog-centered modal-lg" contenu-class="modal-fourn" entete-class="modal-header-fourn" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="fournisseur-form">@csrf
                    <input type="hidden" name="_method" id="fournisseur-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="fournisseur-title"><i class="fas fa-truck me-2"></i>Nouveau fournisseur</x-slot:titre>
        <x-section titre="Identité" icone="fas fa-info-circle" class="mb-3">
            <div class="row g-3">
                <div class="col-md-8">
                    <x-libelle requis>Raison sociale</x-libelle>
                    <input type="text" name="raison_social" id="f-raison" class="form-control" maxlength="150">
                    <div class="text-danger text-xs mt-1" id="raison_social-error"></div>
                </div>
                <div class="col-md-4">
                    <x-libelle>Nom du contact</x-libelle>
                    <input type="text" name="nom_contact" id="f-contact" class="form-control" maxlength="150">
                </div>
            </div>
        </x-section>

        <x-section titre="Coordonnées" icone="fas fa-address-book" class="mb-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Téléphone</x-libelle>
                    <input type="text" name="telephone_contact" id="f-telephone" class="form-control" maxlength="30">
                </div>
                <div class="col-md-6">
                    <x-libelle>Email</x-libelle>
                    <input type="email" name="email_contact" id="f-email" class="form-control" maxlength="150">
                    <div class="text-danger text-xs mt-1" id="email_contact-error"></div>
                </div>
                <div class="col-12">
                    <x-libelle>Adresse</x-libelle>
                    <textarea name="adresse" id="f-adresse" rows="2" class="form-control" maxlength="1000"></textarea>
                </div>
            </div>
        </x-section>

        <x-section titre="Informations fiscales (facultatif)" icone="fas fa-file-invoice">
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>NIF</x-libelle>
                    <input type="text" name="nif" id="f-nif" class="form-control" maxlength="50">
                </div>
                <div class="col-md-6">
                    <x-libelle>RCCM</x-libelle>
                    <input type="text" name="rccm" id="f-rccm" class="form-control" maxlength="50">
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
    const $form = $('#fournisseur-form');
    if (!$form.length) return;

    let currentId = null;

    $('#btnNouveauFournisseur').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#fournisseur-method').val('POST');
        $('#fournisseur-title').html('<i class="fas fa-truck me-2"></i>Nouveau fournisseur');
        clearErrors();
    });

    $(document).on('click', '.btn-edit-fourn', function () {
        currentId = $(this).data('id');
        $('#f-raison').val($(this).data('raison'));
        $('#f-contact').val($(this).data('contact'));
        $('#f-telephone').val($(this).data('telephone'));
        $('#f-email').val($(this).data('email'));
        $('#f-adresse').val($(this).data('adresse'));
        $('#f-nif').val($(this).data('nif'));
        $('#f-rccm').val($(this).data('rccm'));
        $('#fournisseur-method').val('PUT');
        $('#fournisseur-title').html('<i class="fas fa-pen me-2"></i>Modifier le fournisseur');
        clearErrors();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId
            ? `{{ url('logistique/fournisseurs') }}/${currentId}`
            : `{{ route('logistique.fournisseurs.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#fournisseurModal').modal('hide');
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