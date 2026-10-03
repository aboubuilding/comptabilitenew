{{-- Saisie d'un paiement (cahier des charges § 4, 2.2).
     1. Recherche de l'élève (liste avec recherche) ;
     2. sa situation frais par frais et ses paiements passés ;
     3. le nouveau paiement : montants sur ses engagements + articles du catalogue. --}}
@extends('admin.layouts.app')

@section('title', 'Nouveau paiement')
@section('page_title', 'Nouveau paiement')
@section('page_icon', 'fa-receipt')

@section('breadcrumb')
    <li><a href="{{ route('tableau') }}">Accueil</a></li>
    <li><a href="{{ route('finances.paiements.index') }}">Paiements</a></li>
    <li>Nouveau paiement</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.paiements.index') }}" class="btn-outline-scolarite">
        <i class="fas fa-list"></i> Liste des paiements
    </a>
@endsection

@section('css')
<style>
    .ps { --ps-bg-soft:#faf6ee; --ps-border:#ece4d6; }
    .ps .carte { background:#fff; border:1px solid var(--ps-border); border-radius:14px; box-shadow:0 4px 14px rgba(122,15,28,.05); margin-bottom:1rem; overflow:hidden; }
    .ps .carte-titre { padding:.85rem 1.15rem; border-bottom:1px solid var(--ps-border); display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .ps .carte-titre h6 { margin:0; font-size:.95rem; font-weight:700; color:var(--school-ink); display:flex; align-items:center; gap:8px; }
    .ps .carte-titre h6 i { color:var(--school-gold-dark); }
    .ps .carte-corps { padding:1rem 1.15rem; }
    .ps .etape { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:50%; background:var(--school-red); color:#fff; font-size:.72rem; font-weight:700; }

    .ps .recherche-eleve .select2-container .select2-selection--single { height:46px; border-radius:10px; border:1px solid var(--ps-border); }
    .ps .recherche-eleve .select2-selection__rendered { line-height:44px; padding-left:14px; font-size:.95rem; }
    .ps .recherche-eleve .select2-selection__arrow { height:44px; }

    .ps .eleve-bandeau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border-radius:14px; padding:1.1rem 1.4rem; display:flex; align-items:center; gap:1.1rem; margin-bottom:1rem; box-shadow:0 8px 24px rgba(122,15,28,.18); }
    .ps .eleve-avatar { width:54px; height:54px; border-radius:50%; background:rgba(255,255,255,.18); border:2px solid rgba(255,255,255,.35); display:flex; align-items:center; justify-content:center; font-weight:800; flex-shrink:0; }
    .ps .eleve-nom { font-weight:700; font-size:1.15rem; margin:0; color:#fff; }
    .ps .eleve-meta { font-size:.82rem; opacity:.9; }

    .ps .chiffre { background:#fff; border:1px solid var(--ps-border); border-radius:12px; padding:.9rem 1rem; height:100%; }
    .ps .chiffre .valeur { font-size:1.25rem; font-weight:800; font-variant-numeric:tabular-nums; color:var(--school-ink); }
    .ps .chiffre .intitule { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.03em; }
    .ps .chiffre.paye .valeur { color:#1c7a4d; }
    .ps .chiffre.attente .valeur { color:var(--school-gold-dark); }
    .ps .chiffre.reste .valeur { color:var(--school-red); }

    .ps table.tableau { width:100%; margin:0; }
    .ps table.tableau thead th { background:var(--ps-bg-soft); color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:.65rem .9rem; border-bottom:2px solid var(--ps-border); white-space:nowrap; }
    .ps table.tableau tbody td, .ps table.tableau tfoot td { padding:.6rem .9rem; border-bottom:1px solid var(--ps-border); font-size:.87rem; vertical-align:middle; }
    .ps table.tableau tfoot td { font-weight:700; background:var(--ps-bg-soft); border-bottom:none; }
    .ps .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .ps .nature { display:inline-block; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; background:var(--ps-bg-soft); border:1px solid var(--ps-border); color:var(--school-red-dark); border-radius:999px; padding:.1rem .55rem; }
    .ps .barre { height:6px; border-radius:999px; background:#eceff2; overflow:hidden; min-width:70px; }
    .ps .barre > div { height:100%; background:#1c7a4d; }
    .ps .etat { display:inline-flex; align-items:center; gap:5px; font-size:.72rem; font-weight:600; border-radius:999px; padding:.2em .7em; }
    .ps .etat.encaisse { background:#e3f7ec; color:#1c7a4d; }
    .ps .etat.attente { background:#fdf4e0; color:var(--school-gold-dark); }
    .ps .etat.annule { background:#eceff2; color:#5b6b7a; }
    .ps .historique-zone { max-height:340px; overflow-y:auto; }

    .ps .engagement-row { display:flex; align-items:center; gap:.75rem; padding:.7rem .75rem; border-radius:10px; border:1px solid var(--ps-border); margin-bottom:.5rem; background:#fff; }
    .ps .engagement-row.exists { cursor:pointer; background:#f6fdf9; border-color:#cfeedd; }
    .ps .engagement-row.exists:hover { border-color:#1c7a4d; }
    .ps .engagement-row.exists.selectionne { border-color:var(--school-red); background:#fff7f8; }
    .ps .engagement-row.solde, .ps .engagement-row.attente, .ps .engagement-row.missing { background:#fafbfc; }
    .ps .engagement-check { width:20px; height:20px; border-radius:6px; border:2px solid var(--ps-border); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:.7rem; color:#fff; background:#fff; }
    .ps .engagement-check.checked { background:var(--school-red); border-color:var(--school-red); }
    .ps .engagement-check.disabled { background:#eceff2; border-color:#eceff2; color:#9aa7b2; }
    .ps .engagement-label { font-weight:600; font-size:.9rem; color:var(--school-ink); }
    .ps .engagement-sub { font-size:.75rem; color:var(--school-muted); }
    .ps .engagement-montant { font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .ps .engagement-montant.du { color:var(--school-red); }
    .ps .engagement-saisie { width:140px; text-align:right; font-weight:700; border-radius:8px; border:1px solid var(--ps-border); padding:.35rem .55rem; }
    .ps .engagement-saisie:focus { border-color:var(--school-red); outline:none; box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .ps .engagement-link { font-size:.8rem; color:var(--school-red); text-decoration:none; white-space:nowrap; background:none; border:none; padding:0; }
    .ps .engagement-link:hover { text-decoration:underline; }
    .ps .barre-souscrire { display:flex; align-items:center; gap:.4rem; flex-wrap:wrap; padding:.65rem 1.15rem; border-bottom:1px solid var(--ps-border); background:#fffdf8; }
    .ps .barre-souscrire .intitule { font-size:.78rem; font-weight:600; color:var(--school-muted); margin-right:.25rem; }
    .ps .btn-souscrire { border:1px dashed var(--school-red); color:var(--school-red); background:#fff; border-radius:999px; font-size:.78rem; font-weight:600; padding:.3rem .8rem; }
    .ps .btn-souscrire:hover { background:var(--school-red); color:#fff; border-style:solid; }
    .ps .engagement-row.nouveau { box-shadow:0 0 0 2px var(--school-gold); animation:clignote 1.2s ease 2; }
    @keyframes clignote { 50% { background:#fff6dd; } }
    .modal-souscription .modal-header { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; }
    .modal-souscription .modal-title { font-size:1rem; font-weight:700; }
    .modal-souscription .resume-du { background:#faf6ee; border:1px solid #ece4d6; border-radius:10px; padding:.6rem .9rem; display:flex; justify-content:space-between; align-items:center; font-weight:700; }
    .modal-souscription .resume-du .montant { color:var(--school-red); font-size:1.1rem; font-variant-numeric:tabular-nums; }

    .ps .search-panier { position:relative; }
    .ps .search-panier > i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .ps .search-panier input { padding-left:36px; border-radius:10px; border:1px solid var(--ps-border); }
    .ps .search-results { position:absolute; top:calc(100% + 4px); left:0; right:0; background:#fff; border:1px solid var(--ps-border); border-radius:10px; max-height:300px; overflow-y:auto; z-index:100; box-shadow:0 10px 30px rgba(122,15,28,.12); }
    .ps .search-results:empty { display:none; }
    .ps .search-result-item { display:flex; align-items:center; gap:.75rem; padding:.6rem 1rem; cursor:pointer; border-bottom:1px solid #f5f1e8; }
    .ps .search-result-item:last-child { border-bottom:none; }
    .ps .search-result-item:hover { background:var(--ps-bg-soft); }
    .ps .search-result-libelle { font-weight:600; font-size:.88rem; }
    .ps .search-result-sub { font-size:.74rem; color:var(--school-muted); }
    .ps .search-result-prix { margin-left:auto; font-weight:700; font-variant-numeric:tabular-nums; color:var(--school-red-dark); }

    .ps .panier-qte { width:74px; text-align:center; border-radius:8px; border:1px solid var(--ps-border); padding:.25rem .3rem; }
    .ps .btn-remove { border:none; background:transparent; color:var(--school-red); }
    .ps .panier-empty { text-align:center; color:#9aa7b2; padding:1.4rem 1rem; font-size:.88rem; }
    .ps .total-box { background:var(--ps-bg-soft); border:1px solid var(--ps-border); border-radius:14px; padding:1rem 1.15rem; }
    .ps .total-row { display:flex; justify-content:space-between; font-size:.9rem; padding:.15rem 0; }
    .ps .total-row.grand { font-size:1.2rem; font-weight:800; color:var(--school-red-dark); border-top:1px dashed var(--ps-border); margin-top:.4rem; padding-top:.6rem; }
    .ps .invite { text-align:center; padding:2.5rem 1rem; color:var(--school-muted); }
    .ps .invite i { font-size:2.4rem; color:#d8c8ae; display:block; margin-bottom:.8rem; }
</style>
@endsection

@section('contenu')
@php
    $fmt = fn ($v, $d = 0) => number_format((float) $v, $d, ',', ' ');
@endphp
<div class="ps">

    {{-- ============ 1. Recherche de l'élève ============ --}}
    <div class="carte" style="overflow:visible;">
        <div class="carte-titre">
            <h6><span class="etape">1</span> <i class="fas fa-user-graduate"></i> Élève</h6>
            <span class="text-muted small">Année scolaire {{ $annee?->libelle ?? '—' }}</span>
        </div>
        <div class="carte-corps recherche-eleve">
            <select id="recherche-eleve" style="width:100%;" data-url="{{ route('finances.paiements.eleves') }}"
                    data-fiche="{{ url('finances/paiements') }}">
                @if ($inscription)
                    <option value="{{ $inscription->eleve_id }}" selected>{{ $libelleEleve }}</option>
                @endif
            </select>
            <div class="form-text small mt-2">
                Tapez au moins deux lettres du nom, du prénom ou du matricule. Seuls les élèves inscrits sur l'année
                {{ $annee?->libelle }} sont proposés.
            </div>
        </div>
    </div>

    @if (! $inscription)
        <div class="carte">
            <div class="invite" id="invite-recherche">
                <i class="fas fa-search"></i>
                Recherchez un élève pour afficher sa situation, ses paiements passés et saisir un nouveau paiement.
            </div>
        </div>
    @else
        {{-- ============ Bandeau élève + chiffres clés ============ --}}
        <div class="eleve-bandeau" id="eleve-bandeau">
            <div class="eleve-avatar">{{ strtoupper(mb_substr($eleve->nom ?? '?', 0, 1) . mb_substr($eleve->prenom ?? '', 0, 1)) }}</div>
            <div>
                <p class="eleve-nom">{{ $eleve->nom }} {{ $eleve->prenom }}</p>
                <div class="eleve-meta">
                    <i class="fas fa-id-card me-1"></i>{{ $eleve->matricule ?? '—' }}
                    &nbsp;·&nbsp; <i class="fas fa-chalkboard me-1"></i>{{ $inscription->classe?->libelle ?? $inscription->niveau?->libelle ?? 'Sans classe' }}
                    &nbsp;·&nbsp; <i class="fas fa-calendar-alt me-1"></i>{{ $annee?->libelle }}
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3" id="chiffres-cles">
            <div class="col-6 col-lg-3"><div class="chiffre"><div class="valeur" id="total-du">{{ $fmt($totaux['du']) }}</div><div class="intitule">Total dû</div></div></div>
            <div class="col-6 col-lg-3"><div class="chiffre paye"><div class="valeur" id="total-paye">{{ $fmt($totaux['paye']) }}</div><div class="intitule">Déjà encaissé</div></div></div>
            <div class="col-6 col-lg-3"><div class="chiffre attente"><div class="valeur" id="total-attente">{{ $fmt($totaux['en_attente']) }}</div><div class="intitule">En attente d'encaissement</div></div></div>
            <div class="col-6 col-lg-3"><div class="chiffre reste"><div class="valeur" id="total-reste">{{ $fmt($totaux['reste']) }}</div><div class="intitule">Reste à payer</div></div></div>
        </div>

        {{-- ============ 2. Situation frais par frais ============ --}}
        <div class="carte" id="carte-situation">
            <div class="carte-titre">
                <h6><span class="etape">2</span> <i class="fas fa-balance-scale"></i> Situation de l'élève — {{ $annee?->libelle }}</h6>
                <span class="text-muted small">{{ $lignes->count() }} engagement(s)</span>
            </div>
            <div class="table-responsive">
                <table class="tableau" id="situation-frais">
                    <thead>
                        <tr>
                            <th>Frais</th>
                            <th class="num">Dû</th>
                            <th class="num">Déjà payé</th>
                            <th class="num">En attente</th>
                            <th class="num">Reste</th>
                            <th style="width:130px;">Avancement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lignes as $ligne)
                            <tr data-cle="{{ $ligne['cle'] }}">
                                <td>
                                    <span class="nature">{{ $ligne['nature_libelle'] }}</span>
                                    <span class="ms-1 fw-semibold">{{ $ligne['libelle'] }}</span>
                                </td>
                                <td class="num">{{ $fmt($ligne['du']) }}</td>
                                <td class="num" style="color:#1c7a4d;">{{ $fmt($ligne['paye']) }}</td>
                                <td class="num" style="color:var(--school-gold-dark);">{{ $ligne['en_attente'] > 0 ? $fmt($ligne['en_attente']) : '—' }}</td>
                                <td class="num fw-bold" style="color:{{ $ligne['reste'] > 0 ? 'var(--school-red)' : '#1c7a4d' }};">
                                    {{ $ligne['reste'] > 0 ? $fmt($ligne['reste']) : 'Soldé' }}
                                </td>
                                <td>
                                    @php $taux = $ligne['du'] > 0 ? min(100, round($ligne['paye'] / $ligne['du'] * 100)) : 100; @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="barre flex-grow-1"><div style="width:{{ $taux }}%"></div></div>
                                        <span class="small text-muted">{{ $taux }} %</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Aucun frais souscrit pour cet élève sur cette année.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($lignes->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="num">{{ $fmt($totaux['du']) }}</td>
                                <td class="num">{{ $fmt($totaux['paye']) }}</td>
                                <td class="num">{{ $totaux['en_attente'] > 0 ? $fmt($totaux['en_attente']) : '—' }}</td>
                                <td class="num">{{ $fmt($totaux['reste']) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ============ Paiements passés ============ --}}
        <div class="carte">
            <div class="carte-titre">
                <h6><i class="fas fa-history"></i> Paiements déjà enregistrés</h6>
                <span class="text-muted small" id="historique-compte">{{ $historique->count() }} ligne(s) — année {{ $annee?->libelle }}</span>
            </div>
            <div class="historique-zone">
                <table class="tableau" id="historique-paiements">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Référence</th>
                            <th>Nature</th>
                            <th>Libellé</th>
                            <th class="num">Montant</th>
                            <th>Mode</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($historique as $h)
                            @php
                                $statut = \App\Domain\Finances\Types\PaiementStatut::tryFrom((int) $h->statut_paiement);
                                $classe = match ($statut) {
                                    \App\Domain\Finances\Types\PaiementStatut::ENCAISSE => 'encaisse',
                                    \App\Domain\Finances\Types\PaiementStatut::ANNULE   => 'annule',
                                    default                                             => 'attente',
                                };
                            @endphp
                            <tr>
                                <td>{{ $h->date_paiement ? \Illuminate\Support\Carbon::parse($h->date_paiement)->format('d/m/Y') : '—' }}</td>
                                <td><code>{{ $h->reference ?? '—' }}</code></td>
                                <td><span class="nature">{{ \App\Domain\Finances\Types\TypeDetail::tryFrom((int) $h->type_paiement)?->label() ?? '—' }}</span></td>
                                <td>{{ $h->libelle }}</td>
                                <td class="num fw-semibold">{{ $fmt($h->montant) }}</td>
                                <td>{{ \App\Domain\Finances\Types\ModePaiement::tryFrom((int) $h->mode_paiement)?->label() ?? '—' }}</td>
                                <td><span class="etat {{ $classe }}">{{ $statut?->label() ?? '—' }}</span></td>
                                <td class="text-end">
                                    @if ($statut === \App\Domain\Finances\Types\PaiementStatut::ENCAISSE)
                                        @can('encaissements.voir')
                                            <a href="{{ route('finances.encaissements.recu', $h->id) }}" target="_blank" title="Reçu"><i class="fas fa-file-pdf"></i></a>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Aucun paiement enregistré pour cet élève sur l'année {{ $annee?->libelle }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============ 3. Nouveau paiement ============ --}}
        <form id="paiement-form" action="{{ route('finances.paiements.store') }}" method="POST" autocomplete="off">
            @csrf
            <input type="hidden" name="inscription_id" value="{{ $inscription->id }}">

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="carte">
                        <div class="carte-titre">
                            <h6><span class="etape">3</span> <i class="fas fa-hand-holding-usd"></i> Nouveau paiement — frais de l'élève</h6>
                            <span class="text-muted small">Cochez un frais pour le solder, ou saisissez le montant versé</span>
                        </div>
                        @canany(['cantine.inscrire', 'transport.abonner', 'activites.inscrire'])
                            <div class="barre-souscrire" id="barre-souscrire">
                                <span class="intitule"><i class="fas fa-plus-circle me-1"></i>Souscrire :</span>
                                @can('cantine.inscrire')
                                    <button type="button" class="btn-souscrire" data-bs-toggle="modal" data-bs-target="#modal-cantine"><i class="fas fa-utensils me-1"></i>Cantine</button>
                                @endcan
                                @can('transport.abonner')
                                    <button type="button" class="btn-souscrire" data-bs-toggle="modal" data-bs-target="#modal-transport"><i class="fas fa-bus me-1"></i>Transport</button>
                                @endcan
                                @can('activites.inscrire')
                                    <button type="button" class="btn-souscrire" data-bs-toggle="modal" data-bs-target="#modal-activite"><i class="fas fa-futbol me-1"></i>Activité</button>
                                    <button type="button" class="btn-souscrire" data-bs-toggle="modal" data-bs-target="#modal-evenement"><i class="fas fa-calendar-check me-1"></i>Événement</button>
                                @endcan
                            </div>
                        @endcanany
                        <div class="carte-corps" id="engagements-container">
                            @foreach ($lignes as $ligne)
                                @if ($ligne['payable'] > 0)
                                    <div class="engagement-row exists"
                                         data-cle="{{ $ligne['cle'] }}"
                                         data-type="{{ $ligne['type'] }}"
                                         data-libelle="{{ $ligne['libelle'] }}"
                                         data-payable="{{ $ligne['payable'] }}"
                                         data-souscription="{{ $ligne['souscription_id'] }}"
                                         data-frais="{{ $ligne['frais_ecole_id'] }}"
                                         data-activite="{{ $ligne['activite_id'] }}"
                                         data-evenement="{{ $ligne['evenement_id'] }}">
                                        <div class="engagement-check" data-role="check"><i class="fas fa-check" style="display:none;"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="engagement-label">
                                                <span class="nature">{{ $ligne['nature_libelle'] }}</span> {{ $ligne['libelle'] }}
                                            </div>
                                            <div class="engagement-sub">
                                                Dû : {{ $fmt($ligne['du'], 2) }} · Payé : {{ $fmt($ligne['paye'], 2) }}
                                                @if ($ligne['en_attente'] > 0) · En attente d'encaissement : {{ $fmt($ligne['en_attente'], 2) }} @endif
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="engagement-montant du">{{ $fmt($ligne['payable'], 2) }}</div>
                                            <div class="engagement-sub">reste dû</div>
                                        </div>
                                        <input type="number" class="engagement-saisie" min="0" step="1" max="{{ $ligne['payable'] }}"
                                               placeholder="Montant" aria-label="Montant versé pour {{ $ligne['libelle'] }}">
                                    </div>
                                @elseif ($ligne['en_attente'] > 0)
                                    <div class="engagement-row attente">
                                        <div class="engagement-check disabled"><i class="fas fa-hourglass-half"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="engagement-label text-muted"><span class="nature">{{ $ligne['nature_libelle'] }}</span> {{ $ligne['libelle'] }}</div>
                                            <div class="engagement-sub">{{ $fmt($ligne['en_attente'], 2) }} déjà saisi, en attente d'encaissement par le caissier</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="engagement-row solde">
                                        <div class="engagement-check disabled"><i class="fas fa-check"></i></div>
                                        <div class="flex-grow-1">
                                            <div class="engagement-label text-muted"><span class="nature">{{ $ligne['nature_libelle'] }}</span> {{ $ligne['libelle'] }}</div>
                                            <div class="engagement-sub">Soldé — {{ $fmt($ligne['paye'], 2) }} encaissé</div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            {{-- Natures sans engagement : renvoi vers la souscription (jamais créée implicitement) --}}
                            @foreach ($manquants as $manquant)
                                <div class="engagement-row missing">
                                    <div class="engagement-check disabled"><i class="fas fa-minus"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="engagement-label text-muted">{{ $manquant['libelle'] }}</div>
                                        <div class="engagement-sub">Aucun engagement souscrit</div>
                                    </div>
                                    @if ($manquant['fenetre'])
                                        <button type="button" class="engagement-link" data-bs-toggle="modal" data-bs-target="#modal-{{ $manquant['fenetre'] }}">Souscrire <i class="fas fa-plus ms-1"></i></button>
                                    @else
                                        <a href="{{ $manquant['lien'] }}" class="engagement-link">Souscrire <i class="fas fa-arrow-right ms-1"></i></a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="carte" style="overflow:visible;">
                        <div class="carte-titre">
                            <h6><i class="fas fa-shopping-basket"></i> Articles et autres frais</h6>
                            <span class="text-muted small">Tenues, livres, kits, produits en stock, événements…</span>
                        </div>
                        <div class="carte-corps">
                            <div class="search-panier">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search-panier" class="form-control" placeholder="Rechercher un article ou cliquer pour voir le catalogue...">
                                <div class="search-results" id="search-results"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="carte">
                        <div class="carte-titre">
                            <h6><i class="fas fa-shopping-cart"></i> Récapitulatif</h6>
                            <span class="text-muted small" id="panier-count">0 ligne(s)</span>
                        </div>
                        <table class="tableau">
                            <thead>
                                <tr><th>Libellé</th><th class="text-center">Qté</th><th class="num">Montant</th><th></th></tr>
                            </thead>
                            <tbody id="panier-body"></tbody>
                        </table>
                        <div class="panier-empty" id="panier-empty"><i class="fas fa-shopping-cart me-1"></i> Aucune ligne pour l'instant.</div>
                    </div>

                    <div class="total-box">
                        <div class="total-row"><span>Nombre de lignes</span><span id="total-lignes">0</span></div>
                        <div class="total-row grand"><span>TOTAL À ENCAISSER</span><span id="total-montant">0,00</span></div>

                        <div class="row g-2 mt-2">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold" for="payeur">Payeur <span class="text-danger">*</span></label>
                                <input type="text" name="payeur" id="payeur" class="form-control form-control-sm" required maxlength="150"
                                       value="{{ $eleve->nom }} {{ $eleve->prenom }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold" for="telephone_payeur">Téléphone</label>
                                <input type="text" name="telephone_payeur" id="telephone_payeur" class="form-control form-control-sm" maxlength="30">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="mode_paiement">Mode de paiement <span class="text-danger">*</span></label>
                                <select name="mode_paiement" id="mode_paiement" class="form-select form-select-sm" required>
                                    @foreach ($modes as $valeur => $libelle)
                                        <option value="{{ $valeur }}">{{ $libelle }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Paiement par chèque : références du chèque --}}
                        <div id="zone-cheque" class="mt-3 p-3 rounded" style="display:none;background:#fff;border:1px solid var(--ps-border);">
                            <div class="small fw-bold mb-2" style="color:var(--school-red-dark);">
                                <i class="fas fa-money-check me-1"></i> Informations du chèque
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="cheque-numero">Numéro du chèque <span class="text-danger">*</span></label>
                                    <input type="text" id="cheque-numero" class="form-control form-control-sm" maxlength="100">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="cheque-banque">Banque <span class="text-danger">*</span></label>
                                    <select id="cheque-banque" class="form-select form-select-sm">
                                        <option value="">— Sélectionner —</option>
                                        @foreach ($banques as $banque)
                                            <option value="{{ $banque->id }}">{{ $banque->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label small fw-semibold" for="cheque-emetteur">Émetteur (titulaire du compte)</label>
                                    <input type="text" id="cheque-emetteur" class="form-control form-control-sm" maxlength="150"
                                           placeholder="Par défaut : le payeur">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-semibold" for="cheque-date">Date d'émission</label>
                                    <input type="date" id="cheque-date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                                </div>
                            </div>
                            <div class="form-text small mt-2">
                                Le chèque sera enregistré au portefeuille pour le montant total du paiement.
                                @if ($banques->isEmpty())
                                    <span class="text-danger">Aucune banque n'est enregistrée : créez-la d'abord dans Banques &amp; Chèques.</span>
                                @endif
                            </div>
                        </div>

                        <button type="submit" class="btn-enregistrer w-100 mt-3" id="btn-submit" disabled>
                            <i class="fas fa-check"></i> Enregistrer le paiement
                        </button>
                        <div class="form-text small text-center mt-2">
                            Le paiement sera en attente : le caissier constatera l'entrée en caisse et remettra le reçu.
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @include('domain.finances.paiements.partials.fenetres-souscription')
    @endif
</div>
@endsection

@push('js')
<script>
$(function () {
    /* ============ 1. Recherche de l'élève ============ */
    const $eleve = $('#recherche-eleve');

    $eleve.select2({
        placeholder: 'Rechercher un élève (nom, prénom ou matricule)...',
        allowClear: true,
        minimumInputLength: 2,
        width: '100%',
        ajax: {
            url: $eleve.data('url'),
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term }),
            processResults: data => ({ results: data.results || [] }),
        },
        language: {
            inputTooShort: () => 'Tapez au moins 2 caractères',
            searching: () => 'Recherche...',
            noResults: () => 'Aucun élève inscrit cette année ne correspond',
            errorLoading: () => 'La recherche a échoué',
        },
    });

    $eleve.on('select2:select', function (e) {
        window.location.href = $eleve.data('fiche') + '/' + e.params.data.id;
    });
    $eleve.on('select2:clear', function () {
        window.location.href = '{{ route('finances.paiements.recherche') }}';
    });

    const $form = $('#paiement-form');
    if (!$form.length) {
        $eleve.select2('open');
        return;
    }

    /* ============ 3. Nouveau paiement ============ */
    const inscriptionId = $form.find('[name=inscription_id]').val();
    let articles = [];   // { type_paiement, libelle, prix_unitaire, quantite, source_type, source_id, stock }

    const fmt = v => new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v);
    const esc = s => $('<div>').text(s ?? '').html();

    /* ---- Frais de l'élève ---- */
    function montantDe($row) {
        return Math.max(0, parseFloat($row.find('.engagement-saisie').val()) || 0);
    }

    function marquer($row) {
        const actif = montantDe($row) > 0;
        $row.toggleClass('selectionne', actif);
        $row.find('[data-role=check]').toggleClass('checked', actif).find('i').toggle(actif);
    }

    $(document).on('click', '.engagement-row.exists', function (e) {
        if ($(e.target).is('.engagement-saisie')) return;
        const $row = $(this);
        const $saisie = $row.find('.engagement-saisie');
        $saisie.val(montantDe($row) > 0 ? '' : $row.data('payable'));
        marquer($row);
        rendre();
    });

    $(document).on('input', '.engagement-saisie', function () {
        const $row = $(this).closest('.engagement-row');
        const max = parseFloat($row.data('payable'));
        if (parseFloat($(this).val()) > max) {
            $(this).val(max);
            window.showToast('Montant ramené au reste dû (' + fmt(max) + ').', 'error');
        }
        marquer($row);
        rendre();
    });

    function lignesEngagements() {
        return $('.engagement-row.exists').map(function () {
            const $row = $(this);
            const montant = montantDe($row);
            if (montant <= 0) return null;
            return {
                engagement:      $row.data('cle'),
                type_paiement:   $row.data('type'),
                libelle:         $row.data('libelle'),
                montant:         montant,
                souscription_id: $row.data('souscription') || '',
                frais_ecole_id:  $row.data('frais') || '',
                activite_id:     $row.data('activite') || '',
                evenement_id:    $row.data('evenement') || '',
            };
        }).get();
    }

    /* ---- Catalogue ---- */
    let minuterie, requete = null;

    function chercher() {
        const q = $('#search-panier').val().trim();
        clearTimeout(minuterie);
        if (requete) { requete.abort(); requete = null; }

        minuterie = setTimeout(() => {
            requete = $.ajax({
                url: '{{ route('finances.paiements.recherche-panier') }}',
                data: { q, inscription_id: inscriptionId },
                headers: { 'Accept': 'application/json' },
                success: res => afficherResultats(res.lignes || []),
                error: xhr => { if (xhr.statusText !== 'abort') $('#search-results').empty(); },
            });
        }, 250);
    }

    $('#search-panier').on('input focus', chercher);
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.search-panier').length) $('#search-results').empty();
    });

    const sources = {
        frais:     ['tag', 'Article / frais'],
        produit:   ['box', 'Produit en stock'],
        service:   ['concierge-bell', 'Service'],
        evenement: ['calendar-check', 'Événement'],
    };

    function afficherResultats(lignes) {
        const $zone = $('#search-results').empty();
        if (!lignes.length) {
            $zone.html('<div class="search-result-item text-muted small">Aucun résultat.</div>');
            return;
        }
        lignes.forEach(l => {
            const [icone, libelle] = sources[l.type] || ['cube', ''];
            const $item = $(`
                <div class="search-result-item">
                    <i class="fas fa-${icone}" style="color:var(--school-red);"></i>
                    <div>
                        <div class="search-result-libelle">${esc(l.libelle)}</div>
                        <div class="search-result-sub">${libelle}${l.stock ? ' · stock : ' + l.stock : ''}</div>
                    </div>
                    <div class="search-result-prix">${fmt(l.prix_unitaire)}</div>
                </div>`);
            $item.on('click', () => {
                ajouterArticle(l);
                $zone.empty();
                $('#search-panier').val('');
            });
            $zone.append($item);
        });
    }

    function ajouterArticle(l) {
        const deja = articles.find(a => a.source_type === l.type && a.source_id === l.id);
        if (deja && l.requires_qte) {
            deja.quantite = Math.min(deja.quantite + 1, deja.stock || Infinity);
        } else if (!deja) {
            articles.push({
                type_paiement: l.type_paiement,
                libelle:       l.libelle,
                prix_unitaire: parseFloat(l.prix_unitaire) || 0,
                quantite:      1,
                avec_quantite: !!l.requires_qte,
                source_type:   l.type,
                source_id:     l.id,
                stock:         l.stock ? parseFloat(l.stock) : null,
            });
        }
        rendre();
    }

    $(document).on('input', '.panier-qte', function () {
        const article = articles[$(this).data('idx')];
        let qte = parseFloat($(this).val()) || 0;
        if (article.stock !== null && qte > article.stock) {
            qte = article.stock;
            $(this).val(qte);
            window.showToast('Quantité limitée au stock disponible (' + qte + ').', 'error');
        }
        article.quantite = qte;
        rendre(false);
    });

    $(document).on('click', '.btn-remove', function () {
        const idx = $(this).data('idx');
        if ($(this).data('genre') === 'article') {
            articles.splice(idx, 1);
        } else {
            const $row = $('.engagement-row.exists').filter((i, el) => $(el).data('cle') === idx);
            $row.find('.engagement-saisie').val('');
            marquer($row);
        }
        rendre();
    });

    /* ---- Récapitulatif ---- */
    function lignesArticles() {
        return articles
            .filter(a => a.quantite > 0)
            .map(a => {
                const ligne = {
                    type_paiement: a.type_paiement,
                    libelle:       a.libelle,
                    montant:       +(a.quantite * a.prix_unitaire).toFixed(2),
                };
                if (a.avec_quantite) { ligne.quantite = a.quantite; ligne.prix_unitaire = a.prix_unitaire; }
                const champ = { frais: 'frais_ecole_id', produit: 'produit_id', service: 'service_id', evenement: 'evenement_id' }[a.source_type];
                if (champ) ligne[champ] = a.source_id;
                return ligne;
            })
            .filter(l => l.montant > 0);
    }

    function rendre(reconstruire = true) {
        const engagements = lignesEngagements();
        const achats = lignesArticles();
        const total = [...engagements, ...achats].reduce((s, l) => s + l.montant, 0);

        if (reconstruire) {
            const $corps = $('#panier-body').empty();

            engagements.forEach(l => $corps.append(`
                <tr>
                    <td class="fw-semibold">${esc(l.libelle)}</td>
                    <td class="text-center">—</td>
                    <td class="num">${fmt(l.montant)}</td>
                    <td><button type="button" class="btn-remove" data-genre="engagement" data-idx="${esc(l.engagement)}" title="Retirer"><i class="fas fa-times"></i></button></td>
                </tr>`));

            articles.forEach((a, idx) => $corps.append(`
                <tr>
                    <td><div class="fw-semibold">${esc(a.libelle)}</div><div class="small text-muted">${fmt(a.prix_unitaire)} l'unité</div></td>
                    <td class="text-center">${a.avec_quantite
                        ? `<input type="number" class="panier-qte" min="1" step="1" value="${a.quantite}" data-idx="${idx}" aria-label="Quantité">`
                        : '—'}</td>
                    <td class="num panier-montant" data-idx="${idx}">${fmt(a.quantite * a.prix_unitaire)}</td>
                    <td><button type="button" class="btn-remove" data-genre="article" data-idx="${idx}" title="Retirer"><i class="fas fa-times"></i></button></td>
                </tr>`));
        } else {
            articles.forEach((a, idx) => $(`.panier-montant[data-idx=${idx}]`).text(fmt(a.quantite * a.prix_unitaire)));
        }

        const nombre = engagements.length + achats.length;
        $('#panier-empty').toggle(nombre === 0);
        $('#panier-count').text(nombre + ' ligne(s)');
        $('#total-lignes').text(nombre);
        $('#total-montant').text(fmt(total));
        $('#btn-submit').prop('disabled', nombre === 0 || total <= 0);
    }

    /* ---- Mode de paiement : le chèque demande ses références ---- */
    const MODE_CHEQUE = '{{ \App\Domain\Finances\Types\ModePaiement::CHEQUE->value }}';
    const parCheque = () => $('#mode_paiement').val() === MODE_CHEQUE;

    $('#mode_paiement').on('change', function () {
        $('#zone-cheque').toggle(parCheque());
        if (parCheque()) $('#cheque-numero').trigger('focus');
    }).trigger('change');

    /* ---- Enregistrement ---- */
    $form.on('submit', function (e) {
        e.preventDefault();
        const lignes = [...lignesEngagements(), ...lignesArticles()];
        if (!lignes.length) return;

        let cheque = null;
        if (parCheque()) {
            cheque = {
                numero:        $('#cheque-numero').val().trim(),
                banque_id:     $('#cheque-banque').val(),
                emetteur:      $('#cheque-emetteur').val().trim(),
                date_emission: $('#cheque-date').val(),
            };
            $('#cheque-numero').toggleClass('is-invalid', !cheque.numero);
            $('#cheque-banque').toggleClass('is-invalid', !cheque.banque_id);
            if (!cheque.numero || !cheque.banque_id) {
                window.showToast('Renseignez le numéro et la banque du chèque.', 'error');
                return;
            }
        }

        const $btn = $('#btn-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            data: {
                _token:           $form.find('[name=_token]').val(),
                inscription_id:   inscriptionId,
                payeur:           $('#payeur').val(),
                telephone_payeur: $('#telephone_payeur').val(),
                mode_paiement:    $('#mode_paiement').val(),
                cheque:           cheque,
                lignes:           lignes,
            },
            // Après l'enregistrement, on revient à la liste des paiements, où le message s'affiche
            success: function (res) {
                sessionStorage.setItem('pendingToast', JSON.stringify({ type: 'success', message: res.message || 'Paiement enregistré.' }));
                window.location.href = '{{ route('finances.paiements.index') }}';
            },
            error: function (xhr) {
                $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Enregistrer le paiement');
                const erreurs = xhr.responseJSON?.errors;
                const message = erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
                window.showToast(message, 'error');
            },
        });
    });

    /* ============ Souscriptions depuis l'écran ============ */

    /**
     * Recharge la situation, les chiffres clés et les engagements sans quitter
     * l'écran : les montants déjà saisis et les articles du panier sont conservés.
     * Le ou les engagements apparus sont mis en évidence, prêts à être réglés.
     */
    function rafraichirEngagements() {
        const saisis = {};
        $('.engagement-row.exists').each(function () {
            const v = $(this).find('.engagement-saisie').val();
            if (v) saisis[$(this).data('cle')] = v;
        });
        const avant = $('#engagements-container .engagement-row[data-cle]').map((i, el) => String($(el).data('cle'))).get();

        return $.get(window.location.href).done(function (html) {
            const $page = $('<div>').append($.parseHTML(html));
            ['#chiffres-cles', '#carte-situation', '#engagements-container'].forEach(sel => $(sel).replaceWith($page.find(sel)));
            // Les activités et événements déjà souscrits ne sont plus proposés dans les fenêtres
            ['#souscrire-activite', '#souscrire-evenement'].forEach(sel => $(sel).html($page.find(sel).html()));

            let $premier = null;
            $('.engagement-row.exists').each(function () {
                const $row = $(this);
                const cle = String($row.data('cle'));
                if (saisis[cle]) $row.find('.engagement-saisie').val(saisis[cle]);
                if (!avant.includes(cle)) {
                    $row.addClass('nouveau');
                    $premier = $premier || $row;
                }
                marquer($row);
            });
            rendre();

            if ($premier) {
                $premier[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                $premier.find('.engagement-saisie').trigger('focus');
            }
        });
    }

    const fmtEntier = v => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v);

    // Montant dû calculé en direct : mensualité × nombre de mois
    function majDu($form) {
        const mensuel = parseFloat($form.find('[name=montant_mensuel]').val()) || 0;
        const mois = parseInt($form.find('[name=nombre_mois]').val(), 10) || 0;
        $form.find('.du-calcule').text(fmtEntier(mensuel * mois));
    }
    $(document).on('input', '.form-souscription [name=montant_mensuel], .form-souscription [name=nombre_mois]', function () {
        majDu($(this).closest('form'));
    });

    // Un tarif (cantine) ou une ligne (bus) choisi propose sa mensualité
    $(document).on('change', '.form-souscription [data-mensualite]', function () {
        const mensualite = $(this).find(':selected').data('montant');
        const $form = $(this).closest('form');
        if (mensualite) $form.find('[name=montant_mensuel]').val(mensualite);
        majDu($form);
    });

    // Activité : le montant dû reprend le tarif de l'activité choisie
    $(document).on('change', '#souscrire-activite', function () {
        const montant = $(this).find(':selected').data('montant');
        $('#modal-activite [name=montant_du]').val(montant ?? '');
    });

    // Événement : à capacité limitée → pré-inscription (engagement) ; sinon → réglé directement au récapitulatif
    $(document).on('change', '#souscrire-evenement', function () {
        const $opt = $(this).find(':selected');
        const limite = String($opt.data('limitee')) === '1';
        $('#evenement-info').toggle(!!$opt.val()).find('.valeur-participation').text(fmtEntier($opt.data('montant') || 0));
        $('#evenement-mode').html(!$opt.val() ? '' : (limite
            ? '<i class="fas fa-user-clock me-1"></i>Événement à places limitées : l\'élève est pré-inscrit, la participation devient un engagement à régler.'
            : '<i class="fas fa-shopping-cart me-1"></i>Événement sans limite de places : la participation est ajoutée directement au récapitulatif du paiement.'));
    });

    $('.modal-souscription').on('show.bs.modal', function () {
        const $form = $(this).find('form');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[data-mensualite]').trigger('change');
        $form.find('#souscrire-evenement').trigger('change');
        if ($form.find('[name=montant_mensuel]').length) majDu($form);
    });

    $(document).on('submit', '.form-souscription', function (e) {
        e.preventDefault();
        const $f = $(this);
        const $modal = $f.closest('.modal');

        // Événement sans limite de places : pas de pré-inscription, la participation entre au panier
        if ($f.is('#form-evenement')) {
            const $opt = $f.find('#souscrire-evenement :selected');
            if (!$opt.val()) { window.showToast('Choisissez un événement.', 'error'); return; }
            if (String($opt.data('limitee')) !== '1') {
                ajouterArticle({
                    type: 'evenement', id: parseInt($opt.val(), 10), libelle: $opt.data('libelle'),
                    type_paiement: {{ \App\Domain\Finances\Types\TypeDetail::EVENEMENT->value }},
                    prix_unitaire: $opt.data('montant'), requires_qte: false,
                });
                bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
                window.showToast('Participation ajoutée au récapitulatif.', 'success');
                return;
            }
        }

        const $btn = $f.find('[type=submit]').prop('disabled', true);
        $.ajax({
            url: $f.attr('action'),
            method: 'POST',
            data: $f.serialize(),
            headers: { 'Accept': 'application/json' },
        }).done(function (res) {
            bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            window.showToast(res.message || 'Souscription enregistrée.', 'success');
            rafraichirEngagements();
        }).fail(function (xhr) {
            const erreurs = xhr.responseJSON?.errors;
            if (erreurs) Object.keys(erreurs).forEach(champ => $f.find(`[name="${champ}"]`).addClass('is-invalid'));
            const message = erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'La souscription a échoué.');
            window.showToast(message, 'error');
        }).always(() => $btn.prop('disabled', false));
    });

    rendre();
});
</script>
@endpush
