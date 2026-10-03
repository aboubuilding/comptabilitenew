@extends('admin.layouts.app')

@section('title', 'Dossier #' . $dossier->id)
@section('page_icon', 'fa-folder-open')
@section('page_title', 'Dossier de recouvrement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.recouvrement.index') }}">Recouvrement</a></li>
    <li class="breadcrumb-item active">Dossier #{{ $dossier->id }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.recouvrement.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
    @if (! $dossier->isClos())
        @can('recouvrement.gerer')
            <button type="button" class="btn btn-outline-scolarite btn-sm rounded-pill px-3"
                    data-bs-toggle="modal" data-bs-target="#statutModal">
                <i class="fas fa-exchange-alt me-1"></i> Changer le statut
            </button>
        @endcan
        @can('recouvrement.relancer')
            <button type="button" class="btn btn-nouveau" data-bs-toggle="modal" data-bs-target="#relanceModal">
                <i class="fas fa-bullhorn me-1"></i> Ajouter une relance
            </button>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid #ece4d6 !important; }
    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.8rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; }
    .info-value { font-weight:600; color:var(--school-ink); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.positif { color:#1c7a4d; }
    .num.negatif { color:var(--school-red); }
    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.4em .9em; border-radius:999px; font-size:.75rem; font-weight:600; }
    .badge-statut.encours      { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.echeancier   { background:#e0ecfc; color:#2563eb; }
    .badge-statut.solde        { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.irrecouvrable{ background:#eceff2; color:#5b6b7a; }
    .badge-statut.contentieux  { background:#fdecea; color:var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .timeline { position:relative; padding-left:32px; }
    .timeline::before { content:''; position:absolute; left:14px; top:8px; bottom:8px; width:2px; background:#ece4d6; }
    .timeline-item { position:relative; padding-bottom:1.25rem; }
    .timeline-item:last-child { padding-bottom:0; }
    .timeline-marker {
        position:absolute; left:-32px; top:2px; width:28px; height:28px; border-radius:50%;
        display:flex; align-items:center; justify-content:center; font-size:.75rem;
        background:#faf6ee; color:var(--school-red); border:2px solid #fff;
        box-shadow: 0 0 0 2px #ece4d6;
    }
    .timeline-content { background:#faf6ee; border:1px solid #ece4d6; border-radius:10px; padding:.85rem 1rem; }
    .timeline-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.4rem; }
    .timeline-type { font-weight:700; color:var(--school-ink); font-size:.9rem; }
    .timeline-date { font-size:.75rem; color:var(--school-muted); }
    .timeline-body { font-size:.85rem; color:#4a5560; }
    .timeline-resultat { display:inline-block; padding:.2em .7em; border-radius:999px; font-size:.7rem; font-weight:600; margin-top:.4rem; }
    .timeline-resultat.neutre  { background:#eceff2; color:#5b6b7a; }
    .timeline-resultat.attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .timeline-resultat.partiel { background:#e0ecfc; color:#2563eb; }
    .timeline-resultat.positif { background:#e3f7ec; color:#1c7a4d; }
    .timeline-resultat.negatif { background:#fdecea; color:var(--school-red); }
    .empty-state { padding:3rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
    .modal-reco { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-reco { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:#faf6ee; border:1px solid #ece4d6; border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid #ece4d6; padding:.55rem .75rem; font-size:.88rem; }
    .form-section .form-control:focus, .form-section .form-select:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .btn-annuler { background:#fff; border:1px solid #ece4d6; color:#5b6b7a; border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="row g-4">

    {{-- Fiche dossier --}}
    <div class="col-md-5">
        <div class="card main-card">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-folder-open me-1" style="color:var(--school-gold-dark);"></i> Dossier #{{ $dossier->id }}</h6>
            </div>
            <div class="card-body p-4">
                <div class="info-row">
                    <span class="info-label">Élève</span>
                    <span class="info-value">
                        @if ($dossier->inscription?->eleve)
                            {{ $dossier->inscription->eleve->nom }} {{ $dossier->inscription->eleve->prenom }}
                            <span class="text-muted fw-normal">({{ $dossier->inscription->eleve->matricule }})</span>
                        @else
                            Inscription #{{ $dossier->inscription_id }}
                        @endif
                    </span>
                </div>
                @if ($dossier->inscription?->classe)
                    <div class="info-row">
                        <span class="info-label">Classe</span>
                        <span class="info-value">{{ $dossier->inscription->classe->libelle }}</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Nature</span>
                    <span class="info-value">{{ $dossier->nature?->label() }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="badge-statut {{ $dossier->statut?->css() }}">
                        <span class="badge-dot"></span>{{ $dossier->statut?->label() }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant initial</span>
                    <span class="info-value">{{ number_format((float) $dossier->montant_initial_du, 2, ',', ' ') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Recouvré</span>
                    <span class="info-value positif">{{ number_format((float) $dossier->montant_recouvre, 2, ',', ' ') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Restant dû</span>
                    <span class="info-value negatif" style="font-size:1.1rem;">{{ number_format($dossier->montant_restant, 2, ',', ' ') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ouvert le</span>
                    <span class="info-value">{{ optional($dossier->date_ouverture)->format('d/m/Y') }}</span>
                </div>
                @if ($dossier->date_cloture)
                    <div class="info-row">
                        <span class="info-label">Clos le</span>
                        <span class="info-value">{{ $dossier->date_cloture->format('d/m/Y') }}</span>
                    </div>
                @endif
                @if ($dossier->responsable)
                    <div class="info-row">
                        <span class="info-label">Responsable</span>
                        <span class="info-value">{{ $dossier->responsable->name }}</span>
                    </div>
                @endif
                @if ($dossier->motif_cloture)
                    <div class="mt-3 p-2 rounded" style="background:#faf6ee;font-size:.85rem;color:#4a5560;">
                        <strong>Motif de clôture :</strong> {{ $dossier->motif_cloture }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Timeline relances --}}
    <div class="col-md-7">
        <div class="card main-card" id="relance">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-history me-1" style="color:var(--school-gold-dark);"></i> Historique des relances</h6>
                <span class="compteur-badge">{{ $dossier->relances->count() }} action(s)</span>
            </div>
            <div class="card-body p-4">
                @if ($dossier->relances->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <p class="mb-0">Aucune relance enregistrée.</p>
                        @if (! $dossier->isClos())
                            <p class="text-muted small mb-0">Utilisez le bouton « Ajouter une relance ».</p>
                        @endif
                    </div>
                @else
                    <div class="timeline">
                        @foreach ($dossier->relances as $relance)
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="fas {{ $relance->type_relance?->icon() }}"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <div>
                                            <div class="timeline-type">{{ $relance->type_relance?->label() }}</div>
                                            <div class="timeline-date">
                                                <i class="far fa-calendar me-1"></i>{{ optional($relance->date_relance)->format('d/m/Y') }}
                                                @if ($relance->auteur)
                                                    · par {{ $relance->auteur->name }}
                                                @endif
                                            </div>
                                        </div>
                                        @if ($relance->resultat)
                                            <span class="timeline-resultat {{ $relance->resultat->css() }}">
                                                {{ $relance->resultat->label() }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($relance->contenu)
                                        <div class="timeline-body">{{ $relance->contenu }}</div>
                                    @endif
                                    @if ($relance->date_prochaine_relance)
                                        <div class="text-xs text-muted mt-2">
                                            <i class="far fa-clock me-1"></i>
                                            Relance suivante prévue le {{ $relance->date_prochaine_relance->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Modal nouvelle relance --}}
@can('recouvrement.relancer')
    @if (! $dossier->isClos())
    <div class="modal fade" id="relanceModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-reco">
                <form method="POST" action="{{ route('finances.recouvrement.relancer', $dossier) }}" id="relance-form">
                    @csrf
                    <div class="modal-header modal-header-reco">
                        <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Nouvelle relance</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select name="type_relance" class="form-select" required>
                                        @foreach ($typesRelance as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" name="date_relance" class="form-control"
                                           value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Résultat</label>
                                    <select name="resultat" class="form-select">
                                        <option value="">— Non renseigné —</option>
                                        @foreach ($resultats as $v => $l)
                                            <option value="{{ $v }}">{{ $l }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prochaine relance</label>
                                    <input type="date" name="date_prochaine_relance" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Contenu / Notes</label>
                                    <textarea name="contenu" rows="3" class="form-control" maxlength="2000"></textarea>
                                </div>
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
    @endif
@endcan

{{-- Modal changement de statut --}}
@can('recouvrement.gerer')
    @if (! $dossier->isClos())
    <div class="modal fade" id="statutModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-reco">
                <form method="POST" action="{{ route('finances.recouvrement.statut', $dossier) }}" id="statut-form">
                    @csrf
                    <div class="modal-header modal-header-reco">
                        <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Changer le statut du dossier</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-section">
                            <div class="mb-3">
                                <label class="form-label">Nouveau statut <span class="text-danger">*</span></label>
                                <select name="statut" class="form-select" required>
                                    @foreach ($statutsDossier as $v => $l)
                                        <option value="{{ $v }}" @selected($v === $dossier->statut?->value)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Motif (si clôture)</label>
                                <textarea name="motif" rows="2" class="form-control" maxlength="500"></textarea>
                            </div>
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
    @endif
@endcan
@endsection

@push('js')
<script>
$(function () {
    $('#relance-form').on('submit', function (e) {
        e.preventDefault();
        submitForm($(this));
    });

    $('#statut-form').on('submit', function (e) {
        e.preventDefault();
        submitForm($(this));
    });

    function submitForm($form) {
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: { 'Accept': 'application/json' },
            success: function (res) {
                $('.modal').modal('hide');
                window.showToastThenReload(res.message || 'Enregistré.', 'success');
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach(arr => window.showToast(arr[0], 'error'));
                } else {
                    window.showToast("Une erreur est survenue.", 'error');
                }
            }
        });
    }
});
</script>
@endpush