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
    <button type="button" class="btn-nouveau" id="btnNouveau" data-bs-toggle="modal" data-bs-target="#cycleModal">
        <i class="fas fa-plus-circle"></i> Nouveau cycle
    </button>
@endsection

@section('css')
    <style>
        /* Jetons propres à cette page uniquement — school-red, gold, ff...
           viennent déjà de layouts/app.blade.php, non redéclarés ici. */
        .cycles-page {
            --cycles-bg-soft: #faf6ee;
            --cycles-border: #ece4d6;
        }

        .cycles-card { border: none; border-radius: 16px; overflow: hidden; }
        .cycles-card .card-header-cycles { border-bottom: 1px solid var(--cycles-border); }

        .filtre-label {
            font-size: .8rem; font-weight: 600; color: #5b6b7a;
            text-transform: uppercase; letter-spacing: .03em; margin-bottom: 6px;
        }
        .filtre-label i { color: var(--school-red); margin-right: 4px; }
        .filtre-input { border-radius: 10px; border: 1px solid var(--cycles-border); padding: .55rem .9rem; }
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
            display: inline-block; background: var(--cycles-bg-soft); color: var(--school-red);
            font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
            border: 1px solid var(--cycles-border);
        }

        .btn-nouveau {
            background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
            color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
            box-shadow: 0 4px 14px rgba(200,30,58,.25); transition: all .2s ease;
        }
        .btn-nouveau:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); color: #fff; }

        .table-cycles thead tr { background: var(--cycles-bg-soft); }
        .table-cycles thead th {
            color: var(--school-red); font-size: .78rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; border-bottom: 2px solid var(--cycles-border); padding: .9rem 1rem;
        }
        .table-cycles thead th i { margin-right: 6px; color: var(--school-gold-dark); }
        .table-cycles tbody td { padding: .85rem 1rem; border-bottom: 1px solid var(--cycles-border); font-size: .92rem; }
        .table-cycles tbody tr:hover { background: #fdfaf5; }
        .table-cycles tbody tr:last-child td { border-bottom: none; }

        /* Bouton d'action "..." et menu déroulant : classes .btn-action /
           .dropdown-menu-actions, définies globalement dans
           layouts/app.blade.php — rien à redéclarer ici. */

        .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
        .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

        .modal-cycles { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header-cycles {
            background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
            color: #fff; border-bottom: none; padding: 1.1rem 1.5rem;
        }
        .modal-header-cycles .modal-title { font-weight: 600; font-size: 1.05rem; }

        .form-section { background: var(--cycles-bg-soft); border: 1px solid var(--cycles-border); border-radius: 12px; padding: 1rem 1.1rem; }
        .form-section .form-control { border-radius: 8px; border: 1px solid var(--cycles-border); }
        .form-section .form-control:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.1); }

        .btn-annuler { background: #fff; border: 1px solid var(--cycles-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
        .btn-annuler:hover { background: var(--cycles-bg-soft); }
        .btn-enregistrer {
            background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff;
            border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem;
        }
        .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }
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
                            <div class="col-md-4 text-end">
                                <span class="compteur-badge">{{ $cycles->total() }} cycle(s)</span>
                            </div>
                        </form>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-cycles table-hover align-middle mb-0">
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
                                                <div class="dropdown">
                                                    <button class="btn-action dropdown-toggle" type="button"
                                                            data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                        <li>
                                                            <button type="button" class="dropdown-item btn-edit-cycle"
                                                                    data-id="{{ $cycle->id }}"
                                                                    data-bs-toggle="modal" data-bs-target="#cycleModal">
                                                                <i class="fas fa-pen"></i> Modifier
                                                            </button>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('scolarite.cycles.destroy', $cycle) }}"
                                                                  method="POST" class="form-confirm-delete"
                                                                  data-confirm-title="Supprimer ce cycle ?"
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
                                            <td colspan="3">
                                                <div class="empty-state">
                                                    <i class="fas fa-layer-group"></i>
                                                    <p class="mb-0">Aucun cycle ne correspond à ces critères.</p>
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
                    {{ $cycles->appends(request()->query())->links() }}
                </div>
            </div>
        </div>

        {{-- Modale unique, réutilisée pour l'ajout ET la modification —
             plus de champ etat, on crée/modifie toujours un cycle actif. --}}
        <div class="modal fade" id="cycleModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-cycles">
                    <form id="cycle-form">
                        @csrf
                        <input type="hidden" name="_method" id="cycle-method" value="POST">
                        <div class="modal-header modal-header-cycles">
                            <h5 class="modal-title" id="cycle-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouveau cycle</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section">
                                <div class="mb-0">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="cycle-libelle" class="form-control"
                                           placeholder="Ex : Primaire, Secondaire...">
                                    <div class="text-danger small mt-1" id="cycle-libelle-error"></div>
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