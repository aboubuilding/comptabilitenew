@extends('admin.layouts.app')

@section('title', 'Paiement ' . $paiement->reference)
@section('page_icon', 'fa-file-invoice-dollar')
@section('page_title', 'Détail du paiement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item"><a href="{{ route('finances.paiements.index') }}">Paiements</a></li>
    <li class="breadcrumb-item active">{{ $paiement->reference }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.paiements.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour à la liste
    </a>
    @if ($eleve)
        @can('paiements.creer')
            <a href="{{ route('finances.paiements.show', $eleve->id) }}" class="btn btn-nouveau" id="btn-situation-eleve">
                <i class="fas fa-user-graduate me-1"></i> Situation de l'élève
            </a>
        @endcan
    @endif
@endsection

@section('css')
<style>
    .pd { --pd-soft:#faf6ee; --pd-border:#ece4d6; }
    .pd .carte { background:#fff; border:1px solid var(--pd-border); border-radius:14px; box-shadow:0 6px 20px rgba(122,15,28,.05); margin-bottom:1.25rem; overflow:hidden; }
    .pd .carte-titre { padding:.85rem 1.15rem; border-bottom:1px solid var(--pd-border); display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
    .pd .carte-titre h6 { margin:0; font-weight:700; color:var(--school-ink); font-size:.92rem; }
    .pd .carte-titre h6 i { color:var(--school-red); margin-right:.4rem; }
    .pd .carte-corps { padding:1rem 1.15rem; }

    .pd .entete { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; padding:1.15rem 1.25rem; background:linear-gradient(135deg,#fff,var(--pd-soft)); }
    .pd .reference { font-family:monospace; font-size:1rem; background:#fff; color:var(--school-red); padding:3px 10px; border-radius:8px; font-weight:700; border:1px solid var(--pd-border); }
    .pd .montant-total { font-size:1.6rem; font-weight:800; color:var(--school-ink); font-variant-numeric:tabular-nums; line-height:1.1; text-align:right; }
    .pd .montant-total small { font-size:.8rem; font-weight:600; color:var(--school-muted); }

    .pd .info { display:flex; gap:.75rem; padding:.45rem 0; border-bottom:1px dashed var(--pd-border); font-size:.88rem; }
    .pd .info:last-child { border-bottom:none; }
    .pd .info .cle { width:42%; color:var(--school-muted); flex-shrink:0; }
    .pd .info .valeur { font-weight:600; color:var(--school-ink); word-break:break-word; }

    .pd .tableau { width:100%; margin:0; }
    .pd .tableau thead th { background:var(--pd-soft); color:var(--school-red); font-size:.72rem; text-transform:uppercase; font-weight:700; padding:.7rem .9rem; border-bottom:2px solid var(--pd-border); }
    .pd .tableau tbody td, .pd .tableau tfoot td { padding:.7rem .9rem; border-bottom:1px solid var(--pd-border); font-size:.86rem; vertical-align:middle; }
    .pd .tableau tfoot td { font-weight:700; background:#fdfaf5; }
    .pd .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .pd .nature { display:inline-block; background:var(--pd-soft); color:var(--school-red); font-weight:600; font-size:.68rem; padding:.18rem .55rem; border-radius:999px; border:1px solid var(--pd-border); }
    .pd .sub { font-size:.74rem; color:var(--school-muted); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.74rem; font-weight:600; }
    .badge-statut.attente  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.encaisse { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule   { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .pd .chiffre { border:1px solid var(--pd-border); border-radius:10px; padding:.7rem .9rem; background:#fff; height:100%; }
    .pd .chiffre .v { font-size:1.1rem; font-weight:700; font-variant-numeric:tabular-nums; }
    .pd .chiffre .l { font-size:.72rem; color:var(--school-muted); text-transform:uppercase; font-weight:600; }

    .pd .chrono { list-style:none; margin:0; padding:0; }
    .pd .chrono li { position:relative; padding:0 0 .9rem 1.4rem; font-size:.85rem; }
    .pd .chrono li::before { content:''; position:absolute; left:4px; top:5px; width:9px; height:9px; border-radius:50%; background:var(--school-red); }
    .pd .chrono li::after { content:''; position:absolute; left:8px; top:16px; bottom:0; width:1px; background:var(--pd-border); }
    .pd .chrono li:last-child::after { display:none; }
    .pd .chrono li.ok::before { background:#1c7a4d; }
    .pd .chrono li.annule::before { background:#5b6b7a; }
</style>
@endsection

@section('contenu')
@php
    $fmt = fn ($v, $d = 2) => number_format((float) $v, $d, ',', ' ');
    $statut = $paiement->statut_paiement;
    $encaissements = $paiement->details->filter(fn ($d) => $d->date_encaissement)->sortBy('date_encaissement');
@endphp
<div class="pd" id="detail-paiement">

    {{-- En-tête : référence, statut, montant --}}
    <div class="carte">
        <div class="entete">
            <div>
                <span class="reference">{{ $paiement->reference }}</span>
                <span class="badge-statut {{ $statut?->css() }} ms-2" id="detail-statut"><span class="badge-dot"></span>{{ $statut?->label() }}</span>
                <div class="sub mt-2">
                    <i class="far fa-calendar me-1"></i>Saisi le {{ optional($paiement->date_paiement)->format('d/m/Y') ?? '—' }}
                    par {{ $paiement->comptable?->nom_complet ?: '—' }}
                </div>
            </div>
            <div class="montant-total" id="detail-montant">
                {{ $fmt($paiement->montant) }} <small>FCFA</small>
                <div class="sub">{{ $paiement->details->count() }} ligne(s)</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Élève et payeur --}}
        <div class="col-lg-6">
            <div class="carte h-100 mb-0">
                <div class="carte-titre"><h6><i class="fas fa-user-graduate"></i>Élève et payeur</h6></div>
                <div class="carte-corps" id="detail-eleve">
                    <div class="info"><span class="cle">Élève</span><span class="valeur">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : '—' }}</span></div>
                    <div class="info"><span class="cle">Matricule</span><span class="valeur">{{ $eleve->matricule ?? '—' }}</span></div>
                    <div class="info"><span class="cle">Classe</span><span class="valeur">{{ $paiement->inscription?->classe?->libelle ?? $paiement->inscription?->niveau?->libelle ?? '—' }}</span></div>
                    <div class="info"><span class="cle">Année scolaire</span><span class="valeur">{{ $annee?->libelle ?? '—' }}</span></div>
                    <div class="info"><span class="cle">Payeur</span><span class="valeur">{{ $paiement->payeur ?: '—' }}</span></div>
                    <div class="info"><span class="cle">Téléphone</span><span class="valeur">{{ $paiement->telephone_payeur ?: '—' }}</span></div>
                </div>
            </div>
        </div>

        {{-- Règlement --}}
        <div class="col-lg-6">
            <div class="carte h-100 mb-0">
                <div class="carte-titre"><h6><i class="fas fa-wallet"></i>Règlement</h6></div>
                <div class="carte-corps" id="detail-reglement">
                    <div class="info">
                        <span class="cle">Mode de paiement</span>
                        <span class="valeur">
                            @if ($paiement->mode_paiement)<i class="fas {{ $paiement->mode_paiement->icone() }} me-1 text-muted"></i>{{ $paiement->mode_paiement->label() }}@else — @endif
                        </span>
                    </div>
                    @if ($paiement->cheque)
                        <div class="info"><span class="cle">N° du chèque</span><span class="valeur">{{ $paiement->cheque->numero }}</span></div>
                        <div class="info"><span class="cle">Banque</span><span class="valeur">{{ $paiement->cheque->banque?->nom ?? '—' }}</span></div>
                        <div class="info"><span class="cle">Émetteur</span><span class="valeur">{{ $paiement->cheque->emetteur ?: '—' }}</span></div>
                        <div class="info"><span class="cle">Émis le</span><span class="valeur">{{ optional($paiement->cheque->date_emission)->format('d/m/Y') ?? '—' }}</span></div>
                        <div class="info"><span class="cle">Statut du chèque</span><span class="valeur">{{ $paiement->cheque->statut?->label() ?? '—' }}</span></div>
                    @endif
                    <div class="row g-2 mt-2">
                        <div class="col-4"><div class="chiffre"><div class="v">{{ $fmt($totaux['total'], 0) }}</div><div class="l">Total</div></div></div>
                        <div class="col-4"><div class="chiffre"><div class="v" style="color:#1c7a4d;" id="detail-encaisse">{{ $fmt($totaux['encaisse'], 0) }}</div><div class="l">Encaissé</div></div></div>
                        <div class="col-4"><div class="chiffre"><div class="v" style="color:var(--school-gold-dark);" id="detail-attente">{{ $fmt($totaux['en_attente'], 0) }}</div><div class="l">En attente</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lignes réglées --}}
    <div class="carte mt-3">
        <div class="carte-titre">
            <h6><i class="fas fa-list-ul"></i>Ce qui a été réglé</h6>
            <span class="sub">Chaque ligne est encaissée séparément par le caissier</span>
        </div>
        <div class="table-responsive">
            <table class="tableau" id="detail-lignes">
                <thead>
                    <tr>
                        <th>Frais / article</th>
                        <th class="num">Montant</th>
                        <th>Statut</th>
                        <th>Encaissement</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paiement->details as $d)
                        <tr>
                            <td>
                                <span class="nature">{{ $d->type_paiement?->label() ?? '—' }}</span>
                                <span class="fw-semibold ms-1">{{ $d->libelle }}</span>
                                @if ((float) $d->quantite > 1)
                                    <div class="sub">{{ rtrim(rtrim(number_format((float) $d->quantite, 2, ',', ' '), '0'), ',') }} × {{ $fmt($d->prix_unitaire) }}</div>
                                @endif
                            </td>
                            <td class="num fw-semibold">{{ $fmt($d->montant) }}</td>
                            <td><span class="badge-statut {{ $d->statut_paiement?->css() }}"><span class="badge-dot"></span>{{ $d->statut_paiement?->label() }}</span></td>
                            <td class="sub">
                                @if ($d->date_encaissement)
                                    Le {{ $d->date_encaissement->format('d/m/Y') }}
                                    @if ($d->caissier) par {{ $d->caissier->nom_complet }} @endif
                                    @if ($d->caisse)<br>{{ $d->caisse->libelle }}@endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($d->statut_paiement === \App\Domain\Finances\Types\PaiementStatut::ENCAISSE)
                                    @can('encaissements.voir')
                                        <a href="{{ route('finances.encaissements.recu', $d->id) }}" target="_blank" title="Reçu"><i class="fas fa-file-pdf"></i></a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune ligne.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr><td>Total</td><td class="num">{{ $fmt($totaux['total']) }}</td><td colspan="3"></td></tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Historique --}}
    <div class="carte">
        <div class="carte-titre"><h6><i class="fas fa-stream"></i>Historique</h6></div>
        <div class="carte-corps">
            <ul class="chrono" id="detail-historique">
                <li>
                    <strong>Saisi</strong> le {{ optional($paiement->date_paiement)->format('d/m/Y') ?? '—' }}
                    par {{ $paiement->comptable?->nom_complet ?: '—' }} — {{ $fmt($paiement->montant) }}
                    @if ($paiement->mode_paiement) en {{ mb_strtolower($paiement->mode_paiement->label()) }} @endif
                </li>
                @foreach ($encaissements as $d)
                    <li class="ok">
                        <strong>Encaissé</strong> le {{ $d->date_encaissement->format('d/m/Y') }}
                        @if ($d->caissier) par {{ $d->caissier->nom_complet }} @endif
                        — {{ $d->libelle }} ({{ $fmt($d->montant) }})
                    </li>
                @endforeach
                @if ($statut === \App\Domain\Finances\Types\PaiementStatut::ANNULE)
                    <li class="annule">
                        <strong>Annulé</strong>@if ($paiement->updated_at) le {{ $paiement->updated_at->format('d/m/Y') }}@endif
                        @if ($paiement->motif_suppression) — motif : {{ $paiement->motif_suppression }} @endif
                    </li>
                @elseif ($totaux['en_attente'] > 0)
                    <li>
                        <span class="text-muted">En attente : {{ $fmt($totaux['en_attente']) }} restent à encaisser par le caissier.</span>
                    </li>
                @endif
            </ul>

            @if ($paiement->isEnAttente())
                @can('paiements.annuler')
                    <form action="{{ route('finances.paiements.annuler', $paiement->id) }}" method="POST" class="form-confirm-delete mt-2" data-confirm-title="Annuler ce paiement ?">
                        @csrf
                        <input type="hidden" name="motif_suppression" value="Annulation demandée par l'utilisateur">
                        <button class="btn btn-outline-danger btn-sm rounded-pill px-3"><i class="fas fa-times me-1"></i> Annuler ce paiement</button>
                    </form>
                @endcan
            @endif
        </div>
    </div>
</div>
@endsection
