@extends('admin.layouts.app')

@section('title', 'Paiements')
@section('page_icon', 'fa-receipt')
@section('page_title', 'Paiements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Paiements</li>
@endsection

@section('page_actions')
    <x-bouton variante="contour" href="{{ route('finances.paiements.export', ['onglet' => in_array($onglet ?? '', ['mes', 'tous'], true) ? $onglet : null] + request()->except('onglet')) }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </x-bouton>
    @can('paiements.creer')
        <x-bouton variante="nouveau" href="{{ route('finances.paiements.recherche') }}">
            <i class="fas fa-plus-circle me-1"></i> Nouveau paiement
        </x-bouton>
    @endcan
@endsection

@section('css')
<style>
    .pay-page { --py-bg-soft:var(--eim-cream); --py-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--py-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition:transform .2s; box-shadow:0 2px 8px rgba(142,18,22,.04); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--py-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--py-border); padding:.55rem .9rem; font-size:.88rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .compteur-badge { display:inline-block; background:var(--py-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--py-border); }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--py-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--py-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-pay thead tr { background:var(--py-bg-soft); }
    .table-pay thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--py-border); white-space:nowrap; }
    .table-pay tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--py-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-pay tbody tr:hover { background:var(--eim-hover); }

    .reference { font-family:monospace; font-size:.8rem; background:var(--py-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }


    .onglets { border-bottom:2px solid var(--py-border, var(--eim-border)); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; margin-bottom:1.25rem; }
    .onglets .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.92rem; padding:.7rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .table-types { table-layout:fixed; width:100%; }
    .table-types thead th { color:var(--school-red); font-size:.74rem; font-weight:700; text-transform:uppercase; background:var(--py-bg-soft, var(--eim-cream)); padding:.7rem .9rem; border-bottom:2px solid var(--py-border, var(--eim-border)); }
    .table-types thead th small { display:block; font-weight:500; text-transform:none; color:var(--school-muted); font-size:.7rem; }
    .table-types tbody td, .table-types tfoot td { padding:.6rem .9rem; border-bottom:1px solid var(--py-border, var(--eim-border)); font-size:.88rem; overflow-wrap:anywhere; }
    .table-types tfoot td { font-weight:700; background:var(--eim-hover); }
    .table-types .num { text-align:right; font-variant-numeric:tabular-nums; }
    .table-types .zero { color:#b8c2cc; }
    .bloc-type-titre { font-size:.85rem; font-weight:700; padding:.8rem 1.1rem; display:flex; align-items:center; gap:.5rem; }
    .bloc-type-titre.encaisse { color:var(--eim-success); }
    .bloc-type-titre.attente { color:var(--school-gold-dark); }
    .totaux-encaisses { background:linear-gradient(135deg,#fff,var(--py-bg-soft)); border:1px solid var(--py-border); border-radius:14px; padding:1rem 1.15rem; margin-bottom:1.5rem; }
    .totaux-titre { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; color:var(--school-red); margin-bottom:.75rem; }
    .total-case { background:#fff; border:1px solid var(--py-border); border-radius:10px; padding:.75rem .9rem; height:100%; }
    .total-case .total-label { font-size:.72rem; color:var(--school-muted); text-transform:uppercase; font-weight:600; }
    .total-case .total-valeur { font-size:1.15rem; font-weight:700; color:var(--eim-success); font-variant-numeric:tabular-nums; line-height:1.3; }
    .total-case .total-periode { font-size:.7rem; color:#7A6A6A; }

    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .nature { display:inline-block; background:var(--py-bg-soft, var(--eim-cream)); color:var(--school-red); font-weight:600; font-size:.7rem; padding:.15rem .6rem; border-radius:999px; border:1px solid var(--py-border, var(--eim-border)); }
    /* Colonnes contenues : le tableau tient dans la largeur, la colonne Actions reste visible sans défilement */
    .table-pay { table-layout:auto; }
    .table-pay td.col-paiement { white-space:nowrap; }
    .table-pay td.col-eleve  { min-width:170px; max-width:240px; }
    .table-pay td.col-detail { max-width:200px; }
    .table-pay td.col-eleve .fw-semibold, .table-pay td.col-eleve .cell-sub,
    .table-pay td.col-detail .natures, .table-pay td.col-detail .cell-sub { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .table-pay td.col-detail .natures { font-size:.78rem; font-weight:600; color:var(--school-red); }
    .table-pay td.col-montant { white-space:nowrap; }
    .table-pay td.col-actions { width:56px; }

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#7A6A6A; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="pay-page">

    {{-- Onglets : « Mes paiements » (ses propres saisies), « Tous les paiements » (Administrateur et Direction),
         lignes de paiement (Administrateur) et totaux par type (Administrateur et Direction) --}}
    <ul class="nav onglets" id="onglets-paiements">
        @if ($voitMes)
            <li class="nav-item">
                <a class="nav-link @if ($onglet === 'mes') active @endif" id="onglet-mes" href="{{ route('finances.paiements.index', ['onglet' => 'mes']) }}">
                    <i class="fas fa-user-edit me-1"></i> Mes paiements
                </a>
            </li>
        @endif
        @if ($voitTous)
            <li class="nav-item">
                <a class="nav-link @if ($onglet === 'tous') active @endif" id="onglet-tous" href="{{ route('finances.paiements.index', ['onglet' => 'tous']) }}">
                    <i class="fas fa-list me-1"></i> Tous les paiements
                </a>
            </li>
        @endif
        @if ($voitDetails)
            <li class="nav-item">
                <a class="nav-link @if ($onglet === 'details') active @endif" id="onglet-details" href="{{ route('finances.paiements.index', ['onglet' => 'details']) }}">
                    <i class="fas fa-stream me-1"></i> Détails des paiements
                </a>
            </li>
        @endif
        @if ($voitTotaux)
            <li class="nav-item">
                <a class="nav-link @if ($onglet === 'totaux') active @endif" id="onglet-totaux" href="{{ route('finances.paiements.index', ['onglet' => 'totaux']) }}">
                    <i class="fas fa-chart-bar me-1"></i> Totaux par type de paiement
                </a>
            </li>
        @endif
    </ul>

    @if ($onglet === 'details')
        @php
            $fmtD = fn ($v) => number_format((float) $v, 0, ',', ' ');
            $statutsLigne = \App\Domain\Finances\Types\PaiementStatut::cases();
        @endphp
        {{-- ==================== Lignes de paiement (table details) ==================== --}}
        <x-carte id="details-paiements">
            <div class="card-header bg-white py-3 px-4">
                <form method="GET" class="row g-2 align-items-center" id="filtres-details">
                    <input type="hidden" name="onglet" value="details">
                    <div class="col-md-4">
                        <x-recherche name="search" placeholder="Élève, matricule, référence, payeur, libellé..." value="{{ $filtresDetails['search'] ?? '' }}" />
                    </div>
                    <div class="col-md-3">
                        <select name="type_paiement" class="form-select filtre-input" title="Type de paiement">
                            <option value="">— Tous les types —</option>
                            @foreach ($types as $v => $l)
                                <option value="{{ $v }}" @selected(($filtresDetails['type_paiement'] ?? '') == $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="statut_ligne" class="form-select filtre-input" title="Statut de la ligne">
                            <option value="">— Statut —</option>
                            @foreach ($statutsLigne as $st)
                                <option value="{{ $st->value }}" @selected(($filtresDetails['statut_ligne'] ?? '') === (string) $st->value)>{{ $st->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="mode_paiement" class="form-select filtre-input" title="Mode de paiement">
                            <option value="">— Mode —</option>
                            @foreach ($modes as $v => $l)
                                <option value="{{ $v }}" @selected(($filtresDetails['mode_paiement'] ?? '') == $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-0">Saisi du</label>
                        <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filtresDetails['date_debut'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted mb-0">au</label>
                        <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filtresDetails['date_fin'] ?? '' }}">
                    </div>
                    <div class="col-md-2 d-flex gap-1 align-self-end">
                        <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                        <x-bouton variante="contour" href="{{ route('finances.paiements.index', ['onglet' => 'details']) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                    </div>
                </form>
                <div class="small text-muted mt-2" id="total-details">
                    {{ $fmtD($totauxDetails['lignes']) }} ligne(s) de paiement en {{ $annee?->libelle }} pour ces critères —
                    total <strong style="color:var(--school-red-dark);">{{ $fmtD($totauxDetails['montant']) }} F</strong>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-types align-middle mb-0" id="table-details">
                    <thead>
                        <tr>
                            <th style="width:20%;">Paiement</th>
                            <th style="width:26%;">Élève / Payeur</th>
                            <th>Ligne de paiement</th>
                            <th class="text-end" style="width:17%;">Montant</th>
                            <th class="text-center" style="width:96px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($details as $d)
                        @php
                            $type = \App\Domain\Finances\Types\TypeDetail::tryFrom((int) $d->type_paiement);
                            $statut = \App\Domain\Finances\Types\PaiementStatut::tryFrom((int) $d->statut_paiement);
                            $mode = \App\Domain\Finances\Types\ModePaiement::tryFrom((int) $d->mode_paiement);
                        @endphp
                        <tr data-detail="{{ $d->id }}">
                            <td>
                                <span class="reference">{{ $d->reference ?? '—' }}</span>
                                <div class="cell-sub mt-1"><i class="far fa-calendar me-1"></i>{{ $d->date_paiement ? \Illuminate\Support\Carbon::parse($d->date_paiement)->format('d/m/Y') : '—' }}</div>
                                @if ($mode)<div class="cell-sub"><i class="fas {{ $mode->icone() }} me-1"></i>{{ $mode->label() }}</div>@endif
                            </td>
                            <td>
                                <div class="fw-semibold">{{ trim(($d->nom ?? '') . ' ' . ($d->prenom ?? '')) ?: ($d->payeur ?? '—') }}</div>
                                <div class="cell-sub">{{ $d->matricule }}@if ($d->niveau) · {{ $d->niveau }}@endif @if ($d->classe) · {{ $d->classe }}@endif</div>
                                @if ($d->payeur)<div class="cell-sub">Payé par {{ $d->payeur }}@if ($d->telephone_payeur) · {{ $d->telephone_payeur }}@endif</div>@endif
                            </td>
                            <td>
                                <span class="nature">{{ $type?->label() ?? 'Non précisé' }}</span>
                                <div class="mt-1">{{ $d->libelle ?: '—' }}@if ((float) $d->quantite > 1) <span class="cell-sub">× {{ rtrim(rtrim(number_format((float) $d->quantite, 2, ',', ' '), '0'), ',') }}</span>@endif</div>
                                @if ($d->frais && $d->frais !== $d->libelle)<div class="cell-sub">Frais : {{ $d->frais }}</div>@endif
                            </td>
                            <td class="num">
                                <div class="fw-bold">{{ $fmtD($d->montant) }} F</div>
                                <x-badge class="{{ $statut?->css() ?? 'annule' }} mt-1" point>{{ $statut?->label() ?? '—' }}</x-badge>
                                @if ($d->date_encaissement)<div class="cell-sub">encaissé le {{ \Illuminate\Support\Carbon::parse($d->date_encaissement)->format('d/m/Y') }}</div>@endif
                            </td>
                            <td class="text-center">
                                <x-actions>
                                    @if ($d->paiement_id)
                                        <li><a class="dropdown-item" href="{{ route('finances.paiements.detail', $d->paiement_id) }}"><i class="fas fa-eye"></i> Voir le paiement</a></li>
                                    @endif
                                </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-receipt">Aucune ligne de paiement pour ces critères.</x-vide>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-carte>
        <div class="mt-4" id="pagination-details"><x-pagination :liste="$details" /></div>

    @elseif ($onglet === 'totaux')
        @php
            $fmtT = fn ($v) => (float) $v > 0 ? number_format((float) $v, 0, ',', ' ') . ' F' : '<span class="zero">—</span>';
            $maintenant = now();
            $periodesT = [
                'annee'   => ["Année", $annee?->libelle ?? '—'],
                'mois'    => ['Mois', ucfirst($maintenant->copy()->locale('fr')->isoFormat('MMMM YYYY'))],
                'semaine' => ['Semaine', 'du ' . $maintenant->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('d/m') . ' au ' . $maintenant->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('d/m')],
                'jour'    => ['Jour', $maintenant->format('d/m/Y')],
            ];
            $blocs = [
                'encaisse' => ['Paiements encaissés', 'encaisse', 'fa-check-circle'],
                'attente'  => ["Paiements en attente d'encaissement", 'attente', 'fa-hourglass-half'],
            ];
        @endphp
        <div id="totaux-par-type">
            @foreach ($blocs as $cle => [$titre, $classe, $icone])
                <x-carte class="mb-4 bloc-type" data-statut="{{ $cle }}">
                    <div class="bloc-type-titre {{ $classe }}"><i class="fas {{ $icone }}"></i> {{ $titre }}</div>
                    <div class="card-body p-0">
                        <table class="table table-types align-middle mb-0" id="table-types-{{ $cle }}">
                            <thead>
                                <tr>
                                    <th style="width:28%;">Type de paiement</th>
                                    @foreach ($periodesT as [$libelleP, $detailP])
                                        <th class="text-end">{{ $libelleP }}<small>{{ $detailP }}</small></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @forelse ($parType[$cle] as $ligne)
                                <tr data-type="{{ $ligne->type }}">
                                    <td>
                                        <span class="fw-semibold">{{ $ligne->libelle }}</span>
                                        <div class="text-muted" style="font-size:.72rem;">{{ number_format($ligne->lignes, 0, ',', ' ') }} ligne(s) sur l'année</div>
                                    </td>
                                    @foreach (array_keys($periodesT) as $periode)
                                        <td class="num">{!! $fmtT($ligne->$periode) !!}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <x-vide colspan="5" icone="fas fa-receipt">Aucun paiement de ce type pour {{ $annee?->libelle }}.</x-vide>
                            @endforelse
                            </tbody>
                            @if ($parType[$cle]->isNotEmpty())
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        @foreach (array_keys($periodesT) as $periode)
                                            <td class="num total-{{ $periode }}">{!! $fmtT($parType[$cle]->sum($periode)) !!}</td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </x-carte>
            @endforeach
            <p class="text-muted small">
                Lignes de paiement actives de l'année scolaire {{ $annee?->libelle }}. Pour un paiement encaissé, la date retenue est celle
                de l'encaissement (à défaut celle de la saisie) ; pour un paiement en attente, celle de la saisie.
            </p>
        </div>
    @else

    {{-- Totaux encaissés : réservés à l'Administrateur et à la Direction --}}
    @if ($totaux)
        @php
            $aujourdhui = now();
            $casesTotaux = [
                ['annee',   "Toute l'année",  $annee?->libelle ?? '—'],
                ['mois',    'Ce mois',        ucfirst($aujourdhui->copy()->locale('fr')->isoFormat('MMMM YYYY'))],
                ['semaine', 'Cette semaine',  'du ' . $aujourdhui->copy()->startOfWeek(\Carbon\Carbon::MONDAY)->format('d/m') . ' au ' . $aujourdhui->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->format('d/m')],
                ['jour',    "Aujourd'hui",    $aujourdhui->format('d/m/Y')],
            ];
        @endphp
        <div class="totaux-encaisses" id="totaux-encaisses">
            <div class="totaux-titre"><i class="fas fa-coins me-1"></i> Total des paiements encaissés</div>
            <div class="row g-2">
                @foreach ($casesTotaux as [$cle, $libelle, $periode])
                    <div class="col-6 col-lg-3">
                        <div class="total-case" data-total="{{ $cle }}">
                            <div class="total-label">{{ $libelle }}</div>
                            <div class="total-valeur">{{ number_format($totaux[$cle], 0, ',', ' ') }} <small>FCFA</small></div>
                            <div class="total-periode">{{ $periode }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <x-stat icone="fas fa-hourglass-half" couleur="gold" libelle="En attente d'encaissement">{{ $compteurs['attente'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-4">
            <x-stat icone="fas fa-check-circle" couleur="green" libelle="Encaissés">{{ $compteurs['encaisse'] }}</x-stat>
        </div>
        <div class="col-sm-6 col-md-4">
            <x-stat icone="fas fa-times-circle" couleur="red" libelle="Annulés">{{ $compteurs['annule'] }}</x-stat>
        </div>
    </div>

    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center" id="filtres-paiements">
                <input type="hidden" name="onglet" value="{{ $onglet }}">
                @if ($onglet === 'mes')
                    <div class="col-12 small text-muted" id="aide-mes-paiements"><i class="fas fa-user-edit me-1"></i>Paiements que vous avez saisis en {{ $annee?->libelle }}.</div>
                @endif
                <div class="col-md-3">
                    <x-recherche name="search" placeholder="Référence, payeur..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                @if ($onglet === 'tous')
                    <div class="col-md-3">
                        <select name="saisi_par" class="form-select filtre-input" id="filtre-saisi-par" title="Saisi par">
                            <option value="">— Saisi par —</option>
                            @foreach ($saisisseurs as $u)
                                <option value="{{ $u->id }}" @selected(($filters['saisi_par'] ?? '') == $u->id)>{{ $u->nom }} ({{ $u->paiements }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <select name="statut_paiement" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['statut_paiement'] ?? '') === (string) $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="mode_paiement" class="form-select filtre-input">
                        <option value="">— Mode —</option>
                        @foreach ($modes as $v => $l)
                            <option value="{{ $v }}" @selected(($filters['mode_paiement'] ?? '') == $v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_debut" class="form-control filtre-input" value="{{ $filters['date_debut'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_fin" class="form-control filtre-input" value="{{ $filters['date_fin'] ?? '' }}">
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.paiements.index', ['onglet' => $onglet]) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-pay align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Paiement</th>
                            <th>Élève / Payeur</th>
                            <th>Détail</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($paiements as $p)
                        <tr>
                            {{-- 1. Paiement : référence, date, comptable --}}
                            <td class="ps-4 col-paiement">
                                <span class="reference">{{ $p->reference }}</span>
                                <div class="cell-sub mt-1">
                                    <i class="far fa-calendar me-1"></i>{{ optional($p->date_paiement)->format('d/m/Y') ?? '—' }}
                                </div>
                                <div class="cell-sub"><i class="far fa-user me-1"></i>{{ $p->comptable?->name ?? '—' }}</div>
                            </td>

                            {{-- 2. Élève réglé et personne qui paie --}}
                            <td class="col-eleve">
                                @php $eleve = $p->inscription?->eleve; @endphp
                                @if ($eleve)
                                    <div class="fw-semibold">{{ $eleve->nom }} {{ $eleve->prenom }}</div>
                                    <div class="cell-sub">
                                        {{ $eleve->matricule }}@if ($p->inscription?->classe) · {{ $p->inscription->classe->libelle }}@endif
                                    </div>
                                @endif
                                <div class="{{ $eleve ? 'cell-sub' : 'fw-semibold' }}">
                                    @if ($eleve)Payé par @endif{{ $p->payeur }}@if ($p->telephone_payeur) · {{ $p->telephone_payeur }}@endif
                                </div>
                            </td>

                            {{-- 3. Détail : natures réglées et mode de paiement --}}
                            @php
                                $natures = $p->details->map(fn ($d) => $d->type_paiement?->label())->filter()->unique()->implode(', ');
                                $mode = $p->mode_paiement
                                    ? $p->mode_paiement->label() . ($p->cheque ? ' n° ' . $p->cheque->numero . ($p->cheque->banque ? ' · ' . $p->cheque->banque->nom : '') : '')
                                    : '';
                            @endphp
                            <td class="col-detail">
                                <div class="natures" title="{{ $natures }}">{{ $natures ?: '—' }}</div>
                                <div class="cell-sub" title="{{ $mode }}">
                                    {{ $p->details->count() }} ligne(s)@if ($mode) · @if ($p->mode_paiement)<i class="fas {{ $p->mode_paiement->icone() }}"></i>@endif {{ $mode }}@endif
                                </div>
                            </td>

                            {{-- 4. Montant et statut --}}
                            <td class="text-end col-montant">
                                <div class="num">{{ number_format((float) $p->montant, 2, ',', ' ') }}</div>
                                <x-badge class="{{ $p->statut_paiement?->css() }} mt-1" point>{{ $p->statut_paiement?->label() }}
                                </x-badge>
                            </td>

                            {{-- 5. Actions --}}
                            <td class="text-center pe-4 col-actions">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.paiements.detail', $p->id) }}"><i class="fas fa-eye"></i> Détail</a>
                                        </li>
                                        @if ($p->isEnAttente())
                                            @can('paiements.annuler')
                                                <li>
                                                    <form action="{{ route('finances.paiements.annuler', $p) }}" method="POST" class="form-confirm-delete" data-confirm-title="Annuler ce paiement ?">
                                                        @csrf
                                                        <input type="hidden" name="motif_suppression" value="Annulation demandée par l'utilisateur">
                                                        <button class="dropdown-item text-danger"><i class="fas fa-times"></i> Annuler</button>
                                                    </form>
                                                </li>
                                            @endcan
                                        @endif
                                        @if ($p->statut_paiement?->isVerrouille())
                                            <li><span class="dropdown-item text-muted small" style="cursor:default;"><i class="fas fa-lock"></i> Verrouillé</span></li>
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-receipt">Aucun paiement.</x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4"><x-pagination :liste="$paiements->appends(request()->query())" /></div>
    @endif
</div>
@endsection