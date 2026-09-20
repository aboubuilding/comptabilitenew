{{-- resources/views/admin/finances/plan-echeanciers/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', "Plans d'échéancier")
@section('page_icon', 'fa-calendar-alt')
@section('page_title', "Plans d'échéancier")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.frais-ecoles.index') }}">Finances</a></li>
    <li class="breadcrumb-item active">Plans d'échéancier</li>
@endsection

@section('page_actions')
    <button type="button" class="btn btn-nouveau" id="btnNouveauPlan" data-bs-toggle="modal" data-bs-target="#planModal">
        <i class="fas fa-plus-circle me-1"></i> Nouveau plan
    </button>
@endsection

@section('css')
<style>
    .plans-page { --plans-bg-soft: #faf6ee; --plans-border: #ece4d6; }
    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .table-plans thead tr { background: var(--plans-bg-soft); }
    .table-plans thead th { color: var(--school-red); font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--plans-border); padding: .85rem .9rem; }
    .table-plans tbody td { padding: .8rem .9rem; border-bottom: 1px solid var(--plans-border); font-size: .88rem; }
    .table-plans tbody tr:hover { background: #fdfaf5; }
    .table-plans tbody tr:last-child td { border-bottom: none; }
    .badge-lignes-count { background: var(--plans-bg-soft); color: var(--school-red); font-weight: 700; font-size: .8rem; padding: .3em .7em; border-radius: 999px; border: 1px solid var(--plans-border); }
    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }
    .btn-nouveau { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600; box-shadow: 0 4px 14px rgba(200,30,58,.25); }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }
    .modal-plans { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-plans { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .form-section { background: var(--plans-bg-soft); border: 1px solid var(--plans-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section .form-control { border-radius: 8px; border: 1px solid var(--plans-border); }
    .btn-annuler { background: #fff; border: 1px solid var(--plans-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--plans-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem; }
</style>
@endsection

@section('contenu')
    <div class="plans-page">
        <div class="card main-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-plans align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nom du plan</th>
                                <th>Description</th>
                                <th class="text-center">Échéances</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($plans as $plan)
                                <tr>
                                    <td><strong>{{ $plan->nom }}</strong></td>
                                    <td class="text-muted small">{{ \Illuminate\Support\Str::limit($plan->description, 60) ?: '—' }}</td>
                                    <td class="text-center"><span class="badge-lignes-count">{{ $plan->lignes_count }}</span></td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-plan"
                                                            data-id="{{ $plan->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#planModal">
                                                        <i class="fas fa-pen"></i> Modifier
                                                    </button>
                                                </li>
                                                <li>
                                                    {{-- TODO : gestion des lignes (échéances), prochain tour --}}
                                                    <a href="#" class="dropdown-item">
                                                        <i class="fas fa-list-ol"></i> Gérer les échéances
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('finances.plan-echeanciers.archiver', $plan) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Archiver ce plan ?"
                                                          data-confirm-text="Les frais qui l'utilisent perdront leur échéancier associé.">
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
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-alt"></i>
                                            <p class="mb-0">Aucun plan d'échéancier pour l'instant.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $plans->links() }}</div>

        <div class="modal fade" id="planModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-plans">
                    <form id="plan-form">
                        @csrf
                        <input type="hidden" name="_method" id="plan-method" value="POST">
                        <div class="modal-header modal-header-plans">
                            <h5 class="modal-title" id="plan-modal-title"><i class="fas fa-plus-circle me-2"></i>Nouveau plan</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section">
                                <div class="mb-3">
                                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                                    <input type="text" name="nom" id="plan-nom" class="form-control" placeholder="Ex : 3 tranches trimestrielles">
                                    <div class="text-danger small mt-1" id="plan-nom-error"></div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" id="plan-description" class="form-control" rows="2"></textarea>
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
    const modalEl  = document.getElementById('planModal');
    const modal    = new bootstrap.Modal(modalEl);
    const $form    = $('#plan-form');
    const $title   = $('#plan-modal-title');
    const $method  = $('#plan-method');
    const $nom     = $('#plan-nom');
    const $nomError = $('#plan-nom-error');

    const storeUrl = '{{ route('finances.plan-echeanciers.store') }}';
    let currentId  = null;

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        $nomError.text('');
        $nom.removeClass('is-invalid');
    }

    $('#btnNouveauPlan').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouveau plan');
    });

    $(document).on('click', '.btn-edit-plan', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le plan');
        $method.val('PUT');

        $.getJSON(`{{ url('finances/plan-echeanciers') }}/${currentId}/edit`, function (data) {
            $nom.val(data.plan.nom);
            $('#plan-description').val(data.plan.description);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        $nomError.text('');
        $nom.removeClass('is-invalid');

        const url = currentId ? `{{ url('finances/plan-echeanciers') }}/${currentId}` : storeUrl;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors?.nom) {
                    $nom.addClass('is-invalid');
                    $nomError.text(xhr.responseJSON.errors.nom[0]);
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush