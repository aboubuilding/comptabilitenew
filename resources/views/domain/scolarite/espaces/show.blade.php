@extends('admin.layouts.app')

@section('page_title', $espace->nom_famille)
@section('page_icon', 'fa-people-roof')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('scolarite.espaces.index') }}">Parents & Familles</a></li>
    <li class="breadcrumb-item active">{{ $espace->nom_famille }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('scolarite.espaces.index') }}" class="btn-annuler">Retour</a>
@endsection

@section('contenu')
    <div class="row g-3">

        {{-- Parents --}}
        <div class="col-md-6">
            <div class="main-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="fa-user-tie"></i> Parents / Tuteurs</h6>
                    @can('familles.gerer')
                    <button type="button" class="btn-nouveau btn-sm"
                            id="btn-nouveau-parent"
                            data-espace="{{ $espace->id }}">
                        <i class="fa fa-plus"></i> Ajouter
                    </button>
                    @endcan
                </div>

                @forelse($espace->parents as $p)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $p->nom_complet }}</strong>
                                @if($p->is_principal)
                                    <span class="badge bg-primary ms-1">Principal</span>
                                @endif
                                @if($p->role)
                                    <br><small class="text-muted">
                                        {{ \App\Domain\Scolarite\Types\RoleParent::tryFrom($p->role)?->libelle() }}
                                    </small>
                                @endif
                            </div>
                            @can('familles.gerer')
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action" data-bs-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item btn-edit-parent"
                                                data-url="{{ route('scolarite.espaces.parents.edit', [$espace, $p]) }}">
                                            <i class="fa fa-edit"></i> Modifier
                                        </button>
                                    </li>
                                    @unless($p->is_principal)
                                        <li>
                                            <form action="{{ route('scolarite.espaces.parents.principal', [$espace, $p]) }}"
                                                  method="POST" class="form-confirm-delete"
                                                  data-confirm-title="Désigner ce parent comme principal ?"
                                                  data-confirm-text="Il remplacera le parent principal actuel de la famille.">
                                                @csrf
                                                <button type="submit" class="dropdown-item text-primary">
                                                    <i class="fa fa-star"></i> Désigner comme principal
                                                </button>
                                            </form>
                                        </li>
                                    @endunless
                                    <li>
                                        <form action="{{ route('scolarite.espaces.parents.destroy', [$espace, $p]) }}"
                                              method="POST" class="form-confirm-delete">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fa fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            @endcan
                        </div>
                        @if($p->telephone) <div class="small mt-1"><i class="fa fa-phone"></i> {{ $p->telephone }}</div> @endif
                        @if($p->email)     <div class="small"><i class="fa fa-envelope"></i> {{ $p->email }}</div> @endif
                        @if($p->quartier || $p->adresse)
                            <div class="small text-muted">
                                <i class="fa fa-map-marker-alt"></i>
                                {{ $p->quartier }}{{ $p->quartier && $p->adresse ? ' — ' : '' }}{{ $p->adresse }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted text-center py-3">Aucun parent enregistré.</div>
                @endforelse
            </div>
        </div>

        {{-- Comptes portail --}}
        <div class="col-md-6">
            <div class="main-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0"><i class="fa-key"></i> Compte portail parent</h6>
                    @if($espace->comptes->isEmpty())
                        @can('familles.gerer')
                        <button type="button" class="btn-nouveau btn-sm" id="btn-nouveau-compte">
                            <i class="fa fa-plus"></i> Créer un compte
                        </button>
                        @endcan
                    @endif
                </div>

                @forelse($espace->comptes as $c)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $c->email }}</strong>
                                <br>
                                <span class="badge-statut bg-{{ $c->etat ? 'success' : 'secondary' }}">
                                    {{ $c->etat ? 'Actif' : 'Inactif' }}
                                </span>
                                @if($c->statut_compte)
                                    <span class="badge-statut bg-info ms-1">
                                        {{ $c->statut_compte->libelle() }}
                                    </span>
                                @endif
                                @if($c->parent)
                                    <br><small class="text-muted">Titulaire : {{ $c->parent->nom_complet }}</small>
                                @endif
                            </div>
                            @can('familles.gerer')
                            <div class="dropdown">
                                <button class="btn btn-sm btn-action" data-bs-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                    <li>
                                        <button type="button" class="dropdown-item btn-edit-compte"
                                                data-url="{{ route('scolarite.espaces.comptes.edit', [$espace, $c]) }}">
                                            <i class="fa fa-edit"></i> Modifier
                                        </button>
                                    </li>
                                    <li>
                                        <form action="{{ route('scolarite.espaces.comptes.regenerer', [$espace, $c]) }}"
                                              method="POST" class="form-confirm-delete"
                                              data-confirm-title="Régénérer le mot de passe ?"
                                              data-confirm-text="L'ancien mot de passe ne fonctionnera plus ; le nouveau est envoyé par email au parent.">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-warning">
                                                <i class="fa fa-sync"></i> Régénérer le mot de passe
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('scolarite.espaces.comptes.destroy', [$espace, $c]) }}"
                                              method="POST" class="form-confirm-delete">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fa fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="text-muted text-center py-3">Aucun compte portail.</div>
                @endforelse
            </div>
        </div>

        {{-- Élèves de la fratrie --}}
        <div class="col-md-12">
            <div class="main-card p-3">
                <h6 class="mb-3"><i class="fa-user-graduate"></i> Élèves de la fratrie ({{ $espace->eleves->count() }})</h6>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Matricule</th>
                                <th>Nom & prénom</th>
                                <th>Sexe</th>
                                <th>Nationalité</th>
                                <th>Classe / Niveau · Cycle</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($espace->eleves as $e)
                                @php $insc = $e->inscriptionActive; @endphp
                                <tr>
                                    <td><code>{{ $e->matricule }}</code></td>
                                    <td><strong>{{ $e->nom }}</strong> {{ $e->prenom }}</td>
                                    <td>{{ $e->sexe?->initiale() ?? '—' }}</td>
                                    <td>{{ $e->nationalite->libelle ?? '—' }}</td>
                                    <td>
                                        @if($insc?->classe)
                                            <span class="badge-statut bg-primary">{{ $insc->classe->libelle }}</span>
                                        @else
                                            <span class="badge-statut bg-warning text-dark">Non affecté</span>
                                        @endif
                                        <small class="d-block text-muted">
                                            {{ $insc?->niveau?->libelle ?? '—' }}
                                            @if($insc?->cycle) · {{ $insc->cycle->libelle }} @endif
                                        </small>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Aucun élève rattaché.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modale parent --}}
    <div class="modal fade" id="modalParent" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalParentContent"></div>
        </div>
    </div>

    {{-- Modale compte --}}
    <div class="modal fade" id="modalCompte" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" id="modalCompteContent"></div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(function () {
    const espaceId = {{ $espace->id }};

    // ============================================================
    // Modale parent
    // ============================================================
    const modalParentEl  = document.getElementById('modalParent');
    const modalParent    = new bootstrap.Modal(modalParentEl);
    const $contenuParent = $('#modalParentContent');

    function chargerParent(url) {
        $contenuParent.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modalParent.show();
        $.get(url, html => $contenuParent.html(html))
         .fail(() => $contenuParent.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    }

    $('#btn-nouveau-parent').on('click', function () {
        chargerParent("{{ route('scolarite.espaces.parents.create', $espace) }}");
    });

    $(document).on('click', '.btn-edit-parent', function () { chargerParent($(this).data('url')); });

    $(document).on('submit', '#formParent', function (e) {
        e.preventDefault();
        const $form = $(this);
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize() + '&_method=' + methode,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modalParent.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    if (!$input.length) { window.showToast(messages[0], 'error'); return; }
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
            }
        });
    });

    modalParentEl.addEventListener('hidden.bs.modal', () => $contenuParent.html(''));

    // ============================================================
    // Modale compte
    // ============================================================
    const modalCompteEl  = document.getElementById('modalCompte');
    const modalCompte    = new bootstrap.Modal(modalCompteEl);
    const $contenuCompte = $('#modalCompteContent');

    function chargerCompte(url) {
        $contenuCompte.html('<div class="p-4 text-center"><i class="fa fa-spinner fa-spin"></i> Chargement...</div>');
        modalCompte.show();
        $.get(url, html => $contenuCompte.html(html))
         .fail(() => $contenuCompte.html('<div class="p-4 text-danger">Erreur de chargement.</div>'));
    }

    $('#btn-nouveau-compte').on('click', function () {
        chargerCompte("{{ route('scolarite.espaces.comptes.create', $espace) }}");
    });

    $(document).on('click', '.btn-edit-compte', function () { chargerCompte($(this).data('url')); });

    $(document).on('submit', '#formCompte', function (e) {
        e.preventDefault();
        const $form = $(this);
        const methode = $form.find('input[name="_method"]').val() || 'POST';

        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').remove();

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: $form.serialize() + '&_method=' + methode,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        })
        .done(r => {
            modalCompte.hide();
            if (window.showToastThenReload) window.showToastThenReload(r.message || 'OK');
            else window.location.reload();
        })
        .fail(xhr => {
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                $.each(xhr.responseJSON.errors, function (champ, messages) {
                    const $input = $form.find('[name="' + champ + '"]');
                    if (!$input.length) { window.showToast(messages[0], 'error'); return; }
                    $input.addClass('is-invalid');
                    $input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                });
            } else {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                if (window.showToast) window.showToast(msg, 'error'); else alert(msg);
            }
        });
    });

    modalCompteEl.addEventListener('hidden.bs.modal', () => $contenuCompte.html(''));
});
</script>
@endpush