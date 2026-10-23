{{-- Export PDF : élèves à jour et non à jour à chaque échéance d'un plan (intervalle et statut de l'écran) --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Suivi des échéances — {{ $plan->nom }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #2B1E1E; margin: 0; }
        .header { border-bottom: 2px solid #D4A94D; padding-bottom: 6px; margin-bottom: 10px; }
        .header-table { width: 100%; }
        .header-brand { font-size: 14px; font-weight: bold; color: #E31E24; }
        .header-meta { text-align: right; font-size: 8px; color: #5A4A4A; line-height: 1.5; }
        .doc-title { font-size: 13px; font-weight: bold; margin: 0 0 2px; }
        .doc-subtitle { font-size: 9px; color: #5A4A4A; margin-bottom: 4px; }
        .aide { font-size: 7.5px; color: #5A4A4A; background: #FBF4E6; border-left: 3px solid #D4A94D; padding: 4px 7px; margin-bottom: 10px; }
        .echeance { margin-top: 12px; page-break-inside: auto; }
        .echeance-titre { font-size: 10.5px; font-weight: bold; color: #B3171C; border-bottom: 1px solid #EADFCB; padding-bottom: 3px; }
        .echeance-chiffres { font-size: 8px; color: #2B1E1E; margin: 3px 0 5px; }
        .groupe { font-size: 8px; font-weight: bold; text-transform: uppercase; padding: 4px 6px; margin-top: 5px; }
        .groupe.retard { background: #fdecea; color: #E31E24; }
        .groupe.ok { background: #e3f7ec; color: #1F7A4D; }
        table.liste { width: 100%; border-collapse: collapse; }
        table.liste th { background: #B3171C; color: #fff; font-size: 7.5px; text-transform: uppercase; padding: 4px 6px; text-align: left; }
        table.liste td { padding: 3px 6px; border-bottom: 1px solid #EADFCB; }
        table.liste tr:nth-child(even) td { background: #FDF9F2; }
        .num { text-align: right; white-space: nowrap; }
        .reste { color: #E31E24; font-weight: bold; }
        .vide { color: #5A4A4A; padding: 4px 6px; }
    </style>
</head>
<body>
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $groupes = ['en_retard' => ['Élèves non à jour', 'retard'], 'a_jour' => ['Élèves à jour', 'ok']];
@endphp
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

<div class="doc-title">Élèves à jour / en retard — {{ $plan->nom }}</div>
<div class="doc-subtitle">
    Année scolaire {{ $annee?->libelle }}
    @if ($suivi['frais_suivis']) · {{ implode(', ', $suivi['frais_suivis']) }} @endif
    · {{ $suivi['eleves'] }} élève(s) concerné(s)
</div>
<div class="aide">
    Échéances dont la date butoir tombe
    @if ($suivi['du']) du {{ $suivi['du']->format('d/m/Y') }} @else jusqu' @endif
    au {{ $suivi['au']->format('d/m/Y') }} ; situation arrêtée au {{ $suivi['au']->format('d/m/Y') }}
    (encaissements de scolarité jusqu'à cette date).
    @if ($statut) Affichage limité aux élèves {{ $statut === 'a_jour' ? 'à jour' : 'non à jour' }}. @endif
</div>

@forelse ($suivi['echeances'] as $echeance)
    <div class="echeance">
        <div class="echeance-titre">
            {{ $echeance['libelle'] }} — date butoir : {{ optional($echeance['date'])->format('d/m/Y') ?? 'non fixée' }}
        </div>
        <div class="echeance-chiffres">
            {{ count($echeance['a_jour']) }} à jour · {{ count($echeance['en_retard']) }} non à jour
            · prévu {{ $fmt($echeance['prevu']) }} F · encaissé {{ $fmt($echeance['paye']) }} F
            · reste <span class="reste">{{ $fmt($echeance['reste']) }} F</span>
        </div>

        @foreach ($groupes as $cle => [$titre, $classe])
            @continue($statut && $statut !== $cle)
            <div class="groupe {{ $classe }}">{{ $titre }} ({{ count($echeance[$cle]) }})</div>
            @if ($echeance[$cle])
                <table class="liste">
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Élève</th>
                            <th>Niveau / Classe</th>
                            <th class="num">Dû à l'échéance</th>
                            <th class="num">Encaissé</th>
                            <th class="num">Reste</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($echeance[$cle] as $eleve)
                        <tr>
                            <td>{{ $eleve['matricule'] }}</td>
                            <td>{{ $eleve['nom'] }}</td>
                            <td>{{ $eleve['niveau'] }}@if ($eleve['classe']) · {{ $eleve['classe'] }}@endif</td>
                            <td class="num">{{ $fmt($eleve['prevu']) }}</td>
                            <td class="num">{{ $fmt($eleve['paye']) }}</td>
                            <td class="num {{ $eleve['reste'] > 0 ? 'reste' : '' }}">{{ $fmt($eleve['reste']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <div class="vide">Aucun élève.</div>
            @endif
        @endforeach
    </div>
@empty
    <div class="vide">Aucune échéance de ce plan n'a de date butoir dans l'intervalle choisi.</div>
@endforelse
</body>
</html>
