@extends('admin.layouts.app')

@section('title', 'Dossier de recouvrement')
@section('page_icon', 'fa-folder-open')
@section('page_title', 'Dossier de recouvrement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.recouvrement.index') }}">Recouvrement</a></li>
    <li class="breadcrumb-item active">{{ $dossier->inscription?->eleve ? $dossier->inscription->eleve->nom . ' ' . $dossier->inscription->eleve->prenom : 'Dossier' }}</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.recouvrement.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>
    @if (! $dossier->isClos())
        @can('recouvrement.gerer')
            <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#statutModal">
                <i class="fas fa-exchange-alt me-1"></i> Changer le statut
            </x-bouton>
        @endcan
        @can('recouvrement.relancer')
            <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#relanceModal">
                <i class="fas fa-phone me-1"></i> Noter un appel / courrier
            </x-bouton>
            <x-bouton variante="nouveau" type="button" id="btn-relance-message" onclick="window.ouvrirRelance({ cible: 'dossiers', ids: [{{ $dossier->id }}] })">
                <i class="fas fa-paper-plane me-1"></i> Relancer par email / SMS
            </x-bouton>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--eim-border) !important; }
    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.8rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; }
    .info-value { font-weight:600; color:var(--school-ink); }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.positif { color:var(--eim-success); }
    .num.negatif { color:var(--school-red); }
    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.4em .9em; border-radius:999px; font-size:.75rem; font-weight:600; }
    .badge-statut.encours      { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.echeancier   { background:#e0ecfc; color:#2563eb; }
    .badge-statut.solde        { background:#e3f7ec; color:var(--eim-success); }
    .badge-statut.irrecouvrable{ background:#eceff2; color:var(--eim-ink-soft); }
    .badge-statut.contentieux  { background:#fdecea; color:var(--school-red); }

    .timeline { position:relative; padding-left:32px; }
    .timeline::before { content:''; position:absolute; left:14px; top:8px; bottom:8px; width:2px; background:var(--eim-border); }
    .timeline-item { position:relative; padding-bottom:1.25rem; }
    .timeline-item:last-child { padding-bottom:0; }
    .timeline-marker {
        position:absolute; left:-32px; top:2px; width:28px; height:28px; border-radius:50%;
        display:flex; align-items:center; justify-content:center; font-size:.75rem;
        background:var(--eim-cream); color:var(--school-red); border:2px solid #fff;
        box-shadow: 0 0 0 2px var(--eim-border);
    }
    .timeline-content { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.85rem 1rem; }
    .timeline-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.4rem; }
    .timeline-type { font-weight:700; color:var(--school-ink); font-size:.9rem; }
    .timeline-date { font-size:.75rem; color:var(--school-muted); }
    .timeline-body { font-size:.85rem; color:var(--eim-ink-soft); }
    .timeline-resultat { display:inline-block; padding:.2em .7em; border-radius:999px; font-size:.7rem; font-weight:600; margin-top:.4rem; }
    .timeline-resultat.neutre  { background:#eceff2; color:var(--eim-ink-soft); }
    .timeline-resultat.attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .timeline-resultat.partiel { background:#e0ecfc; color:#2563eb; }
    .timeline-resultat.positif { background:#e3f7ec; color:var(--eim-success); }
    .timeline-resultat.negatif { background:#fdecea; color:var(--school-red); }
    .empty-state { padding:3rem 1rem; text-align:center; color:#7A6A6A; font-size:.85rem; }
    .empty-state i { font-size:2rem; margin-bottom:.5rem; display:block; color:#d8c8ae; }
    .modal-reco { border:none; border-radius:16px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,.15); }
    .modal-header-reco { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-bottom:none; padding:1.15rem 1.5rem; }
    .form-section { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:12px; padding:1.1rem; }
    .form-section .form-label { font-size:.82rem; font-weight:600; color:var(--school-ink); }
    .form-section .form-control, .form-section .form-select { border-radius:8px; border:1px solid var(--eim-border); padding:.55rem .75rem; font-size:.88rem; }
    .btn-annuler { background:#fff; border:1px solid var(--eim-border); color:var(--eim-ink-soft); border-radius:8px; padding:.55rem 1.2rem; font-size:.88rem; }
    .btn-enregistrer { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:8px; font-weight:600; padding:.55rem 1.4rem; font-size:.88rem; }
</style>
@endsection

@section('contenu')
<div class="row g-4">

    {{-- Fiche dossier --}}
    <div class="col-md-5">
        <x-carte>
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-folder-open me-1" style="color:var(--school-gold-dark);"></i> Dossier de recouvrement</h6>
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
                <div class="info-row">
                    <span class="info-label">Niveau / classe</span>
                    <span class="info-value">{{ $dossier->inscription?->niveau?->libelle ?? '—' }}@if ($dossier->inscription?->classe) · {{ $dossier->inscription->classe->libelle }}@endif</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Natures réclamées</span>
                    <span class="info-value">{{ $dossier->echeances->map(fn ($e) => $e->nature?->label())->filter()->unique()->implode(', ') ?: ($dossier->nature?->label() ?? '—') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <x-badge class="{{ $dossier->statut?->css() }}" point>{{ $dossier->statut?->label() }}
                    </x-badge>
                </div>
                <div class="info-row">
                    <span class="info-label">Montant échu réclamé</span>
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
                @if ($dossier->paiementCloture)
                    <div class="info-row" id="paiement-cloture">
                        <span class="info-label">Soldé par le paiement</span>
                        <span class="info-value">{{ $dossier->paiementCloture->reference }} — {{ number_format((float) $dossier->paiementCloture->montant, 0, ',', ' ') }} F
                            <span class="text-muted fw-normal">({{ $dossier->paiementCloture->date_paiement?->format('d/m/Y') }})</span></span>
                    </div>
                @endif
                @if ($dossier->motif_cloture)
                    <div class="mt-3 p-2 rounded" style="background:var(--eim-cream);font-size:.85rem;color:var(--eim-ink-soft);">
                        <strong>Motif de clôture :</strong> {{ $dossier->motif_cloture }}
                    </div>
                @endif
            </div>
        </x-carte>

        {{-- Échéances réclamées --}}
        <x-carte class="mt-4" id="echeances-reclamees">
            <div class="card-header bg-white py-3 px-4">
                <h6 class="mb-0"><i class="fas fa-list-ol me-1" style="color:var(--school-gold-dark);"></i> Échéances réclamées</h6>
            </div>
            <x-tableau class="table-sm align-middle mb-0">
                <thead>
                    <tr><th class="ps-3">Échéance</th><th>Date</th><th class="text-end">Montant</th><th class="text-end pe-3">Reste</th></tr>
                </thead>
                <tbody>
                @forelse ($dossier->echeances as $e)
                    <tr>
                        <td class="ps-3"><span class="badge bg-light text-dark border me-1">{{ $e->nature?->label() }}</span>{{ preg_replace('/^.* — /u', '', $e->libelle) }}</td>
                        <td>{{ $e->date_echeance?->format('d/m/Y') }}</td>
                        <td class="text-end">{{ number_format($e->montant, 0, ',', ' ') }} F</td>
                        <td class="text-end pe-3 fw-bold" style="color:{{ $e->reste > 0 ? 'var(--school-red)' : '#1F7A4D' }};">{{ $e->reste > 0 ? number_format($e->reste, 0, ',', ' ') . ' F' : 'Réglée' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Aucune échéance enregistrée (recalculez le recouvrement).</td></tr>
                @endforelse
                </tbody>
            </x-tableau>
        </x-carte>
    </div>

    {{-- Timeline relances --}}
    <div class="col-md-7">
        <x-carte id="relance">
            <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-history me-1" style="color:var(--school-gold-dark);"></i> Historique des relances</h6>
                <x-compteur>{{ $dossier->relances->count() }} action(s)</x-compteur>
            </div>
            <div class="card-body p-4">
                @if ($dossier->relances->isEmpty())
                    <x-vide icone="fas fa-bullhorn" :paragraphe="false"><p class="mb-0">Aucune relance enregistrée.</p>
                        @if (! $dossier->isClos())
                            <p class="text-muted small mb-0">Utilisez le bouton « Ajouter une relance ».</p>
                        @endif</x-vide>
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
                                        <div class="timeline-body" style="white-space:pre-line;">{{ $relance->contenu }}</div>
                                    @endif
                                    @if ($relance->destinataire || $relance->statut_envoi)
                                        <div class="text-xs text-muted mt-2">
                                            <i class="fas fa-at me-1"></i>{{ $relance->destinataire ?: 'Aucun destinataire' }} · {{ $relance->libelleStatutEnvoi() }}
                                        </div>
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
        </x-carte>
    </div>
</div>

{{-- Modal nouvelle relance --}}
@can('recouvrement.relancer')
    @if (! $dossier->isClos())
    <x-modale id="relanceModal" data-bs-backdrop="static" contenu-class="modal-reco" entete-class="modal-header-reco" corps-class="p-4" croix-blanche>
        <x-slot:formulaire method="POST" action="{{ route('finances.recouvrement.relancer', $dossier) }}" id="relance-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-bullhorn me-2"></i>Nouvelle relance</x-slot:titre>
        <x-section>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-libelle requis>Type</x-libelle>
                    <select name="type_relance" class="form-select" required>
                        @foreach ($typesRelance as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle requis>Date</x-libelle>
                    <input type="date" name="date_relance" class="form-control"
                           value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-6">
                    <x-libelle>Résultat</x-libelle>
                    <select name="resultat" class="form-select">
                        <option value="">— Non renseigné —</option>
                        @foreach ($resultats as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <x-libelle>Prochaine relance</x-libelle>
                    <input type="date" name="date_prochaine_relance" class="form-control">
                </div>
                <div class="col-12">
                    <x-libelle>Contenu / Notes</x-libelle>
                    <textarea name="contenu" rows="3" class="form-control" maxlength="2000"></textarea>
                </div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Enregistrer</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endif
@endcan

{{-- Modal changement de statut --}}
@can('recouvrement.gerer')
    @if (! $dossier->isClos())
    <x-modale id="statutModal" data-bs-backdrop="static" contenu-class="modal-reco" entete-class="modal-header-reco" corps-class="p-4" croix-blanche>
        <x-slot:formulaire method="POST" action="{{ route('finances.recouvrement.statut', $dossier) }}" id="statut-form">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-exchange-alt me-2"></i>Changer le statut du dossier</x-slot:titre>
        <x-section>
            <div class="mb-3">
                <x-libelle requis>Nouveau statut</x-libelle>
                <select name="statut" class="form-select" required>
                    @foreach ($statutsDossier as $v => $l)
                        <option value="{{ $v }}" @selected($v === $dossier->statut?->value)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-0">
                <x-libelle>Motif (si clôture)</x-libelle>
                <textarea name="motif" rows="2" class="form-control" maxlength="500"></textarea>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit"><i class="fas fa-save me-1"></i> Mettre à jour</x-bouton>
        </x-slot:pied>
    </x-modale>
    @endif
@endcan
@include('domain.finances.recouvrement.partials.relance-modale')
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