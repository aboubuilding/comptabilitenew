@extends('admin.layouts.app')

@section('title', 'Clients')
@section('page_icon', 'fa-user-friends')
@section('page_title', 'Clients externes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('logistique.ventes.index') }}">Boutique</a></li>
    <li class="breadcrumb-item active">Clients</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('logistique.clients.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('ventes.gerer')
        <x-bouton variante="nouveau" type="button" id="btnNouveauClient" data-bs-toggle="modal" data-bs-target="#clientModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau client
        </x-bouton>
    @endcan
@endsection

@section('contenu')
<div class="container-fluid p-0">
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Nom, téléphone, email..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-3">
                    <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter me-1"></i> Filtrer</x-bouton>
                </div>
                <div class="col-md-3 text-end">
                    <x-compteur>{{ $clients->total() }} client(s)</x-compteur>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-ventes align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Nom</th>
                            <th>Type</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($clients as $c)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $c->nom_raison_sociale }}</td>
                            <td class="text-sm">{{ $c->type_client?->label() }}</td>
                            <td class="text-sm">{{ $c->telephone ?? '—' }}</td>
                            <td class="text-sm">{{ $c->email ?? '—' }}</td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        @can('ventes.gerer')
                                        <li>
                                            <button class="dropdown-item btn-edit-client"
                                                    data-id="{{ $c->id }}"
                                                    data-nom="{{ $c->nom_raison_sociale }}"
                                                    data-type="{{ $c->type_client?->value }}"
                                                    data-telephone="{{ $c->telephone }}"
                                                    data-email="{{ $c->email }}"
                                                    data-adresse="{{ $c->adresse }}"
                                                    data-nif="{{ $c->nif }}"
                                                    data-rccm="{{ $c->rccm }}"
                                                    data-bs-toggle="modal" data-bs-target="#clientModal">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        <li><hr class="dropdown-divider"></li>
                                        @can('ventes.gerer')
                                        <x-actions.supprimer :action="route('logistique.clients.destroy', $c)" />
                                        @endcan
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-user-friends">Aucun client.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$clients->appends(request()->query())" /></div>

    @can('ventes.gerer')
    <x-modale id="clientModal" data-bs-backdrop="static" contenu-class="modal-vente" entete-class="modal-header-vente" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="client-form">@csrf
                    <input type="hidden" name="_method" id="client-method" value="POST"></x-slot:formulaire>
        <x-slot:titre id="client-title"><i class="fas fa-user-plus me-2"></i>Nouveau client</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Nom / Raison sociale</x-libelle>
                <input type="text" name="nom_raison_sociale" id="c-nom" class="form-control" required maxlength="200">
            </div>
            <div class="mb-3">
                <x-libelle requis>Type</x-libelle>
                <select name="type_client" id="c-type" class="form-select">
                    @foreach ($types as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle>Téléphone</x-libelle>
                    <input type="text" name="telephone" id="c-tel" class="form-control" maxlength="30">
                </div>
                <div class="col-md-6">
                    <x-libelle>Email</x-libelle>
                    <input type="email" name="email" id="c-email" class="form-control" maxlength="150">
                </div>
                <div class="col-12">
                    <x-libelle>Adresse</x-libelle>
                    <input type="text" name="adresse" id="c-adresse" class="form-control" maxlength="1000">
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
    let currentId = null;
    const $form = $('#client-form');

    $('#btnNouveauClient').on('click', function () {
        currentId = null; $form[0].reset(); $('#client-method').val('POST');
        $('#client-title').html('<i class="fas fa-user-plus me-2"></i>Nouveau client');
    });

    $(document).on('click', '.btn-edit-client', function () {
        currentId = $(this).data('id');
        $('#c-nom').val($(this).data('nom'));
        $('#c-type').val($(this).data('type'));
        $('#c-tel').val($(this).data('telephone'));
        $('#c-email').val($(this).data('email'));
        $('#c-adresse').val($(this).data('adresse'));
        $('#client-method').val('PUT');
        $('#client-title').html('<i class="fas fa-pen me-2"></i>Modifier le client');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('logistique/clients') }}/${currentId}`
            : `{{ route('logistique.clients.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#clientModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush