{{-- resources/views/admin/scolarite/niveaux/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Niveaux')
@section('page_icon', 'fa-flag')
@section('page_title', 'Niveaux')

@section('breadcrumb')
    <li><a href="{{ route('tableau') }}">Accueil</a></li>
    <li>Scolarité</li>
    <li>Niveaux</li>
@endsection

@section('page_actions')
    @can('structure.gerer')
    <button type="button" class="btn-nouveau" id="btnNouveauNiveau" data-bs-toggle="modal" data-bs-target="#niveauModal">
        <i class="fas fa-plus-circle"></i> Nouveau niveau
    </button>
    @endcan
@endsection

@section('css')
<style>
    /* Jetons propres à cette page uniquement — school-red, gold, ff...
       viennent déjà de layouts/app.blade.php, non redéclarés ici. */
    .niveaux-page {
        --niveaux-bg-soft: var(--eim-cream);
        --niveaux-border: var(--eim-border);
    }

    .niveaux-card { border: none; border-radius: 16px; overflow: hidden; }
    .niveaux-card .card-header-niveaux { border-bottom: 1px solid var(--niveaux-border); }

    .filtre-label {
        font-size: .8rem; font-weight: 600; color: var(--eim-ink-soft);
        text-transform: uppercase; letter-spacing: .03em; margin-bottom: 6px;
    }
    .filtre-label i { color: var(--school-red); margin-right: 4px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--niveaux-border); padding: .55rem .9rem; }
    .search-input { padding-left: 34px !important; }

    .btn-filtrer {
        background: var(--school-red); color: #fff; border: none; border-radius: 10px;
        padding: .55rem 1rem; font-weight: 600; transition: all .2s ease;
    }

    .compteur-badge {
        display: inline-block; background: var(--niveaux-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
        border: 1px solid var(--niveaux-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(227,30,36,.25); transition: all .2s ease;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(227,30,36,.35); }

    .table-niveaux thead tr { background: var(--niveaux-bg-soft); }
    .table-niveaux thead th {
        color: var(--school-red); font-size: .78rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--niveaux-border); padding: .9rem 1rem;
    }
    .table-niveaux thead th i { margin-right: 6px; color: var(--school-gold-dark); }
    .table-niveaux tbody td { padding: .85rem 1rem; border-bottom: 1px solid var(--niveaux-border); font-size: .92rem; }
    .table-niveaux tbody tr:hover { background: var(--eim-hover); }
    .table-niveaux tbody tr:last-child td { border-bottom: none; }


    /* .btn-action / .dropdown-menu-actions : définies globalement dans
       layouts/app.blade.php — rien à redéclarer ici. */

    .modal-niveaux { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-niveaux {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border-bottom: none; padding: 1.1rem 1.5rem;
    }
    .modal-header-niveaux .modal-title { font-weight: 600; font-size: 1.05rem; }

    .form-section { background: var(--niveaux-bg-soft); border: 1px solid var(--niveaux-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section .form-control, .form-section .form-select { border-radius: 8px; border: 1px solid var(--niveaux-border); }
    .form-section .form-control:focus, .form-section .form-select:focus {
        border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(227,30,36,.1);
    }

    .btn-annuler { background: #fff; border: 1px solid var(--niveaux-border); color: var(--eim-ink-soft); border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--niveaux-bg-soft); }
</style>
@endsection

@section('contenu')
    <div class="niveaux-page">
        <div class="card niveaux-card shadow-sm">
            <div class="card-header card-header-niveaux bg-white py-4 px-4">
                {{-- Filtres GET classiques : recharge la page avec ?cycle_id=&search= --}}
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <x-libelle class="filtre-label"><i class="fas fa-layer-group"></i>Cycle</x-libelle>
                        <select name="cycle_id" class="form-select filtre-input">
                            <option value="">Tous les cycles</option>
                            @foreach ($cyclesOptions as $cycle)
                                <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>
                                    {{ $cycle->libelle }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <x-libelle class="filtre-label"><i class="fas fa-search"></i>Recherche</x-libelle>
                        <x-recherche name="search" placeholder="Libellé..." value="{{ request('search') }}" />
                    </div>
                    <div class="col-md-2">
                        <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i> Filtrer</x-bouton>
                    </div>
                    <div class="col-md-3 text-end">
                        <x-compteur>{{ $niveaux->total() }} niveau(x)</x-compteur>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <x-tableau class="table-niveaux table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><i class="fas fa-sort-numeric-down"></i>Ordre</th>
                                <th><i class="fas fa-flag"></i>Libellé</th>
                                <th><i class="fas fa-layer-group"></i>Cycle</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($niveaux as $niveau)
                                <tr>
                                    <td>{{ $niveau->numero_ordre }}</td>
                                    <td>{{ $niveau->libelle }}</td>
                                    <td>{{ $niveau->cycle?->libelle }}</td>
                                    <td class="text-center">
                                        <x-actions>
                                                @can('structure.gerer')
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-niveau"
                                                            data-id="{{ $niveau->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#niveauModal">
                                                        <i class="fas fa-pen"></i> Modifier
                                                    </button>
                                                </li>
                                                @endcan
                                                <li><hr class="dropdown-divider"></li>
                                                @can('structure.gerer')
                                                <x-actions.supprimer :action="route('scolarite.niveaux.destroy', $niveau)" data-confirm-title="Supprimer ce niveau ?" data-confirm-text="Il n'apparaîtra plus dans la liste.">Supprimer</x-actions.supprimer>
                                                @endcan
                                            </x-actions>
                                    </td>
                                </tr>
                            @empty
                                <x-vide colspan="4" icone="fas fa-flag" :paragraphe="false"><p class="mb-0">Aucun niveau ne correspond à ces critères.</p></x-vide>
                            @endforelse
                        </tbody>
                    </x-tableau>
            </div>
        </div>
        <div class="mt-3">
            <x-pagination :liste="$niveaux->appends(request()->query())" />
        </div>

        {{-- Modale unique, réutilisée pour l'ajout ET la modification --}}
        @can('structure.gerer')
        <x-modale id="niveauModal" data-bs-backdrop="static" aria-hidden="true" dialogue="" contenu-class="modal-niveaux" entete-class="modal-header-niveaux" corps-class="p-4" croix-blanche>
            <x-slot:formulaire id="niveau-form">@csrf
                        <input type="hidden" name="_method" id="niveau-method" value="POST"></x-slot:formulaire>
            <x-slot:titre id="niveau-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouveau niveau</x-slot:titre>
            <x-section>
                <div class="mb-3">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="niveau-libelle" class="form-control"
                           placeholder="Ex : 6ème, CP...">
                    <div class="text-danger small mt-1" id="niveau-libelle-error"></div>
                </div>

                <div class="mb-3">
                    <x-libelle requis>Cycle</x-libelle>
                    <select name="cycle_id" id="niveau-cycle" class="form-select">
                        <option value="">-- Choisir --</option>
                        @foreach ($cyclesOptions as $cycle)
                            <option value="{{ $cycle->id }}">{{ $cycle->libelle }}</option>
                        @endforeach
                    </select>
                    <div class="text-danger small mt-1" id="niveau-cycle-error"></div>
                </div>

                <div class="mb-3">
                    <x-libelle>Ordre</x-libelle>
                    <input type="number" name="numero_ordre" id="niveau-ordre" class="form-control" min="0">
                    <div class="text-danger small mt-1" id="niveau-ordre-error"></div>
                </div>

                <div class="mb-0">
                    <x-libelle>Description</x-libelle>
                    <textarea name="description" id="niveau-description" class="form-control" rows="2"></textarea>
                </div>
            </x-section>
            <x-slot:pied>
                <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
                <x-bouton type="submit"><i class="fas fa-save me-1"></i>Enregistrer</x-bouton>
            </x-slot:pied>
        </x-modale>
        @endcan
    </div>
@endsection

@push('js')
<script>
$(function () {
    // La fenêtre de saisie n'est rendue que pour qui peut gérer.
    if (!document.getElementById('niveauModal')) return;
    const modalEl        = document.getElementById('niveauModal');
    const modal          = new bootstrap.Modal(modalEl);
    const $form          = $('#niveau-form');
    const $title         = $('#niveau-modal-title');
    const $method        = $('#niveau-method');
    const $libelle       = $('#niveau-libelle');
    const $cycle         = $('#niveau-cycle');
    const $ordre         = $('#niveau-ordre');
    const $description   = $('#niveau-description');

    const fields = {
        libelle:      { input: $libelle,  error: $('#niveau-libelle-error') },
        cycle_id:     { input: $cycle,    error: $('#niveau-cycle-error') },
        numero_ordre: { input: $ordre,    error: $('#niveau-ordre-error') },
    };

    const storeUrl = '{{ route('scolarite.niveaux.store') }}';
    let currentId  = null;

    function clearErrors() {
        Object.values(fields).forEach(f => {
            f.input.removeClass('is-invalid');
            f.error.text('');
        });
    }

    function showErrors(errors) {
        Object.keys(errors).forEach(function (field) {
            if (fields[field]) {
                fields[field].input.addClass('is-invalid');
                fields[field].error.text(errors[field][0]);
            }
        });
    }

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        clearErrors();
    }

    $('#btnNouveauNiveau').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouveau niveau');
    });

    $(document).on('click', '.btn-edit-niveau', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le niveau');
        $method.val('PUT');

        $.getJSON(`{{ url('scolarite/niveaux') }}/${currentId}/edit`, function (data) {
            $libelle.val(data.niveau.libelle);
            $cycle.val(data.niveau.cycle_id);
            $ordre.val(data.niveau.numero_ordre);
            $description.val(data.niveau.description);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId ? `{{ url('scolarite/niveaux') }}/${currentId}` : storeUrl;

        $.ajax({
            url: url,
            method: 'POST', // POST + champ _method : Laravel lit le spoofing dans le corps de la requête
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush