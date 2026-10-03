@extends('admin.layouts.app')

@section('title', 'Frais — ' . $frais->libelle)
@section('page_icon', 'fa-tag')
@section('page_title', 'Détail du frais')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.frais-ecoles.index') }}">Frais scolaires</a></li>
    <li class="breadcrumb-item active">{{ \Str::limit($frais->libelle, 40) }}</li>
@endsection

@section('page_actions')
    <a href="{{ route('finances.frais-ecoles.index') }}" class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Retour
    </a>
@endsection

@section('css')
<style>
    .show-page { --sp-bg-soft:#faf6ee; --sp-border:#ece4d6; }
    .text-xs { font-size:.72rem; }
    .text-sm { font-size:.8rem; }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(122,15,28,.06); background:#fff; overflow:hidden; margin-bottom:1rem; }
    .main-card .card-header { border-bottom:1px solid var(--sp-border) !important; }

    .info-row { display:flex; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid #f1ece2; }
    .info-row:last-child { border-bottom:none; }
    .info-label { font-size:.75rem; color:var(--school-muted); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .info-value { font-weight:600; color:var(--school-ink); text-align:right; }
    .info-value.grand { font-size:1.15rem; color:var(--school-red-dark); }

    .badge-type { display:inline-flex; padding:.35em .85em; border-radius:999px; font-size:.75rem; font-weight:600; }
    .badge-type.comptant  { background:#e0ecfc; color:#2563eb; }
    .badge-type.echelonne { background:#fdf4e0; color:var(--school-gold-dark); }
    .badge-type.obligatoire { background:#e3f7ec; color:#1c7a4d; }
    .badge-type.optionnel { background:#eceff2; color:#5b6b7a; }

    .stat-card { background:#fff; border:1px solid var(--sp-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; }
    .stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
    .stat-icon.blue  { background:rgba(37,99,235,.10);  color:#2563eb; }
    .stat-icon.gold  { background:rgba(212,169,77,.18); color:var(--school-gold-dark); }
    .stat-icon.green { background:rgba(28,122,77,.10);  color:#1c7a4d; }
    .stat-info .stat-value { font-size:1.1rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-info .stat-label { font-size:.72rem; color:var(--school-muted); margin:0; }

    .table-sousc thead tr { background:var(--sp-bg-soft); }
    .table-sousc thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); white-space:nowrap; }
    .table-sousc tbody td { padding:.7rem .9rem; border-bottom:1px solid var(--sp-border); font-size:.85rem; color:var(--school-ink); vertical-align:middle; }
    .table-sousc tbody tr:hover { background:#fdfaf5; }
    .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:600; }
    .num.grand { color:var(--school-red-dark); font-weight:700; }

    .remise-badge { display:inline-flex; align-items:center; gap:4px; padding:.2em .7em; border-radius:999px; background:#fdf4e0; color:var(--school-gold-dark); font-size:.7rem; font-weight:700; }

    .empty-state { padding:3rem 1rem; text-align:center; color:#9aa7b2; font-size:.85rem; }
    .empty-state i { font-size:2.5rem; margin-bottom:.6rem; display:block; color:#d8c8ae; }

    .ligne-plan-item { display:flex; align-items:center; gap:.75rem; padding:.55rem .75rem; border-radius:8px; background:var(--sp-bg-soft); margin-bottom:.35rem; }
    .ligne-plan-ordre { width:22px; height:22px; border-radius:50%; background:var(--school-red); color:#fff; font-size:.7rem; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
</style>
@endsection

@section('contenu')
<div class="show-page">

    {{-- En-tête / identité + totaux --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="card main-card h-100">
                <div class="card-header bg-white py-3 px-4">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-1" style="color:var(--school-gold-dark);"></i>
                        Informations
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="info-label">Libellé</span>
                        <span class="info-value">{{ $frais->libelle }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Niveau</span>
                        <span class="info-value">{{ $frais->niveau?->libelle ?? 'Tous niveaux' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Montant</span>
                        <span class="info-value grand">{{ number_format((float) $frais->montant, 2, ',', ' ') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type de paiement</span>
                        <span class="info-value">
                            <span class="badge-type {{ $frais->modalite_paiement?->css() }}">
                                {{ $frais->modalite_paiement?->label() }}
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Type forfait</span>
                        <span class="info-value">
                            <span class="badge-type {{ $frais->type_forfait?->css() }}">
                                {{ $frais->type_forfait?->label() }}
                            </span>
                        </span>
                    </div>
                    @if ($frais->planEcheancier)
                        <div class="info-row">
                            <span class="info-label">Plan d'échéancier</span>
                            <span class="info-value">
                                <i class="fas fa-calendar-alt me-1"></i>{{ $frais->planEcheancier->nom }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Totaux souscriptions --}}
        <div class="col-lg-7">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-file-signature"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ $souscriptions->total() }}</div>
                            <div class="stat-label">Souscriptions</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon gold"><i class="fas fa-tag"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ number_format($totaux['remise'], 0, ',', ' ') }}</div>
                            <div class="stat-label">Remises accordées</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-coins"></i></div>
                        <div class="stat-info">
                            <div class="stat-value">{{ number_format($totaux['net'], 0, ',', ' ') }}</div>
                            <div class="stat-label">Montant net attendu</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Plan d'échéancier --}}
            @if ($frais->planEcheancier && $frais->planEcheancier->lignes->isNotEmpty())
                <div class="card main-card">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-calendar-alt me-1" style="color:var(--school-gold-dark);"></i>
                            Plan d'échéancier — {{ $frais->planEcheancier->nom }}
                        </h6>
                        <span class="text-xs text-muted">
                            {{ $frais->planEcheancier->lignes->count() }} échéance(s)
                        </span>
                    </div>
                    <div class="card-body p-3">
                        @foreach ($frais->planEcheancier->lignes as $ligne)
                            <div class="ligne-plan-item">
                                <div class="ligne-plan-ordre">{{ $ligne->ordre }}</div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-sm">{{ $ligne->libelle }}</div>
                                    @if ($ligne->date_echeance || $ligne->jour_echeance)
                                        <div class="text-xs text-muted">
                                            <i class="far fa-calendar me-1"></i>
                                            @if ($ligne->date_echeance)
                                                {{ $ligne->date_echeance->format('d/m/Y') }}
                                            @else
                                                Jour {{ $ligne->jour_echeance }} du mois
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="text-end">
                                    @if ($ligne->pourcentage)
                                        <div class="fw-bold text-sm">{{ number_format((float) $ligne->pourcentage, 2, ',', ' ') }} %</div>
                                    @endif
                                    @if ($ligne->montant)
                                        <div class="text-xs text-muted">{{ number_format((float) $ligne->montant, 2, ',', ' ') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Souscriptions rattachées --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="fas fa-users me-1" style="color:var(--school-gold-dark);"></i>
                Élèves souscrits à ce frais
            </h6>
            <div class="d-flex gap-2">
                <span class="text-xs text-muted">Total brut : {{ number_format($totaux['brut'], 2, ',', ' ') }}</span>
                @can('souscriptions.gerer')
                    <a href="{{ route('finances.souscriptions.index', ['frais_ecole_id' => $frais->id]) }}"
                       class="btn btn-outline-scolarite btn-sm rounded-pill px-3">
                        <i class="fas fa-plus me-1"></i> Gérer les souscriptions
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sousc align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Élève</th>
                            <th>Classe</th>
                            <th>Date</th>
                            <th class="text-end">Montant brut</th>
                            <th class="text-end">Remise</th>
                            <th class="text-end">Net</th>
                            <th>Souscrit par</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($souscriptions as $s)
                        <tr>
                            <td class="ps-4">
                                <div class="fw-semibold">{{ $s->inscription?->eleve?->nom }} {{ $s->inscription?->eleve?->prenom }}</div>
                                <div class="text-xs text-muted">{{ $s->inscription?->eleve?->matricule }}</div>
                            </td>
                            <td class="text-sm">{{ $s->inscription?->classe?->libelle ?? '—' }}</td>
                            <td class="text-sm text-muted">{{ optional($s->date_souscription)->format('d/m/Y') }}</td>
                            <td class="num">{{ number_format((float) $s->montant_annuel_prevu, 2, ',', ' ') }}</td>
                            <td class="text-end">
                                @if ((float) $s->taux_remise > 0)
                                    <span class="remise-badge">
                                        <i class="fas fa-tag"></i> {{ number_format((float) $s->taux_remise, 1, ',', ' ') }} %
                                    </span>
                                    <div class="text-xs text-muted">− {{ number_format($s->montant_remise, 2, ',', ' ') }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="num grand">{{ number_format($s->montant_net, 2, ',', ' ') }}</td>
                            <td class="text-sm text-muted">{{ $s->auteur?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p class="mb-0">Aucun élève n'est encore souscrit à ce frais.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">{{ $souscriptions->appends(request()->query())->links('vendor.pagination.custom') }}</div>
</div>
@endsection