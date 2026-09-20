{{-- resources/views/admin/scolarite/annees/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Années scolaires')
@section('page_icon', 'fa-calendar-alt')
@section('page_title', 'Années scolaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Scolarité</li>
    <li class="breadcrumb-item active">Années scolaires</li>
@endsection

@section('page_actions')
    <button type="button" class="btn btn-nouveau" id="btnNouvelleAnnee" data-bs-toggle="modal" data-bs-target="#anneeModal">
        <i class="fas fa-plus-circle me-1"></i> Nouvelle année scolaire
    </button>
@endsection

@section('css')
<style>
    /* Jetons propres à cette page uniquement — school-red, gold, ink,
       muted, ff... viennent déjà de layouts/app.blade.php, non
       redéclarés ici. Palette chaude alignée sur Cycles/Niveaux (pas de
       gris Bootstrap générique #f8f9fa/#e9ecef). */
    .annees-page {
        --annees-bg-soft: #faf6ee;
        --annees-border: #ece4d6;
    }

    /* ===== Échelle de tailles de texte pour cette page =====
       (fs-7/fs-8 n'existent pas dans Bootstrap — remplacés ici par des
       valeurs explicites) */
    .text-xs  { font-size: .72rem; }
    .text-sm  { font-size: .8rem; }
    .text-md  { font-size: .92rem; }

    /* Cartes stats */
    .stat-card {
        background: #fff;
        border: 1px solid var(--annees-border);
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(122,15,28,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(122,15,28,.08); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; background: rgba(200,30,58,.08); color: var(--school-red);
        flex-shrink: 0;
    }
    .stat-info .stat-value { font-size: 1.2rem; font-weight: 700; line-height: 1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size: .78rem; color: var(--school-muted); margin: 0; }

    .btn-outline-scolarite {
        border: 1px solid var(--annees-border);
        color: var(--school-ink);
        background: #fff;
        font-size: .82rem;
    }
    .btn-outline-scolarite:hover { background: var(--annees-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    /* Card & tableau principal */
    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--annees-border) !important; }

    .filtre-input {
        border-radius: 10px; border: 1px solid var(--annees-border);
        padding: .55rem .9rem; font-size: .88rem;
    }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .85rem; }
    .search-input { padding-left: 36px !important; }

    .btn-filtrer {
        background: var(--school-red); color: #fff; border: none; border-radius: 10px;
        padding: .55rem 1rem; font-weight: 600; font-size: .88rem; transition: all .2s ease;
    }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.25); }

    .compteur-badge {
        display: inline-block; background: var(--annees-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
        border: 1px solid var(--annees-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25); transition: all .2s ease;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    /* Tableau */
    .table-annees thead tr { background: var(--annees-bg-soft); }
    .table-annees thead th {
        color: var(--school-red); font-size: .76rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--annees-border); padding: .9rem 1rem;
    }
    .table-annees thead th i { color: var(--school-gold-dark); }
    .table-annees tbody td { padding: .9rem 1rem; border-bottom: 1px solid var(--annees-border); font-size: .9rem; color: var(--school-ink); }
    .table-annees tbody tr:hover { background: #fdfaf5; }
    .table-annees tbody tr:last-child td { border-bottom: none; }

    .annee-libelle { font-weight: 700; color: var(--school-ink); display: block; font-size: .94rem; }
    .annee-periode { font-size: .76rem; color: var(--school-muted); }
    .inscription-periode { font-size: .82rem; color: var(--school-ink); }
    .inscription-periode i { font-size: .7rem; color: var(--school-muted); }

    /* Badges statut, alignés sur le vert déjà utilisé pour "Actif" sur
       Cycles (#e3f7ec / #1c7a4d) — pas une teinte différente. */
    .badge-etat {
        display: inline-flex; align-items: center; gap: 6px; font-size: .75rem;
        font-weight: 600; padding: .4em .85em; border-radius: 999px;
    }
    .badge-etat-active { background: #e3f7ec; color: #1c7a4d; }
    .badge-etat-cloturee { background: var(--annees-bg-soft); color: var(--school-muted); }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; }
    .badge-etat-active .badge-dot { background: #1c7a4d; }
    .badge-etat-cloturee .badge-dot { background: var(--school-muted); }

    .badge-non-configure {
        display: inline-block; background: var(--annees-bg-soft); color: var(--school-muted);
        border: 1px solid var(--annees-border); font-size: .76rem; font-weight: 500;
        padding: .3em .7em; border-radius: 999px;
    }

    .empty-state { padding: 3.5rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }
    .empty-state p { font-size: .9rem; }

    /* .btn-action / .dropdown-menu-actions viennent de layouts/app.blade.php
       — ne pas ajouter border-0/shadow-sm dessus, ça écrase le style
       global (Bootstrap applique border-0 en !important). */

    /* Modale */
    .modal-annees { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,.15); }
    .modal-header-annees {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border-bottom: none; padding: 1.15rem 1.5rem;
    }
    .modal-header-annees .modal-title { font-weight: 600; font-size: 1.05rem; }

    .form-section { background: var(--annees-bg-soft); border: 1px solid var(--annees-border); border-radius: 12px; padding: 1.1rem; }
    .form-section-title {
        font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        color: var(--school-red); margin-bottom: .75rem; display: flex; align-items: center; gap: 6px;
    }
    .form-section .form-label { font-size: .82rem; font-weight: 600; color: var(--school-ink); }
    .form-section .form-control {
        border-radius: 8px; border: 1px solid var(--annees-border); padding: .55rem .75rem; font-size: .88rem;
    }
    .form-section .form-control:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }

    .btn-annuler { background: #fff; border: 1px solid var(--annees-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .55rem 1.2rem; font-size: .88rem; }
    .btn-annuler:hover { background: var(--annees-bg-soft); }
    .btn-enregistrer {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff;
        border: none; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;
    }
    .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }
</style>
@endsection

@section('contenu')
    <div class="annees-page">
        {{-- En-tête / structure pédagogique synthétique --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-sitemap"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $cyclesCount }}</div>
                        <div class="stat-label">Cycles enregistrés</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="stat-info">
                        <div class="stat-value">{{ $niveauxCount }}</div>
                        <div class="stat-label">Niveaux d'étude</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-6 d-flex align-items-center justify-content-md-end gap-2">
                <a href="{{ route('scolarite.cycles.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
                    <i class="fas fa-sitemap me-1"></i> Cycles
                </a>
                <a href="{{ route('scolarite.niveaux.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
                    <i class="fas fa-layer-group me-1"></i> Niveaux
                </a>
            </div>
        </div>

        {{-- Tableau principal --}}
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Rechercher une année scolaire (ex : 2026-2027)..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <button type="submit" class="btn btn-filtrer w-100">
                            <i class="fas fa-filter me-1"></i> Filtrer
                        </button>
                    </div>
                    <div class="col-md-3 col-6 text-end">
                        <span class="compteur-badge">{{ $annees->total() }} année(s)</span>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-annees align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4"><i class="fas fa-calendar-alt me-2"></i>Année scolaire</th>
                                <th><i class="fas fa-door-open me-2"></i>Période inscriptions</th>
                                <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                                <th class="text-center pe-4" style="width:80px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($annees as $annee)
                                <tr>
                                    <td class="ps-4">
                                        <span class="annee-libelle">{{ $annee->libelle }}</span>
                                        <span class="annee-periode">
                                            <i class="far fa-clock me-1"></i>
                                            {{ $annee->date_rentree?->format('d/m/Y') }} — {{ $annee->date_fin?->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($annee->date_ouverture_inscription || $annee->date_fermeture_reinscription)
                                            <span class="inscription-periode font-monospace">
                                                {{ $annee->date_ouverture_inscription?->format('d/m/Y') ?? '—' }}
                                                <i class="fas fa-arrow-right mx-1"></i>
                                                {{ $annee->date_fermeture_reinscription?->format('d/m/Y') ?? '—' }}
                                            </span>
                                        @else
                                            <span class="badge-non-configure">Non configurées</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($annee->isOuverte())
                                            <span class="badge-etat badge-etat-active"><span class="badge-dot"></span>Active</span>
                                        @else
                                            <span class="badge-etat badge-etat-cloturee"><span class="badge-dot"></span>Clôturée</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                <li>
                                                    <button type="button" class="dropdown-item btn-edit-annee"
                                                            data-id="{{ $annee->id }}"
                                                            data-bs-toggle="modal" data-bs-target="#anneeModal">
                                                        <i class="fas fa-pen"></i> Modifier
                                                    </button>
                                                </li>
                                                @if ($annee->isOuverte())
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form action="{{ route('scolarite.annees.cloturer', $annee) }}"
                                                              method="POST" class="form-confirm-delete"
                                                              data-confirm-title="Clôturer cette année scolaire ?"
                                                              data-confirm-text="Elle passera en lecture seule dans les listes historiques.">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="fas fa-lock"></i> Clôturer
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="fas fa-calendar-times"></i>
                                            <p class="mb-0 fw-medium">Aucune année scolaire trouvée.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4">
            {{ $annees->appends(request()->query())->links() }}
        </div>

        {{-- Modale d'ajout/édition --}}
        <div class="modal fade" id="anneeModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-annees">
                    <form id="annee-form">
                        @csrf
                        <input type="hidden" name="_method" id="annee-method" value="POST">
                        <div class="modal-header modal-header-annees">
                            <h5 class="modal-title" id="annee-modal-title">
                                <i class="fas fa-plus-circle me-2"></i>Nouvelle année scolaire
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section mb-3">
                                <div class="form-section-title"><i class="fas fa-info-circle"></i> Informations générales</div>
                                <div class="mb-3">
                                    <label class="form-label">Libellé <span class="text-danger">*</span></label>
                                    <input type="text" name="libelle" id="annee-libelle" class="form-control"
                                           placeholder="Ex : 2026-2027">
                                    <div class="text-danger text-xs mt-1" id="annee-libelle-error"></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label">Date de rentrée <span class="text-danger">*</span></label>
                                        <input type="date" name="date_rentree" id="annee-date-rentree" class="form-control">
                                        <div class="text-danger text-xs mt-1" id="annee-date_rentree-error"></div>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Date de fin <span class="text-danger">*</span></label>
                                        <input type="date" name="date_fin" id="annee-date-fin" class="form-control">
                                        <div class="text-danger text-xs mt-1" id="annee-date_fin-error"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-section-title"><i class="fas fa-door-open"></i> Inscriptions &amp; réinscriptions</div>
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label">Ouverture</label>
                                        <input type="date" name="date_ouverture_inscription" id="annee-date-ouverture" class="form-control">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Fermeture</label>
                                        <input type="date" name="date_fermeture_reinscription" id="annee-date-fermeture" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3">
                            <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-enregistrer">
                                <i class="fas fa-save me-1"></i> Enregistrer
                            </button>
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
    const modalEl  = document.getElementById('anneeModal');
    const modal    = new bootstrap.Modal(modalEl);
    const $form    = $('#annee-form');
    const $title   = $('#annee-modal-title');
    const $method  = $('#annee-method');

    const $libelle       = $('#annee-libelle');
    const $dateRentree   = $('#annee-date-rentree');
    const $dateFin       = $('#annee-date-fin');
    const $dateOuverture = $('#annee-date-ouverture');
    const $dateFermeture = $('#annee-date-fermeture');

    const fields = {
        libelle:      { input: $libelle,     error: $('#annee-libelle-error') },
        date_rentree: { input: $dateRentree, error: $('#annee-date_rentree-error') },
        date_fin:     { input: $dateFin,     error: $('#annee-date_fin-error') },
    };

    const storeUrl = '{{ route('scolarite.annees.store') }}';
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

    $('#btnNouvelleAnnee').on('click', function () {
        resetForm();
        $title.html('<i class="fas fa-plus-circle me-2"></i>Nouvelle année scolaire');
    });

    $(document).on('click', '.btn-edit-annee', function () {
        resetForm();
        currentId = $(this).data('id');
        $title.html('<i class="fas fa-pen me-2"></i>Modifier l\'année scolaire');
        $method.val('PUT');

        $.getJSON(`{{ url('scolarite/annees') }}/${currentId}/edit`, function (data) {
            $libelle.val(data.annee.libelle);
            $dateRentree.val(data.annee.date_rentree);
            $dateFin.val(data.annee.date_fin);
            $dateOuverture.val(data.annee.date_ouverture_inscription);
            $dateFermeture.val(data.annee.date_fermeture_reinscription);
        });
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const url = currentId ? `{{ url('scolarite/annees') }}/${currentId}` : storeUrl;

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