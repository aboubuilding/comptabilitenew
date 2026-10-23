@extends('admin.layouts.app')

@section('title', 'Encaissements')
@section('page_icon', 'fa-cash-register')
@section('page_title', 'Encaissements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item">Finances &amp; Comptabilité</li>
    <li class="breadcrumb-item active">Encaissements</li>
@endsection

@section('page_actions')
    @if ($caisseOuverte)
        <span class="badge-etat badge-etat-ouverte" id="caisse-ouverte" style="padding:.5em 1em;">
            <span class="badge-dot"></span> Caisse « {{ $caisseOuverte->libelle }} » ouverte
        </span>
    @elseif (auth()->user()->can('encaissements.creer'))
        <span class="badge-etat badge-etat-fermee" id="caisse-fermee" style="padding:.5em 1em;">
            <span class="badge-dot"></span> Aucune caisse ouverte
        </span>
    @endif
@endsection

@section('css')
<style>
    .enc-page { --en-soft:var(--eim-cream); --en-border:var(--eim-border); }
    .text-xs { font-size:.72rem; }

    .stat-card { background:#fff; border:1px solid var(--en-border); border-radius:12px; padding:1rem 1.15rem; display:flex; align-items:center; gap:.9rem; box-shadow:0 2px 8px rgba(142,18,22,.04); height:100%; }
    .stat-value { font-size:1.15rem; font-weight:700; color:var(--school-ink); line-height:1.2; }
    .stat-label { font-size:.75rem; color:var(--school-muted); }

    .badge-etat { display:inline-flex; align-items:center; gap:6px; border-radius:999px; font-size:.8rem; font-weight:600; }
    .badge-etat-ouverte { background:#e3f7ec; color:var(--eim-success); }
    .badge-etat-fermee  { background:#fdecef; color:var(--school-red); }

    .onglets { border-bottom:2px solid var(--en-border); gap:.25rem; flex-wrap:nowrap; overflow-x:auto; }
    .onglets .nav-link { border:none; border-bottom:3px solid transparent; color:var(--school-muted); font-weight:600; font-size:.9rem; padding:.75rem 1rem; white-space:nowrap; margin-bottom:-2px; background:none; }
    .onglets .nav-link.active { color:var(--school-red); border-bottom-color:var(--school-red); }
    .onglets .compte { display:inline-block; min-width:22px; padding:0 .45rem; border-radius:999px; background:var(--en-soft); color:var(--school-red); font-size:.72rem; margin-left:.35rem; border:1px solid var(--en-border); }

    .main-card { border:none; border-radius:16px; box-shadow:0 10px 30px rgba(142,18,22,.06); background:#fff; overflow:hidden; }
    .barre-outils { padding:1rem 1.25rem; border-bottom:1px solid var(--en-border); display:flex; gap:.5rem; flex-wrap:wrap; align-items:center; }
    .filtre-input { border-radius:10px; border:1px solid var(--en-border); padding:.5rem .85rem; font-size:.86rem; }
    .btn-filtrer { background:var(--school-red); color:#fff; border:none; border-radius:10px; padding:.5rem 1rem; font-weight:600; }

    .table-enc thead tr { background:var(--en-soft); }
    .table-enc thead th { color:var(--school-red); font-size:.74rem; font-weight:700; text-transform:uppercase; padding:.85rem 1rem; border-bottom:2px solid var(--en-border); white-space:nowrap; }
    .table-enc tbody td { padding:.8rem 1rem; border-bottom:1px solid var(--en-border); font-size:.88rem; vertical-align:middle; }
    .table-enc tbody tr:hover { background:var(--eim-hover); }
    .table-enc td.col-eleve, .table-enc td.col-detail { max-width:230px; }
    .table-enc td.col-eleve > div, .table-enc td.col-detail > div { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .table-enc td.col-montant, .table-enc td.col-ref { white-space:nowrap; }
    .table-enc tr.ligne-annee td { background:var(--en-soft); color:var(--school-red); font-weight:700; font-size:.82rem; padding:.55rem 1rem; }
    .table-enc tfoot td { padding:.8rem 1rem; font-weight:700; background:var(--eim-hover); }
    .cell-sub { font-size:.74rem; color:var(--school-muted); line-height:1.35; }
    .natures { font-size:.78rem; font-weight:600; color:var(--school-red); }
    .reference { font-family:monospace; font-size:.8rem; background:var(--en-soft); color:var(--school-red); padding:2px 8px; border-radius:6px; font-weight:700; }
    .num { font-variant-numeric:tabular-nums; font-weight:700; }
    .nature { display:inline-block; background:var(--en-soft); color:var(--school-red); font-weight:600; font-size:.66rem; padding:.12rem .5rem; border-radius:999px; border:1px solid var(--en-border); }

    .badge-statut { display:inline-flex; align-items:center; gap:6px; padding:.3em .8em; border-radius:999px; font-size:.7rem; font-weight:600; }

    .btn-encaisser { background:linear-gradient(135deg,var(--eim-success),#25a065); color:#fff; border:none; border-radius:8px; padding:.4rem .8rem; font-size:.8rem; font-weight:600; white-space:nowrap; }
    .btn-encaisser:hover { color:#fff; filter:brightness(1.05); }
    .btn-imprimer { border:1px solid var(--en-border); background:#fff; color:var(--school-ink); border-radius:8px; padding:.35rem .7rem; font-size:.8rem; white-space:nowrap; text-decoration:none; }
    .btn-imprimer:hover { border-color:var(--school-red); color:var(--school-red); }

    .empty-state i { font-size:2.2rem; margin-bottom:.6rem; display:block; color:#d8c8ae; }

    .modal-enc .modal-header { background:linear-gradient(135deg,var(--school-red-dark),var(--school-red)); color:#fff; }
    .modal-enc .modal-title { font-size:1rem; font-weight:700; }
    .resume-bloc { background:var(--en-soft); border:1px solid var(--en-border); border-radius:10px; padding:.55rem .8rem; height:100%; }
    .resume-titre { font-size:.68rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .resume-lignes thead th { font-size:.7rem; text-transform:uppercase; color:var(--school-red); background:var(--en-soft); }
    .resume-totaux { display:flex; flex-direction:column; gap:.25rem; align-items:flex-end; font-size:.88rem; }
    .resume-totaux > div { display:flex; gap:1rem; }
    .resume-totaux .a-encaisser strong { color:var(--school-red); font-size:1.05rem; }
    .saisie-montant { background:#f2fbf6; border:1px solid #bfe6d0; border-radius:12px; padding:.9rem 1rem; }
    .saisie-montant .monnaie { font-weight:700; color:var(--eim-success); }
</style>
@endsection

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $natures = fn (?string $codes) => collect(explode(',', (string) $codes))
        ->map(fn ($c) => \App\Domain\Finances\Types\TypeDetail::tryFrom((int) $c)?->label())->filter()->implode(', ');
    $peutEncaisser = auth()->user()->can('encaissements.creer');

    // Filtre « Année » des paiements en attente : toutes les années qui en ont, plus l'année choisie
    $anneesAttente = $attenteParAnnee->keyBy('annee_id');
    if (! $anneesAttente->has($annee->id)) {
        $anneesAttente->put($annee->id, (object) ['annee_id' => $annee->id, 'libelle' => $annee->libelle, 'nb' => 0, 'montant' => 0]);
    }
    $anneesAttente = $anneesAttente->sortByDesc('libelle');
    $libelleAnneeAttente = $anneeAttente ? ($anneesAttente->get($anneeAttente)?->libelle ?? '—') : 'toutes les années';
@endphp
<div class="enc-page">

    <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="annee-encaissements">
        <span class="badge-etat badge-etat-ouverte" style="padding:.45em .9em;">
            <i class="fas fa-calendar-alt"></i> Année scolaire : {{ $annee->libelle }}
        </span>
    </div>

    {{-- Chiffres clés --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-hourglass-half" couleur="gold" valeur-id="compteur-attente">{{ $fmt($compteurAttente['montant']) }} F<x-slot:libelle>En attente d'encaissement ({{ $libelleAnneeAttente }}) — <span id="nb-attente">{{ $compteurAttente['nb'] }}</span> paiement(s)</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-6 col-lg-4">
            <x-stat icone="fas fa-coins" couleur="green" valeur-id="compteur-jour">{{ $fmt($totalJour) }} F<x-slot:libelle>Mes encaissements du jour — <span id="nb-jour">{{ $duJour->count() }}</span> encaissement(s)</x-slot:libelle></x-stat>
        </div>
        <div class="col-sm-12 col-lg-4">
            <x-stat icone="fas fa-cash-register" couleur="red">{{ $caisseOuverte?->libelle ?? 'Aucune caisse ouverte' }}<x-slot:libelle>{{ $caisseOuverte ? 'Ouverte le ' . \Illuminate\Support\Carbon::parse($caisseOuverte->date_ouverture)->format('d/m/Y à H:i') : 'Ma caisse' }}</x-slot:libelle></x-stat>
        </div>
    </div>

    <x-carte>
        <ul class="nav onglets px-3 pt-2" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link @if ($onglet === 'tous') active @endif" id="onglet-tous" data-bs-toggle="tab" data-bs-target="#panneau-tous" type="button" role="tab" data-onglet="tous">
                    <i class="fas fa-list me-1"></i> Tous les encaissements
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link @if ($onglet === 'attente') active @endif" id="onglet-attente" data-bs-toggle="tab" data-bs-target="#panneau-attente" type="button" role="tab" data-onglet="attente">
                    <i class="fas fa-hourglass-half me-1"></i> Paiements en attente <span class="compte">{{ $compteurAttente['nb'] }}</span>
                </button>
            </li>
            @if ($mesEncaissements)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($onglet === 'mes') active @endif" id="onglet-mes" data-bs-toggle="tab" data-bs-target="#panneau-mes" type="button" role="tab" data-onglet="mes">
                        <i class="fas fa-user-check me-1"></i> Mes encaissements <span class="compte">{{ $totalMes['nb'] }}</span>
                    </button>
                </li>
            @endif
            @if ($encaissementsCaisse)
                <li class="nav-item" role="presentation">
                    <button class="nav-link @if ($onglet === 'caisse') active @endif" id="onglet-caisse" data-bs-toggle="tab" data-bs-target="#panneau-caisse" type="button" role="tab" data-onglet="caisse">
                        <i class="fas fa-cash-register me-1"></i> Caisse ouverte <span class="compte">{{ $totalCaisse['nb'] }}</span>
                    </button>
                </li>
            @endif
        </ul>

        <div class="tab-content">

            {{-- ==================== Tous les encaissements ==================== --}}
            <div class="tab-pane fade @if ($onglet === 'tous') show active @endif" id="panneau-tous" role="tabpanel">
                <form method="GET" class="barre-outils">
                    <input type="hidden" name="onglet" value="tous">
                    <input type="text" name="search" class="form-control filtre-input" style="max-width:260px;" placeholder="Élève, payeur, référence..." value="{{ $filtres['search'] ?? '' }}">
                    <select name="caisse_id" class="form-select filtre-input" style="max-width:200px;">
                        <option value="">— Toutes les caisses —</option>
                        @foreach ($caisses as $c)
                            <option value="{{ $c->id }}" @selected(($filtres['caisse_id'] ?? '') == $c->id)>{{ $c->libelle }}</option>
                        @endforeach
                    </select>
                    <select name="caissier_id" id="filtre-caissier" class="form-select filtre-input" style="max-width:220px;" title="Caissier">
                        <option value="">— Tous les caissiers —</option>
                        @foreach ($caissiers as $u)
                            <option value="{{ $u->id }}" @selected(($filtres['caissier_id'] ?? '') == $u->id)>{{ $u->nom }} ({{ $u->nb }})</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_debut" class="form-control filtre-input" style="max-width:160px;" value="{{ $filtres['date_debut'] ?? '' }}" title="Du">
                    <input type="date" name="date_fin" class="form-control filtre-input" style="max-width:160px;" value="{{ $filtres['date_fin'] ?? '' }}" title="Au">
                    <x-bouton variante="filtrer" type="submit" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.encaissements.index', ['onglet' => 'tous']) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </form>
                <x-tableau class="table-enc align-middle mb-0" id="table-tous">
                        <thead>
                            <tr>
                                <th class="ps-4">Encaissement</th>
                                <th>Élève / Payeur</th>
                                <th>Caisse / Caissier</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($encaissements as $e)
                            @include('domain.finances.encaissements.partials.ligne-encaissement', ['e' => $e, 'avecCaisse' => true])
                        @empty
                            <x-vide colspan="5" icone="fas fa-receipt" :paragraphe="false">Aucun encaissement.</x-vide>
                        @endforelse
                        </tbody>
                    </x-tableau>
                <div class="p-3"><x-pagination :liste="$encaissements->appends(['onglet' => 'tous'])" /></div>
            </div>

            {{-- ==================== Paiements en attente ==================== --}}
            <div class="tab-pane fade @if ($onglet === 'attente') show active @endif" id="panneau-attente" role="tabpanel">
                <form method="GET" class="barre-outils">
                    <input type="hidden" name="onglet" value="attente">
                    <input type="text" name="recherche_attente" class="form-control filtre-input" style="max-width:300px;" placeholder="Élève, matricule, payeur, référence..." value="{{ $recherche['search'] ?? '' }}">
                    <select name="annee_attente" id="filtre-annee-attente" class="form-select filtre-input" style="max-width:240px;" title="Année scolaire" onchange="this.form.submit()">
                        <option value="toutes" @selected(! $anneeAttente)>Toutes les années ({{ $attenteParAnnee->sum('nb') }})</option>
                        @foreach ($anneesAttente as $a)
                            <option value="{{ $a->annee_id }}" @selected($anneeAttente === $a->annee_id)>{{ $a->libelle ?? '—' }} ({{ $a->nb }})</option>
                        @endforeach
                    </select>
                    <x-bouton variante="filtrer" type="submit"><i class="fas fa-search"></i></x-bouton>
                    @if ($peutEncaisser && ! $caisseOuverte)
                        <span class="text-danger small ms-auto"><i class="fas fa-lock me-1"></i>Ouvrez votre caisse pour pouvoir encaisser.</span>
                    @endif
                </form>
                <x-tableau class="table-enc align-middle mb-0" id="table-attente">
                        <thead>
                            <tr>
                                <th class="ps-4">Paiement</th>
                                <th>Élève / Payeur</th>
                                <th>Détail</th>
                                <th class="text-end">À encaisser</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($enAttente as $p)
                            @php
                                $eleve = $p->inscription?->eleve;
                                $listeNatures = $p->details->map(fn ($d) => $d->type_paiement?->label())->filter()->unique()->implode(', ');
                                $libelleAnnee = $anneesAttente->get($p->annee_id)?->libelle ?? '—';
                                // L'encaissement ne porte que sur l'année choisie dans l'en-tête
                                $horsAnnee = (int) $p->annee_id !== (int) $annee->id;
                                $titreEncaisser = $horsAnnee
                                    ? "Choisissez l'année {$libelleAnnee} en haut de page pour encaisser ce paiement"
                                    : ($caisseOuverte ? 'Encaisser ce paiement' : 'Ouvrez votre caisse pour encaisser');
                            @endphp
                            @if (! $anneeAttente && ($loop->first || $p->annee_id !== $enAttente[$loop->index - 1]->annee_id))
                                <tr class="ligne-annee" data-annee="{{ $p->annee_id }}">
                                    <td colspan="5" class="ps-4">
                                        <i class="fas fa-calendar-alt me-1"></i> Année scolaire {{ $libelleAnnee }}
                                        <span class="cell-sub ms-2">{{ $anneesAttente->get($p->annee_id)?->nb }} paiement(s) · {{ $fmt($anneesAttente->get($p->annee_id)?->montant) }} F</span>
                                    </td>
                                </tr>
                            @endif
                            <tr data-paiement="{{ $p->id }}" data-annee="{{ $p->annee_id }}">
                                <td class="ps-4 col-ref">
                                    <span class="reference">{{ $p->reference }}</span>
                                    <div class="cell-sub mt-1"><i class="far fa-calendar me-1"></i>{{ optional($p->date_paiement)->format('d/m/Y') }}</div>
                                    <div class="cell-sub"><i class="far fa-user me-1"></i>{{ $p->comptable?->nom_complet ?: '—' }}</div>
                                    <div class="mt-1"><span class="nature annee-paiement">{{ $libelleAnnee }}</span></div>
                                </td>
                                <td class="col-eleve">
                                    <div class="fw-semibold">{{ $eleve ? $eleve->nom . ' ' . $eleve->prenom : $p->payeur }}</div>
                                    <div class="cell-sub niveau-classe">{{ $eleve->matricule ?? '' }}@if ($p->inscription?->niveau) · {{ $p->inscription->niveau->libelle }}@endif @if ($p->inscription?->classe) · {{ $p->inscription->classe->libelle }}@endif</div>
                                    <div class="cell-sub">Payé par {{ $p->payeur }}@if ($p->telephone_payeur) · {{ $p->telephone_payeur }}@endif</div>
                                </td>
                                <td class="col-detail">
                                    <div class="natures" title="{{ $listeNatures }}">{{ $listeNatures ?: '—' }}</div>
                                    <div class="cell-sub">
                                        {{ $p->details->count() }} ligne(s)
                                        @if ($p->mode_paiement) · <i class="fas {{ $p->mode_paiement->icone() }}"></i> {{ $p->mode_paiement->label() }}@endif
                                        @if ($p->cheque) n° {{ $p->cheque->numero }}@endif
                                    </div>
                                </td>
                                <td class="text-end col-montant">
                                    <div class="num">{{ $fmt($p->details->sum('montant')) }} F</div>
                                    <x-badge class="attente mt-1" point>En attente</x-badge>
                                </td>
                                <td class="text-center pe-4 text-nowrap">
                                    @if ($peutEncaisser)
                                        <button type="button" class="btn-encaisser btn-encaisser-action" data-paiement="{{ $p->id }}" @disabled(! $caisseOuverte || $horsAnnee)
                                                title="{{ $titreEncaisser }}">
                                            <i class="fas fa-hand-holding-usd me-1"></i>Encaisser
                                        </button>
                                    @endif
                                    <x-actions class="d-inline-block">
                                            <li><a href="#" role="button" class="dropdown-item btn-detail" data-paiement="{{ $p->id }}"><i class="fas fa-eye"></i> Détail</a></li>
                                            @can('paiements-en-attente.annuler')
                                                <li><button type="button" class="dropdown-item text-danger btn-annuler-paiement" data-paiement="{{ $p->id }}" data-reference="{{ $p->reference }}"><i class="fas fa-times"></i> Annuler le paiement</button></li>
                                            @endcan
                                        </x-actions>
                                </td>
                            </tr>
                        @empty
                            <x-vide colspan="5" icone="fas fa-check-circle" :paragraphe="false">Aucun paiement en attente d'encaissement.</x-vide>
                        @endforelse
                        </tbody>
                    </x-tableau>
                <div class="p-3"><x-pagination :liste="$enAttente->appends(['onglet' => 'attente'])" /></div>
            </div>

            {{-- ==================== Mes encaissements (tout caissier) ==================== --}}
            @if ($mesEncaissements)
            <div class="tab-pane fade @if ($onglet === 'mes') show active @endif" id="panneau-mes" role="tabpanel">
                <form method="GET" class="barre-outils" id="filtres-mes">
                    <input type="hidden" name="onglet" value="mes">
                    <input type="text" name="recherche_mes" class="form-control filtre-input" style="max-width:260px;" placeholder="Élève, payeur, référence..." value="{{ $filtresMes['search'] ?? '' }}">
                    <input type="date" name="mes_debut" class="form-control filtre-input" style="max-width:160px;" value="{{ $filtresMes['date_debut'] ?? '' }}" title="Du">
                    <input type="date" name="mes_fin" class="form-control filtre-input" style="max-width:160px;" value="{{ $filtresMes['date_fin'] ?? '' }}" title="Au">
                    <x-bouton variante="filtrer" type="submit" title="Filtrer"><i class="fas fa-filter"></i></x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.encaissements.index', ['onglet' => 'mes']) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                    <span class="small text-muted ms-auto">Vos encaissements de l'année {{ $annee->libelle }}</span>
                </form>
                <x-tableau class="table-enc align-middle mb-0" id="table-mes">
                        <thead>
                            <tr>
                                <th class="ps-4">Encaissement</th>
                                <th>Élève / Payeur</th>
                                <th>Caisse / Caissier</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($mesEncaissements as $e)
                            @include('domain.finances.encaissements.partials.ligne-encaissement', ['e' => $e, 'avecCaisse' => true])
                        @empty
                            <x-vide colspan="5" icone="fas fa-coins" :paragraphe="false">Aucun encaissement.</x-vide>
                        @endforelse
                        </tbody>
                        @if ($totalMes['nb'] > 0)
                            <tfoot>
                                <tr><td class="ps-4" colspan="3">Total ({{ $totalMes['nb'] }} encaissement(s))</td><td class="text-end" id="total-mes">{{ $fmt($totalMes['montant']) }} F</td><td></td></tr>
                            </tfoot>
                        @endif
                    </x-tableau>
                <div class="p-3"><x-pagination :liste="$mesEncaissements->appends(['onglet' => 'mes'])" /></div>
            </div>
            @endif

            {{-- ==================== Encaissements de ma caisse ouverte ==================== --}}
            @if ($encaissementsCaisse)
            <div class="tab-pane fade @if ($onglet === 'caisse') show active @endif" id="panneau-caisse" role="tabpanel">
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
                        <x-bouton variante="nouveau" href="{{ route('finances.encaissements.nouveau') }}" id="btn-nouvel-encaissement">
                            <i class="fas fa-plus-circle me-1"></i> Nouvel encaissement
                        </x-bouton>
                    </div>
                </div>
                <x-tableau class="table-enc align-middle mb-0" id="table-caisse">
                        <thead>
                            <tr>
                                <th class="ps-4">Encaissement</th>
                                <th>Élève / Payeur</th>
                                <th>Caisse / Caissier</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($encaissementsCaisse as $e)
                            @include('domain.finances.encaissements.partials.ligne-encaissement', ['e' => $e, 'avecCaisse' => true, 'annulable' => true])
                        @empty
                            <x-vide colspan="5" icone="fas fa-coins" :paragraphe="false">Aucun encaissement dans cette caisse pour l'instant.</x-vide>
                        @endforelse
                        </tbody>
                        @if ($totalCaisse['nb'] > 0)
                            <tfoot>
                                <tr><td class="ps-4" colspan="3">Total encaissé dans la caisse</td><td class="text-end" id="total-caisse">{{ $fmt($totalCaisse['montant']) }} F</td><td></td></tr>
                            </tfoot>
                        @endif
                    </x-tableau>
                <div class="p-3"><x-pagination :liste="$encaissementsCaisse->appends(['onglet' => 'caisse'])" /></div>
            </div>
            @endif
        </div>
    </x-carte>
</div>

{{-- ==================== Fenêtre d'encaissement ==================== --}}
@if ($peutEncaisser)
<x-modale class="modal-enc" id="encaisserModal" aria-labelledby="titre-encaisser" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" croix-blanche>
    <x-slot:titre id="titre-encaisser"><i class="fas fa-hand-holding-usd me-2"></i><span>Encaisser le paiement</span></x-slot:titre>
    @include('domain.finances.encaissements.partials.saisie-encaissement')
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Annuler</x-bouton>
        <x-bouton type="button" id="enc-valider" disabled><i class="fas fa-check me-1"></i>Encaisser</x-bouton>
    </x-slot:pied>
</x-modale>
@endif

{{-- ==================== Détail d'un paiement / encaissement ==================== --}}
<x-modale class="modal-enc" id="detailModal" aria-labelledby="titre-detail" aria-hidden="true" dialogue="modal-dialog-centered modal-lg" corps-id="detail-contenu" croix-blanche>
    <x-slot:titre id="titre-detail"><i class="fas fa-eye me-2"></i>Détail</x-slot:titre>
    <x-slot:pied>
        <a href="#" target="_blank" class="btn-imprimer" id="detail-recu" style="display:none;"><i class="fas fa-print me-1"></i>Imprimer le reçu</a>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
    </x-slot:pied>
</x-modale>

{{-- ==================== Annulation d'un encaissement ==================== --}}
@can('encaissements.annuler')
<x-modale class="modal-enc" id="annulerModal" aria-hidden="true" form-contenu croix-blanche>
    <x-slot:formulaire id="annuler-form" method="POST">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-undo me-2"></i>Annuler l'encaissement <span id="annuler-reference"></span></x-slot:titre>
    <p class="small text-muted">Le paiement retourne dans les paiements en attente. Possible tant que la caisse est ouverte.</p>
    <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
    <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
        <button type="submit" class="btn btn-danger"><i class="fas fa-undo me-1"></i>Annuler l'encaissement</button>
    </x-slot:pied>
</x-modale>
@endcan

{{-- ==================== Annulation d'un paiement en attente (Administrateur) ==================== --}}
@can('paiements-en-attente.annuler')
<x-modale class="modal-enc" id="annulerPaiementModal" aria-hidden="true" form-contenu croix-blanche>
    <x-slot:formulaire id="annuler-paiement-form" method="POST">@csrf</x-slot:formulaire>
    <x-slot:titre><i class="fas fa-times me-2"></i>Annuler le paiement <span id="annuler-paiement-reference"></span></x-slot:titre>
    <p class="small text-muted">Le paiement ne sera plus proposé à l'encaissement. Cette action est définitive.</p>
    <x-libelle class="small fw-semibold" requis>Motif</x-libelle>
    <textarea name="motif" rows="3" class="form-control" required minlength="5"></textarea>
    <x-slot:pied>
        <x-bouton variante="annuler" type="button" data-bs-dismiss="modal">Fermer</x-bouton>
        <button type="submit" class="btn btn-danger"><i class="fas fa-times me-1"></i>Annuler le paiement</button>
    </x-slot:pied>
</x-modale>
@endcan
@endsection

@push('js')
@include('domain.finances.encaissements.partials.script-encaissement')
<script>
$(function () {
    const URL_RESUME = '{{ url('finances/encaissements/paiement') }}';
    const URL_LISTE  = '{{ route('finances.encaissements.index') }}';
    const fmt = v => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(v);
    const erreur = xhr => {
        const erreurs = xhr.responseJSON?.errors;
        return erreurs ? Object.values(erreurs)[0][0] : (xhr.responseJSON?.message || 'Une erreur est survenue.');
    };
    const revenirSur = (onglet, message) => {
        sessionStorage.setItem('pendingToast', JSON.stringify({ type: 'success', message }));
        // Le filtre « Année » des paiements en attente est conservé au retour
        const anneeAttente = new URL(window.location.href).searchParams.get('annee_attente');
        window.location.href = URL_LISTE + '?onglet=' + onglet + (anneeAttente ? '&annee_attente=' + encodeURIComponent(anneeAttente) : '');
    };

    // L'onglet choisi est gardé dans l'adresse (retour après une action, pagination)
    $('.onglets [data-bs-toggle=tab]').on('shown.bs.tab', function () {
        const url = new URL(window.location.href);
        url.searchParams.set('onglet', $(this).data('onglet'));
        history.replaceState(null, '', url);
    });

    /* ---------------- Détail d'un paiement / encaissement ---------------- */
    $(document).on('click', '.btn-detail', function (e) {
        e.preventDefault();
        const id = $(this).data('paiement');
        const recu = $(this).data('recu');
        $('#detail-contenu').html('<div class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i></div>');
        $('#detail-recu').toggle(!!recu).attr('href', recu || '#');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('detailModal')).show();
        $.get(URL_RESUME + '/' + id + '/resume')
            .done(html => $('#detail-contenu').html(html))
            .fail(xhr => $('#detail-contenu').html('<div class="text-danger">' + erreur(xhr) + '</div>'));
    });

    /* ---------------- Encaisser (fenêtre d'une ligne en attente) ---------------- */
    const $modal = $('#encaisserModal');
    if ($modal.length) {
        const modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
        const saisie = window.brancherEncaissement({
            $racine: $modal,
            $valider: $('#enc-valider'),
            onSucces: message => { modal.hide(); revenirSur('caisse', message); },
        });

        $(document).on('click', '.btn-encaisser-action', function () {
            modal.show();
            saisie.charger($(this).data('paiement'));
        });
    }
    /* ---------------- Annulations (fenêtres avec motif) ---------------- */
    function brancherAnnulation(selecteur, $form, $libelle, modalId, action, onglet) {
        $(document).on('click', selecteur, function () {
            $form[0].reset();
            $form.attr('action', URL_RESUME + '/' + $(this).data('paiement') + '/' + action);
            $libelle.text($(this).data('reference'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId)).show();
        });
        $form.on('submit', function (e) {
            e.preventDefault();
            if (!this.checkValidity()) { this.reportValidity(); return; }
            const $btn = $form.find('[type=submit]').prop('disabled', true);
            $.ajax({ url: $form.attr('action'), method: 'POST', data: $form.serialize(), headers: { 'Accept': 'application/json' } })
                .done(res => revenirSur(onglet, res.message || 'Opération effectuée.'))
                .fail(xhr => { window.showToast(erreur(xhr), 'error'); $btn.prop('disabled', false); });
        });
    }
    if ($('#annuler-form').length) {
        brancherAnnulation('.btn-annuler-encaissement', $('#annuler-form'), $('#annuler-reference'), 'annulerModal', 'annuler-encaissement', 'attente');
    }
    if ($('#annuler-paiement-form').length) {
        brancherAnnulation('.btn-annuler-paiement', $('#annuler-paiement-form'), $('#annuler-paiement-reference'), 'annulerPaiementModal', 'annuler', 'attente');
    }
});
</script>
@endpush
