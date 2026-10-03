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
    <a href="{{ route('finances.paiements.export', request()->query()) }}"
       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-file-excel me-1"></i> Exporter
    </a>
    @can('paiements.creer')
        <a href="{{ route('finances.paiements.recherche') }}" class="btn btn-nouveau">
            <i class="fas fa-plus-circle me-1"></i> Nouveau paiement
        </a>
    @endcan
@endsection

@section('css')
<style>
    .pay-page { --py-bg-soft:#faf6ee; --py-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .stat-card { background:#fff; border:1px solid var(--py-border); border-radius:12px; padding:1.05rem 1.15rem; display:flex; align-items:center; gap:.9rem; transition:transform .2s; box-shadow:0 2px 8px rgba(122,15,28,.04); }
    .stat-card:hover { transform:translateY(-2px); }
    .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.1rem; flex-shrink:0; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-icon.red   { background:rgba(200,30,58,.10);  color:var(--school-red); }
    .stat-info .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.75rem; color:var(--school-muted); margin:0; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; }
    .main-card .card-header { border-bottom:1px solid var(--py-border) !important; }

    .filtre-input { border-radius:10px; border:1px solid var(--py-border); padding:.55rem .9rem; font-size:.88rem; }
    .filtre-input:focus { border-color:var(--school-red); box-shadow:0 0 0 .2rem rgba(200,30,58,.12); }
    .search-wrapper { position:relative; }
    .search-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#9aa7b2; font-size:.85rem; }
    .search-input { padding-left:36px !important; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.55rem 1rem; font-weight:600; font-size:.88rem; }
    .btn-filtrer:hover { background:var(--school-red-dark); color:#fff; }
    .compteur-badge { display:inline-block; background:var(--py-bg-soft); color:var(--school-red); font-weight:600; font-size:.85rem; padding:.45rem .9rem; border-radius:999px; border:1px solid var(--py-border); }
    .btn-nouveau { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; border:none; border-radius:10px; padding:.6rem 1.2rem; font-weight:600; }
    .btn-outline-scolarite { border:1px solid var(--py-border); color:var(--school-ink); background:#fff; font-size:.82rem; }
    .btn-outline-scolarite:hover { background:var(--py-bg-soft); border-color:var(--school-red); color:var(--school-red); }

    .table-pay thead tr { background:var(--py-bg-soft); }
    .table-pay thead th { color:var(--school-red); font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.9rem 1rem; border-bottom:2px solid var(--py-border); white-space:nowrap; }
    .table-pay tbody td { padding:.9rem 1rem; border-bottom:1px solid var(--py-border); font-size:.9rem; color:var(--school-ink); vertical-align:middle; }
    .table-pay tbody tr:hover { background:#fdfaf5; }

    .reference { font-family:monospace; font-size:.8rem; background:var(--py-bg-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:700; }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.35em .85em; border-radius:999px; font-size:.72rem; font-weight:600; }
    .badge-statut.attente  { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-statut.encaisse { background:#e3f7ec; color:#1c7a4d; }
    .badge-statut.annule   { background:#eceff2; color:#5b6b7a; }
    .badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

    .totaux-encaisses { background:linear-gradient(135deg,#fff,var(--py-bg-soft)); border:1px solid var(--py-border); border-radius:14px; padding:1rem 1.15rem; margin-bottom:1.5rem; }
    .totaux-titre { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; color:var(--school-red); margin-bottom:.75rem; }
    .total-case { background:#fff; border:1px solid var(--py-border); border-radius:10px; padding:.75rem .9rem; height:100%; }
    .total-case .total-label { font-size:.72rem; color:var(--school-muted); text-transform:uppercase; font-weight:600; }
    .total-case .total-valeur { font-size:1.15rem; font-weight:700; color:#1c7a4d; font-variant-numeric:tabular-nums; line-height:1.3; }
    .total-case .total-periode { font-size:.7rem; color:#9aa7b2; }

    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
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

    .empty-state { padding:3.5rem 1rem; text-align:center; color:#9aa7b2; }
    .empty-state i { font-size:2.5rem; margin-bottom:.75rem; display:block; color:#d8c8ae; }
</style>
@endsection

@section('contenu')
<div class="pay-page">

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
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['attente'] }}</div>
                    <div class="stat-label">En attente d'encaissement</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['encaisse'] }}</div>
                    <div class="stat-label">Encaissés</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="stat-value">{{ $compteurs['annule'] }}</div>
                    <div class="stat-label">Annulés</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control filtre-input search-input"
                               placeholder="Référence, payeur..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
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
                <div class="col-md-1">
                    <button type="submit" class="btn btn-filtrer w-100"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-pay align-middle mb-0">
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
                                <span class="badge-statut {{ $p->statut_paiement?->css() }} mt-1">
                                    <span class="badge-dot"></span>{{ $p->statut_paiement?->label() }}
                                </span>
                            </td>

                            {{-- 5. Actions --}}
                            <td class="text-center pe-4 col-actions">
                                <div class="dropdown">
                                    <button class="btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-actions dropdown-menu-end">
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
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty-state"><i class="fas fa-receipt"></i><p>Aucun paiement.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $paiements->appends(request()->query())->links('vendor.pagination.custom') }}</div>
</div>
@endsection