{{-- Export PDF : élèves non à jour à une date donnée, tous échéanciers en cours confondus --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Élèves non à jour au {{ $situation['jour']->format('d/m/Y') }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #2B1E1E; margin: 0; }
        .header { border-bottom: 2px solid #D4A94D; padding-bottom: 6px; margin-bottom: 10px; }
        .header-table { width: 100%; }
        .header-brand { font-size: 14px; font-weight: bold; color: #E31E24; }
        .header-meta { text-align: right; font-size: 8px; color: #5A4A4A; line-height: 1.5; }
        .doc-title { font-size: 13px; font-weight: bold; margin: 0 0 2px; }
        .doc-subtitle { font-size: 9px; color: #5A4A4A; margin-bottom: 8px; }
        .stats { width: 100%; border-collapse: separate; border-spacing: 5px 0; margin-bottom: 10px; }
        .stats td { background: #FBF4E6; border: 1px solid #EADFCB; padding: 6px 8px; }
        .stats .label { font-size: 7px; text-transform: uppercase; color: #5A4A4A; }
        .stats .value { font-size: 11px; font-weight: bold; margin-top: 2px; }
        table.liste { width: 100%; border-collapse: collapse; }
        table.liste th { background: #B3171C; color: #fff; font-size: 7.5px; text-transform: uppercase; padding: 5px 6px; text-align: left; }
        table.liste td { padding: 4px 6px; border-bottom: 1px solid #EADFCB; }
        table.liste tr:nth-child(even) td { background: #FDF9F2; }
        .num { text-align: right; white-space: nowrap; }
        .retard { color: #E31E24; font-weight: bold; }
    </style>
</head>
<body>
@php $fmt = fn ($v) => number_format((float) $v, 0, ',', ' '); @endphp
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 19mm; padding-right: 8px;">@include('admin.layouts.partials._pdf-sceau', ['taille' => '17mm'])</td>
            <td><div class="header-brand">ÉCOLE INTERNATIONALE MARIAM</div></td>
            <td class="header-meta">
                Édité le {{ $genereLe->format('d/m/Y à H:i') }}<br>
                par {{ $generePar?->nom_complet ?? $generePar?->name }}
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">Élèves non à jour au {{ $situation['jour']->format('d/m/Y') }}</div>
<div class="doc-subtitle">
    Année scolaire {{ $annee?->libelle }} · {{ $situation['plans'] }} échéancier(s) en cours
    @if ($niveau) · niveau {{ $niveau }} @endif
    @if ($recherche) · recherche « {{ $recherche }} » @endif
</div>

<table class="stats">
    <tr>
        <td><div class="label">Élèves non à jour</div><div class="value retard">{{ $fmt(count($situation['eleves'])) }}</div></td>
        <td><div class="label">Élèves suivis</div><div class="value">{{ $fmt($situation['suivis']) }}</div></td>
        <td><div class="label">Dû à cette date</div><div class="value">{{ $fmt($situation['du']) }} F</div></td>
        <td><div class="label">Encaissé</div><div class="value">{{ $fmt($situation['paye']) }} F</div></td>
        <td><div class="label">Retard total</div><div class="value retard">{{ $fmt($situation['retard']) }} F</div></td>
    </tr>
</table>

<table class="liste">
    <thead>
        <tr>
            <th>Matricule</th>
            <th>Élève</th>
            <th>Niveau / Classe</th>
            <th>Échéances non réglées</th>
            <th class="num">Dû</th>
            <th class="num">Encaissé</th>
            <th class="num">Retard</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($situation['eleves'] as $eleve)
        <tr>
            <td>{{ $eleve['matricule'] }}</td>
            <td>{{ $eleve['nom'] }}</td>
            <td>{{ $eleve['niveau'] }}@if ($eleve['classe']) · {{ $eleve['classe'] }}@endif</td>
            <td>{{ implode(', ', array_map(fn ($e) => $e['libelle'] . ($e['date'] ? ' (' . $e['date']->format('d/m/Y') . ')' : ''), $eleve['echeances'])) }}</td>
            <td class="num">{{ $fmt($eleve['du']) }}</td>
            <td class="num">{{ $fmt($eleve['paye']) }}</td>
            <td class="num retard">{{ $fmt($eleve['retard']) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" style="text-align:center; padding:14px;">Aucun élève en retard à cette date.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
