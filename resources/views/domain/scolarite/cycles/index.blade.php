{{-- resources/views/admin/scolarite/cycles/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Cycles')
@section('page_icon', 'fa-layer-group')
@section('page_title', 'Cycles')

@section('breadcrumb')
    <li><a href="{{ route('tableau') }}">Accueil</a></li>
    <li>Scolarité</li>
    <li>Cycles</li>
@endsection

@section('page_actions')
    @can('structure.gerer')
    <button type="button" class="btn-nouveau" id="btnNouveau" data-bs-toggle="modal" data-bs-target="#cycleModal">
        <i class="fas fa-plus-circle"></i> Nouveau cycle
    </button>
    @endcan
@endsection

@section('css')
    <style>
        /* Jetons propres à cette page uniquement — school-red, gold, ff...
           viennent déjà de layouts/app.blade.php, non redéclarés ici. */
        .cycles-page {
            --cycles-bg-soft: var(--eim-cream);
            --cycles-border: var(--eim-border);
        }

        .cycles-card { border: none; border-radius: 16px; overflow: hidden; }
        .cycles-card .card-header-cycles { border-bottom: 1px solid var(--cycles-border); }

        .filtre-label {
            font-size: .8rem; font-weight: 600; color: var(--eim-ink-soft);
            text-transform: uppercase; letter-spacing: .03em; margin-bottom: 6px;
        }
        .filtre-label i { color: var(--school-red); margin-right: 4px; }
        .filtre-input { border-radius: 10px; border: 1px solid var(--cycles-border); padding: .55rem .9rem; }
        .search-input { padding-left: 34px !important; }

        .btn-filtrer {
            background: var(--school-red); color: #fff; border: none; border-radius: 10px;
            padding: .55rem 1rem; font-weight: 600; transition: all .2s ease;
        }

        .compteur-badge {
            display: inline-block; background: var(--cycles-bg-soft); color: var(--school-red);
            font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
            border: 1px solid var(--cycles-border);
        }

        .btn-nouveau {
            background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
            color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
            box-shadow: 0 4px 14px rgba(227,30,36,.25); transition: all .2s ease;
        }

        .table-cycles thead tr { background: var(--cycles-bg-soft); }
        .table-cycles thead th {
            color: var(--school-red); font-size: .78rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; border-bottom: 2px solid var(--cycles-border); padding: .9rem 1rem;
        }
        .table-cycles thead th i { margin-right: 6px; color: var(--school-gold-dark); }
        .table-cycles tbody td { padding: .85rem 1rem; border-bottom: 1px solid var(--cycles-border); font-size: .92rem; }
        .table-cycles tbody tr:hover { background: var(--eim-hover); }
        .table-cycles tbody tr:last-child td { border-bottom: none; }

        /* Bouton d'action "..." et menu déroulant : classes .btn-action /
           .dropdown-menu-actions, définies globalement dans
           layouts/app.blade.php — rien à redéclarer ici. */


        .modal-cycles { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header-cycles {
            background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
            color: #fff; border-bottom: none; padding: 1.1rem 1.5rem;
        }
        .modal-header-cycles .modal-title { font-weight: 600; font-size: 1.05rem; }

        .form-section { background: var(--cycles-bg-soft); border: 1px solid var(--cycles-border); border-radius: 12px; padding: 1rem 1.1rem; }
        .form-section .form-control { border-radius: 8px; border: 1px solid var(--cycles-border); }
        .form-section .form-control:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(227,30,36,.1); }

        .btn-annuler { background: #fff; border: 1px solid var(--cycles-border); color: var(--eim-ink-soft); border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
        .btn-annuler:hover { background: var(--cycles-bg-soft); }
    </style>
@endsection

@section('contenu')
    <div class="cycles-page">
        <div class="row">
            <div class="col-12">
                <div class="card cycles-card shadow-sm">
                    <div class="card-header card-header-cycles bg-white py-4 px-4">
                        {{-- Filtre GET classique : recharge la page avec ?search=,
                             pas de rendu JS côté client. Plus de filtre Statut :
                             la liste ne montre que les cycles actifs. --}}
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <x-libelle class="filtre-label"><i class="fas fa-search"></i>Recherche</x-libelle>
                                <x-recherche name="search" placeholder="Libellé..." value="{{ request('search') }}" />
                            </div>
                            <div class="col-md-2">
                                <x-bouton variante="filtrer" type="submit" class="w-100"><i class="fas fa-filter"></i> Filtrer</x-bouton>
                            </div>
                            <div class="col-md-4 text-end">
                                <x-compteur>{{ $cycles->total() }} cycle(s)</x-compteur>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <x-tableau class="table-cycles table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-tag"></i>Libellé</th>
                                        <th><i class="fas fa-calendar"></i>Créé le</th>
                                        <th class="text-center" style="width:70px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($cycles as $cycle)
                                        <tr>
                                            <td>{{ $cycle->libelle }}</td>
                                            <td>{{ $cycle->created_at?->format('d/m/Y') }}</td>
                                            <td class="text-center">
                                                <x-actions>
                                                        @can('structure.gerer')
                                                        <li>
                                                            <button type="button" class="dropdown-item btn-edit-cycle"
                                                                    data-id="{{ $cycle->id }}"
                                                                    data-bs-toggle="modal" data-bs-target="#cycleModal">
                                                                <i class="fas fa-pen"></i> Modifier
                                                            </button>
                                                        </li>
                                                        @endcan
                                                        <li><hr class="dropdown-divider"></li>
                                                        @can('structure.gerer')
                                                        <x-actions.supprimer :action="route('scolarite.cycles.destroy', $cycle)" data-confirm-title="Supprimer ce cycle ?" data-confirm-text="Il n'apparaîtra plus dans la liste.">Supprimer</x-actions.supprimer>
                                                        @endcan
                                                    </x-actions>
                                            </td>
                                        </tr>
                                    @empty
                                        <x-vide colspan="3" icone="fas fa-layer-group" :paragraphe="false"><p class="mb-0">Aucun cycle ne correspond à ces critères.</p></x-vide>
                                    @endforelse
                                </tbody>
                            </x-tableau>
                    </div>
                </div>
                <div class="mt-3">
                    <x-pagination :liste="$cycles->appends(request()->query())" />
                </div>
            </div>
        </div>

        {{-- Modale unique, réutilisée pour l'ajout ET la modification —
             plus de champ etat, on crée/modifie toujours un cycle actif. --}}
        @can('structure.gerer')
        <x-modale id="cycleModal" data-bs-backdrop="static" aria-hidden="true" dialogue="" contenu-class="modal-cycles" entete-class="modal-header-cycles" corps-class="p-4" croix-blanche>
            <x-slot:formulaire id="cycle-form">@csrf
                        <input type="hidden" name="_method" id="cycle-method" value="POST"></x-slot:formulaire>
            <x-slot:titre id="cycle-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouveau cycle</x-slot:titre>
            <x-section>
                <div class="mb-0">
                    <x-libelle requis>Libellé</x-libelle>
                    <input type="text" name="libelle" id="cycle-libelle" class="form-control"
                           placeholder="Ex : Primaire, Secondaire...">
                    <div class="text-danger small mt-1" id="cycle-libelle-error"></div>
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
    if (!document.getElementById('cycleModal')) return;
    const modalEl      = document.getElementById('cycleModal');
    const modal        = new bootstrap.Modal(modalEl);
    const $form        = $('#cycle-form');
    const $title       = $('#cycle-modal-title');
    const $method       = $('#cycle-method');
    const $libelle      = $('#cycle-libelle');
    const $libelleError = $('#cycle-libelle-error');

    const storeUrl = '{{ route('scolarite.cycles.store') }}';
    let currentId  = null;

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        $libelleError.text('');
        $libelle.removeClass('is-invalid');
    }

    $('#btnNouveau').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouveau cycle');
    });

    $(document).on('click', '.btn-edit-cycle', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le cycle');
        $method.val('PUT');

        $.getJSON(`{{ url('scolarite/cycles') }}/${currentId}/edit`, function (data) {
            $libelle.val(data.cycle.libelle);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $libelleError.text('');
        $libelle.removeClass('is-invalid');

        const url = currentId ? `{{ url('scolarite/cycles') }}/${currentId}` : storeUrl;

        $.ajax({
            url: url,
            method: 'POST', // POST + champ _method : Laravel lit le spoofing dans le corps de la requête
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors?.libelle) {
                    $libelle.addClass('is-invalid');
                    $libelleError.text(xhr.responseJSON.errors.libelle[0]);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush