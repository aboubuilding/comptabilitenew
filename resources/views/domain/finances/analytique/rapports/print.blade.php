{{-- resources/views/domain/finances/analytique/rapports/print.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport analytique</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2d3a; margin: 0; }
        .header { border-bottom: 3px solid #C81E3A; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; }
        .header-brand { font-size: 15px; font-weight: bold; color: #C81E3A; }
        .header-sub { font-size: 8.5px; color: #6f7e8c; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 8px; color: #6f7e8c; line-height: 1.5; }
        .doc-title { font-size: 13px; font-weight: bold; margin: 0 0 3px; }
        .doc-subtitle { font-size: 9px; color: #6f7e8c; margin-bottom: 10px; }
        table.journal { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        table.journal thead th { background: #1f2d3a; color: #fff; font-size: 7.5px; text-transform: uppercase; padding: 6px; text-align: left; font-weight: normal; }
        table.journal thead th.right { text-align: right; }
        table.journal tbody td { padding: 5px 6px; border-bottom: 1px solid #ece4d6; vertical-align: top; }
        table.journal tbody tr:nth-child(even) { background: #fafaf7; }
        table.journal tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        table.journal tfoot td { padding: 6px; font-weight: bold; background: #faf6ee; border-top: 2px solid #C81E3A; font-size: 9px; }
        .centre-code { font-family: monospace; background: #faf6ee; color: #C81E3A; padding: 1px 6px; border-radius: 4px; font-weight: bold; }
        .positif { color: #1c7a4d; }
        .negatif { color: #C81E3A; }
        .footer { margin-top: 16px; padding-top: 6px; border-top: 1px solid #ece4d6; font-size: 7.5px; color: #6f7e8c; text-align: center; }
    </style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <div class="header-brand">ÉCOLE INTERNATIONALE MARIAM</div>
                <div class="header-sub">Plateforme de gestion scolaire et budgétaire</div>
            </td>
            <td class="header-meta">
                <div><strong>Généré le</strong> {{ $genereLe->format('d/m/Y à H:i') }}</div>
                @if($generePar)<div>par {{ $generePar->name }}</div>@endif
            </td>
        </tr>
    </table>
</div>

<div class="doc-title">Rapport analytique par centre de coûts</div>
<div class="doc-subtitle">
    Période analysée :
    {{ !empty($filters['date_debut']) ? \Carbon\Carbon::parse($filters['date_debut'])->format('d/m/Y') : '—' }}
    →
    {{ !empty($filters['date_fin']) ? \Carbon\Carbon::parse($filters['date_fin'])->format('d/m/Y') : '—' }}
</div>

<table class="journal">
    <thead>
        <tr>
            <th style="width: 25%;">Centre analytique</th>
            <th class="right" style="width: 12%;">Recettes</th>
            <th class="right" style="width: 12%;">Dépenses</th>
            <th class="right" style="width: 12%;">Résultat</th>
            <th class="right" style="width: 12%;">Budget</th>
            <th class="right" style="width: 12%;">Écart</th>
            <th class="right" style="width: 15%;">Taux réalisation</th>
        </tr>
    </thead>
    <tbody>
        @php $totR=$totD=$totRes=$totB=0; @endphp
        @foreach ($lignes as $l)
            @php $totR+=$l['recettes']; $totD+=$l['depenses']; $totRes+=$l['resultat']; $totB+=$l['budget']; @endphp
            <tr>
                <td><span class="centre-code">{{ $l['code'] }}</span> {{ $l['libelle'] }}</td>
                <td class="right positif">+{{ number_format($l['recettes'], 2, ',', ' ') }}</td>
                <td class="right negatif">−{{ number_format($l['depenses'], 2, ',', ' ') }}</td>
                <td class="right {{ $l['resultat'] >= 0 ? 'positif' : 'negatif' }}">{{ number_format($l['resultat'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($l['budget'], 2, ',', ' ') }}</td>
                <td class="right {{ $l['ecart_budget'] >= 0 ? 'positif' : 'negatif' }}">{{ number_format($l['ecart_budget'], 2, ',', ' ') }}</td>
                <td class="right">{{ number_format($l['taux_realisation'], 1, ',', ' ') }} %</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAUX</td>
            <td class="right positif">+{{ number_format($totR, 2, ',', ' ') }}</td>
            <td class="right negatif">−{{ number_format($totD, 2, ',', ' ') }}</td>
            <td class="right {{ $totRes >= 0 ? 'positif' : 'negatif' }}">{{ number_format($totRes, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($totB, 2, ',', ' ') }}</td>
            <td class="right {{ ($totRes - $totB) >= 0 ? 'positif' : 'negatif' }}">{{ number_format($totRes - $totB, 2, ',', ' ') }}</td>
            <td class="right"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    École Internationale Mariam — Rapport analytique — Document de contrôle interne, généré automatiquement.
</div>

</body>
</html>