{{-- resources/views/admin/scolarite/inscriptions/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Inscriptions')
@section('page_icon', 'fa-file-signature')
@section('page_title', 'Inscriptions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Scolarité</li>
    <li class="breadcrumb-item active">Inscriptions</li>
@endsection

@section('page_actions')
    <button type="button" class="btn btn-nouveau" id="btnNouvelleInscription" data-bs-toggle="modal" data-bs-target="#inscriptionModal">
        <i class="fas fa-plus-circle me-1"></i> Nouvelle inscription
    </button>
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('app/assets/plugins/select2/css/select2.min.css') }}">
<style>
    .inscriptions-page { --insc-bg-soft: #faf6ee; --insc-border: #ece4d6; }

    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(122,15,28,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--insc-border) !important; }

    .filtre-label { font-size: .74rem; font-weight: 600; color: #5b6b7a; text-transform: uppercase; letter-spacing: .03em; margin-bottom: 5px; }
    .filtre-input { border-radius: 10px; border: 1px solid var(--insc-border); padding: .5rem .8rem; font-size: .85rem; }
    .filtre-input:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position: relative; }
    .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #9aa7b2; font-size: .8rem; }
    .search-input { padding-left: 32px !important; }

    .btn-filtrer { background: var(--school-red); color: #fff; border: none; border-radius: 10px; padding: .5rem 1rem; font-weight: 600; font-size: .85rem; }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; }

    .compteur-badge {
        display: inline-block; background: var(--insc-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .82rem; padding: .4rem .85rem; border-radius: 999px; border: 1px solid var(--insc-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(200,30,58,.25);
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(200,30,58,.35); }

    .table-inscriptions thead tr { background: var(--insc-bg-soft); }
    .table-inscriptions thead th {
        color: var(--school-red); font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--insc-border); padding: .8rem .85rem; white-space: nowrap;
    }
    .table-inscriptions tbody td { padding: .7rem .85rem; border-bottom: 1px solid var(--insc-border); font-size: .85rem; color: var(--school-ink); vertical-align: middle; }
    .table-inscriptions tbody tr:hover { background: #fdfaf5; }
    .table-inscriptions tbody tr:last-child td { border-bottom: none; }

    .eleve-nom { font-weight: 700; font-size: .87rem; display: block; }
    .eleve-matricule { font-family: monospace; font-size: .78rem; color: var(--school-muted); }
    .classe-cell { font-size: .84rem; }
    .classe-cell small { color: var(--school-muted); display: block; font-size: .74rem; }

    .badge-statut { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; font-weight: 600; padding: .35em .75em; border-radius: 999px; white-space: nowrap; }
    .badge-statut-attente { background: #fdf3e2; color: #b8720b; }
    .badge-statut-validee { background: #e3f7ec; color: #1c7a4d; }
    .badge-statut-rejetee { background: #fbeaea; color: #b13b3b; }
    .badge-statut-abandon { background: var(--insc-bg-soft); color: var(--school-muted); }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9aa7b2; }
    .empty-state i { font-size: 2.2rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }

    .modal-inscriptions { border: none; border-radius: 16px; overflow: hidden; }
    .modal-header-inscriptions { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border-bottom: none; padding: 1.1rem 1.5rem; }
    .modal-header-inscriptions .modal-title { font-weight: 600; font-size: 1.05rem; }
    .form-section { background: var(--insc-bg-soft); border: 1px solid var(--insc-border); border-radius: 12px; padding: 1rem 1.1rem; }
    .form-section-title { font-size: .74rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--school-red); margin-bottom: .6rem; }
    .form-section .form-control, .form-section .form-select { border-radius: 8px; border: 1px solid var(--insc-border); font-size: .88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color: var(--school-red); box-shadow: 0 0 0 .2rem rgba(200,30,58,.1); }
    .btn-annuler { background: #fff; border: 1px solid var(--insc-border); color: #5b6b7a; border-radius: 8px; font-weight: 500; padding: .5rem 1.1rem; }
    .btn-annuler:hover { background: var(--insc-bg-soft); }
    .btn-enregistrer { background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff; border: none; border-radius: 8px; font-weight: 600; padding: .5rem 1.3rem; }
    .btn-enregistrer:hover { color: #fff; box-shadow: 0 4px 12px rgba(200,30,58,.3); }

    /* .btn-action / .dropdown-menu-actions viennent de layouts/app.blade.php */
</style>
@endsection

@section('contenu')
    <div class="inscriptions-page">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Cycle</label>
                        <select name="cycle_id" class="form-select filtre-input">
                            <option value="">Tous</option>
                            @foreach ($cyclesOptions as $cycle)
                                <option value="{{ $cycle->id }}" {{ request('cycle_id') == $cycle->id ? 'selected' : '' }}>{{ $cycle->libelle }}</option>
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
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Classe</label>
                        <select name="classe_id" class="form-select filtre-input">
                            <option value="">Toutes</option>
                            @foreach ($classesOptions as $classe)
                                <option value="{{ $classe->id }}" {{ request('classe_id') == $classe->id ? 'selected' : '' }}>{{ $classe->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="filtre-label">Statut</label>
                        <select name="statut" class="form-select filtre-input">
                            <option value="">Tous</option>
                            <option value="en_attente" {{ request('statut') === 'en_attente' ? 'selected' : '' }}>En attente</option>
                            <option value="validee" {{ request('statut') === 'validee' ? 'selected' : '' }}>Validée</option>
                            <option value="rejetee" {{ request('statut') === 'rejetee' ? 'selected' : '' }}>Rejetée</option>
                            <option value="abandonnee" {{ request('statut') === 'abandonnee' ? 'selected' : '' }}>Abandonnée</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filtre-label">Recherche</label>
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control filtre-input search-input"
                                   placeholder="Nom, matricule..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-6 col-md-1">
                        <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i></button>
                    </div>
                </form>
                <div class="text-end mt-2">
                    <span class="compteur-badge">{{ $inscriptions->total() }} inscription(s)</span>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-inscriptions align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Élève</th>
                                <th>Classe / Niveau / Cycle</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th class="text-end">Frais scolarité</th>
                                <th class="text-end">Remise</th>
                                <th class="text-center" style="width:70px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inscriptions as $inscription)
                                @php $statut = $inscription->statutAffiche(); @endphp
                                <tr>
                                    <td>
                                        <span class="eleve-nom">{{ $inscription->eleve?->nom }} {{ $inscription->eleve?->prenom }}</span>
                                        <span class="eleve-matricule">{{ $inscription->eleve?->matricule ?? '—' }}</span>
                                    </td>
                                    <td class="classe-cell">
                                        {{ $inscription->classe?->libelle ?? '—' }}
                                        <small>{{ $inscription->niveau?->libelle }} · {{ $inscription->cycle?->libelle }}</small>
                                    </td>
                                    <td>{{ $inscription->date_inscription?->format('d/m/Y') }}</td>
                                    <td>{{ $inscription->type_inscription?->label() ?? '—' }}</td>
                                    <td><span class="badge-statut {{ $statut['classe'] }}">{{ $statut['label'] }}</span></td>
                                    <td class="text-end">{{ number_format($inscription->frais_scolarite ?? 0, 0, ',', ' ') }} F</td>
                                    <td class="text-end">{{ $inscription->taux_remise ?? 0 }} %</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                                @if ($inscription->isEnAttente())
                                                    <li>
                                                        <form action="{{ route('scolarite.inscriptions.valider', $inscription) }}" method="POST"
                                                              class="form-confirm-delete"
                                                              data-confirm-title="Valider cette inscription ?"
                                                              data-confirm-text="L'élève sera considéré comme inscrit pour l'année.">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item text-success">
                                                                <i class="fas fa-check-circle"></i> Valider
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-motif"
                                                                data-url="{{ route('scolarite.inscriptions.rejeter', $inscription) }}"
                                                                data-title="Motif du rejet"
                                                                data-bs-toggle="modal" data-bs-target="#motifModal">
                                                            <i class="fas fa-times-circle"></i> Rejeter
                                                        </button>
                                                    </li>
                                                @endif
                                                @if ($inscription->isValidee() && !$inscription->isAbandonnee())
                                                    <li>
                                                        <button type="button" class="dropdown-item btn-motif"
                                                                data-url="{{ route('scolarite.inscriptions.abandon', $inscription) }}"
                                                                data-title="Motif de l'abandon"
                                                                data-bs-toggle="modal" data-bs-target="#motifModal">
                                                            <i class="fas fa-user-slash"></i> Enregistrer un abandon
                                                        </button>
                                                    </li>
                                                @endif
                                                <li>
                                                    {{-- TODO : édition du certificat, prochain tour --}}
                                                    <a href="#" class="dropdown-item">
                                                        <i class="fas fa-certificate"></i> Certificat de scolarité
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-file-signature"></i>
                                            <p class="mb-0">Aucune inscription ne correspond à ces critères.</p>
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
            {{ $inscriptions->appends(request()->query())->links() }}
        </div>

        {{-- Modale de création --}}
        <div class="modal fade" id="inscriptionModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content modal-inscriptions">
                    <form id="inscription-form">
                        @csrf
                        <div class="modal-header modal-header-inscriptions">
                            <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nouvelle inscription</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="form-section mb-3">
                                <div class="form-section-title">Élève &amp; classe</div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Élève <span class="text-danger">*</span></label>
                                        <select name="eleve_id" id="insc-eleve" class="form-select" style="width:100%;">
                                            <option value=""></option>
                                            @foreach ($elevesOptions as $eleve)
                                                <option value="{{ $eleve->id }}">{{ $eleve->nom }} {{ $eleve->prenom }} @if($eleve->matricule) ({{ $eleve->matricule }}) @endif</option>
                                            @endforeach
                                        </select>
                                        <div class="text-danger small mt-1" id="insc-eleve_id-error"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Type <span class="text-danger">*</span></label>
                                        <select name="type_inscription" class="form-select">
                                            @foreach (\App\Domain\Scolarite\Types\TypeInscription::options() as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date_inscription" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Cycle <span class="text-danger">*</span></label>
                                        <select name="cycle_id" class="form-select">
                                            <option value="">-- Choisir --</option>
                                            @foreach ($cyclesOptions as $cycle)
                                                <option value="{{ $cycle->id }}">{{ $cycle->libelle }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Niveau <span class="text-danger">*</span></label>
                                        <select name="niveau_id" class="form-select">
                                            <option value="">-- Choisir --</option>
                                            @foreach ($niveauxOptions as $niveau)
                                                <option value="{{ $niveau->id }}">{{ $niveau->libelle }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Classe</label>
                                        <select name="classe_id" class="form-select">
                                            <option value="">-- Choisir --</option>
                                            @foreach ($classesOptions as $classe)
                                                <option value="{{ $classe->id }}">{{ $classe->libelle }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="form-section-title">Frais &amp; remise</div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="form-label">Frais scolarité</label>
                                        <input type="number" step="0.01" name="frais_scolarite" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Frais inscription</label>
                                        <input type="number" step="0.01" name="frais_inscription" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Taux de remise (%)</label>
                                        <input type="number" name="taux_remise" class="form-control" min="0" max="100">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Frais cantine</label>
                                        <input type="number" step="0.01" name="frais_cantine" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Frais bus</label>
                                        <input type="number" step="0.01" name="frais_bus" class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Frais livre</label>
                                        <input type="number" step="0.01" name="frais_livre" class="form-control">
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

        {{-- Modale générique motif (rejet ou abandon — même formulaire,
             action/titre définis en JS selon le bouton cliqué). Soumission
             en POST classique (pas d'AJAX) : rechargement + flash de
             session standard. --}}
        <div class="modal fade" id="motifModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content modal-inscriptions">
                    <form id="motif-form" method="POST" action="">
                        @csrf
                        <div class="modal-header modal-header-inscriptions">
                            <h5 class="modal-title" id="motif-modal-title">Motif</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <label class="form-label">Motif <span class="text-danger">*</span></label>
                            <textarea name="motif" id="motif-textarea" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-enregistrer">Confirmer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script src="{{ asset('app/assets/plugins/select2/js/select2.min.js') }}"></script>
<script>
$(function () {
    $('#insc-eleve').select2({
        dropdownParent: $('#inscriptionModal'),
        placeholder: 'Rechercher un élève...',
        width: '100%',
    });

    $(document).on('click', '.btn-motif', function () {
        $('#motif-form').attr('action', $(this).data('url'));
        $('#motif-modal-title').text($(this).data('title'));
        $('#motif-textarea').val('');
    });

    const $form = $('#inscription-form');
    const $eleveError = $('#insc-eleve_id-error');

    $form.on('submit', function (e) {
        e.preventDefault();
        $eleveError.text('');
        $('#insc-eleve').removeClass('is-invalid');

        $.ajax({
            url: '{{ route('scolarite.inscriptions.store') }}',
            method: 'POST',
            data: $form.serialize(),
            success: function (res) {
                bootstrap.Modal.getInstance(document.getElementById('inscriptionModal')).hide();
                window.showToastThenReload(res.message, 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    const errors = xhr.responseJSON.errors;
                    if (errors.eleve_id) { $eleveError.text(errors.eleve_id[0]); }
                    const firstMsg = Object.values(errors)[0][0];
                    window.showToast(firstMsg, 'error');
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    });
});
</script>
@endpush