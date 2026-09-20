{{-- resources/views/admin/finances/frais-ecoles/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Frais scolaires')
@section('page_icon', 'fa-tags')
@section('page_title', 'Frais scolaires & Échéanciers')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances</li>
    <li class="breadcrumb-item active">Frais scolaires</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.plan-echeanciers.index') }}" class="btn btn-outline-scolarite me-2">
        <i class="fas fa-calendar-alt me-1"></i> Plans d'échéancier
    </a>
    <button type="button" class="btn btn-nouveau" id="btnNouveauFrais" data-bs-toggle="modal" data-bs-target="#fraisModal">
        <i class="fas fa-plus-circle me-1"></i> Définir un frais
    </button>
@endsection

@section('css')
<style>
    .frais-page { --frais-bg-soft: #faf6ee; --frais-border: #ece4d6; }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--frais-border) !important; }

    .btn-outline-scolarite { border: 1px solid var(--frais-border); color: var(--school-ink); background: #fff; font-size: .85rem; }
    .btn-outline-scolarite:hover { background: var(--frais-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    .filtre-label { font-size: .74rem; font-weight: 600; color: #5b6b7a; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 5px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--frais-border); padding: .5rem .8rem; font-size: .85rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .8rem; }
    .search-input { padding-left: 32px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }

    .compteur-badge {
        display: inline-block; background: var(--frais-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .82rem; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--frais-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25);
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    .table-frais thead tr { background: var(--frais-bg-soft); }
    .table-frais thead th {
        color: var(--school-red); font-size: .74rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--frais-border); padding: .85rem .9rem;
    }
    .table-frais tbody td { padding: .8rem .9rem; border-bottom: 1px solid var(--frais-border); font-size: .87rem; color: var(--school-ink); vertical-align: middle; }
    .table-frais tbody tr:hover { background: #fdfaf5; }
    .table-frais tbody tr:last-child td { border-bottom: none; }

    .badge-forfait { display: inline-flex; align-items: center; font-size: .74rem; font-weight: 600; padding: .35em .8em; border-radius: 999px; background: var(--frais-bg-soft); color: var(--school-red); }
    .badge-paiement { display: inline-flex; align-items: center; font-size: .74rem; font-weight: 600; padding: .35em .8em; border-radius: 999px; }
    .badge-paiement-comptant { background: #e3f7ec; color: #1c7a4d; }
    .badge-paiement-echelonne { background: #fdf3e2; color: #b8720b; }

    .montant-frais { font-weight: 700; color: var(--school-red-dark); }
    .plan-nom { font-size: .84rem; }
    .plan-absent { color: var(--school-muted); font-size: .84rem; }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

    .modal-frais { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-frais { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .modal-header-frais .modal-title { font-weight: 600; font-size: 1.05rem; }
    .form-section { background: var(--frais-bg-soft); border: 1px solid var(--frais-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section .form-control, .form-section .form-select { border-radius: 8px; border: 1px solid var(--frais-border); }
    .btn-annuler { background: #fff; border: 1px solid var(--frais-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--frais-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem; }
    .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }

    /* .btn-action / .dropdown-menu-actions viennent de layouts/app.blade.php */
</style>
@endsection

@section('contenu')
    <div class="frais-page">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Année</label>
                        <select name="annee_id" class="form-select filtre-input" onchange="this.form.submit()">
                            @foreach ($anneesOptions as $annee)
                                <option value="{{ $annee->id }}" {{ $anneeId == $annee->id ? 'selected' : '' }}>{{ $annee->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Niveau</label>
                        <select name="niveau_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($niveauxOptions as $niveau)
                                <option value="{{ $niveau->id }}" {{ request('niveau_id') == $niveau->id ? 'selected' : '' }}>{{ $niveau->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="filtre-label">Nature du frais</label>
                        <select name="type_forfait" class="form-select filtre-input">
                            <option value="">Toutes</option>
                            @foreach (\App\Domain\Finances\Types\TypeForfait::options() as $val => $label)
                                <option value="{{ $val }}" {{ request('type_forfait') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filtre-label">Recherche</label>
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Libellé..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-1">
                        <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i></button>
                    </div>
                    <div class="col-6 col-md-1 text-end">
                        <span class="compteur-badge">{{ $fraisEcoles->total() }}</span>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-frais align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Libellé</th>
                                <th>Nature</th>
                                <th>Niveau</th>
                                <th class="text-end">Montant</th>
                                <th>Type de paiement</th>
                                <th>Plan d'échéancier</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fraisEcoles as $frais)
                                <tr>
                                    <td><strong>{{ $frais->libelle }}</strong></td>
                                    <td><span class="badge-forfait">{{ $frais->type_forfait?->label() ?? '—' }}</span></td>
                                    <td>{{ $frais->niveau?->libelle ?? '—' }}</td>
                                    <td class="text-end"><span class="montant-frais">{{ number_format($frais->montant ?? 0, 0, ',', ' ') }} F</span></td>
                                    <td>
                                        @if ($frais->type_paiement?->value === 1)
                                            <span class="badge-paiement badge-paiement-comptant">Comptant</span>
                                        @else
                                            <span class="badge-paiement badge-paiement-echelonne">Échelonné</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($frais->planEcheancier)
                                            <span class="plan-nom">{{ $frais->planEcheancier->nom }}</span>
                                        @else
                                            <span class="plan-absent">Aucun</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-frais"
                                                            data-id="{{ $frais->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#fraisModal">
                                                        <i class="fas fa-pen"></i> Modifier
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('finances.frais-ecoles.archiver', $frais) }}"
                                                          method="POST" class="form-confirm-delete"
                                                          data-confirm-title="Archiver ce frais ?"
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
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-tags"></i>
                                            <p class="mb-0">Aucun frais ne correspond à ces critères.</p>
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
            {{ $fraisEcoles->appends(request()->query())->links() }}
        </div>

        {{-- Modale unique, réutilisée pour l'ajout ET la modification --}}
        <div class="modal fade" id="fraisModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-frais">
                    <form id="frais-form">
                        @csrf
                        <input type="hidden" name="_method" id="frais-method" value="POST">
                        <div class="modal-header modal-header-frais">
                            <h5 class="modal-title" id="frais-modal-title"><i class="fas fa-plus-circle me-2"></i>Définir un frais</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section">
                                <div class="mb-3">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="frais-libelle" class="form-control" placeholder="Ex : Frais de scolarité CM1">
                                    <div class="text-danger small mt-1" id="frais-libelle-error"></div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">Nature <span class="text-danger">*</span></label>
                                        <select name="type_forfait" id="frais-type-forfait" class="form-select">
                                            @foreach (\App\Domain\Finances\Types\TypeForfait::options() as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Niveau <span class="text-danger">*</span></label>
                                        <select name="niveau_id" id="frais-niveau" class="form-select">
                                            <option value="">-- Choisir --</option>
                                            @foreach ($niveauxOptions as $niveau)
                                                <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                                            @endforeach
                                        </select>
                                        <div class="text-danger small mt-1" id="frais-niveau_id-error"></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Montant <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="montant" id="frais-montant" class="form-control">
                                        <div class="text-danger small mt-1" id="frais-montant-error"></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Type de paiement <span class="text-danger">*</span></label>
                                        <select name="type_paiement" id="frais-type-paiement" class="form-select">
                                            @foreach (\App\Domain\Finances\Types\TypePaiement::options() as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Plan d'échéancier (si échelonné)</label>
                                        <select name="plan_echeancier_id" id="frais-plan" class="form-select">
                                            <option value="">Aucun</option>
                                            @foreach ($plansOptions as $plan)
                                                <option value="{{ $plan->id }}">{{ $plan->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>
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
    const modalEl  = document.getElementById('fraisModal');
    const modal    = new bootstrap.Modal(modalEl);
    const $form    = $('#frais-form');
    const $title   = $('#frais-modal-title');
    const $method  = $('#frais-method');

    const fields = {
        libelle:   { input: $('#frais-libelle'),  error: $('#frais-libelle-error') },
        niveau_id: { input: $('#frais-niveau'),   error: $('#frais-niveau_id-error') },
        montant:   { input: $('#frais-montant'),  error: $('#frais-montant-error') },
    };

    const storeUrl = '{{ route('finances.frais-ecoles.store') }}';
    let currentId  = null;

    function clearErrors() {
        Object.values(fields).forEach(f => { f.input.removeClass('is-invalid'); f.error.text(''); });
    }

    function resetForm() {
        $form[0].reset();
        currentId = null;
        $method.val('POST');
        clearErrors();
    }

    $('#btnNouveauFrais').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Définir un frais');
    });

    $(document).on('click', '.btn-edit-frais', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier le frais');
        $method.val('PUT');

        $.getJSON(`{{ url('finances/frais-ecoles') }}/${currentId}/edit`, function (data) {
            $('#frais-libelle').val(data.fraisEcole.libelle);
            $('#frais-type-forfait').val(data.fraisEcole.type_forfait);
            $('#frais-niveau').val(data.fraisEcole.niveau_id);
            $('#frais-montant').val(data.fraisEcole.montant);
            $('#frais-type-paiement').val(data.fraisEcole.type_paiement);
            $('#frais-plan').val(data.fraisEcole.plan_echeancier_id);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId ? `{{ url('finances/frais-ecoles') }}/${currentId}` : storeUrl;

        $.ajax({
            url: url,
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                modal.hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function (field) {
                        if (fields[field]) {
                            fields[field].input.addClass('is-invalid');
                            fields[field].error.text(errors[field][0]);
                        }
                    });
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush