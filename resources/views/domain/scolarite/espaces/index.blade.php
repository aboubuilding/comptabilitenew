@extends('admin.layouts.app')

@section('page_title', 'Parents & Familles')
@section('page_icon', 'fa-people-roof')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Scolarité</a></li>
    <li class="breadcrumb-item active">Parents & Familles</li>
@endsection

@section('page_actions')
    @can('familles.gerer')
    <button type="button" class="btn-nouveau" id="btn-nouvel-espace">
        <i class="fa fa-plus"></i> Nouvel espace familial
    </button>
    <button type="button" class="btn-outline-scolarite ms-2" id="btn-regrouper">
        <i class="fa fa-object-group"></i> Regrouper une fratrie
    </button>
    @endcan
    <a href="{{ route('scolarite.espaces.export', request()->query()) }}" class="btn-outline-scolarite ms-2">
        <i class="fa fa-file-excel"></i> Exporter
    </a>
@endsection

@section('css')
<style>
    .stat-card { border-left: 4px solid var(--school-red); }
    .avatar-parent { width: 40px; height: 40px; border-radius: 50%; background: var(--school-gold);
                     color: white; display: inline-flex; align-items: center; justify-content: center;
                     font-weight: bold; }
</style>
@endsection

@section('contenu')

    <div class="alert alert-info d-flex align-items-center mb-3">
        <i class="fa fa-calendar-alt me-2"></i>
        <span>
            Espaces familiaux comptant au moins un élève <strong>inscrit pour l'année active</strong>, et espaces encore vides à compléter.
            @unless($anneeId)
                <span class="badge bg-warning text-dark ms-2">Aucune année active</span>
            @endunless
        </span>
    </div>

    {{-- Filtres --}}
    <div class="main-card p-3 mb-3">
        <form method="GET" action="{{ route('scolarite.espaces.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Recherche</label>
                <input type="text" name="recherche" class="form-control filtre-input"
                       value="{{ $filtres['recherche'] ?? '' }}"
                       placeholder="Nom famille, parent, téléphone, email...">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Compte portail</label>
                <select name="avec_compte" class="form-select filtre-input">
                    <option value="">Tous</option>
                    <option value="1" @selected(($filtres['avec_compte'] ?? '') === '1')>Avec compte actif</option>
                    <option value="0" @selected(($filtres['avec_compte'] ?? '') === '0')>Sans compte</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn-filtrer w-100"><i class="fa fa-filter"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('scolarite.espaces.index') }}" class="btn-annuler w-100">Réinitialiser</a>
            </div>
        </form>
    </div>

    {{-- Tableau --}}
    <div class="main-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Famille</th>
                        <th>Parent principal</th>
                        <th>Contact</th>
                        <th>Quartier / Adresse</th>
                        <th class="text-center">Élèves</th>
                        <th>Compte portail</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($espaces as $espace)
                        @php
                            $principal = $espace->parents->firstWhere('is_principal', 1) ?? $espace->parents->first();
                            $compte    = $espace->comptes->firstWhere('etat', 1);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('scolarite.espaces.show', $espace) }}" class="fw-bold">
                                    {{ $espace->nom_famille }}
                                </a>
                            </td>
                            <td>
                                @if($principal)
                                    <div class="d-flex align-items-center">
                                        <span class="avatar-parent me-2">
                                            {{ strtoupper(substr($principal->nom_parent ?? '?', 0, 1)) }}
                                        </span>
                                        <div>
                                            <strong>{{ $principal->nom_complet }}</strong>
                                            @if($principal->role)
                                                <br><small class="text-muted">
                                                    {{ \App\Domain\Scolarite\Types\RoleParent::tryFrom($principal->role)?->libelle() }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($principal?->telephone)
                                    <div><i class="fa fa-phone"></i> {{ $principal->telephone }}</div>
                                @endif
                                @if($principal?->whatsapp)
                                    <div><i class="fa fa-whatsapp text-success"></i> {{ $principal->whatsapp }}</div>
                                @endif
                                @if($principal?->email)
                                    <div><i class="fa fa-envelope"></i> <small>{{ $principal->email }}</small></div>
                                @endif
                            </td>
                            <td>
                                @if($principal?->quartier)
                                    <div>{{ $principal->quartier }}</div>
                                @endif
                                @if($principal?->adresse)
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($principal->adresse, 40) }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge-statut bg-info">
                                    {{ $espace->eleves_count }} élève{{ $espace->eleves_count > 1 ? 's' : '' }}
                                </span>
                            </td>
                            <td>
                                @if($compte)
                                    <span class="badge-statut bg-{{ $compte->etat ? 'success' : 'secondary' }}">
                                        {{ $compte->etat ? 'Actif' : 'Inactif' }}
                                    </span>
                                @else
                                    <span class="badge-statut bg-warning text-dark">Aucun</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-action" data-bs-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('scolarite.espaces.show', $espace) }}">
                                                <i class="fa fa-eye"></i> Consulter
                                            </a>
                                        </li>
                                        @can('familles.gerer')
                                        <li>
                                            <button type="button" class="dropdown-item btn-edit-espace"
                                                    data-url="{{ route('scolarite.espaces.edit', $espace) }}">
                                                <i class="fa fa-edit"></i> Modifier
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('scolarite.espaces.destroy', $espace) }}"
                                                  method="POST" class="form-confirm-delete">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fa fa-trash"></i> Supprimer
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
                                    <i class="fa fa-people-roof fa-2x text-muted"></i>
                                    <p class="mt-2 mb-0">Aucun espace familial pour l'année active.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $espaces->links('vendor.pagination.custom') }}
        </div>
    </div>

    {{-- Modale espace --}}
    <div class="modal fade" id="modalEspace" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalEspaceContent"></div>
        </div>
    </div>

    {{-- Modale regroupement --}}
    <div class="modal fade" id="modalRegrouper" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" id="modalRegrouperContent"></div>
        </div>
    </div>

@endsection

@push('js')
<script>
$(function () {
    // ============================================================
    // Modale espace
    // ============================================================
    const modalEspaceEl  = document.getElementById('modalEspace');
    const modalEspace    = new bootstrap.Modal(modalEspaceEl);
    const $contenuEspace = $('#modalEspaceContent');

    function chargerEspace(url) {
        $contenuEspace.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modalEspace.show();
        $.get(url, html => $contenuEspace.html(html))
         .fail(() => $contenuEspace.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    }

    $('#btn-nouvel-espace').on('click', () => chargerEspace("{{ route('scolarite.espaces.create') }}"));
    $(document).on('click', '.btn-edit-espace', function () { chargerEspace($(this).data('url')); });

    $(document).on('submit', '#formEspace', function (e) {
        e.preventDefault();
        const $form = $(this);
        const url   = $form.attr('action');
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $.ajax({
            url, type: 'POST',
            data: $form.serialize() + '&_method=' + methode,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modalEspace.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => afficherErreurs($form, xhr));
    });

    modalEspaceEl.addEventListener('hidden.bs.modal', () => $contenuEspace.html(''));

    // ============================================================
    // Modale regroupement
    // ============================================================
    const modalRegEl  = document.getElementById('modalRegrouper');
    const modalReg    = new bootstrap.Modal(modalRegEl);
    const $contenuReg = $('#modalRegrouperContent');

    $('#btn-regrouper').on('click', function () {
        $contenuReg.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modalReg.show();
        $.get("{{ route('scolarite.espaces.regrouper-form') }}", html => $contenuReg.html(html))
         .fail(() => $contenuReg.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    });

    $(document).on('submit', '#formRegrouper', function (e) {
        e.preventDefault();
        const $form = $(this);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modalReg.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => afficherErreurs($form, xhr));
    });

    modalRegEl.addEventListener('hidden.bs.modal', () => $contenuReg.html(''));

    // ============================================================
    // Utilitaire : affichage des erreurs 422
    // ============================================================
    function afficherErreurs($form, xhr) {
        if (xhr.status === 422 && xhr.responseJSON?.errors) {
            $.each(xhr.responseJSON.errors, function (champ, messages) {
                const $input = $form.find('[name="' + champ + '"]');
                $input.addClass('is-invalid');
                $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
            });
        } else {
            const msg = xhr.responseJSON?.message || 'Erreur.';
            if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
        }
    }
});
</script>
@endpush