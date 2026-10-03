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
    <a href="{{ route('logistique.clients.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('ventes.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouveauClient"
                data-bs-toggle="modal" data-bs-target="#clientModal">
            <i class="fas fa-plus-circle me-1"></i> Nouveau client
        </button>
    @endcan
@endsection

@section('contenu')
<div class="container-fluid p-0">
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Nom, téléphone, email..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-3 text-end">
                    <span class="compteur-badge">{{ $clients->total() }} client(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-ventes align-middle mb-0">
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
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
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
                                        <li>
                                            <form action="{{ route('logistique.clients.destroy', $c) }}" method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button class="dropdown-item text-danger"><i class="fas fa-trash"></i> Désactiver</button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-user-friends"></i><p>Aucun client.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $clients->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('ventes.gerer')
    <div class="modal fade" id="clientModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-vente">
                <form id="client-form">
                    @csrf
                    <input type="hidden" name="_method" id="client-method" value="POST">
                    <div class="modal-header modal-header-vente">
                        <h5 class="modal-title" id="client-title"><i class="fas fa-user-plus me-2"></i>Nouveau client</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Nom / Raison sociale <span class="text-danger">*</span></label>
                                <input type="text" name="nom_raison_sociale" id="c-nom" class="form-control" required maxlength="200">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type_client" id="c-type" class="form-select">
                                    @foreach ($types as $v => $l)
                                        <option value="{{ $v }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Téléphone</label>
                                    <input type="text" name="telephone" id="c-tel" class="form-control" maxlength="30">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" id="c-email" class="form-control" maxlength="150">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" name="adresse" id="c-adresse" class="form-control" maxlength="1000">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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