{{-- Élèves à jour et en retard à chaque échéance d'un plan, sur un intervalle de dates --}}
@extends('admin.layouts.app')

@section('title', 'Suivi des élèves — ' . $plan->nom)
@section('page_icon', 'fa-user-check')
@section('page_title', 'Élèves à jour / en retard')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.plan-echeanciers.index') }}">Plans d'échéancier</a></li>
    <li class="breadcrumb-item active">{{ $plan->nom }}</li>
@endsection

@section('page_actions')
    {{-- Les exports reprennent l'intervalle de dates et le filtre affichés --}}
    @php $requete = array_filter(['du' => $du, 'au' => $au, 'statut' => $statut], fn ($v) => $v !== null && $v !== ''); @endphp
    <div class="dropdown">
        <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3 dropdown-toggle" id="btn-exporter" data-bs-toggle="dropdown">
            <i class="fas fa-file-export me-1"></i> Exporter
        </x-bouton>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" id="export-excel" href="{{ route('finances.plan-echeanciers.eleves.export', ['plan' => $plan->id] + $requete) }}"><i class="fas fa-file-excel me-2 text-success"></i>Excel</a></li>
            <li><a class="dropdown-item" id="export-pdf" href="{{ route('finances.plan-echeanciers.eleves.exportPdf', ['plan' => $plan->id] + $requete) }}"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF</a></li>
        </ul>
    </div>
    <x-bouton variante="contour" href="{{ route('finances.plan-echeanciers.index') }}" class="btn-sm rounded-pill px-3">
        <i class="fas fa-arrow-left me-1"></i> Plans d'échéancier
    </x-bouton>
@endsection

@section('css')
<style>
    .text-xs { font-size:.72rem; }
    .pastille { display:inline-block; background:var(--eim-cream); color:var(--school-red); font-weight:600; font-size:.8rem; padding:.35rem .8rem; border-radius:999px; border:1px solid var(--eim-border); }
    .filtre-label { font-size:.7rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .echeance-entete { display:flex; flex-wrap:wrap; align-items:center; gap:.75rem 1.5rem; }
    .echeance-entete h6 { margin:0; font-weight:700; }
    .echeance-chiffres { display:flex; flex-wrap:wrap; gap:.4rem 1.1rem; font-size:.82rem; margin-left:auto; }
    .jauge { height:6px; border-radius:999px; background:#f1ebdf; overflow:hidden; }
    .jauge > span { display:block; height:100%; background:var(--eim-success); border-radius:999px; }
    .table-eleves thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; background:var(--eim-cream); padding:.6rem .9rem; border-bottom:2px solid var(--eim-border); white-space:nowrap; }
    .table-eleves tbody td { padding:.55rem .9rem; border-bottom:1px solid var(--eim-border); font-size:.86rem; vertical-align:middle; }
    .num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .groupe-titre { font-size:.76rem; font-weight:700; text-transform:uppercase; padding:.55rem .9rem; }
    .groupe-titre.retard { background:#fdecea; color:var(--school-red); }
    .groupe-titre.ok { background:#e3f7ec; color:var(--eim-success); }
    .niveau-pastille { display:inline-block; font-size:.66rem; font-weight:700; padding:.08rem .5rem; border-radius:999px; background:#e0ecfc; color:#2563eb; }
</style>
@endsection

@section('contenu')
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $groupes = [
        'en_retard' => ['Élèves non à jour', 'retard', 'fa-exclamation-circle'],
        'a_jour'    => ['Élèves à jour', 'ok', 'fa-check-circle'],
    ];
@endphp
<div class="container-fluid p-0" id="suivi-eleves">

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="pastille"><i class="fas fa-calendar-alt me-1"></i>Année scolaire : {{ $annee?->libelle }}</span>
        <span class="pastille"><i class="fas fa-list-ol me-1"></i>{{ $plan->nom }}</span>
        @foreach ($suivi['frais_suivis'] as $libelle)
            <span class="pastille"><i class="fas fa-tags me-1"></i>{{ $libelle }}</span>
        @endforeach
        <span class="text-xs text-muted">{{ $suivi['eleves'] }} élève(s) concerné(s)</span>
    </div>

    {{-- ==================== Intervalle de dates ==================== --}}
    <x-carte class="mb-3">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-2 align-items-end" id="filtres-suivi">
                <div class="col-6 col-md-3">
                    <label class="filtre-label" for="du">Échéances du</label>
                    <input type="date" name="du" id="du" class="form-control filtre-input" value="{{ $du }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="filtre-label" for="au">au (situation arrêtée à cette date)</label>
                    <input type="date" name="au" id="au" class="form-control filtre-input" value="{{ $au }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="filtre-label" for="statut">Afficher</label>
                    <select name="statut" id="statut" class="form-select filtre-input">
                        <option value="">Tous les élèves</option>
                        <option value="en_retard" @selected($statut === 'en_retard')>Non à jour seulement</option>
                        <option value="a_jour" @selected($statut === 'a_jour')>À jour seulement</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex gap-1">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Afficher</x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.plan-echeanciers.eleves', $plan) }}" title="Réinitialiser"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
            @error('au')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            <p class="text-xs text-muted mb-0 mt-2" id="aide-suivi">
                Sont affichées les échéances dont la date butoir tombe dans l'intervalle. Un élève est « à jour » à une échéance
                quand ses encaissements de scolarité, jusqu'à la date de fin, couvrent tout ce qui était dû jusqu'à cette échéance.
            </p>
        </div>
    </x-carte>

    @if ($suivi['frais_non_suivis'])
        <div class="alert small" style="background:#fdf4e0; border:1px solid #ecd9a6; border-radius:12px;" id="frais-non-suivis">
            <i class="fas fa-info-circle me-1"></i>
            Non suivi(s) ici : {{ implode(', ', $suivi['frais_non_suivis']) }}. Le suivi par élève n'existe que pour les frais de scolarité rattachés à un niveau.
        </div>
    @endif

    {{-- ==================== Une carte par échéance ==================== --}}
    @forelse ($suivi['echeances'] as $echeance)
        @php
            $nbAJour = count($echeance['a_jour']);
            $nbRetard = count($echeance['en_retard']);
            $total = $nbAJour + $nbRetard;
            $taux = $total > 0 ? round($nbAJour / $total * 100) : 0;
        @endphp
        <x-carte class="mb-3 carte-echeance">
            <div class="card-header bg-white py-3 px-4">
                <div class="echeance-entete">
                    <h6><i class="fas fa-flag me-1" style="color:var(--school-gold-dark);"></i>{{ $echeance['libelle'] }}</h6>
                    <span class="text-xs text-muted"><i class="far fa-calendar me-1"></i>date butoir : {{ optional($echeance['date'])->format('d/m/Y') ?? 'non fixée' }}</span>
                    <div class="echeance-chiffres">
                        <span><strong class="text-success nb-a-jour">{{ $nbAJour }}</strong> à jour</span>
                        <span><strong class="text-danger nb-en-retard">{{ $nbRetard }}</strong> non à jour</span>
                        <span>prévu <strong>{{ $fmt($echeance['prevu']) }} F</strong></span>
                        <span>encaissé <strong class="text-success">{{ $fmt($echeance['paye']) }} F</strong></span>
                        <span>reste <strong class="text-danger">{{ $fmt($echeance['reste']) }} F</strong></span>
                    </div>
                </div>
                <div class="jauge mt-2" title="{{ $taux }} % des élèves à jour"><span style="width:{{ $taux }}%;"></span></div>
            </div>
            <div class="card-body p-0">
                @foreach ($groupes as $cle => [$titre, $classe, $icone])
                    @continue($statut && $statut !== $cle)
                    <div class="groupe-titre {{ $classe }}"><i class="fas {{ $icone }} me-1"></i>{{ $titre }} ({{ count($echeance[$cle]) }})</div>
                    @if ($echeance[$cle])
                        <x-tableau class="table-eleves align-middle mb-0 table-{{ $cle }}">
                            <thead>
                                <tr>
                                    <th class="ps-4">Élève</th>
                                    <th class="text-end">Dû à cette échéance</th>
                                    <th class="text-end">Encaissé</th>
                                    <th class="text-end pe-4">Reste</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($echeance[$cle] as $eleve)
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-semibold">{{ $eleve['nom'] }}</span>
                                        @if ($eleve['niveau'])<span class="niveau-pastille ms-1">{{ $eleve['niveau'] }}</span>@endif
                                        @if ($eleve['classe'])<span class="text-xs text-muted ms-1">{{ $eleve['classe'] }}</span>@endif
                                        <div class="text-xs text-muted">{{ $eleve['matricule'] }}</div>
                                    </td>
                                    <td class="num">{{ $fmt($eleve['prevu']) }} F</td>
                                    <td class="num text-success">{{ $fmt($eleve['paye']) }} F</td>
                                    <td class="num fw-bold pe-4 {{ $eleve['reste'] > 0 ? 'text-danger' : 'text-muted' }}">{{ $fmt($eleve['reste']) }} F</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </x-tableau>
                    @else
                        <div class="text-muted small px-4 py-2">Aucun élève.</div>
                    @endif
                @endforeach
            </div>
        </x-carte>
    @empty
        <x-carte>
            <x-vide icone="fas fa-calendar-times">
                Aucune échéance de ce plan n'a de date butoir dans l'intervalle choisi.
            </x-vide>
        </x-carte>
    @endforelse
</div>
@endsection
