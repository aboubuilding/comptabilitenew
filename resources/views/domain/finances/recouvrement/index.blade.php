@extends('admin.layouts.app')

@section('title', 'Recouvrement')
@section('page_icon', 'fa-hand-holding-usd')
@section('page_title', 'Recouvrement des impayés')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Recouvrement</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.recouvrement.planification') }}" class="btn-sm rounded-pill px-3" id="lien-planification">
        <i class="fas fa-calendar-alt me-1"></i> Planification des relances
    </x-bouton>
    <x-bouton variante="contour" href="{{ route('finances.recouvrement.export', request()->query()) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('recouvrement.gerer')
        <x-bouton variante="nouveau" type="button" id="btnRecalculer">
            <i class="fas fa-sync-alt me-1"></i> Recalculer
        </x-bouton>
    @endcan
@endsection

@push('css')
<style>
    .reco-page { --rc-soft:var(--eim-cream); --rc-border:var(--eim-border); }
    .reco-page .stat-card { background:#fff; border:1px solid var(--rc-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); height:100%; }
    .reco-page .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .reco-page .stat-label { font-size:.75rem; color:var(--school-muted); }
    .onglets-reco { border-bottom:2px solid var(--rc-border); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets-reco .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets-reco .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets-reco .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--rc-soft); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--rc-border); }
    .filtre-input { border-radius:10px; border:1px solid var(--rc-border); padding:.5rem .85rem; font-size:.86rem; }
    .table-reco thead tr { background:var(--rc-soft); }
    .table-reco thead th { color:var(--school-red); font-size:.74rem; font-weight:700; text-transform:uppercase; padding:.85rem 1rem; border-bottom:2px solid var(--rc-border); white-space:nowrap; }
    .table-reco tbody td { padding:.8rem 1rem; border-bottom:1px solid var(--rc-border); font-size:.88rem; vertical-align:top; }
    .table-reco tbody tr:hover { background:var(--eim-hover); }
    /* Cinq colonnes qui tiennent dans la largeur : les libellés vont à la ligne */
    #table-dossiers, #table-relances { table-layout:fixed; width:100%; }
    #table-dossiers thead th, #table-relances thead th { white-space:normal; }
    #table-dossiers td, #table-relances td { overflow-wrap:anywhere; }
    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .num { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .echeance { font-size:.78rem; line-height:1.35; }
    .echeance .nature { display:inline-block; font-size:.62rem; font-weight:700; text-transform:uppercase; padding:.05rem .4rem; border-radius:4px; background:var(--rc-soft); color:var(--school-red); border:1px solid var(--rc-border); margin-right:.25rem; }
    .badge-statut.encours      { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.echeancier   { background:#e0ecfc; color:#2563eb; }
    .badge-statut.solde        { background:#e3f7ec; color:var(--eim-success); }
    .badge-statut.irrecouvrable{ background:#eceff2; color:var(--eim-ink-soft); }
    .badge-statut.contentieux  { background:#fdecea; color:var(--school-red); }
    .barre-selection { display:none; background:#fdf4e0; border-bottom:1px solid var(--rc-border); padding:.6rem 1.25rem; align-items:center; gap:.75rem; }
    .barre-selection.visible { display:flex; }
</style>
@endpush

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $peutRelancer = auth()->user()->can('recouvrement.relancer');
    $onglets = [
        'tous'     => ['Tous les dossiers', 'fas fa-folder-open'],
        'clotures' => ['Dossiers clôturés', 'fas fa-folder'],
        'relances' => ['Historique des relances', 'fas fa-history'],
    ];
@endphp
<div class="reco-page">
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-folder-open" couleur="gold" libelle="Dossiers en cours">{{ $compteurs['en_cours'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-calendar-check" couleur="blue" libelle="Promesses de paiement">{{ $compteurs['echeancier'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Dossiers clôturés">{{ $compteurs['solde'] + $compteurs['irrecouvrable'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-lg-3">
            <x-stat icone="fas fa-coins" couleur="red" id="reste-a-recouvrer">{{ $fmt($montants['total_restant']) }} F
                <x-slot:libelle>Reste à recouvrer — {{ $annee?->libelle }}</x-slot:libelle>
            </x-stat>
        </div>
    </div>

    <x-carte>
        <ul class="nav onglets-reco px-3 pt-2" id="onglets-recouvrement">
            @foreach ($onglets as $cle => [$libelle, $icone])
                <li class="nav-item">
                    <a class="nav-link @if ($onglet === $cle) active @endif" id="onglet-{{ $cle }}" href="{{ route('finances.recouvrement.index', ['onglet' => $cle]) }}">
                        <i class="{{ $icone }} me-1"></i> {{ $libelle }} <span class="compte">{{ $nbOnglets[$cle] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center" id="filtres-recouvrement">
                <input type="hidden" name="onglet" value="{{ $onglet }}">
                <div class="col-md-4">
                    <x-recherche name="search" placeholder="Élève, matricule..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                @if ($onglet === 'relances')
                    <div class="col-md-3">
                        <select name="canal" class="form-select filtre-input" id="filtre-canal">
                            <option value="">— Tous les canaux —</option>
                            @foreach ($canaux as $v => $l)
                                <option value="{{ $v }}" @selected(($filters['canal'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-3">
                        <select name="nature" class="form-select filtre-input" id="filtre-nature" title="Dossiers qui réclament ce type de paiement">
                            <option value="">— Toutes les natures —</option>
                            @foreach ($natures as $v => $l)
                                <option value="{{ $v }}" @selected(($filters['nature'] ?? '') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if ($onglet === 'tous')
                        <div class="col-md-2">
                            <select name="statut" class="form-select filtre-input">
                                <option value="">— Statut —</option>
                                @foreach ($statuts as $v => $l)
                                    <option value="{{ $v }}" @selected(($filters['statut'] ?? '') === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endif
                <div class="col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.recouvrement.index', ['onglet' => $onglet]) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
                <div class="col small text-muted text-end" id="annee-recouvrement"><i class="fas fa-calendar-alt me-1"></i>Année {{ $annee?->libelle ?? '—' }}</div>
            </form>
        </div>

        @if ($onglet === 'relances')
            {{-- ==================== Historique des relances ==================== --}}
            @if ($peutRelancer)
                <div class="d-flex flex-wrap align-items-center gap-2 px-4 py-2" style="background:var(--eim-hover); border-bottom:1px solid var(--rc-border);" id="barre-envoi">
                    <span class="small"><strong id="nb-relances-selection">0</strong> relance(s) sélectionnée(s)</span>
                    <x-bouton type="button" class="btn-sm" id="btn-envoyer-relances" disabled><i class="fas fa-paper-plane me-1"></i>Envoyer la sélection</x-bouton>
                    @unless ($envoiActif)
                        <span class="small text-muted ms-auto" id="envoi-desactive"><i class="fas fa-info-circle me-1"></i>Envoi réel des emails et SMS pas encore activé : les messages et leurs statuts sont conservés.</span>
                    @endunless
                </div>
            @endif
            <x-tableau class="table-reco align-middle mb-0" id="table-relances">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:15%;">
                            @if ($peutRelancer)<input type="checkbox" class="form-check-input me-2" id="tout-selectionner-relances" title="Tout sélectionner">@endif
                            Date / canal
                        </th>
                        <th style="width:22%;">Élève</th>
                        <th style="width:33%;">Message</th>
                        <th style="width:17%;">Destinataire / envoi</th>
                        <th class="text-end pe-4" style="width:13%;">Montant réclamé</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($historique as $r)
                    @php $eleve = $r->inscription?->eleve; @endphp
                    <tr data-relance="{{ $r->id }}">
                        <td class="ps-4">
                            <div class="fw-semibold">
                                @if ($peutRelancer && in_array($r->statut_envoi, \App\Domain\Finances\Services\RelanceService::A_ENVOYER, true) && $r->destinataire)
                                    <input type="checkbox" class="form-check-input me-2 selection-relance" value="{{ $r->id }}">
                                @endif
                                {{ $r->date_relance?->format('d/m/Y') }}
                            </div>
                            <div class="cell-sub"><i class="fas {{ $r->type_relance?->icon() ?? 'fa-comment' }} me-1"></i>{{ $r->type_relance?->label() ?? '—' }}</div>
                            @if ($r->auteur)<div class="cell-sub">par {{ $r->auteur->nom }} {{ $r->auteur->prenom }}</div>@endif
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : '—' }}</div>
                            <div class="cell-sub">{{ $eleve?->matricule }}@if ($r->inscription?->niveau) · {{ $r->inscription->niveau->libelle }}@endif @if ($r->inscription?->classe) · {{ $r->inscription->classe->libelle }}@endif</div>
                            <div class="cell-sub">{{ $r->dossier_recouvrement_id ? 'Dossier de recouvrement' : 'Relance préventive' }}</div>
                        </td>
                        <td style="max-width:320px;"><div class="cell-sub" style="white-space:pre-line;">{{ \Illuminate\Support\Str::limit($r->contenu, 180) }}</div></td>
                        <td>
                            <div class="small">{{ $r->destinataire ?: '—' }}</div>
                            <x-badge class="{{ $r->statut_envoi === 'sans_destinataire' ? 'contentieux' : ($r->statut_envoi === 'envoye' ? 'solde' : 'encours') }}" point>{{ $r->libelleStatutEnvoi() }}</x-badge>
                        </td>
                        <td class="text-end pe-4 num">{{ $fmt($r->montant_du_moment) }} F</td>
                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-history" :paragraphe="false">Aucune relance pour l'année {{ $annee?->libelle }}.</x-vide>
                @endforelse
                </tbody>
            </x-tableau>
            <div class="p-3"><x-pagination :liste="$historique" /></div>
        @else
            {{-- ==================== Dossiers ==================== --}}
            @if ($peutRelancer && $onglet === 'tous')
                <div class="barre-selection" id="barre-selection">
                    <span><strong id="nb-selection">0</strong> dossier(s) sélectionné(s)</span>
                    <x-bouton type="button" class="btn-sm" id="btn-relance-groupee"><i class="fas fa-paper-plane me-1"></i>Relance groupée</x-bouton>
                </div>
            @endif
            <x-tableau class="table-reco mb-0" id="table-dossiers">
                <thead>
                    <tr>
                        <th class="ps-4" style="width:26%;">
                            @if ($peutRelancer && $onglet === 'tous')<input type="checkbox" class="form-check-input me-2" id="tout-selectionner" title="Tout sélectionner">@endif
                            Élève
                        </th>
                        <th style="width:30%;">Échéances réclamées</th>
                        <th class="text-end" style="width:14%;">Montant réclamé</th>
                        <th style="width:20%;">{{ $onglet === 'clotures' ? 'Clôture' : 'Statut / relances' }}</th>
                        <th class="text-center pe-4" style="width:10%; white-space:nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($dossiers as $d)
                    @php
                        $eleve = $d->inscription?->eleve;
                        $relance = $d->derniereRelance();
                        $reclamees = $d->echeances;
                        $ouvert = ! $d->isClos();
                    @endphp
                    <tr data-dossier="{{ $d->id }}">
                        <td class="ps-4">
                            <div class="d-flex gap-2">
                                @if ($peutRelancer && $onglet === 'tous' && $ouvert)
                                    <input type="checkbox" class="form-check-input mt-1 selection-dossier" value="{{ $d->id }}">
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : '—' }}</div>
                                    <div class="cell-sub">{{ $eleve?->matricule }}</div>
                                    <div class="cell-sub niveau-classe">
                                        {{ $d->inscription?->niveau?->libelle ?? '—' }}@if ($d->inscription?->classe) · {{ $d->inscription->classe->libelle }}@endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @forelse ($reclamees->take(4) as $e)
                                <div class="echeance">
                                    <span class="nature">{{ $e->nature?->label() }}</span>{{ preg_replace('/^.* — /u', '', $e->libelle) }}
                                    <span class="cell-sub">({{ $e->date_echeance?->format('d/m/Y') }})</span>
                                    @if ($ouvert)<span class="num" style="color:var(--school-red);">{{ $fmt($e->reste) }} F</span>@endif
                                </div>
                            @empty
                                <span class="cell-sub">—</span>
                            @endforelse
                            @if ($reclamees->count() > 4)<div class="cell-sub">+ {{ $reclamees->count() - 4 }} autre(s) échéance(s)</div>@endif
                        </td>
                        <td class="text-end">
                            <div class="num" style="color:{{ $ouvert ? 'var(--school-red)' : '#1F7A4D' }};">{{ $fmt($ouvert ? $d->montant_restant : $d->montant_initial_du) }} F</div>
                            <div class="cell-sub">échu {{ $fmt($d->montant_initial_du) }} F</div>
                            @if ($ouvert && $d->montant_recouvre > 0)<div class="cell-sub">payé {{ $fmt($d->montant_recouvre) }} F</div>@endif
                        </td>
                        <td>
                            <x-badge class="{{ $d->statut?->css() }}" point>{{ $d->statut?->label() }}</x-badge>
                            @if ($ouvert)
                                <div class="cell-sub mt-1">Ouvert le {{ $d->date_ouverture?->format('d/m/Y') }}</div>
                                <div class="cell-sub">
                                    @if ($relance)
                                        <i class="fas fa-paper-plane me-1"></i>{{ $d->relances->count() }} relance(s), la dernière le {{ $relance->date_relance?->format('d/m/Y') }}@if ($relance->resultat) — {{ $relance->resultat->label() }}@endif
                                    @else
                                        Aucune relance
                                    @endif
                                </div>
                            @else
                                <div class="cell-sub mt-1">Clôturé le {{ $d->date_cloture?->format('d/m/Y') }}</div>
                                @if ($d->paiementCloture)
                                    <div class="cell-sub paiement-cloture"><i class="fas fa-receipt me-1"></i>Payé par {{ $d->paiementCloture->reference }}
                                        ({{ $fmt($d->paiementCloture->montant) }} F, {{ $d->paiementCloture->date_paiement?->format('d/m/Y') }})</div>
                                @elseif ($d->motif_cloture)
                                    <div class="cell-sub">{{ $d->motif_cloture }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="text-center pe-4">
                            <x-actions>
                                <li><a class="dropdown-item" href="{{ route('finances.recouvrement.show', $d) }}"><i class="fas fa-eye"></i> Voir le dossier</a></li>
                                @if ($peutRelancer && $ouvert)
                                    <li><button type="button" class="dropdown-item btn-relancer-eleve" data-dossier="{{ $d->id }}"><i class="fas fa-paper-plane"></i> Relancer cet élève</button></li>
                                @endif
                            </x-actions>
                        </td>
                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-folder-open" :paragraphe="false">
                        <p class="mb-0 fw-medium" id="pas-de-dossiers">Pas de dossiers{{ $onglet === 'clotures' ? ' clôturés' : '' }} pour l'année {{ $annee?->libelle }}.</p>
                        @if ($onglet === 'tous' && empty($filters['search']) && empty($filters['nature']) && empty($filters['statut']))
                            <small class="text-muted">Aucun élève n'est en retard sur une échéance échue, ou le recouvrement n'a pas encore été recalculé.</small>
                        @endif
                    </x-vide>
                @endforelse
                </tbody>
            </x-tableau>
            <div class="p-3"><x-pagination :liste="$dossiers" /></div>
        @endif
    </x-carte>
</div>

@include('domain.finances.recouvrement.partials.relance-modale')
@endsection

@push('js')
<script>
$(function () {
    // Sélection pour la relance groupée
    const majSelection = () => {
        const nb = $('.selection-dossier:checked').length;
        $('#nb-selection').text(nb);
        $('#barre-selection').toggleClass('visible', nb > 0);
    };
    $('#tout-selectionner').on('change', function () { $('.selection-dossier').prop('checked', this.checked); majSelection(); });
    $(document).on('change', '.selection-dossier', majSelection);
    $('#btn-relance-groupee').on('click', () => window.ouvrirRelance({ cible: 'dossiers', ids: $('.selection-dossier:checked').map((i, c) => c.value).get() }));
    $(document).on('click', '.btn-relancer-eleve', function () { window.ouvrirRelance({ cible: 'dossiers', ids: [$(this).data('dossier')] }); });

    // Recalcul de l'année de l'en-tête : confirmation puis bilan dans des fenêtres SweetAlert
    const ANNEE = @json($annee?->libelle);
    const fmt = v => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v || 0);
    $('#btnRecalculer').on('click', function () {
        Swal.fire({
            icon: 'question',
            title: 'Recalculer le recouvrement ?',
            html: `Les dossiers de l'année <strong>${ANNEE}</strong> sont recalculés d'après les échéances échues (scolarité, cantine, transport).<br>`
                + '<small class="text-muted">Ce recalcul se fait aussi automatiquement chaque nuit à 2 h.</small>',
            showCancelButton: true, confirmButtonText: 'Recalculer', cancelButtonText: 'Annuler', reverseButtons: true,
            showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => $.ajax({
                url: '{{ route('finances.recouvrement.recalculer') }}', method: 'POST',
                data: { _token: '{{ csrf_token() }}' }, headers: { 'Accept': 'application/json' },
            }).then(res => res.data || res).catch(xhr => { Swal.showValidationMessage(xhr.responseJSON?.message || 'Erreur lors du recalcul.'); }),
        }).then(resultat => {
            if (!resultat.isConfirmed || !resultat.value) return;
            const b = resultat.value;
            const aucun = (b.echus ?? 0) === 0;
            Swal.fire({
                icon: aucun ? 'info' : 'success',
                title: aucun ? `Pas de dossiers pour ${ANNEE}` : 'Recouvrement recalculé',
                html: aucun
                    ? `Aucun élève n'est en retard sur une échéance échue pour l'année ${ANNEE}.`
                        + ((b.dossiers_soldes ?? 0) > 0 ? `<br>${b.dossiers_soldes} dossier(s) clôturé(s) : les familles ont réglé.` : '')
                    : `<strong>${b.echus}</strong> élève(s) en retard pour <strong>${fmt(b.retard)} F</strong><br>`
                        + `${b.dossiers_ouverts ?? 0} dossier(s) ouvert(s) · ${b.dossiers_maj ?? 0} mis à jour · ${b.dossiers_soldes ?? 0} clôturé(s)`,
                confirmButtonText: 'Voir les dossiers',
            }).then(() => window.location.reload());
        });
    });

    // Historique : envoi réel des relances sélectionnées
    const relancesChoisies = () => $('.selection-relance:checked').map((i, c) => c.value).get();
    const majEnvoi = () => { const n = relancesChoisies().length; $('#nb-relances-selection').text(n); $('#btn-envoyer-relances').prop('disabled', n === 0); };
    $('#tout-selectionner-relances').on('change', function () { $('.selection-relance').prop('checked', this.checked); majEnvoi(); });
    $(document).on('change', '.selection-relance', majEnvoi);
    $('#btn-envoyer-relances').on('click', function () {
        const ids = relancesChoisies();
        Swal.fire({
            icon: 'question', title: `Envoyer ${ids.length} relance(s) ?`,
            text: 'Chaque message part à son destinataire, par email ou par SMS.',
            showCancelButton: true, confirmButtonText: 'Envoyer', cancelButtonText: 'Annuler', reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => $.ajax({
                url: '{{ route('finances.recouvrement.relances.envoyer') }}', method: 'POST',
                data: { _token: '{{ csrf_token() }}', relances: ids }, headers: { 'Accept': 'application/json' },
            }).then(res => ({ ok: true, res })).catch(xhr => ({ ok: false, message: xhr.responseJSON?.message || 'Une erreur est survenue.' })),
        }).then(resultat => {
            if (!resultat.isConfirmed) return;
            const r = resultat.value;
            if (!r.ok) {
                Swal.fire({ icon: 'info', title: 'Envoi non disponible', text: r.message, confirmButtonText: 'Compris' });
                return;
            }
            const b = r.res.data?.bilan || {};
            Swal.fire({ icon: (b.echecs ?? 0) > 0 ? 'warning' : 'success', title: 'Envoi terminé',
                html: `${b.envoyes ?? 0} envoyée(s) · ${b.echecs ?? 0} en échec · ${b.ignores ?? 0} ignorée(s)` }).then(() => window.location.reload());
        });
    });
});
</script>
@endpush
