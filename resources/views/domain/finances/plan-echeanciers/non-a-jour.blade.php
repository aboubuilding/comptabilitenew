{{-- Élèves non à jour à une date donnée, tous échéanciers en cours de l'année confondus --}}
@extends('admin.layouts.app')

@section('title', 'Élèves non à jour')
@section('page_icon', 'fa-user-clock')
@section('page_title', 'Élèves non à jour')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tableau') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('finances.plan-echeanciers.index') }}">Plans d'échéancier</a></li>
    <li class="breadcrumb-item active">Élèves non à jour</li>
@endsection

@section('page_actions')
    {{-- Les exports reprennent la date et les filtres affichés --}}
    @php $requete = array_filter(['jour' => $jour, 'niveau' => $niveau, 'search' => $recherche], fn ($v) => $v !== null && $v !== ''); @endphp
    <div class="dropdown">
        <x-bouton variante="contour" type="button" class="btn-sm rounded-pill px-3 dropdown-toggle" id="btn-exporter" data-bs-toggle="dropdown">
            <i class="fas fa-file-export me-1"></i> Exporter
        </x-bouton>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" id="export-excel" href="{{ route('finances.plan-echeanciers.non-a-jour.export', $requete) }}"><i class="fas fa-file-excel me-2 text-success"></i>Excel</a></li>
            <li><a class="dropdown-item" id="export-pdf" href="{{ route('finances.plan-echeanciers.non-a-jour.exportPdf', $requete) }}"><i class="fas fa-file-pdf me-2 text-danger"></i>PDF</a></li>
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
    .indicateurs { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:.6rem; }
    .indicateur { background:var(--eim-cream); border:1px solid var(--eim-border); border-radius:10px; padding:.55rem .8rem; }
    .indicateur span { display:block; font-size:.66rem; text-transform:uppercase; font-weight:700; color:var(--school-muted); }
    .indicateur strong { font-size:1.05rem; font-variant-numeric:tabular-nums; }
    .table-retard { table-layout:fixed; width:100%; }
    .table-retard thead th { color:var(--school-red); font-size:.72rem; font-weight:700; text-transform:uppercase; background:var(--eim-cream); padding:.65rem .6rem; border-bottom:2px solid var(--eim-border); overflow-wrap:anywhere; }
    .table-retard tbody td { padding:.55rem .6rem; border-bottom:1px solid var(--eim-border); font-size:.86rem; vertical-align:middle; overflow-wrap:anywhere; }
    .table-retard th.col-frais { width:20%; }
    .table-retard th.col-echeances { width:20%; }
    .table-retard th.col-montants { width:15%; }
    .table-retard th.col-retard { width:13%; }
    .table-retard th, .table-retard td { white-space:normal !important; }
    .table-retard .ps-4 { padding-left:1rem !important; }
    .table-retard .pe-4 { padding-right:1rem !important; }
    .num { text-align:right; font-variant-numeric:tabular-nums; }
    #eleves-non-a-jour, #eleves-non-a-jour .main-card { max-width:100%; overflow-x:hidden; }
    .niveau-pastille { display:inline-block; font-size:.66rem; font-weight:700; padding:.08rem .5rem; border-radius:999px; background:#e0ecfc; color:#2563eb; }
    .echeance-pastille { display:inline-block; font-size:.72rem; padding:.1rem .5rem; border-radius:10px; background:#fdecea; color:var(--school-red); margin:.08rem .1rem .08rem 0; }
</style>
@endsection

@section('contenu')
@php $fmt = fn ($v) => number_format((float) $v, 0, ',', ' '); @endphp
<div class="container-fluid p-0" id="eleves-non-a-jour">

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="pastille"><i class="fas fa-calendar-alt me-1"></i>Année scolaire : {{ $annee?->libelle }}</span>
        <span class="text-xs text-muted">{{ $situation['plans'] }} échéancier(s) en cours pris en compte</span>
    </div>

    <x-carte class="mb-3">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-2 align-items-end" id="filtres-non-a-jour">
                <div class="col-6 col-md-3">
                    <label class="filtre-label" for="jour">Situation au</label>
                    <input type="date" name="jour" id="jour" class="form-control filtre-input" value="{{ $jour }}" required>
                </div>
                <div class="col-6 col-md-3">
                    <label class="filtre-label" for="niveau">Niveau</label>
                    <select name="niveau" id="niveau" class="form-select filtre-input">
                        <option value="">Tous</option>
                        @foreach ($niveaux as $n)
                            <option value="{{ $n }}" @selected($niveau === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filtre-label">Recherche</label>
                    <x-recherche name="search" placeholder="Nom, prénom, matricule..." value="{{ $recherche }}" />
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <x-bouton variante="filtrer" type="submit" class="flex-fill"><i class="fas fa-filter me-1"></i> Afficher</x-bouton>
                    <x-bouton variante="contour" href="{{ route('finances.plan-echeanciers.non-a-jour') }}" title="Aujourd'hui, sans filtre"><i class="fas fa-undo"></i></x-bouton>
                </div>
            </form>
            <p class="text-xs text-muted mb-0 mt-2" id="aide-non-a-jour">
                Un élève est non à jour quand, à la date choisie, ses encaissements de scolarité ne couvrent pas toutes les échéances
                dont la date butoir est passée. Les échéances à venir ne comptent pas.
            </p>
        </div>
    </x-carte>

    <div class="indicateurs mb-3" id="indicateurs-non-a-jour">
        <div class="indicateur"><span>Élèves non à jour</span><strong class="text-danger" id="stat-eleves">{{ $fmt(count($situation['eleves'])) }}</strong></div>
        <div class="indicateur"><span>Élèves suivis</span><strong id="stat-suivis">{{ $fmt($situation['suivis']) }}</strong></div>
        <div class="indicateur"><span>Dû au {{ $situation['jour']->format('d/m/Y') }}</span><strong id="stat-du">{{ $fmt($situation['du']) }} F</strong></div>
        <div class="indicateur"><span>Encaissé</span><strong class="text-success" id="stat-paye">{{ $fmt($situation['paye']) }} F</strong></div>
        <div class="indicateur"><span>Retard total</span><strong class="text-danger" id="stat-retard">{{ $fmt($situation['retard']) }} F</strong></div>
    </div>

    <x-carte>
        <div class="card-body p-0">
            <table class="table table-retard align-middle mb-0" id="table-non-a-jour">
                <thead>
                    <tr>
                        <th class="ps-4">Élève</th>
                        <th class="col-frais">Frais</th>
                        <th class="col-echeances">Échéances non réglées</th>
                        <th class="text-end col-montants">Dû · encaissé</th>
                        <th class="text-end pe-4 col-retard">Retard</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($pagination as $eleve)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-semibold">{{ $eleve['nom'] }}</span>
                            @if ($eleve['niveau'])<span class="niveau-pastille ms-1">{{ $eleve['niveau'] }}</span>@endif
                            @if ($eleve['classe'])<span class="text-xs text-muted ms-1">{{ $eleve['classe'] }}</span>@endif
                            <div class="text-xs text-muted">{{ $eleve['matricule'] }}</div>
                        </td>
                        <td>{{ $eleve['frais'] }}</td>
                        <td>
                            @foreach ($eleve['echeances'] as $echeance)
                                <span class="echeance-pastille" title="reste {{ $fmt($echeance['reste']) }} F">{{ $echeance['libelle'] }}@if ($echeance['date']) · {{ $echeance['date']->format('d/m/Y') }}@endif</span>
                            @endforeach
                        </td>
                        <td class="num">
                            {{ $fmt($eleve['du']) }} F
                            <div class="text-xs text-success">encaissé {{ $fmt($eleve['paye']) }} F</div>
                        </td>
                        <td class="num fw-bold text-danger pe-4">{{ $fmt($eleve['retard']) }} F</td>
                    </tr>
                @empty
                    <x-vide colspan="5" icone="fas fa-check-circle">Aucun élève en retard au {{ $situation['jour']->format('d/m/Y') }}.</x-vide>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-carte>

    <div class="mt-3" id="pagination-non-a-jour">
        <x-pagination :liste="$pagination" />
    </div>
</div>
@endsection
