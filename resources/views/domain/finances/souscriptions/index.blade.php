@extends('admin.layouts.app')

@section('title', 'Souscriptions')
@section('page_icon', 'fa-file-signature')
@section('page_title', 'Souscriptions aux frais')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.frais-ecoles.index') }}">Frais scolaires</a></li>
    <li class="breadcrumb-item active">Souscriptions</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.souscriptions.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('souscriptions.gerer')
        <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#souscModal">
            <i class="fas fa-plus-circle me-1"></i> Nouvelle souscription
        </button>
    @endcan
@endsection

@section('css')
<style>
    /* Réutilise les classes frais-page */
    .table-sousc thead tr { background:#faf6ee; }
    .table-sousc thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid #ece4d6; white-space:nowrap; }
    .table-sousc tbody td { padding:.9rem 1rem; border-bottom:1px solid #ece4d6; font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-sousc tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }
    .remise-badge { display:inline-flex; align-items:center; gap:4px; padding:.25em .7em; border-radius:999px; background:#fdf4e0; color:var(--school-gold-dark); font-size:.72rem; font-weight:700; }
</style>
@endsection

@section('contenu')
<div class="container-fluid p-0">
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Nom, prénom, matricule..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="frais_ecole_id" class="form-select filtre-input">
                        <option value="">— Frais —</option>
                        @foreach ($frais as $f)
                            <option value="{{ $f->id }}" @selected(($filters['frais_ecole_id'] ?? '') == $f->id)>{{ $f->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="annee_id" class="form-select filtre-input">
                        <option value="">— Année —</option>
                        @foreach ($annees as $a)
                            <option value="{{ $a->id }}" @selected(($filters['annee_id'] ?? '') == $a->id)>{{ $a->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('finances.souscriptions.index') }}" class="btn btn-outline-scolarite w-100"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sousc align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Frais</th>
                            <th class="text-end">Montant brut</th>
                            <th class="text-end">Remise</th>
                            <th class="text-end">Montant net</th>
                            <th>Date</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($souscriptions as $s)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $s->inscription?->eleve?->nom }} {{ $s->inscription?->eleve?->prenom }}</div>
                                <div class="text-xs text-muted">{{ $s->inscription?->eleve?->matricule }}</div>
                            </td>
                            <td class="text-sm">{{ $s->fraisEcole?->libelle ?? '—' }}</td>
                            <td class="num">{{ number_format((float) $s->montant_annuel_prevu, 2, ',', ' ') }}</td>
                            <td class="text-end">
                                @if ((float) $s->taux_remise > 0)
                                    <span class="remise-badge">
                                        <i class="fas fa-tag"></i>
                                        {{ number_format((float) $s->taux_remise, 1, ',', ' ') }} %
                                    </span>
                                    <div class="text-xs text-muted">− {{ number_format($s->montant_remise, 2, ',', ' ') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="num" style="color:var(--school-red-dark);">{{ number_format($s->montant_net, 2, ',', ' ') }}</td>
                            <td class="text-sm text-muted">{{ optional($s->date_souscription)->format('d/m/Y') }}</td>
                            <td class="text-center pe-4">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
                                        @can('souscriptions.remise')
                                        <li>
                                            <button class="dropdown-item btn-edit-remise"
                                                    data-id="{{ $s->id }}"
                                                    data-eleve="{{ $s->inscription?->eleve?->nom }} {{ $s->inscription?->eleve?->prenom }}"
                                                    data-taux="{{ $s->taux_remise }}"
                                                    data-bs-toggle="modal" data-bs-target="#remiseModal">
                                                <i class="fas fa-tag"></i> Modifier la remise
                                            </button>
                                        </li>
                                        @endcan
                                        @can('souscriptions.gerer')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('finances.souscriptions.destroy', $s) }}" method="POST" class="form-confirm-delete">
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
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-signature"></i><p>Aucune souscription.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $souscriptions->appends(request()->query())->links('vendor.pagination.custom') }}</div>

    {{-- Modale création --}}
    @can('souscriptions.gerer')
    <div class="modal fade" id="souscModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-frais">
                <form id="sousc-form" action="{{ route('finances.souscriptions.store') }}" method="POST">
                    @csrf
                    <div class="modal-header modal-header-frais">
                        <h5 class="modal-title"><i class="fas fa-file-signature me-2"></i>Nouvelle souscription</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Frais <span class="text-danger">*</span></label>
                                <select name="frais_ecole_id" class="form-select" required>
                                    <option value="">— Sélectionner —</option>
                                    @foreach ($frais as $f)
                                        <option value="{{ $f->id }}">{{ $f->libelle }} — {{ number_format((float) $f->montant, 2, ',', ' ') }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Élève (inscription) <span class="text-danger">*</span></label>
                                <select name="inscription_id" id="sousc-inscription" class="form-select" required style="width:100%;">
                                    <option value=""></option>
                                    @foreach ($inscriptions as $inscription)
                                        <option value="{{ $inscription->id }}">
                                            {{ $inscription->eleve->nom }} {{ $inscription->eleve->prenom }}
                                            @if ($inscription->eleve->matricule) ({{ $inscription->eleve->matricule }}) @endif
                                            — {{ $inscription->classe?->libelle ?? $inscription->niveau?->libelle ?? 'sans classe' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">
                                    Élèves inscrits sur l'année scolaire active.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Taux de remise (%)</label>
                                <input type="number" name="taux_remise" step="0.01" min="0" max="100" value="0" class="form-control">
                            </div>
                            <div>
                                <label class="form-label">Date de souscription</label>
                                <input type="date" name="date_souscription" class="form-control" value="{{ now()->toDateString() }}">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Souscrire</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @endcan

    {{-- Modale remise : Comptable et Direction (cahier des charges, 2.1 et profil Direction) --}}
    @can('souscriptions.remise')
    <div class="modal fade" id="remiseModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-frais">
                <form id="remise-form" method="POST" action="">
                    @csrf @method('PUT')
                    <div class="modal-header modal-header-frais">
                        <h5 class="modal-title"><i class="fas fa-tag me-2"></i>Modifier la remise</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p>Élève : <strong id="remise-eleve">—</strong></p>
                        <div class="form-section">
                            <label class="form-label">Nouveau taux de remise (%) <span class="text-danger">*</span></label>
                            <input type="number" name="taux_remise" id="remise-taux" step="0.01" min="0" max="100" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-annuler" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-enregistrer"><i class="fas fa-save me-1"></i> Mettre à jour</button>
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
    $('#sousc-inscription').select2({
        dropdownParent: $('#souscModal'),
        placeholder: 'Rechercher un élève...',
        width: '100%',
    });

    // Création
    $('#sousc-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#souscModal').modal('hide');
                window.showToastThenReload(res.message || 'Souscription enregistrée.', 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                window.showToast(msg, 'error');
            }
        });
    });

    // Édition remise
    $(document).on('click', '.btn-edit-remise', function () {
        const id = $(this).data('id');
        $('#remise-eleve').text($(this).data('eleve'));
        $('#remise-taux').val($(this).data('taux'));
        $('#remise-form').attr('action', `{{ url('finances/souscriptions') }}/${id}`);
    });

    $('#remise-form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'), method: 'POST', data: $(this).serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('#remiseModal').modal('hide');
                window.showToastThenReload(res.message || 'Remise mise à jour.', 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message || 'Erreur.';
                window.showToast(msg, 'error');
            }
        });
    });
});
</script>
@endpush