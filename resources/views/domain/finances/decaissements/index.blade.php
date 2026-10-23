@extends('admin.layouts.app')

@section('title', 'Décaissements')
@section('page_icon', 'fa-money-bill-wave')
@section('page_title', 'Décaissements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Décaissements</li>
@endsection

@section('page_actions')
    @if ($caisseOuverte)
        <span class="badge-etat badge-etat-ouverte" id="caisse-ouverte" style="padding:.5em 1em;">
            <span class="badge-dot"></span> Caisse « {{ $caisseOuverte->libelle }} » ouverte
        </span>
    @elseif ($peutDecaisser)
        <span class="badge-etat badge-etat-fermee" id="caisse-fermee" style="padding:.5em 1em;">
            <span class="badge-dot"></span> Aucune caisse ouverte
        </span>
    @endif
@endsection

@push('css')
<style>
    .dec-page { --de-soft:var(--eim-cream); --de-border:var(--eim-border); }
    .dec-page .stat-card { background:#fff; border:1px solid var(--de-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); height:100%; }
    .dec-page .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .dec-page .stat-label { font-size:.75rem; color:var(--school-muted); }
    .badge-etat { display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:.8rem; font-weight:600; }
    .badge-etat-ouverte { background:#e3f7ec; color:var(--eim-success); }
    .badge-etat-fermee  { background:#fdecef; color:var(--school-red); }
    .onglets-dec { border-bottom:2px solid var(--de-border); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets-dec .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets-dec .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets-dec .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--de-soft); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--de-border); }
    .barre-outils { padding:1rem 1.25rem; border-bottom:1px solid var(--de-border); display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
    .filtre-input { border-radius:10px; border:1px solid var(--de-border); padding:.5rem .85rem; font-size:.86rem; }
    .table-dec thead tr { background:var(--de-soft); }
    .table-dec thead th { color:var(--school-red); font-size:.74rem; font-weight:700; text-transform:uppercase; padding:.85rem 1rem; border-bottom:2px solid var(--de-border); white-space:nowrap; }
    .table-dec tbody td { padding:.8rem 1rem; border-bottom:1px solid var(--de-border); font-size:.88rem; vertical-align:middle; }
    .table-dec td.col-libelle { max-width:280px; }
    .table-dec td.col-libelle > div { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .table-dec tfoot td { padding:.8rem 1rem; font-weight:700; background:var(--eim-hover); }
    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .num { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; color:var(--school-red); }
    .type-sortie { display:inline-block; background:#fdecea; color:var(--school-red); font-weight:600; font-size:.68rem; padding:.12rem .55rem; border-radius:999px; }
    .liste-a-decaisser .ligne { border:1px solid var(--de-border); border-radius:10px; padding:.65rem .85rem; display:flex; gap:.75rem; align-items:center; margin-bottom:.5rem; }
    .liste-a-decaisser .ligne:hover { background:var(--eim-hover); }
    .piece-ok { font-size:.72rem; font-weight:600; color:var(--eim-success); text-decoration:none; }
    .piece-manquante { font-size:.72rem; font-weight:600; color:var(--school-red); }
</style>
@endpush

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $libelles = [
        'tous'   => ['Tous les décaissements', 'fas fa-list', $compteurs['tous']['nb']],
        'attente' => ['Dépenses en attente', 'fas fa-hourglass-half', $compteurAttente['nb']],
        'justificatifs' => ['Sans justificatif', 'fas fa-paperclip', $compteurJustif['nb']],
        'mes'    => ['Mes décaissements', 'fas fa-user-check', $compteurs['mes']['nb'] ?? 0],
        'caisse' => ['Caisse ouverte', 'fas fa-cash-register', $compteurs['caisse']['nb'] ?? 0],
    ];
@endphp
<div class="dec-page">
    {{-- Chiffres clés --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-hourglass-half" couleur="gold" id="stat-a-decaisser">{{ $fmt($compteurAttente['montant']) }} F
                <x-slot:libelle>Dépenses en attente — {{ $compteurAttente['nb'] }}, dont {{ $compteurAttente['validees'] }} validée(s) à décaisser ({{ $fmt($compteurAttente['montant_validees']) }} F)</x-slot:libelle>
            </x-stat>
        </div>
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-money-bill-wave" couleur="red" id="stat-decaisse">{{ $fmt($compteurs['tous']['montant']) }} F
                <x-slot:libelle>Décaissé sur l'année {{ $annee?->libelle }} — {{ $compteurs['tous']['nb'] }} sortie(s)</x-slot:libelle>
            </x-stat>
        </div>
        <div class="col-sm-12 col-lg-4">
            <x-stat icone="fas fa-cash-register" couleur="green">{{ $caisseOuverte?->libelle ?? 'Aucune caisse ouverte' }}
                <x-slot:libelle>{{ $caisseOuverte ? 'Décaissé dans ma caisse : ' . $fmt($compteurs['caisse']['montant'] ?? 0) . ' F' : 'Ma caisse' }}</x-slot:libelle>
            </x-stat>
        </div>
    </div>

    <x-carte>
        <ul class="nav onglets-dec px-3 pt-2" id="onglets-decaissements">
            @foreach ($onglets as $cle)
                <li class="nav-item">
                    <a class="nav-link @if ($onglet === $cle) active @endif" id="onglet-{{ $cle }}" href="{{ route('finances.decaissements.index', ['onglet' => $cle]) }}">
                        <i class="{{ $libelles[$cle][1] }} me-1"></i> {{ $libelles[$cle][0] }} <span class="compte">{{ $libelles[$cle][2] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($onglet === 'attente')
            <form method="GET" class="barre-outils" id="filtres-attente">
                <input type="hidden" name="onglet" value="attente">
                <input type="text" name="search" class="form-control filtre-input" style="max-width:260px;" placeholder="Dépense, bénéficiaire, motif..." value="{{ request('search') }}">
                <select name="statut" class="form-select filtre-input" style="max-width:240px;" id="filtre-statut-attente">
                    <option value="">— Validées et non validées —</option>
                    <option value="{{ \App\Domain\Finances\Types\DepenseStatut::VALIDEE->value }}" @selected(request('statut') == \App\Domain\Finances\Types\DepenseStatut::VALIDEE->value)>Validées (à décaisser)</option>
                    <option value="{{ \App\Domain\Finances\Types\DepenseStatut::EN_ATTENTE->value }}" @selected(request('statut') == \App\Domain\Finances\Types\DepenseStatut::EN_ATTENTE->value)>En attente de validation</option>
                </select>
                <x-bouton variante="filtrer" type="submit" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                <x-bouton variante="contour" href="{{ route('finances.decaissements.index', ['onglet' => 'attente']) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                @if ($peutDecaisser && ! $caisseOuverte)
                    <span class="text-danger small ms-auto"><i class="fas fa-lock me-1"></i>Ouvrez votre caisse pour décaisser.</span>
                @endif
            </form>
            <x-tableau class="table-dec align-middle mb-0" id="table-attente">
                <thead>
                    <tr>
                        <th class="ps-4">Dépense</th>
                        <th>Bénéficiaire / motif</th>
                        <th>Validation</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($enAttente as $dep)
                    @php $validee = $dep->isValidee(); @endphp
                    <tr data-depense="{{ $dep->id }}">
                        <td class="ps-4 col-libelle">
                            <div class="fw-semibold" title="{{ $dep->libelle }}">{{ $dep->libelle }}</div>
                            <div class="cell-sub"><i class="far fa-calendar me-1"></i>{{ optional($dep->date_depense)->format('d/m/Y') }}@if ($dep->auteur) · saisie par {{ $dep->auteur->nom }} {{ $dep->auteur->prenom }}@endif</div>
                            <div>@if ($dep->justificatif_path)<a class="piece-ok" href="{{ asset('storage/' . $dep->justificatif_path) }}" target="_blank" rel="noopener" title="{{ $dep->justificatif_nom_original }}"><i class="fas fa-paperclip me-1"></i>Justificatif</a>@else<span class="piece-manquante"><i class="fas fa-exclamation-circle me-1"></i>Sans justificatif</span>@endif</div>
                        </td>
                        <td class="col-libelle">
                            <div>{{ $dep->beneficiaire ?: '—' }}</div>
                            @if ($dep->motif_depense)<div class="cell-sub" title="{{ $dep->motif_depense }}">{{ $dep->motif_depense }}</div>@endif
                        </td>
                        <td>
                            @if ($validee)
                                <x-badge class="actif" point>Validée</x-badge>
                                <div class="cell-sub mt-1">{{ $dep->valideur ? 'par ' . $dep->valideur->nom . ' ' . $dep->valideur->prenom : "d'office (moins de " . $fmt(config('finances.seuil_validation_depense')) . ' F)' }}</div>
                            @else
                                <x-badge class="attente" point>En attente de validation</x-badge>
                                <div class="cell-sub mt-1">Validation par la Direction</div>
                            @endif
                        </td>
                        <td class="text-end"><span class="num">{{ $fmt($dep->montant) }} F</span></td>
                        <td class="text-center pe-4 text-nowrap">
                            @if ($peutDecaisser)
                                <x-bouton type="button" class="btn-sm btn-decaisser" data-depense="{{ $dep->id }}" data-libelle="{{ $dep->libelle }}" data-montant="{{ $fmt($dep->montant) }}"
                                          data-montant-brut="{{ (float) $dep->montant }}" data-piece="{{ $dep->justificatif_path ? 1 : 0 }}" data-beneficiaire="{{ $dep->beneficiaire }}" data-date="{{ optional($dep->date_depense)->format('d/m/Y') }}"
                                          :disabled="! $validee || ! $caisseOuverte"
                                          title="{{ ! $validee ? 'La dépense doit être validée pour être décaissée' : ($caisseOuverte ? 'Décaisser de ma caisse' : 'Ouvrez votre caisse pour décaisser') }}">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Décaisser
                                </x-bouton>
                            @endif
                            @can('depenses.voir')
                                <x-bouton variante="contour" href="{{ route('finances.depenses.show', $dep->id) }}" class="btn-sm" title="Voir la dépense"><i class="fas fa-eye"></i></x-bouton>
                            @endcan
                            @can('depenses.justifier')
                                @can('depenses.justifier')<x-bouton variante="contour" type="button" class="btn-sm btn-joindre-justificatif" data-depense="{{ $dep->id }}" data-libelle="{{ $dep->libelle }}" title="Joindre le justificatif"><i class="fas fa-paperclip"></i></x-bouton>@endcan
                            @endcan
                            @can('depenses.voir')
                            @endcan
                        </td>
                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-check-circle" :paragraphe="false">Aucune dépense en attente de décaissement.</x-vide>
                @endforelse
                </tbody>
            </x-tableau>
            <div class="p-3"><x-pagination :liste="$enAttente->appends(['onglet' => 'attente'])" /></div>
        @elseif ($onglet === 'justificatifs')
            <form method="GET" class="barre-outils" id="filtres-justificatifs">
                <input type="hidden" name="onglet" value="justificatifs">
                <input type="text" name="search" class="form-control filtre-input" style="max-width:260px;" placeholder="Dépense, bénéficiaire..." value="{{ request('search') }}">
                <x-bouton variante="filtrer" type="submit" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                <x-bouton variante="contour" href="{{ route('finances.decaissements.index', ['onglet' => 'justificatifs']) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                <span class="small text-muted ms-auto" id="resume-justificatifs">
                    {{ $compteurJustif['nb'] }} dépense(s) sans justificatif — {{ $fmt($compteurJustif['montant']) }} F, dont {{ $compteurJustif['decaissees'] }} déjà décaissée(s)
                </span>
            </form>
            <x-tableau class="table-dec align-middle mb-0" id="table-justificatifs">
                <thead>
                    <tr>
                        <th class="ps-4">Dépense</th>
                        <th>Bénéficiaire / motif</th>
                        <th>Décaissement</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($sansJustificatif as $dep)
                    <tr data-depense="{{ $dep->id }}">
                        <td class="ps-4 col-libelle">
                            <div class="fw-semibold" title="{{ $dep->libelle }}">{{ $dep->libelle }}</div>
                            <div class="cell-sub"><i class="far fa-calendar me-1"></i>{{ optional($dep->date_depense)->format('d/m/Y') }}@if ($dep->auteur) · saisie par {{ $dep->auteur->nom }} {{ $dep->auteur->prenom }}@endif</div>
                        </td>
                        <td class="col-libelle">
                            <div>{{ $dep->beneficiaire ?: '—' }}</div>
                            @if ($dep->motif_depense)<div class="cell-sub" title="{{ $dep->motif_depense }}">{{ $dep->motif_depense }}</div>@endif
                        </td>
                        <td>
                            @if ($dep->isReglee())
                                <x-badge class="actif" point>Décaissée</x-badge>
                                <div class="cell-sub mt-1">{{ $dep->caisse?->libelle }} · {{ optional($dep->date_reglement)->format('d/m/Y') }}</div>
                            @else
                                <x-badge class="attente" point>Pas encore décaissée</x-badge>
                            @endif
                        </td>
                        <td class="text-end"><span class="num">{{ $fmt($dep->montant) }} F</span></td>
                        <td class="text-center pe-4 text-nowrap">
                            @can('depenses.justifier')
                                <x-bouton type="button" class="btn-sm btn-joindre-justificatif" data-depense="{{ $dep->id }}" data-libelle="{{ $dep->libelle }}">
                                    <i class="fas fa-upload me-1"></i>Joindre
                                </x-bouton>
                            @endcan
                            @can('depenses.voir')
                                <x-bouton variante="contour" href="{{ route('finances.depenses.show', $dep->id) }}" class="btn-sm" title="Voir la dépense"><i class="fas fa-eye"></i></x-bouton>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-check-circle" :paragraphe="false">Toutes les dépenses ont leur justificatif.</x-vide>
                @endforelse
                </tbody>
            </x-tableau>
            <div class="p-3"><x-pagination :liste="$sansJustificatif->appends(['onglet' => 'justificatifs'])" /></div>
        @elseif ($onglet === 'caisse')
            {{-- Caisse ouverte : journal et nouveau décaissement --}}
            <div class="barre-outils">
                <div>
                    <div class="fw-semibold"><i class="fas fa-cash-register me-1 text-muted"></i>Caisse « {{ $caisseOuverte->libelle }} »</div>
                    <div class="cell-sub">Ouverte le {{ \Illuminate\Support\Carbon::parse($caisseOuverte->date_ouverture)->format('d/m/Y à H:i') }}</div>
                </div>
                <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
                    @can('caisses.voir')
                        <x-bouton variante="contour" href="{{ route('finances.caisses.show', $caisseOuverte->id) }}" id="btn-journal-caisse">
                            <i class="fas fa-book me-1"></i> Journal de la caisse
                        </x-bouton>
                    @endcan
                    <x-bouton variante="nouveau" type="button" id="btn-nouveau-decaissement" data-bs-toggle="modal" data-bs-target="#aDecaisserModal">
                        <i class="fas fa-plus-circle me-1"></i> Nouveau décaissement
                    </x-bouton>
                </div>
            </div>
        @else
            <form method="GET" class="barre-outils" id="filtres-decaissements">
                <input type="hidden" name="onglet" value="{{ $onglet }}">
                <input type="text" name="search" class="form-control filtre-input" style="max-width:260px;" placeholder="Dépense, bénéficiaire, référence..." value="{{ $filtres['search'] ?? '' }}">
                @if ($onglet === 'tous')
                    <select name="caissier_id" id="filtre-caissier" class="form-select filtre-input" style="max-width:220px;" title="Caissier">
                        <option value="">— Tous les caissiers —</option>
                        @foreach ($caissiers as $u)
                            <option value="{{ $u->id }}" @selected(($filtres['caissier_id'] ?? '') == $u->id)>{{ $u->nom }}</option>
                        @endforeach
                    </select>
                @endif
                <input type="date" name="date_debut" class="form-control filtre-input" style="max-width:160px;" value="{{ $filtres['date_debut'] ?? '' }}" title="Du">
                <input type="date" name="date_fin" class="form-control filtre-input" style="max-width:160px;" value="{{ $filtres['date_fin'] ?? '' }}" title="Au">
                <x-bouton variante="filtrer" type="submit" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                <x-bouton variante="contour" href="{{ route('finances.decaissements.index', ['onglet' => $onglet]) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                @if ($onglet === 'mes' && ! $caisseOuverte)
                    <span class="text-danger small ms-auto"><i class="fas fa-lock me-1"></i>Ouvrez votre caisse pour décaisser.</span>
                @endif
            </form>
        @endif

        @unless (in_array($onglet, ['attente', 'justificatifs'], true))
        <x-tableau class="table-dec align-middle mb-0" id="table-decaissements">
            <thead>
                <tr>
                    <th class="ps-4">Décaissement</th>
                    <th>Dépense / Bénéficiaire</th>
                    <th>Caisse / Caissier</th>
                    <th class="text-end">Montant</th>
                    <th class="text-center pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($decaissements as $d)
                @php $type = \App\Domain\Finances\Types\MouvementType::tryFrom((int) $d->type_mouvement); @endphp
                <tr data-mouvement="{{ $d->id }}">
                    <td class="ps-4">
                        <div class="fw-semibold"><i class="far fa-calendar me-1 text-muted"></i>{{ \Illuminate\Support\Carbon::parse($d->date_mouvement)->format('d/m/Y') }}</div>
                        <span class="type-sortie">{{ $type?->label() ?? 'Sortie' }}</span>
                        @if ($d->reference)<div class="cell-sub">{{ $d->reference }}</div>@endif
                    </td>
                    <td class="col-libelle">
                        <div class="fw-semibold" title="{{ $d->depense_libelle ?? $d->libelle }}">{{ $d->depense_libelle ?? $d->libelle }}</div>
                        <div class="cell-sub">Bénéficiaire : {{ $d->beneficiaire ?: '—' }}</div>
                        @if ($d->motif_depense ?? $d->motif)<div class="cell-sub" title="{{ $d->motif_depense ?? $d->motif }}">{{ $d->motif_depense ?? $d->motif }}</div>@endif
                        @if ($d->depense_id)<div>@if ($d->justificatif_path)<a class="piece-ok" href="{{ asset('storage/' . $d->justificatif_path) }}" target="_blank" rel="noopener" title="{{ $d->justificatif_nom_original }}"><i class="fas fa-paperclip me-1"></i>Justificatif</a>@else<span class="piece-manquante"><i class="fas fa-exclamation-circle me-1"></i>Sans justificatif</span>@endif</div>@endif
                    </td>
                    <td>
                        <div class="fw-semibold"><i class="fas fa-cash-register me-1 text-muted"></i>{{ $d->caisse ?: '—' }}</div>
                        <div class="cell-sub"><i class="far fa-user me-1"></i>{{ $d->caissier ?: '—' }}</div>
                    </td>
                    <td class="text-end"><span class="num">−{{ $fmt($d->montant) }} F</span></td>
                    <td class="text-center pe-4">
                        <x-actions class="d-inline-block">
                            @can('caisses.voir')
                                <li><a class="dropdown-item" href="{{ route('finances.caisses.show', $d->caisse_id) }}"><i class="fas fa-book"></i> Journal de la caisse</a></li>
                            @endcan
                            @if ($d->depense_id)
                                @can('depenses.voir')
                                    <li><a class="dropdown-item" href="{{ route('finances.depenses.show', $d->depense_id) }}"><i class="fas fa-file-invoice-dollar"></i> Voir la dépense</a></li>
                                @endcan
                                @can('depenses.justifier')
                                    <li><button type="button" class="dropdown-item btn-joindre-justificatif" data-depense="{{ $d->depense_id }}" data-libelle="{{ $d->depense_libelle ?? $d->libelle }}">
                                        <i class="fas fa-paperclip"></i> {{ $d->justificatif_path ? 'Remplacer le justificatif' : 'Joindre le justificatif' }}
                                    </button></li>
                                @endcan
                            @endif
                        </x-actions>
                    </td>
                </tr>
            @empty
                <x-vide colspan="5" icone="fas fa-money-bill-wave" :paragraphe="false">Aucun décaissement.</x-vide>
            @endforelse
            </tbody>
            @if ($total['nb'] > 0)
                <tfoot>
                    <tr><td class="ps-4" colspan="3">Total ({{ $total['nb'] }} décaissement(s))</td><td class="text-end" id="total-decaissements">−{{ $fmt($total['montant']) }} F</td><td></td></tr>
                </tfoot>
            @endif
        </x-tableau>
        <div class="p-3"><x-pagination :liste="$decaissements->appends(['onglet' => $onglet])" /></div>
        @endunless
    </x-carte>
</div>

{{-- ==================== Nouveau décaissement (caisse ouverte) ==================== --}}
@if ($caisseOuverte && $peutDecaisser)
<x-modale id="aDecaisserModal" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" croix-blanche>
    <x-slot:titre><i class="fas fa-money-bill-wave me-2"></i>Nouveau décaissement — caisse « {{ $caisseOuverte->libelle }} »</x-slot:titre>
    <p class="small text-muted mb-3">
        Dépenses validées de l'année {{ $annee?->libelle }}, pas encore décaissées. Les dépenses de moins de
        {{ $fmt(config('finances.seuil_validation_depense')) }} F sont validées dès leur saisie ; au-delà, la Direction les valide.
        Choisissez la dépense : vous confirmerez ensuite le montant réellement décaissé et la caisse.
    </p>
    <div class="liste-a-decaisser" id="liste-a-decaisser">
        @forelse ($aDecaisser as $dep)
            <div class="ligne" data-depense="{{ $dep->id }}">
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $dep->libelle }}</div>
                    <div class="cell-sub">
                        {{ optional($dep->date_depense)->format('d/m/Y') }} · Bénéficiaire : {{ $dep->beneficiaire ?: '—' }}
                        @if ($dep->auteur) · saisie par {{ $dep->auteur->nom }} {{ $dep->auteur->prenom }}@endif
                        @if (! $dep->valide_par) · <span class="text-success">validée d'office</span>@endif
                    </div>
                </div>
                <span class="num">{{ $fmt($dep->montant) }} F</span>
                <x-bouton type="button" class="btn-sm btn-decaisser" data-depense="{{ $dep->id }}" data-libelle="{{ $dep->libelle }}" data-montant="{{ $fmt($dep->montant) }}"
                                          data-montant-brut="{{ (float) $dep->montant }}" data-piece="{{ $dep->justificatif_path ? 1 : 0 }}" data-beneficiaire="{{ $dep->beneficiaire }}" data-date="{{ optional($dep->date_depense)->format('d/m/Y') }}">
                    <i class="fas fa-hand-holding-usd me-1"></i>Décaisser
                </x-bouton>
            </div>
        @empty
            <div class="text-center text-muted py-4" id="rien-a-decaisser"><i class="fas fa-check-circle me-1"></i>Aucune dépense validée à décaisser.</div>
        @endforelse
    </div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
    </x-slot:pied>
</x-modale>
@endif

{{-- ==================== Confirmation d'un décaissement : montant réel et caisse ==================== --}}
@can('depenses.justifier')
<x-modale id="justificatifModal" aria-hidden="true" dialogue="modal-dialog-centered" form-contenu croix-blanche>
    <x-slot:formulaire id="justificatif-form" method="POST" action="" enctype="multipart/form-data">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-paperclip me-2"></i>Justificatif de la dépense</x-slot:titre>
    <p class="fw-semibold mb-2" id="justif-libelle">—</p>
    <x-libelle for="justif-fichier" requis>Fichier (PDF, image, Word — 5 Mo max)</x-libelle>
    <input type="file" name="justificatif" id="justif-fichier" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
    <div class="text-danger small mt-1" id="justif-erreur"></div>
    <div class="cell-sub mt-2">Un justificatif déjà joint est remplacé.</div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit" id="justif-valider"><i class="fas fa-upload me-1"></i>Enregistrer</x-bouton>
    </x-slot:pied>
</x-modale>
@endcan

@if ($peutDecaisser && $caisseOuverte)
<x-modale id="decaisserModal" aria-hidden="true" dialogue="modal-dialog-centered" form-contenu croix-blanche>
    <x-slot:formulaire id="decaisser-form" method="POST" action="">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-hand-holding-usd me-2"></i>Décaisser une dépense</x-slot:titre>
    <div class="p-3 mb-3" style="background:var(--de-soft, var(--eim-cream)); border:1px solid var(--eim-border); border-radius:10px;">
        <div class="fw-semibold" id="dec-libelle">—</div>
        <div class="cell-sub" id="dec-infos">—</div>
        <div class="d-flex justify-content-between mt-2">
            <span class="cell-sub">Montant de la dépense</span>
            <span class="num" id="dec-montant-depense">—</span>
        </div>
    </div>
    {{-- On décaisse uniquement depuis sa propre caisse ouverte --}}
    <input type="hidden" name="caisse_id" id="dec-caisse" value="{{ $caisseOuverte->id }}" data-solde="{{ (float) $caisseOuverte->solde_theorique }}">
    <div class="mb-3">
        <x-libelle>Ma caisse ouverte</x-libelle>
        <div class="d-flex justify-content-between align-items-center p-2 px-3" id="dec-ma-caisse"
             style="border:1px solid #bfe6d0; background:#f2fbf6; border-radius:10px;">
            <div>
                <div class="fw-semibold"><i class="fas fa-cash-register me-1" style="color:var(--eim-success);"></i>{{ $caisseOuverte->libelle }}</div>
                <div class="cell-sub">Ouverte le {{ \Illuminate\Support\Carbon::parse($caisseOuverte->date_ouverture)->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="text-end">
                <div class="cell-sub">Solde théorique</div>
                <div class="fw-bold" style="color:var(--eim-success);">{{ $fmt($caisseOuverte->solde_theorique) }} F</div>
            </div>
        </div>
        <div class="text-danger small mt-1" id="caisse_id-error"></div>
    </div>
    <div class="mb-2">
        <x-libelle for="dec-montant" requis>Montant réellement décaissé (F)</x-libelle>
        <input type="number" name="montant_decaisse" id="dec-montant" class="form-control" min="1" step="1" required>
        <div class="text-danger small mt-1" id="montant_decaisse-error"></div>
    </div>
    <div class="mb-2">
        <x-libelle for="dec-justificatif">Justificatif (facture, reçu — optionnel)</x-libelle>
        <input type="file" name="justificatif" id="dec-justificatif" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
        <div class="cell-sub mt-1" id="dec-piece-actuelle"></div>
        <div class="text-danger small mt-1" id="justificatif-error"></div>
    </div>
    <div class="small text-warning d-none" id="dec-ecart"><i class="fas fa-exclamation-triangle me-1"></i><span></span></div>
    <div class="small text-danger d-none" id="dec-solde"><i class="fas fa-exclamation-circle me-1"></i>Le solde théorique de ma caisse est inférieur à ce montant.</div>
    <div class="cell-sub mt-2"><i class="fas fa-book me-1"></i>La sortie sera inscrite au journal de ma caisse.</div>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="submit" id="dec-valider"><i class="fas fa-check me-1"></i>Confirmer le décaissement</x-bouton>
    </x-slot:pied>
</x-modale>
@endif
@endsection

@push('js')
<script>
$(function () {
    const fmt = v => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v);
    const $form = $('#decaisser-form');
    let montantDepense = 0;

    // Écart avec la dépense et solde de la caisse : signalés, sans bloquer
    const controler = () => {
        const montant = parseFloat($('#dec-montant').val()) || 0;
        const solde = parseFloat($('#dec-caisse').data('solde')) || 0;
        const ecart = montant - montantDepense;
        $('#dec-ecart').toggleClass('d-none', !montant || Math.abs(ecart) < 0.5)
            .find('span').text(ecart > 0 ? `${fmt(ecart)} F de plus que la dépense.` : `${fmt(-ecart)} F de moins que la dépense.`);
        $('#dec-solde').toggleClass('d-none', !montant || montant <= solde);
    };
    $('#dec-montant').on('input change', controler);

    // Joindre (ou remplacer) le justificatif d'une dépense
    const $justif = $('#justificatif-form');
    $(document).on('click', '.btn-joindre-justificatif', function () {
        $justif.attr('action', `{{ url('finances/decaissements/depense') }}/${$(this).data('depense')}/justificatif`);
        $('#justif-libelle').text($(this).data('libelle'));
        $('#justif-fichier').val('');
        $('#justif-erreur').text('');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('justificatifModal')).show();
    });
    $justif.on('submit', function (e) {
        e.preventDefault();
        if (!this.checkValidity()) { this.reportValidity(); return; }
        const $valider = $('#justif-valider').prop('disabled', true);
        $.ajax({ url: $justif.attr('action'), method: 'POST', data: new FormData(this), processData: false, contentType: false, headers: { 'Accept': 'application/json' } })
            .done(res => {
                sessionStorage.setItem('pendingToast', JSON.stringify({ type: 'success', message: res.message || 'Justificatif enregistré.' }));
                window.location.reload();
            })
            .fail(xhr => {
                $valider.prop('disabled', false);
                const erreurs = xhr.responseJSON?.errors;
                $('#justif-erreur').text(erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'Une erreur est survenue.'));
            });
    });

    $(document).on('click', '.btn-decaisser', function () {
        const $btn = $(this);
        montantDepense = parseFloat($btn.data('montant-brut')) || 0;
        $form.attr('action', `{{ url('finances/decaissements/depense') }}/${$btn.data('depense')}/decaisser`);
        $('#dec-libelle').text($btn.data('libelle'));
        $('#dec-infos').text(`${$btn.data('date') || ''} · Bénéficiaire : ${$btn.data('beneficiaire') || '—'}`);
        $('#dec-montant-depense').text(`${$btn.data('montant')} F`);
        $('#dec-montant').val(montantDepense);
        $('#montant_decaisse-error, #caisse_id-error, #justificatif-error').text('');
        $('#dec-justificatif').val('');
        $('#dec-piece-actuelle').html($btn.data('piece') ? '<i class="fas fa-paperclip me-1"></i>Un justificatif est déjà joint ; un nouveau fichier le remplacera.' : '');
        controler();
        bootstrap.Modal.getInstance(document.getElementById('aDecaisserModal'))?.hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('decaisserModal')).show();
    });

    $form.on('submit', function (e) {
        e.preventDefault();
        if (!this.checkValidity()) { this.reportValidity(); return; }
        const $valider = $('#dec-valider').prop('disabled', true);
        $.ajax({ url: $form.attr('action'), method: 'POST', data: new FormData(this), processData: false, contentType: false, headers: { 'Accept': 'application/json' } })
            .done(res => {
                sessionStorage.setItem('pendingToast', JSON.stringify({ type: 'success', message: res.message || 'Décaissement enregistré.' }));
                window.location.href = '{{ route('finances.decaissements.index') }}?onglet=' + ($('#table-attente').length ? 'attente' : 'caisse');
            })
            .fail(xhr => {
                $valider.prop('disabled', false);
                const erreurs = xhr.responseJSON?.errors;
                if (erreurs) {
                    Object.keys(erreurs).forEach(champ => $('#' + champ + '-error').text(erreurs[champ][0]));
                } else {
                    window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                }
            });
    });
});
</script>
@endpush
