@extends('admin.layouts.app')

@section('title', 'Professions')
@section('page_icon', 'fa-briefcase')
@section('page_title', 'Professions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Ressources Humaines</li>
    <li class="breadcrumb-item"><a href="{{ route('rh.employes.index') }}">Employés</a></li>
    <li class="breadcrumb-item active">Professions</li>
@endsection

@section('page_actions')
    <a href="{{ route('rh.employes.index') }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-users me-1"></i> Employés
    </a>
    @can('employes.gerer')
        <button type="button" class="btn btn-nouveau" id="btnNouvelleProfession"
                data-bs-toggle="modal" data-bs-target="#professionModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle profession
        </button>
    @endcan
@endsection

@section('css')
<style>
    .prof-page { --p-bg-soft:#faf6ee; --p-border:#ece4d6; }
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--p-border) !important; }
    .filtre-input { border-radius:10px; border:1px solid var(--p-border); padding:.55rem .9rem; font-size:.88rem; }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--p-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .compteur-badge { display:inline-block; background:var(--p-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--p-border); }

    .table-professions thead tr { background:var(--p-bg-soft); }
    .table-professions thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--p-border); }
    .table-professions tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--p-border); font-size:.9rem; vertical-align:middle; }
    .table-professions tbody tr:hover { background:#fdfaf5; }

    .prof-libelle { font-weight:700; color:var(--school-ink); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .num.grand { color:var(--school-red-dark); }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }

    .modal-prof { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-prof { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--p-bg-soft); border:1px solid var(--p-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control { border-radius:8px; border:1px solid var(--p-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--p-border); color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="prof-page">
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Rechercher une profession..."
                               value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-filtrer w-100">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    <span class="compteur-badge">{{ $professions->total() }} profession(s)</span>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-professions align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Profession</th>
                            <th class="text-end">Salaire de base</th>
                            <th class="text-center">Employés</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($professions as $profession)
                        <tr>
                            <td class="ps-4">
                                <span class="prof-libelle">{{ $profession->libelle }}</span>
                            </td>
                            <td class="num grand">
                                {{ number_format((float) $profession->salaire_base, 0, ',', ' ') }}
                            </td>
                            <td class="text-center">
                                <span class="compteur-badge">{{ $profession->employes_count ?? 0 }}</span>
                            </td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('employes.gerer')
                                            <li>
                                                <button class="dropdown-item btn-edit-profession"
                                                        data-id="{{ $profession->id }}"
                                                        data-libelle="{{ $profession->libelle }}"
                                                        data-salaire="{{ $profession->salaire_base }}"
                                                        data-bs-toggle="modal" data-bs-target="#professionModal">
                                                    <i class="fas fa-pen"></i> Modifier
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('rh.professions.destroy', $profession) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="4"><div class="empty-state"><i class="fas fa-briefcase"></i><p>Aucune profession enregistrée.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $professions->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    @can('employes.gerer')
    <div class="modal fade" id="professionModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-prof">
                <form id="profession-form">
                    @csrf
                    <input type="hidden" name="_method" id="prof-method" value="POST">
                    <div class="modal-header modal-header-prof">
                        <h5 class="modal-title" id="prof-title"><i class="fas fa-briefcase me-2"></i>Nouvelle profession</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                <input type="text" name="libelle" id="pf-libelle" class="form-control" maxlength="150" required>
                                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
                            </div>
                            <div>
                                <label class="form-label">Salaire de base <span class="text-danger">*</span></label>
                                <input type="number" name="salaire_base" id="pf-salaire" step="0.01" min="0" class="form-control" required>
                                <div class="text-danger text-xs mt-1" id="salaire_base-error"></div>
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
    const $form = $('#profession-form');
    if (!$form.length) return;
    let currentId = null;

    $('#btnNouvelleProfession').on('click', function () {
        currentId = null;
        $form[0].reset();
        $('#prof-method').val('POST');
        $('#prof-title').html('<i class="fas fa-briefcase me-2"></i>Nouvelle profession');
    });

    $(document).on('click', '.btn-edit-profession', function () {
        currentId = $(this).data('id');
        $('#pf-libelle').val($(this).data('libelle'));
        $('#pf-salaire').val($(this).data('salaire'));
        $('#prof-method').val('PUT');
        $('#prof-title').html('<i class="fas fa-pen me-2"></i>Modifier la profession');
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        const url = currentId
            ? `{{ url('rh/professions') }}/${currentId}`
            : `{{ route('rh.professions.store') }}`;

        $.ajax({
            url, method: 'POST', data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#professionModal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.keys(xhr.responseJSON.errors).forEach(f => $('#' + f + '-error').text(xhr.responseJSON.errors[f][0]));
                } else if (xhr.responseJSON?.message) {
                    window.showToast(xhr.responseJSON.message, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush