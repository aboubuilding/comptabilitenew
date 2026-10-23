{{-- resources/views/domain/finances/caisses/index.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Caisses & Journaux de caisse')
@section('page_icon', 'fa-clipboard-list')
@section('page_title', 'Caisses & Journaux de caisse')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Caisses</li>
@endsection

@section('page_actions')
    @can('caisses.ouvrir')
        @if ($anneeCloturee)
            <span class="text-muted small" id="annee-cloturee"><i class="fas fa-lock me-1"></i>Année {{ $annee?->libelle }} clôturée : ouverture de caisse impossible</span>
        @else
        <x-bouton variante="nouveau" type="button" data-bs-toggle="modal" data-bs-target="#caisseModal">
            <i class="fas fa-plus-circle me-1"></i> Ouvrir une caisse
        </x-bouton>
        @endif
    @endcan
@endsection

@section('css')
<style>
    /* Jetons propres à cette page — school-red / gold / ink / muted / ff
       viennent de layouts/app.blade.php, non redéclarés ici. */
    .caisses-page {
        --caisses-bg-soft: var(--eim-cream);
        --caisses-border: var(--eim-border);
    }

    .text-xs  { font-size: .72rem; }
    .text-sm  { font-size: .8rem; }
    .text-md  { font-size: .92rem; }

    /* Cartes stats */
    .stat-card {
        background: #fff;
        border: 1px solid var(--caisses-border);
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 2px 8px rgba(142,18,22,.04);
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(142,18,22,.08); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; background: rgba(227,30,36,.08); color: var(--school-red);
        flex-shrink: 0;
    }
    .stat-icon.green { background: rgba(28,122,77,.08); color: var(--eim-success); }
    .stat-info .stat-value { font-size: 1.2rem; font-weight: 700; line-height: 1.2; color: var(--school-ink); }
    .stat-info .stat-label { font-size: .78rem; color: var(--school-muted); margin: 0; }

    .btn-outline-scolarite {
        border: 1px solid var(--caisses-border);
        color: var(--school-ink);
        background: #fff;
        font-size: .82rem;
    }
    .btn-outline-scolarite:hover { background: var(--caisses-bg-soft); border-color: var(--school-red); color: var(--school-red); }

    /* Card & tableau principal */
    .main-card { border: none; border-radius: 16px; box-shadow: 0 10px 30px rgba(142,18,22,.06); background: #fff; overflow: hidden; }
    .main-card .card-header { border-bottom: 1px solid var(--caisses-border) !important; }

    .filtre-input {
        border-radius: 10px; border: 1px solid var(--caisses-border);
        padding: .55rem .9rem; font-size: .88rem;
    }


    .btn-filtrer {
        background: var(--school-red); color: #fff; border: none; border-radius: 10px;
        padding: .55rem 1rem; font-weight: 600; font-size: .88rem; transition: all .2s ease;
    }
    .btn-filtrer:hover { background: var(--school-red-dark); color: #fff; box-shadow: 0 4px 12px rgba(227,30,36,.25); }

    .compteur-badge {
        display: inline-block; background: var(--caisses-bg-soft); color: var(--school-red);
        font-weight: 600; font-size: .85rem; padding: .45rem .9rem; border-radius: 999px;
        border: 1px solid var(--caisses-border);
    }

    .btn-nouveau {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border: none; border-radius: 10px; padding: .6rem 1.2rem; font-weight: 600;
        box-shadow: 0 4px 14px rgba(227,30,36,.25); transition: all .2s ease;
    }
    .btn-nouveau:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(227,30,36,.35); }

    /* Tableau */
    .table-caisses thead tr { background: var(--caisses-bg-soft); }
    .table-caisses thead th {
        color: var(--school-red); font-size: .76rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 2px solid var(--caisses-border); padding: .9rem 1rem;
        white-space: nowrap;
    }
    .table-caisses thead th i { color: var(--school-gold-dark); }
    .table-caisses tbody td { padding: .9rem 1rem; border-bottom: 1px solid var(--caisses-border); font-size: .9rem; color: var(--school-ink); vertical-align: middle; }
    .table-caisses tbody tr:hover { background: var(--eim-hover); }
    .table-caisses tbody tr:last-child td { border-bottom: none; }

    .caisse-libelle { font-weight: 700; color: var(--school-ink); display: block; font-size: .94rem; }
    .caisse-id { font-size: .72rem; color: var(--school-muted); }

    .caissier-chip {
        display: inline-flex; align-items: center; gap: 8px;
    }
    .caissier-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(145deg, var(--school-gold-soft), var(--school-gold));
        color: var(--school-red-deep); font-size: .72rem; font-weight: 800;
        display: inline-flex; align-items: center; justify-content: center;
    }

    .solde { font-variant-numeric: tabular-nums; font-weight: 600; }
    .solde.positif { color: var(--eim-success); }
    .solde.negatif { color: var(--school-red); }

    /* Badges statut, alignés sur les couleurs déjà utilisées pour
       "Active" / "Clôturée" dans l'écran Années scolaires. */
    .badge-etat {
        display: inline-flex; align-items: center; gap: 6px; font-size: .75rem;
        font-weight: 600; padding: .4em .85em; border-radius: 999px;
    }
    .badge-etat-ouverte  { background: #e3f7ec; color: var(--eim-success); }
    .badge-etat-cloturee { background: var(--caisses-bg-soft); color: var(--school-muted); }
    .badge-dot { width: 6px; height: 6px; border-radius: 50%; }
    .badge-etat-ouverte  .badge-dot { background: var(--eim-success); }
    .badge-etat-cloturee .badge-dot { background: var(--school-muted); }

    .empty-state { padding: 3.5rem 1rem; text-align: center; color: #7A6A6A; }
    .empty-state i { font-size: 2.5rem; margin-bottom: .75rem; display: block; color: #d8c8ae; }
    .empty-state p { font-size: .9rem; }

    /* Modale */
    .modal-caisses { border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,.15); }
    .modal-header-caisses {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red));
        color: #fff; border-bottom: none; padding: 1.15rem 1.5rem;
    }
    .modal-header-caisses .modal-title { font-weight: 600; font-size: 1.05rem; }

    .form-section { background: var(--caisses-bg-soft); border: 1px solid var(--caisses-border); border-radius: 12px; padding: 1.1rem; }
    .form-section-title {
        font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        color: var(--school-red); margin-bottom: .75rem; display: flex; align-items: center; gap: 6px;
    }
    .form-section .form-label { font-size: .82rem; font-weight: 600; color: var(--school-ink); }
    .form-section .form-control,
    .form-section .form-select {
        border-radius: 8px; border: 1px solid var(--caisses-border); padding: .55rem .75rem; font-size: .88rem;
    }

    .btn-annuler { background: #fff; border: 1px solid var(--caisses-border); color: var(--eim-ink-soft); border-radius: 8px; font-weight: 500; padding: .55rem 1.2rem; font-size: .88rem; }
    .btn-annuler:hover { background: var(--caisses-bg-soft); }
    .btn-enregistrer {
        background: linear-gradient(135deg, var(--school-red-dark), var(--school-red)); color: #fff;
        border: none; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;
    }
</style>
@endsection

@push('css')
<style>
    .onglets-caisses { border-bottom:2px solid var(--caisses-border, var(--eim-border)); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets-caisses .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets-caisses .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets-caisses .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--eim-cream); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--eim-border); }
    .periode-sous { font-size:.72rem; color:var(--school-muted); }
</style>
@endpush

@section('contenu')
<div class="caisses-page">

    {{-- ===== Tableau de bord de l'année choisie (Administrateur et Direction) ===== --}}
    @if ($compteurs)
        <div class="row g-3 mb-4" id="tableau-de-bord-caisses">
            <div class="col-sm-6 col-xl-3">
                <x-stat icone="fas fa-cash-register" id="compteur-caisses">{{ $compteurs['total'] }} caisse(s)
                    <x-slot:libelle>Année {{ $annee?->libelle }}</x-slot:libelle>
                    <x-slot:complement><div class="periode-sous" id="compteur-statuts"><i class="fas fa-lock-open me-1"></i>{{ $compteurs['ouvertes'] }} ouverte(s) · <i class="fas fa-lock me-1"></i>{{ $compteurs['fermees'] }} clôturée(s)</div></x-slot:complement>
                </x-stat>
            </div>
            @foreach (['mois' => ['Ce mois', 'fas fa-calendar-alt', 'blue'], 'semaine' => ['Cette semaine', 'fas fa-calendar-week', 'gold'], 'jour' => ["Aujourd'hui", 'fas fa-calendar-day', 'green']] as $cle => [$libelle, $icone, $couleur])
                @php $p = $compteurs['periodes'][$cle]; @endphp
                <div class="col-sm-6 col-xl-3">
                    <x-stat :icone="$icone" :couleur="$couleur" id="total-{{ $cle }}">{{ $p['ouvertes'] }} ouverte(s) · {{ $p['cloturees'] }} clôturée(s)
                        <x-slot:libelle>Caisses — {{ $libelle }}</x-slot:libelle>
                        <x-slot:complement><div class="periode-sous">
                            @if ($cle === 'jour') {{ $p['debut']->format('d/m/Y') }} @else du {{ $p['debut']->format('d/m') }} au {{ $p['fin']->format('d/m') }} @endif
                        </div></x-slot:complement>
                    </x-stat>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ===== Onglets ===== --}}
    <div class="d-flex align-items-end justify-content-between gap-2 mb-0">
        <ul class="nav onglets-caisses flex-grow-1" id="onglets-caisses">
            @if (in_array('mes', $onglets, true))
                <li class="nav-item">
                    <a class="nav-link @if ($onglet === 'mes') active @endif" id="onglet-mes-caisses" href="{{ route('finances.caisses.index', ['onglet' => 'mes']) }}">
                        <i class="fas fa-user-check me-1"></i> Mes caisses <span class="compte">{{ $nbMesCaisses }}</span>
                    </a>
                </li>
            @endif
            @if (in_array('toutes', $onglets, true))
                <li class="nav-item">
                    <a class="nav-link @if ($onglet === 'toutes') active @endif" id="onglet-toutes-caisses" href="{{ route('finances.caisses.index', ['onglet' => 'toutes']) }}">
                        <i class="fas fa-cash-register me-1"></i> Toutes les caisses <span class="compte">{{ $compteurs['total'] ?? 0 }}</span>
                    </a>
                </li>
            @endif
        </ul>
        @if ($onglets)
            <x-bouton variante="contour" href="{{ route('finances.caisses.exportListe', ['onglet' => $onglet] + request()->except('onglet')) }}" class="btn-sm rounded-pill px-3 mb-2" id="btnExportGlobal">
                <i class="fas fa-file-excel me-1"></i> Exporter
            </x-bouton>
        @endif
    </div>

    @if (! $onglets)
        <x-carte class="mt-3">
            <div class="card-body text-muted small" id="aucun-onglet-caisses">
                <i class="fas fa-info-circle me-1"></i>La liste des caisses est réservée aux caissiers (leurs caisses), à l'Administrateur et à la Direction (toutes les caisses).
            </div>
        </x-carte>
    @else

    {{-- ===== Tableau principal ===== --}}
    <x-carte>
        <div class="card-header bg-white py-3 px-4">
            <form method="GET" class="row g-3 align-items-center" id="filtres-caisses">
                <input type="hidden" name="onglet" value="{{ $onglet }}">
                <div class="col-md-4">
                    <x-recherche name="search" placeholder="Rechercher une caisse..." value="{{ $filters['search'] ?? '' }}" />
                </div>
                <div class="col-md-2">
                    <select name="statut" class="form-select filtre-input">
                        <option value="">— Statut —</option>
                        @foreach ($statuts as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['statut'] ?? '') == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($onglet === 'toutes')
                    <div class="col-md-2">
                        <select name="responsable_id" class="form-select filtre-input" id="filtre-caissier-caisses" title="Caissiers ayant encaissé">
                            <option value="">— Caissier —</option>
                            @foreach ($caissiers as $c)
                                <option value="{{ $c->id }}" @selected(($filters['responsable_id'] ?? '') == $c->id)>
                                    {{ $c->nom }} {{ $c->prenom }} ({{ $c->nb_caisses }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2 text-muted small">
                    <i class="fas fa-calendar-alt me-1"></i>Année {{ $annee?->libelle ?? '—' }}
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill">
                        <i class="fas fa-filter me-1"></i> Filtrer
                    </x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.caisses.index', ['onglet' => $onglet]) }}" title="Réinitialiser">
                        <i class="fas fa-undo"></i>
                    </x-bouton>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <x-tableau class="table-caisses align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4"><i class="fas fa-tag me-2"></i>Caisse</th>
                            <th><i class="fas fa-user me-2"></i>Caissier / période</th>
                            <th class="text-end"><i class="fas fa-coins me-2"></i>Soldes</th>
                            <th class="text-end"><i class="fas fa-balance-scale me-2"></i>Clôture</th>
                            <th class="text-center pe-4" style="width:80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($caisses as $caisse)
                        <tr>
                            {{-- 1. Caisse et statut --}}
                            <td class="ps-4">
                                <span class="caisse-libelle">{{ $caisse->libelle }}</span>
                                <span class="caisse-id">#{{ $caisse->id }}</span>
                                <div class="mt-1">
                                    @if ($caisse->isOuverte())
                                        <span class="badge-etat badge-etat-ouverte"><span class="badge-dot"></span>Ouverte</span>
                                    @else
                                        <span class="badge-etat badge-etat-cloturee"><span class="badge-dot"></span>Clôturée</span>
                                    @endif
                                </div>
                            </td>

                            {{-- 2. Caissier, ouverture et clôture --}}
                            <td>
                                <div class="caissier-chip">
                                    <div class="caissier-avatar">
                                        {{ strtoupper(substr($caisse->caissier?->nom ?? '??', 0, 2)) }}
                                    </div>
                                    <span class="text-sm fw-semibold">
                                        {{ $caisse->caissier?->nom }} {{ $caisse->caissier?->prenom }}
                                    </span>
                                </div>
                                <div class="text-xs text-muted mt-1">
                                    <i class="fas fa-lock-open me-1"></i>{{ optional($caisse->date_ouverture)->format('d/m/Y H:i') ?? '—' }}
                                    @if ($caisse->date_cloture)
                                        <span class="ms-2"><i class="fas fa-lock me-1"></i>{{ $caisse->date_cloture->format('d/m/Y H:i') }}</span>
                                    @endif
                                </div>
                            </td>

                            {{-- 3. Soldes --}}
                            <td class="text-end">
                                <div class="text-xs text-muted">Initial <span class="solde ms-1">{{ number_format($caisse->solde_initial, 2, ',', ' ') }}</span></div>
                                <div class="text-xs text-muted">Théorique <span class="solde ms-1">{{ number_format($caisse->solde_theorique, 2, ',', ' ') }}</span></div>
                            </td>

                            {{-- 4. Clôture : solde compté et écart --}}
                            <td class="text-end">
                                @if ($caisse->isOuverte())
                                    <span class="text-muted text-sm">En cours</span>
                                @else
                                    @if (! is_null($caisse->solde_compte))
                                        <div class="text-xs text-muted">Compté <span class="solde ms-1">{{ number_format($caisse->solde_compte, 2, ',', ' ') }}</span></div>
                                    @endif
                                    @if (! is_null($caisse->ecart) && abs($caisse->ecart) > 0.01)
                                        <span class="solde negatif"><i class="fas fa-exclamation-triangle me-1"></i>Écart {{ number_format($caisse->ecart, 2, ',', ' ') }}</span>
                                    @else
                                        <span class="solde positif"><i class="fas fa-check me-1"></i>Sans écart</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <x-actions>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.caisses.show', $caisse->id) }}">
                                                <i class="fas fa-book"></i> Consulter le journal
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('finances.caisses.export', $caisse->id) }}">
                                                <i class="fas fa-file-excel"></i> Exporter Excel
                                            </a>
                                        </li>
                                        @if ($caisse->isOuverte())
                                            @can('caisses.cloturer')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger btn-cloturer"
                                                            data-id="{{ $caisse->id }}"
                                                            data-libelle="{{ $caisse->libelle }}"
                                                            data-solde="{{ number_format($caisse->solde_theorique, 2, ',', ' ') }}"
                                                            data-bs-toggle="modal" data-bs-target="#clotureModal">
                                                        <i class="fas fa-lock"></i> Clôturer la caisse
                                                    </button>
                                                </li>
                                            @endcan
                                        @endif
                                    </x-actions>
                            </td>
                        </tr>
                    @empty
                        <x-vide colspan="5" icone="fas fa-cash-register" :paragraphe="false"><p class="mb-0 fw-medium">Aucune session de caisse enregistrée.</p>
                                    <small class="text-muted">Commencez par ouvrir une nouvelle caisse.</small></x-vide>
                    @endforelse
                    </tbody>
                </x-tableau>
        </div>
    </x-carte>

    <div class="mt-4">
        <x-pagination :liste="$caisses->appends(['onglet' => $onglet] + request()->except('onglet'))" />
    </div>
    @endif

    {{-- ===== Modale Ouverture ===== --}}
    @can('caisses.ouvrir')
    <x-modale id="caisseModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-caisses" entete-class="modal-header-caisses" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="caisse-form" method="POST" action="{{ route('finances.caisses.store') }}">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-cash-register me-2"></i>Ouvrir une nouvelle caisse</x-slot:titre>
        <x-section titre="Informations générales" icone="fas fa-info-circle" class="mb-3">
            <div class="mb-3">
                <x-libelle requis>Libellé</x-libelle>
                <input type="text" name="libelle" class="form-control" maxlength="100"
                       placeholder="Ex : Caisse principale, Caisse cantine...">
                <div class="text-danger text-xs mt-1" id="libelle-error"></div>
            </div>

            <div class="row g-3">
                <div class="col-7">
                    <x-libelle requis>Responsable de la caisse</x-libelle>
                    @if (auth()->user()->isAdmin())
                        {{-- L'Administrateur peut confier la caisse à quelqu'un d'autre, ou la prendre lui-même --}}
                        <select name="responsable_id" class="form-select">
                            @foreach ($responsables as $r)
                                <option value="{{ $r->id }}" @selected($r->id === auth()->id())>
                                    {{ $r->nom }} {{ $r->prenom }}@if ($r->id === auth()->id()) (moi)@endif
                                </option>
                            @endforeach
                        </select>
                    @else
                        {{-- Chacun ouvre sa propre caisse --}}
                        <div class="form-control bg-light" id="responsable-moi">{{ auth()->user()->nom }} {{ auth()->user()->prenom }} (moi)</div>
                    @endif
                    <div class="text-danger text-xs mt-1" id="responsable_id-error"></div>
                </div>
                <div class="col-5">
                    <x-libelle requis>Solde initial</x-libelle>
                    <input type="number" name="solde_initial" step="0.01" min="0"
                           class="form-control" value="0.00">
                    <div class="text-danger text-xs mt-1" id="solde_initial-error"></div>
                </div>
            </div>
        </x-section>

        <div class="alert alert-light border-0 small mb-0"
             style="background: var(--caisses-bg-soft); color: var(--school-muted);">
            <i class="fas fa-info-circle me-1" style="color: var(--school-gold-dark);"></i>
            Cette action démarre une nouvelle <strong>session</strong> (journal de caisse) sur l'année
            scolaire active.
        </div>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <x-bouton type="submit">
                <i class="fas fa-lock-open me-1"></i> Ouvrir la caisse
            </x-bouton>
        </x-slot:pied>
    </x-modale>
    @endcan

    {{-- ===== Modale Clôture (une seule, réutilisée) ===== --}}
    @can('caisses.cloturer')
    <x-modale id="clotureModal" data-bs-backdrop="static" aria-hidden="true" contenu-class="modal-caisses" entete-style="background: linear-gradient(135deg, var(--school-red-deep), var(--school-red-dark)); color:#fff; border-radius: 16px 16px 0 0;" corps-class="p-4" croix-blanche>
        <x-slot:formulaire id="cloture-form" method="POST" action="">@csrf</x-slot:formulaire>
        <x-slot:titre><i class="fas fa-lock me-2"></i>Clôturer la caisse</x-slot:titre>
        <div class="alert alert-warning py-2 small mb-3">
            <i class="fas fa-exclamation-triangle me-1"></i>
            La clôture est <strong>définitive</strong> : le journal sera figé et aucune écriture ne
            pourra plus y être ajoutée.
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 p-3"
             style="background: var(--caisses-bg-soft); border-radius: 10px;">
            <div>
                <div class="text-muted text-xs">Caisse</div>
                <div class="fw-semibold" id="cloture-libelle">—</div>
            </div>
            <div class="text-end">
                <div class="text-muted text-xs">Solde théorique</div>
                <div class="fw-bold fs-5" id="cloture-solde">—</div>
            </div>
        </div>

        <x-section>
            <div class="mb-3">
                <x-libelle requis>Solde compté (espèces physiquement en caisse)</x-libelle>
                <input type="number" name="solde_compte" step="0.01" min="0"
                       class="form-control" id="cloture-solde-compte">
                <div class="text-danger text-xs mt-1" id="solde_compte-error"></div>
            </div>

            <div id="ecart-bloc" class="d-none">
                <div class="d-flex justify-content-between align-items-center p-2 rounded mb-2"
                     style="background:#fdf2f2; border-left:3px solid var(--school-red);">
                    <span class="fw-semibold" style="color: var(--school-red);">Écart constaté</span>
                    <span class="fw-bold" style="color: var(--school-red);" id="ecart-valeur">0,00</span>
                </div>
                <x-libelle requis>Motif de l'écart</x-libelle>
                <textarea name="motif_ecart" rows="2" class="form-control" id="motif_ecart"></textarea>
                <div class="text-danger text-xs mt-1" id="motif_ecart-error"></div>
            </div>
        </x-section>
        <x-slot:pied class="bg-light px-4 py-3">
            <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
            <button type="submit" class="btn"
                    style="background: var(--school-red-dark); color: #fff; border-radius: 8px; font-weight: 600; padding: .55rem 1.4rem; font-size: .88rem;">
                <i class="fas fa-check me-1"></i> Clôturer définitivement
            </button>
        </x-slot:pied>
    </x-modale>
    @endcan

</div>
@endsection

@push('js')
<script>
$(function () {
    // ============ OUVERTURE ============
    const $openForm = $('#caisse-form');
    if ($openForm.length) {
        $openForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($openForm);

            $.ajax({
                url:  $openForm.attr('action'),
                type: 'POST',
                data: $openForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#caisseModal').modal('hide');
                    window.showToastThenReload(res.message || 'Caisse ouverte.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors($openForm, xhr.responseJSON.errors);
                    } else {
                        // Refus métier (ex. « ce caissier a déjà une caisse ouverte ») : on affiche le motif
                        window.showToast(xhr.responseJSON?.message || 'Une erreur est survenue.', 'error');
                    }
                }
            });
        });
    }

    // ============ CLÔTURE ============
    const $clotureModal = $('#clotureModal');
    if ($clotureModal.length) {
        const $clotureForm = $('#cloture-form');

        $('.btn-cloturer').on('click', function () {
            const id      = $(this).data('id');
            const libelle = $(this).data('libelle');
            const solde   = $(this).data('solde');

            $('#cloture-libelle').text(libelle);
            $('#cloture-solde').text(solde);
            $('#cloture-solde-compte').val('').data('theorique', parseFloat(solde.replace(/\s/g, '').replace(',', '.')));
            $('#ecart-bloc').addClass('d-none');
            clearErrors($clotureForm);

            $clotureForm.attr('action', '{{ url("finances/caisses") }}/' + id + '/cloturer');
        });

        $('#cloture-solde-compte').on('input', function () {
            const theorique = parseFloat($(this).data('theorique') || 0);
            const compte    = parseFloat($(this).val() || 0);
            const ecart     = +(compte - theorique).toFixed(2);

            if (Math.abs(ecart) > 0.01) {
                $('#ecart-bloc').removeClass('d-none');
                $('#ecart-valeur').text(ecart.toLocaleString('fr-FR', {
                    minimumFractionDigits: 2, maximumFractionDigits: 2
                }));
                $('#motif_ecart').prop('required', true);
            } else {
                $('#ecart-bloc').addClass('d-none');
                $('#motif_ecart').prop('required', false);
            }
        });

        $clotureForm.on('submit', function (e) {
            e.preventDefault();
            clearErrors($clotureForm);

            $.ajax({
                url:  $clotureForm.attr('action'),
                type: 'POST',
                data: $clotureForm.serialize(),
                headers: { 'Accept': 'application/json' },
                success: function (res) {
                    $('#clotureModal').modal('hide');
                    window.showToastThenReload(res.message || 'Caisse clôturée.', 'success');
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showErrors($clotureForm, xhr.responseJSON.errors);
                    } else {
                        window.showToast("Une erreur est survenue.", 'error');
                    }
                }
            });
        });
    }

    // ============ Utilitaires ============
    function clearErrors($form) {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('[id$="-error"]').text('');
    }

    function showErrors($form, errors) {
        Object.keys(errors).forEach(function (field) {
            $form.find('[name="' + field + '"]').addClass('is-invalid');
            $('#' + field + '-error').text(errors[field][0]);
        });
    }
});
</script>
@endpush