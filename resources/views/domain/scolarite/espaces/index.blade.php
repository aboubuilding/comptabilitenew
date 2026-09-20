{{-- resources/views/admin/scolarite/espaces/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Parents & Familles')
@section('page_icon', 'fa-user-friends')
@section('page_title', 'Parents & Familles')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Scolarité</li>
    <li class="breadcrumb-item active">Parents & Familles</li>
@endsection

@section('page_actions')
    <button type="button" class="btn btn-nouveau" id="btnNouvelEspace" data-bs-toggle="modal" data-bs-target="#espaceModal">
        <i class="fas fa-plus-circle me-1"></i> Nouvel espace familial
    </button>
@endsection

@section('css')
<style>
    .espaces-page { --espaces-bg-soft: #faf6ee; --espaces-border: #ece4d6; }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--espaces-border) !important; }

    .filtre-input { border-radius: 10px; border: 1px solid var(--espaces-border); padding: .55rem .9rem; font-size: .88rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .85rem; }
    .search-input { padding-left: 36px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .55rem 1rem; font-weight: 600; font-size: .88rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }

    .compteur-badge {
        display: inline-block; background: var(--espaces-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px; border: 1px solid var(--espaces-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25);
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    .table-espaces thead tr { background: var(--espaces-bg-soft); }
    .table-espaces thead th {
        color: var(--school-red); font-size: .74rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--espaces-border); padding: .85rem .9rem;
    }
    .table-espaces tbody td { padding: .8rem .9rem; border-bottom: 1px solid var(--espaces-border); font-size: .88rem; color: var(--school-ink); vertical-align: middle; }
    .table-espaces tbody tr:hover { background: #fdfaf5; }
    .table-espaces tbody tr:last-child td { border-bottom: none; }

    .famille-nom { font-weight: 700; display: block; }
    .parent-principal { font-size: .82rem; color: var(--school-muted); }
    .parent-principal .fw { color: var(--school-ink); font-weight: 600; }

    .contact-line { font-size: .84rem; }
    .contact-line i { width: 14px; color: var(--school-muted); margin-right: 4px; }

    .badge-nb-eleves {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 28px; height: 26px; padding: 0 8px; border-radius: 999px;
        background: var(--espaces-bg-soft); color: var(--school-red); font-weight: 700; font-size: .82rem;
        border: 1px solid var(--espaces-border);
    }

    .badge-compte { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; padding: .35em .75em; border-radius: 999px; }
    .badge-compte-actif { background: #e3f7ec; color: #1c7a4d; }
    .badge-compte-inactif { background: #fbeaea; color: #b13b3b; }
    .badge-compte-absent { background: var(--espaces-bg-soft); color: var(--school-muted); }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

    .modal-espaces { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-espaces { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .modal-header-espaces .modal-title { font-weight: 600; font-size: 1.05rem; }
    .form-section { background: var(--espaces-bg-soft); border: 1px solid var(--espaces-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section .form-control { border-radius: 8px; border: 1px solid var(--espaces-border); }
    .btn-annuler { background: #fff; border: 1px solid var(--espaces-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--espaces-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem; }
    .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }

    /* .btn-action / .dropdown-menu-actions : définies globalement dans
       layouts/app.blade.php. */
</style>
@endsection

@section('contenu')
    <div class="espaces-page">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-7">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Nom de famille, parent ou téléphone..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="compteur-badge">{{ $espaces->total() }} espace(s)</span>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-espaces align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Famille / Parent principal</th>
                                <th>Contact</th>
                                <th class="text-center">Élèves</th>
                                <th>Quartier / Adresse</th>
                                <th>Compte portail</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($espaces as $espace)
                                @php
                                    $principal = $espace->parents->firstWhere('is_principal', true) ?? $espace->parents->first();
                                    $compte = $espace->comptes->first();
                                @endphp
                                <tr>
                                    <td>
                                        <span class="famille-nom">{{ $espace->nom_famille }}</span>
                                        @if ($principal)
                                            <span class="parent-principal">
                                                <i class="fas fa-user-shield me-1"></i>
                                                <span class="fw">{{ $principal->full_name }}</span>
                                            </span>
                                        @else
                                            <span class="parent-principal text-muted">Aucun parent rattaché</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($principal)
                                            <div class="contact-line"><i class="fas fa-phone"></i>{{ $principal->telephone ?? '—' }}</div>
                                            @if ($principal->whatsapp)
                                                <div class="contact-line"><i class="fab fa-whatsapp"></i>{{ $principal->whatsapp }}</div>
                                            @endif
                                            <div class="contact-line"><i class="fas fa-envelope"></i>{{ $principal->email ?? '—' }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-nb-eleves">{{ $espace->eleves_count }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">
                                            {{ $principal->quartier ?? '—' }}
                                            @if ($principal?->adresse)
                                                <br>{{ \Illuminate\Support\Str::limit($principal->adresse, 40) }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if (!$compte)
                                            <span class="badge-compte badge-compte-absent"><i class="fas fa-user-slash"></i>Aucun compte</span>
                                        @elseif ($compte->isActif())
                                            <span class="badge-compte badge-compte-actif"><i class="fas fa-check-circle"></i>Actif</span>
                                        @else
                                            <span class="badge-compte badge-compte-inactif"><i class="fas fa-times-circle"></i>Inactif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-espace"
                                                            data-id="{{ $espace->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#espaceModal">
                                                        <i class="fas fa-pen"></i> Renommer l'espace
                                                    </button>
                                                </li>
                                                <li>
                                                    {{-- TODO : écran de détail (parents, fratrie, compte), prochain tour --}}
                                                    <a href="#" class="dropdown-item">
                                                        <i class="fas fa-users-cog"></i> Gérer les parents &amp; la fratrie
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('scolarite.espaces.archiver', $espace) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Archiver cet espace familial ?"
                                                          data-confirm-text="Il n'apparaîtra plus dans les listes actives.">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-box-archive"></i> Archiver
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-user-friends"></i>
                                            <p class="mb-0">Aucun espace familial ne correspond à ces critères.</p>
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
            {{ $espaces->appends(request()->query())->links() }}
        </div>

        {{-- Modale unique, réutilisée pour l'ajout ET la modification —
             CRUD minimal de l'espace (nom_famille). La gestion des
             parents/fratrie/compte se fera sur un écran de détail dédié. --}}
        <div class="modal fade" id="espaceModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-espaces">
                    <form id="espace-form">
                        @csrf
                        <input type="hidden" name="_method" id="espace-method" value="POST">
                        <div class="modal-header modal-header-espaces">
                            <h5 class="modal-title" id="espace-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouvel espace familial</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section">
                                <label class="form-label">Nom de famille <span class="text-danger">*</span></label>
                                <input type="text" name="nom_famille" id="espace-nom-famille" class="form-control"
                                       placeholder="Ex : Famille Kouadio">
                                <div class="text-danger small mt-1" id="espace-nom_famille-error"></div>
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
    const modalEl  = document.getElementById('espaceModal');
    const modal    = new bootstrap.Modal(modalEl);
    const $form    = $('#espace-form');
    const $title   = $('#espace-modal-title');
    const $method  = $('#espace-method');
    const $nomFamille = $('#espace-nom-famille');
    const $nomFamilleError = $('#espace-nom_famille-error');

    const storeUrl = '{{ route('scolarite.espaces.store') }}';
    let currentId  = null;

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        $nomFamilleError.text('');
        $nomFamille.removeClass('is-invalid');
    }

    $('#btnNouvelEspace').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouvel espace familial');
    });

    $(document).on('click', '.btn-edit-espace', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Renommer l\'espace');
        $method.val('PUT');

        $.getJSON(`{{ url('scolarite/espaces') }}/${currentId}/edit`, function (data) {
            $nomFamille.val(data.espace.nom_famille);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $nomFamilleError.text('');
        $nomFamille.removeClass('is-invalid');

        const url = currentId ? `{{ url('scolarite/espaces') }}/${currentId}` : storeUrl;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors?.nom_famille) {
                    $nomFamille.addClass('is-invalid');
                    $nomFamilleError.text(xhr.responseJSON.errors.nom_famille[0]);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush