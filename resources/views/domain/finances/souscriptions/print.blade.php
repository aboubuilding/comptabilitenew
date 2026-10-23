{{-- Export PDF des souscriptions de l'année scolaire active (onglet et filtres en cours) --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }}</title>
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
        .reste { color: #E31E24; font-weight: bold; }
        h3 { font-size: 10px; margin: 12px 0 5px; color: #B3171C; }
    </style>
</head>
<body>
@php
    $fmt = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $etats = \App\Domain\Finances\Exports\SouscriptionsExport::ETATS;
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

<div class="doc-title">Souscriptions — {{ $titre }}</div>
<div class="doc-subtitle">Année scolaire {{ $annee?->libelle }} · {{ $souscriptions->count() }} souscription(s)</div>

@if ($statistiques)
    <table class="stats">
        <tr>
            <td><div class="label">Élèves concernés</div><div class="value">{{ $fmt($statistiques['eleves']) }}</div></td>
            <td><div class="label">Réglées / partielles / impayées</div><div class="value">{{ $statistiques['soldees'] }} / {{ $statistiques['partielles'] }} / {{ $statistiques['impayees'] }}</div></td>
            <td><div class="label">Montant attendu</div><div class="value">{{ $fmt($statistiques['attendu']) }} F</div></td>
            <td><div class="label">Encaissé</div><div class="value">{{ $fmt($statistiques['percu']) }} F</div></td>
            <td><div class="label">Reste à recouvrer</div><div class="value reste">{{ $fmt($statistiques['reste']) }} F</div></td>
        </tr>
    </table>
@endif

<table class="liste">
    <thead>
        <tr>
            <th>Matricule</th>
            <th>Élève</th>
            <th>Niveau / Classe</th>
            <th>Souscription</th>
            <th class="num">Attendu</th>
            <th class="num">Payé</th>
            <th class="num">Reste</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
    @forelse ($souscriptions as $s)
        <tr>
            <td>{{ $s->inscription?->eleve?->matricule }}</td>
            <td>{{ $s->inscription?->eleve?->nom }} {{ $s->inscription?->eleve?->prenom }}</td>
            <td>{{ $s->inscription?->classe?->libelle ?? $s->inscription?->niveau?->libelle ?? '—' }}</td>
            <td>{{ $s->type_paiement?->label() }}@if ($s->intitule !== $s->type_paiement?->label()) — {{ $s->intitule }}@endif</td>
            <td class="num">{{ $fmt($s->montant_net) }}</td>
            <td class="num">{{ $fmt($s->montant_paye) }}</td>
            <td class="num reste">{{ $fmt($s->reste) }}</td>
            <td>{{ $etats[$s->etat_paiement] }}</td>
        </tr>
    @empty
        <tr><td colspan="8" style="text-align:center; padding:14px;">Aucune souscription.</td></tr>
    @endforelse
    </tbody>
</table>

@if ($statistiques && $statistiques['niveaux']->isNotEmpty())
    <h3>Répartition par niveau</h3>
    <table class="liste">
        <thead>
            <tr><th>Niveau</th><th class="num">Élèves</th><th class="num">Souscriptions</th><th class="num">Attendu</th><th class="num">Encaissé</th><th class="num">Reste</th></tr>
        </thead>
        <tbody>
        @foreach ($statistiques['niveaux'] as $n)
            <tr>
                <td>{{ $n->niveau ?? 'Sans niveau' }}</td>
                <td class="num">{{ $n->eleves }}</td>
                <td class="num">{{ $n->souscriptions }}</td>
                <td class="num">{{ $fmt($n->attendu) }}</td>
                <td class="num">{{ $fmt($n->percu) }}</td>
                <td class="num reste">{{ $fmt($n->reste) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
</body>
</html>
