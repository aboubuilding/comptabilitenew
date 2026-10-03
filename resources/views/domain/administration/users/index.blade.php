@extends('admin.layouts.app')

@section('page_title', 'Utilisateurs & Profils')
@section('page_icon', 'fa-users-gear')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Administration</a></li>
    <li class="breadcrumb-item active">Utilisateurs</li>
@endsection

@section('page_actions')
    @can('utilisateurs.gerer')
    <button type="button" class="btn-nouveau" id="btn-nouvel-user">
        <i class="fa fa-plus"></i> Nouvel utilisateur
    </button>
    @endcan
    <a href="{{ route('administration.users.export', request()->query()) }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
@endsection

@section('contenu')

    {{-- Filtres --}}
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('administration.users.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Profil</label>
                <select name="role_id" class="form-select filtre-input">
                    <option value="">Tous</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected(($filtres['role_id'] ?? '') == $role->id)>
                            {{ $role->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Recherche</label>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}"
                       placeholder="Nom, login ou email...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn-filtrer w-100">
                    <i class="fa fa-filter"></i> Filtrer
                </button>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Photo</th>
                        <th>Nom</th>
                        <th>Login</th>
                        <th>Profil</th>
                        <th>Statut</th>
                        <th>Dernière connexion</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                @if($user->photo)
                                    <img src="{{ Storage::url($user->photo) }}" class="rounded-circle" width="36" height="36" alt="">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                         style="width:36px;height:36px;">
                                        {{ strtoupper(substr($user->nom ?? '?', 0, 1)) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $user->nom }}</strong> {{ $user->prenom }}
                            </td>
                            <td>{{ $user->login }}</td>
                            <td>
                                <span class="badge-statut bg-primary">
                                    {{ $user->roleRelation->libelle ?? '—' }}
                                </span>
                                @foreach ($user->profilsComplementaires as $complementaire)
                                    <span class="badge-statut bg-secondary">+ {{ $complementaire->libelle }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge-statut bg-{{ $user->etat ? 'success' : 'secondary' }}">
                                    {{ $user->etat ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td>
                                @if($user->last_login_at)
                                    <div>{{ $user->last_login_at->format('d/m/Y H:i') }}</div>
                                    <small class="text-muted">{{ $user->last_login_ip }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-action" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('utilisateurs.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-user"
                                                    data-url="{{ route('administration.users.edit', $user) }}">
                                                <i class="fa fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        @endcan
                                        @can('utilisateurs.gerer')
                                        <li>
                                            <form action="{{ route('administration.users.destroy', $user) }}"
                                                  method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fa fa-trash"></i> Désactiver
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state text-center py-4">
                                    <i class="fa fa-users fa-2x text-muted"></i>
                                    <p class="mt-2 mb-0">Aucun utilisateur.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $users->links('vendor.pagination.custom') }}
        </div>
    </div>

    {{-- Modale --}}
    <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalUserContent"></div>
        </div>
    </div>

@endsection

@push('js')
<script>
$(function () {
    const modalEl  = document.getElementById('modalUser');
    const modal    = new bootstrap.Modal(modalEl);
    const $contenu = $('#modalUserContent');

    function chargerForm(url) {
        $contenu.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modal.show();
        $.get(url, function (html) { $contenu.html(html); })
         .fail(function () { $contenu.html('<div class="p-4 text-danger">Erreur de chargement.</div>'); });
    }

    $('#btn-nouvel-user').on('click', function () {
        chargerForm("{{ route('administration.users.create') }}");
    });

    $(document).on('click', '.btn-edit-user', function () {
        chargerForm($(this).data('url'));
    });

    // Soumission AJAX (multipart pour la photo)
    $(document).on('submit', '#formUser', function (e) {
        e.preventDefault();

        const $form   = $(this);
        const url     = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        const formData = new FormData($form[0]);
        if (methode !== 'POST') {
            formData.append('_method', methode);
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(function (reponse) {
            modal.hide();
            if (window.showToastThenReload) {
                window.showToastThenReload(reponse.message || 'Opération réussie.');
            } else {
                window.location.reload();
            }
        })
        .fail(function (xhr) {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Une erreur est survenue.';
                if (window.showToast) { window.showToast(msg, 'error'); }
                else { alert(msg); }
            }
        });
    });

    modalEl.addEventListener('hidden.bs.modal', function () { $contenu.html(''); });
});
</script>
@endpush