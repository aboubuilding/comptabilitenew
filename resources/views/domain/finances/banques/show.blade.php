{{-- resources/views/domain/finances/banques/show.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Banque — ' . $banque->nom)
@section('page_icon', 'fa-university')
@section('page_title', 'Détail de la banque')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.banques.index') }}">Banques</a></li>
    <li class="breadcrumb-item active">{{ $banque->nom }}</li>


@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.banques.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </x-bouton>

    <x-bouton variante="contour" href="{{ route('finances.banques.exportPdf', $banque) }}" class="btn-sm rounded-pill px-3">
    <i class="fas fa-file-pdf me-1"></i> Exporter PDF
</x-bouton>

@can('cheques.rapprocher')
    <x-bouton variante="contour" href="{{ route('finances.banques.rapprochement.index', $banque) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-import me-1"></i> Rapprochement
    </x-bouton>
@endcan
@endsection

@section('css')
<style>
    .banque-show-page { --bp-bg-soft: var(--eim-cream); --bp-border: var(--eim-border); }
    .text-xs { font-size: .72rem; }
    .text-sm { font-size: .8rem; }

    /* Bandeau d'identité */
    .banque-header {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff;
        border-radius: 16px;
        padding: 1.5rem 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: 0 10px 30px rgba(142,18,22,.18);
    }
    .banque-header-icon {
        width: 60px; height: 60px;
        border-radius: 14px;
        background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
        flex-shrink: 0;
    }
    .banque-header h2 { margin: 0; font-weight: 700; font-size: 1.35rem; color: #fff; }
    .banque-header small { opacity: .8; font-size: .85rem; }

    /* Cartes stats */
    .stat-card { background:#fff; border:1px solid var(--bp-border); border-radius:12px; padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem; box-shadow:0 2px 8px rgba(142,18,22,.04); transition: transform .2s; }
    .stat-icon { width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.15rem; flex-shrink:0; }
    .stat-info .stat-value { font-size:1.2rem; font-weight:700; line-height:1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size:.78rem; color: var(--school-muted); margin:0; }

    /* Tableau chèques */
    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--bp-border) !important; }

    .table-cheques thead tr { background: var(--bp-bg-soft); }
    .table-cheques thead th { color: var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--bp-border); padding:.9rem 1rem; white-space:nowrap; }
    .table-cheques thead th i { color: var(--school-gold-dark); }
    .table-cheques tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--bp-border); font-size:.9rem; color: var(--school-ink); vertical-align:middle; }
    .table-cheques tbody tr:hover { background:var(--eim-hover); }

    .badge-cheque { display:inline-flex; align-items:center; gap:6px; font-size:.75rem; font-weight:600; padding:.4em .85em; border-radius:999px; }
    .badge-cheque-emis     { background:#dbeafe; color:#1d4ed8; }
    .badge-cheque-encaisse { background:#e3f7ec; color:var(--eim-success); }
    .badge-cheque-rejete   { background:#fdecea; color: var(--school-red); }
    .badge-dot { width:6px; height:6px; border-radius:50%; }
    .badge-cheque-emis .badge-dot     { background:#1d4ed8; }
    .badge-cheque-encaisse .badge-dot { background:var(--eim-success); }
    .badge-cheque-rejete .badge-dot   { background: var(--school-red); }

    .cheque-montant { font-variant-numeric: tabular-nums; font-weight:700; text-align:right; }

    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
</style>
@endsection

@push('css')
<style>
    .onglets-banque { border-bottom:2px solid var(--eim-border); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets-banque .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.88rem; padding:.7rem .95rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets-banque .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets-banque .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--eim-cream); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--eim-border); }
    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .reference { font-family:monospace; font-size:.8rem; background:var(--eim-cream); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .table-cheques td.col-eleve { max-width:240px; }
    .table-cheques td.col-eleve > div { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .num { font-variant-numeric:tabular-nums; font-weight:700; white-space:nowrap; }
    .bloc-info { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.55rem .8rem; height:100%; }
    .bloc-titre { font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .nature { display:inline-block; background:var(--eim-cream); color:var(--school-red); font-weight:600; font-size:.7rem; padding:.12rem .5rem; border-radius:999px; border:1px solid var(--eim-border); }
    #lignes-paiement thead th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); background:var(--eim-cream); }
</style>
@endpush

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $libellesOnglets = [
        'attente'  => ['Chèques en attente', 'fas fa-clock', $situation['attente']['nb']],
        'encaisse' => ['Encaissés', 'fas fa-check-circle', $situation['percu']['nb']],
        'rejete'   => ['Rejetés', 'fas fa-times-circle', $situation['rejete']['nb']],
        'tous'     => ['Tous les chèques', 'fas fa-list', $situation['total']['nb']],
    ];
@endphp
<div class="banque-show-page">

    {{-- Bandeau --}}
    <div class="banque-header mb-4">
        <div class="banque-header-icon"><i class="fas fa-university"></i></div>
        <div>
            <h2>{{ $banque->nom }}</h2>
            <small>
                <i class="fas fa-calendar-alt me-1"></i>Année {{ $annee?->libelle ?? '—' }}
                &nbsp;·&nbsp;
                <i class="fas fa-money-check me-1"></i>{{ $situation['total']['nb'] }} chèque(s) — {{ $fmt($situation['total']['montant']) }} F
            </small>
        </div>
    </div>

    {{-- Ce qui a été réellement perçu, ce qui reste --}}
    <div class="row g-3 mb-4" id="situation-banque">
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-check-circle" couleur="green" id="montant-percu">{{ $fmt($situation['percu']['montant']) }} F
                <x-slot:libelle>Réellement perçu — {{ $situation['percu']['nb'] }} chèque(s) encaissé(s)</x-slot:libelle>
            </x-stat>
        </div>
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-hourglass-half" couleur="gold" id="montant-reste">{{ $fmt($situation['attente']['montant']) }} F
                <x-slot:libelle>Reste à percevoir — {{ $situation['attente']['nb'] }} chèque(s) en attente</x-slot:libelle>
            </x-stat>
        </div>
        <div class="col-sm-12 col-lg-4">
            <x-stat icone="fas fa-times-circle" couleur="red" id="montant-rejete">{{ $fmt($situation['rejete']['montant']) }} F
                <x-slot:libelle>Rejeté — {{ $situation['rejete']['nb'] }} chèque(s)</x-slot:libelle>
            </x-stat>
        </div>
    </div>

    {{-- Paiements et chèques adossés --}}
    <x-carte>
        <ul class="nav onglets-banque px-3 pt-2" id="onglets-banque">
            @foreach ($libellesOnglets as $cle => [$libelle, $icone, $nb])
                <li class="nav-item">
                    <a class="nav-link @if ($onglet === $cle) active @endif" id="onglet-{{ $cle }}"
                       href="{{ route('finances.banques.show', ['banque' => $banque->id, 'onglet' => $cle]) }}">
                        <i class="{{ $icone }} me-1"></i> {{ $libelle }} <span class="compte">{{ $nb }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <input type="hidden" name="onglet" value="{{ $onglet }}">
                <div class="col-md-6">
                    <x-recherche name="search" placeholder="Élève, payeur, référence, n° de chèque..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Rechercher"><i class="fas fa-search"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.banques.show', ['banque' => $banque->id, 'onglet' => $onglet]) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
                <div class="col-md-4 text-end"><x-compteur>{{ $lignes->total() }} paiement(s)</x-compteur></div>
            </form>
        </div>

        <x-tableau class="table-cheques align-middle mb-0" id="table-paiements-banque">
                <thead>
                    <tr>
                        <th class="ps-4">Paiement</th>
                        <th>Élève / Payeur</th>
                        <th>Chèque adossé</th>
                        <th class="text-end">Montant</th>
                        <th class="pe-4">Statut du chèque</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($lignes as $l)
                    @php
                        $statut = \App\Domain\Finances\Types\ChequeStatut::tryFrom((int) $l->statut_cheque);
                        $statutPaiement = \App\Domain\Finances\Types\PaiementStatut::tryFrom((int) $l->statut_paiement);
                    @endphp
                    <tr data-cheque="{{ $l->cheque_id }}">
                        <td class="ps-4">
                            @if ($l->paiement_id)
                                <button type="button" class="btn btn-link p-0 text-decoration-none btn-voir-paiement" title="Voir le contenu du paiement"
                                        data-url="{{ route('finances.banques.paiement', ['banque' => $banque->id, 'paiement' => $l->paiement_id]) }}"
                                        data-reference="{{ $l->reference }}">
                                    <span class="reference">{{ $l->reference }}</span> <i class="fas fa-eye ms-1 small" style="color:var(--school-red);"></i>
                                </button>
                                <div class="cell-sub mt-1"><i class="far fa-calendar me-1"></i>{{ $l->date_paiement ? \Illuminate\Support\Carbon::parse($l->date_paiement)->format('d/m/Y') : '—' }}</div>
                                @if ($statutPaiement)<div class="cell-sub">Paiement {{ mb_strtolower($statutPaiement->label()) }}</div>@endif
                            @else
                                <span class="text-muted small">Aucun paiement lié</span>
                            @endif
                        </td>
                        <td class="col-eleve">
                            <div class="fw-semibold">{{ $l->eleve_nom ? $l->eleve_nom . ' ' . $l->eleve_prenom : ($l->payeur ?: '—') }}</div>
                            <div class="cell-sub">{{ $l->matricule }}@if ($l->classe) · {{ $l->classe }}@endif</div>
                            @if ($l->payeur)<div class="cell-sub">Payé par {{ $l->payeur }}</div>@endif
                        </td>
                        <td>
                            <div class="fw-semibold"><i class="fas fa-money-check me-1 text-muted"></i>N° {{ $l->numero }}</div>
                            <div class="cell-sub">Émetteur : {{ $l->emetteur ?: '—' }}</div>
                            <div class="cell-sub">Émis le {{ $l->date_emission ? \Illuminate\Support\Carbon::parse($l->date_emission)->format('d/m/Y') : '—' }}</div>
                        </td>
                        <td class="text-end">
                            <div class="num">{{ $fmt($l->montant_cheque) }} F</div>
                            @if ($l->paiement_id && abs((float) $l->montant_paiement - (float) $l->montant_cheque) > 0.5)
                                <div class="cell-sub">paiement : {{ $fmt($l->montant_paiement) }} F</div>
                            @endif
                        </td>
                        <td class="pe-4">
                            <span class="badge-cheque badge-cheque-{{ $statut?->css() }}"><span class="badge-dot"></span>{{ $statut?->label() ?? '—' }}</span>
                            @if ($l->date_encaissement)
                                <div class="cell-sub mt-1">Encaissé le {{ \Illuminate\Support\Carbon::parse($l->date_encaissement)->format('d/m/Y') }}</div>
                            @endif
                            @if ($l->motif_rejet)
                                <div class="cell-sub mt-1 text-danger">{{ $l->motif_rejet }}</div>
                            @endif
                        </td>

                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-money-check-alt" :paragraphe="false"><p class="mb-0 fw-medium">Aucun chèque {{ $onglet === 'tous' ? '' : mb_strtolower($libellesOnglets[$onglet][0]) }} sur cette banque pour l'année {{ $annee?->libelle }}.</p></x-vide>
                @endforelse
                </tbody>
            </x-tableau>
        <div class="p-3"><x-pagination :liste="$lignes" /></div>
    </x-carte>
</div>

{{-- Contenu d'un paiement adossé à un chèque --}}
<x-modale id="paiementModal" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" corps-id="paiement-contenu" croix-blanche>
    <x-slot:titre><i class="fas fa-receipt me-2"></i>Paiement <span id="paiement-reference"></span></x-slot:titre>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
    </x-slot:pied>
</x-modale>
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.btn-voir-paiement', function () {
        $('#paiement-reference').text($(this).data('reference'));
        $('#paiement-contenu').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('paiementModal')).show();
        $.get($(this).data('url'))
            .done(html => $('#paiement-contenu').html(html))
            .fail(xhr => $('#paiement-contenu').html('<div class="text-danger">' + (xhr.responseJSON?.message || 'Impossible d\'afficher ce paiement.') + '</div>'));
    });
});
</script>
@endpush
