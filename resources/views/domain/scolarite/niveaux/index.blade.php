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
    <button type="button" class="btn-nouveau" id="btnNouveauNiveau" data-bs-toggle="modal" data-bs-target="#niveauModal">
        <i class="fas fa-plus-circle"></i> Nouveau niveau
    </button>
@endsection

@section('css')
<style>
    /* Jetons propres à cette page uniquement — school-red, gold, ff...
       viennent déjà de layouts/app.blade.php, non redéclarés ici. */
    .niveaux-page {
        --niveaux-bg-soft: #faf6ee;
        --niveaux-border: #ece4d6;
    }

    .niveaux-card { border: none; border-radius: 16px; overflow: hidden; }
    .niveaux-card .card-header-niveaux { border-bottom: 1px solid var(--niveaux-border); }

    .filtre-label {
        font-size: .8rem; font-weight: 600; color: #5b6b7a;
        text-transform: uppercase; letter-spacing: .03em; margin-bottom: 6px;
    }
    .filtre-label i { color: var(--school-red); margin-right: 4px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--niveaux-border); padding: .55rem .9rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .85rem; }
    .search-input { padding-left: 34px !important; }

    .btn-filtrer {
        background: var(--school-red); color: #fff; border: none; border-radius: 10px;
        padding: .55rem 1rem; font-weight: 600; transition: all .2s ease;
    }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }

    .compteur-badge {
        display: inline-block; background: var(--niveaux-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
        border: 1px solid var(--niveaux-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25); transition: all .2s ease;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    .table-niveaux thead tr { background: var(--niveaux-bg-soft); }
    .table-niveaux thead th {
        color: var(--school-red); font-size: .78rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--niveaux-border); padding: .9rem 1rem;
    }
    .table-niveaux thead th i { margin-right: 6px; color: var(--school-gold-dark); }
    .table-niveaux tbody td { padding: .85rem 1rem; border-bottom: 1px solid var(--niveaux-border); font-size: .92rem; }
    .table-niveaux tbody tr:hover { background: #fdfaf5; }
    .table-niveaux tbody tr:last-child td { border-bottom: none; }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

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
        border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.1);
    }

    .btn-annuler { background: #fff; border: 1px solid var(--niveaux-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--niveaux-bg-soft); }
    .btn-enregistrer {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff;
        border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem;
    }
    .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }
</style>
@endsection

@section('contenu')
    <div class="niveaux-page">
        <div class="card niveaux-card shadow-sm">
            <div class="card-header card-header-niveaux bg-white py-4 px-4">
                {{-- Filtres GET classiques : recharge la page avec ?cycle_id=&search= --}}
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label filtre-label"><i class="fas fa-layer-group"></i>Cycle</label>
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
                        <label class="form-label filtre-label"><i class="fas fa-search"></i>Recherche</label>
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Libellé..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i> Filtrer</button>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="compteur-badge">{{ $niveaux->total() }} niveau(x)</span>
                    </div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-niveaux table-hover align-middle mb-0">
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
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-niveau"
                                                            data-id="{{ $niveau->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#niveauModal">
                                                        <i class="fas fa-pen"></i> Modifier
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('scolarite.niveaux.destroy', $niveau) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Supprimer ce niveau ?"
                                                          data-confirm-text="Il n'apparaîtra plus dans la liste.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash"></i> Supprimer
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fas fa-flag"></i>
                                            <p class="mb-0">Aucun niveau ne correspond à ces critères.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">
            {{ $niveaux->appends(request()->query())->links() }}
        </div>

        {{-- Modale unique, réutilisée pour l'ajout ET la modification --}}
        <div class="modal fade" id="niveauModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-niveaux">
                    <form id="niveau-form">
                        @csrf
                        <input type="hidden" name="_method" id="niveau-method" value="POST">
                        <div class="modal-header modal-header-niveaux">
                            <h5 class="modal-title" id="niveau-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouveau niveau</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section">
                                <div class="mb-3">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="niveau-libelle" class="form-control"
                                           placeholder="Ex : 6ème, CP...">
                                    <div class="text-danger small mt-1" id="niveau-libelle-error"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Cycle <span class="text-danger">*</span></label>
                                    <select name="cycle_id" id="niveau-cycle" class="form-select">
                                        <option value="">-- Choisir --</option>
                                        @foreach ($cyclesOptions as $cycle)
                                            <option value="{{ $cycle->id }}">{{ $cycle->libelle }}</option>
                                        @endforeach
                                    </select>
                                    <div class="text-danger small mt-1" id="niveau-cycle-error"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ordre</label>
                                    <input type="number" name="numero_ordre" id="niveau-ordre" class="form-control" min="0">
                                    <div class="text-danger small mt-1" id="niveau-ordre-error"></div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="niveau-description" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i>Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
$(function () {
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