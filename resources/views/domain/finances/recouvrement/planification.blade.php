@extends('admin.layouts.app')

@section('title', 'Planification des relances')
@section('page_icon', 'fa-calendar-alt')
@section('page_title', 'Planification des relances')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.recouvrement.index') }}">Recouvrement</a></li>
    <li class="breadcrumb-item active">Planification des relances</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.recouvrement.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Dossiers de recouvrement
    </x-bouton>
@endsection

@push('css')
<style>
    .plan-page { --pl-soft:var(--eim-cream); --pl-border:var(--eim-border); }
    .plan-page .stat-card { background:#fff; border:1px solid var(--pl-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); height:100%; }
    .plan-page .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .plan-page .stat-label { font-size:.75rem; color:var(--school-muted); }
    .filtre-input { border-radius:10px; border:1px solid var(--pl-border); padding:.5rem .85rem; font-size:.86rem; }
    .section-titre { font-weight:700; color:var(--school-ink); font-size:.95rem; }
    .table-plan thead tr { background:var(--pl-soft); }
    .table-plan thead th { color:var(--school-red); font-size:.74rem; font-weight:700; text-transform:uppercase; padding:.8rem 1rem; border-bottom:2px solid var(--pl-border); white-space:normal; }
    .table-plan tbody td { padding:.7rem 1rem; border-bottom:1px solid var(--pl-border); font-size:.86rem; vertical-align:top; overflow-wrap:anywhere; }
    .table-plan tbody tr:hover { background:var(--eim-hover); }
    #table-non-a-jour { table-layout:fixed; width:100%; }
    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .num { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .nature { display:inline-block; font-size:.66rem; font-weight:700; text-transform:uppercase; padding:.1rem .45rem; border-radius:4px; background:var(--pl-soft); color:var(--school-red); border:1px solid var(--pl-border); }
    .echeance { font-size:.78rem; }
    .barre-relance { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; padding:.75rem 1.25rem; background:var(--eim-hover); border-bottom:1px solid var(--pl-border); }
</style>
@endpush

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $peutRelancer = auth()->user()->can('recouvrement.relancer');
    $niveau = $niveaux->firstWhere('id', $niveauId);
    $cible = trim(($nature ? ($natures[$nature] ?? $nature) : 'tous types de paiement') . ($niveau ? ' — niveau ' . $niveau->libelle : '') . ($libelle ? ' — échéance « ' . $libelle . ' »' : ''));
@endphp
<div class="plan-page">
    {{-- Simulation : qui ne sera pas à jour, à quelle date, pour quoi --}}
    <x-carte class="mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end" id="filtres-planification">
                <div class="col-6 col-md-3">
                    <x-libelle for="pl-jour" class="small">Pas à jour au</x-libelle>
                    <input type="date" name="jour" id="pl-jour" class="form-control filtre-input" value="{{ $jour->toDateString() }}">
                </div>
                <div class="col-6 col-md-2">
                    <x-libelle for="pl-nature" class="small">Type de paiement</x-libelle>
                    <select name="nature" id="pl-nature" class="form-select filtre-input">
                        <option value="">— Tous —</option>
                        @foreach ($natures as $v => $l)
                            <option value="{{ $v }}" @selected($nature === $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <x-libelle for="pl-niveau" class="small">Niveau</x-libelle>
                    <select name="niveau_id" id="pl-niveau" class="form-select filtre-input">
                        <option value="">— Tous —</option>
                        @foreach ($niveaux as $n)
                            <option value="{{ $n->id }}" @selected($niveauId === $n->id)>{{ $n->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <x-libelle for="pl-libelle" class="small">Échéance (contient)</x-libelle>
                    <input type="text" name="libelle" id="pl-libelle" class="form-control filtre-input" placeholder="T2, novembre..." value="{{ $libelle }}">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Simuler"><i class="fas fa-search me-1"></i>Simuler</x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.recouvrement.planification') }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
            <div class="cell-sub mt-2"><i class="fas fa-info-circle me-1"></i>Année {{ $annee?->libelle }}. Échéances : scolarité (plans d'échéancier), cantine et transport (une par mois d'abonnement). Les paiements encaissés couvrent les échéances les plus anciennes d'abord.</div>
        </div>
    </x-carte>

    {{-- Par type de paiement --}}
    <div class="row g-3 mb-4" id="resume-par-nature">
        @forelse ($parNature as $n => $t)
            <div class="col-sm-6 col-lg-4">
                <x-stat icone="fas fa-user-clock" couleur="red">{{ $t['eleves'] }} élève(s) · {{ $fmt($t['reste']) }} F
                    <x-slot:libelle>{{ $natures[$n] ?? $n }} — pas à jour au {{ $jour->format('d/m/Y') }}</x-slot:libelle>
                </x-stat>
            </div>
        @empty
            <div class="col-12 text-muted small">Tous les élèves seront à jour au {{ $jour->format('d/m/Y') }}.</div>
        @endforelse
    </div>

    {{-- Élèves qui ne seront pas à jour au jour choisi --}}
    <x-carte id="eleves-non-a-jour">
        <div class="card-header bg-white py-3 px-4 d-flex flex-wrap align-items-center gap-2">
            <span class="section-titre">
                <i class="fas fa-user-clock me-1" style="color:var(--school-red);"></i>
                Pas à jour au {{ $jour->format('d/m/Y') }} — {{ $cible }}
            </span>
            <span class="cell-sub ms-auto" id="total-non-a-jour">{{ $totalEleves['nb'] }} élève(s) · {{ $fmt($totalEleves['reste']) }} F</span>
        </div>

        @if ($peutRelancer && $totalEleves['nb'] > 0)
            <div class="barre-relance" id="barre-relance">
                <span class="small fw-semibold me-1"><i class="fas fa-paper-plane me-1"></i>Relancer :</span>
                <x-bouton type="button" class="btn-sm" id="btn-relancer-tous" data-nombre="{{ $totalEleves['nb'] }}">
                    Tous les élèves de la liste ({{ $totalEleves['nb'] }})
                </x-bouton>
                <x-bouton variante="contour" type="button" class="btn-sm" id="btn-relancer-selection" disabled>
                    La sélection (<span id="nb-selection">0</span>)
                </x-bouton>
                <x-bouton variante="contour" type="button" class="btn-sm" id="btn-relance-ciblee" data-bs-toggle="modal" data-bs-target="#relanceCibleeModal">
                    <i class="fas fa-user-plus me-1"></i>Des élèves choisis
                </x-bouton>
                <span class="cell-sub ms-auto">Concerne : {{ $cible }}</span>
            </div>
        @endif

        <x-tableau class="table-plan mb-0" id="table-non-a-jour">
            <thead>
                <tr>
                    <th class="ps-4" style="width:26%;">@if ($peutRelancer)<input type="checkbox" class="form-check-input me-2" id="tout-selectionner">@endif Élève</th>
                    <th style="width:36%;">Échéances non couvertes</th>
                    <th class="text-end" style="width:12%;">Manque</th>
                    <th style="width:17%;">Situation</th>
                    <th class="text-center pe-4" style="width:9%; white-space:nowrap;">Action</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($eleves as $e)
                <tr data-inscription="{{ $e['inscription_id'] }}">
                    <td class="ps-4">
                        <div class="d-flex gap-2">
                            @if ($peutRelancer)<input type="checkbox" class="form-check-input mt-1 selection-eleve" value="{{ $e['inscription_id'] }}">@endif
                            <div>
                                <div class="fw-semibold">{{ $e['nom'] }}</div>
                                <div class="cell-sub">{{ $e['matricule'] }} · {{ $e['niveau'] ?? '—' }}@if ($e['classe']) · {{ $e['classe'] }}@endif</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @foreach (collect($e['echeances'])->take(4) as $ech)
                            <div class="echeance"><span class="nature">{{ $natures[$ech['nature']] ?? $ech['nature'] }}</span>
                                {{ preg_replace('/^.* — /u', '', $ech['libelle']) }} <span class="cell-sub">({{ $ech['date']?->format('d/m/Y') }})</span> {{ $fmt($ech['reste']) }} F</div>
                        @endforeach
                        @if (count($e['echeances']) > 4)<div class="cell-sub">+ {{ count($e['echeances']) - 4 }} autre(s)</div>@endif
                    </td>
                    <td class="text-end"><span class="num" style="color:var(--school-red);">{{ $fmt($e['reste']) }} F</span></td>
                    <td class="cell-sub">
                        @if ($e['premiere'] && $e['premiere']->lte(today())) En retard depuis le {{ $e['premiere']->format('d/m/Y') }} @else À échoir — relance préventive @endif
                    </td>
                    <td class="text-center pe-4">
                        @if ($peutRelancer)
                            <x-bouton variante="contour" type="button" class="btn-sm btn-relancer-un" data-inscription="{{ $e['inscription_id'] }}" title="Relancer cet élève"><i class="fas fa-paper-plane"></i></x-bouton>
                        @endif
                    </td>
                </tr>
            @empty
                <x-vide colspan="5" icone="fas fa-check-circle" :paragraphe="false">Tous les élèves seront à jour au {{ $jour->format('d/m/Y') }}.</x-vide>
            @endforelse
            </tbody>
        </x-tableau>
        <div class="p-3"><x-pagination :liste="$eleves" /></div>
    </x-carte>
</div>

{{-- Relance ciblée : choisir les élèves (Select2) parmi ceux de la simulation --}}
@if ($peutRelancer && $totalEleves['nb'] > 0)
<x-modale id="relanceCibleeModal" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" croix-blanche>
    <x-slot:titre><i class="fas fa-user-plus me-2"></i>Relancer des élèves choisis</x-slot:titre>
    <p class="small text-muted mb-2">Élèves pas à jour au {{ $jour->format('d/m/Y') }} — {{ $cible }}.</p>
    <x-libelle for="relance-eleves-choisis" requis>Élèves à relancer</x-libelle>
    <select id="relance-eleves-choisis" class="form-select" multiple>
        @foreach ($choix as $c)
            <option value="{{ $c['id'] }}">{{ $c['text'] }}</option>
        @endforeach
    </select>
    <div class="form-text small">Tapez un nom ou un matricule pour ajouter un élève.</div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="button" id="btn-continuer-ciblee"><i class="fas fa-arrow-right me-1"></i>Rédiger le message</x-bouton>
    </x-slot:pied>
</x-modale>
@endif

@include('domain.finances.recouvrement.partials.relance-modale')
@endsection

@push('js')
<script>
$(function () {
    const PARAMETRES = { jour: '{{ $jour->toDateString() }}', nature: '{{ $nature }}', niveau_id: '{{ $niveauId }}', libelle: @json($libelle) };
    const relancer = ids => window.ouvrirRelance({ cible: 'inscriptions', ids, parametres: PARAMETRES });
    const selection = () => $('.selection-eleve:checked').map((i, c) => c.value).get();
    const majSelection = () => { const n = selection().length; $('#nb-selection').text(n); $('#btn-relancer-selection').prop('disabled', n === 0); };

    $('#tout-selectionner').on('change', function () { $('.selection-eleve').prop('checked', this.checked); majSelection(); });
    $(document).on('change', '.selection-eleve', majSelection);
    $('#btn-relancer-selection').on('click', () => relancer(selection()));
    $(document).on('click', '.btn-relancer-un', function () { relancer([$(this).data('inscription')]); });

    // Tous les élèves de la liste (toutes pages) : le serveur reprend les mêmes filtres
    $('#btn-relancer-tous').on('click', function () {
        window.ouvrirRelance({ cible: 'inscriptions', parametres: { ...PARAMETRES, tous: 1 }, nombre: $(this).data('nombre') });
    });

    // Élèves choisis dans un Select2
    const $choix = $('#relance-eleves-choisis');
    if ($choix.length) {
        $choix.select2({ dropdownParent: $('#relanceCibleeModal'), placeholder: 'Rechercher un élève...', width: '100%' });
        $('#btn-continuer-ciblee').on('click', function () {
            const ids = $choix.val() || [];
            if (!ids.length) { window.showToast('Choisissez au moins un élève.', 'error'); return; }
            bootstrap.Modal.getInstance(document.getElementById('relanceCibleeModal'))?.hide();
            relancer(ids);
        });
    }
});
</script>
@endpush
