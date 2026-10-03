{{-- resources/views/domain/services-eleves/bibliotheque/livres/print.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue de la bibliothèque</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2d3a; margin: 0; }

        .header { border-bottom: 3px solid #C81E3A; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; }
        .header-brand { font-size: 15px; font-weight: bold; color: #C81E3A; letter-spacing: .5px; }
        .header-sub { font-size: 8.5px; color: #6f7e8c; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 8px; color: #6f7e8c; line-height: 1.5; }

        .doc-title { font-size: 13px; font-weight: bold; margin: 0 0 3px; }
        .doc-subtitle { font-size: 9px; color: #6f7e8c; margin-bottom: 10px; }
        .doc-filters {
            font-size: 8px; color: #6f7e8c; background: #faf6ee;
            border-left: 3px solid #D4A94D; padding: 5px 8px; margin-bottom: 10px;
        }

        .stats { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 12px; }
        .stats td {
            background: #faf6ee; border: 1px solid #ece4d6; border-radius: 6px;
            padding: 7px 10px; width: 25%;
        }
        .stats .label { font-size: 7px; text-transform: uppercase; color: #6f7e8c; letter-spacing: .5px; }
        .stats .value { font-size: 11px; font-weight: bold; color: #1f2d3a; margin-top: 2px; }
        .stats .value.blue  { color: #2563eb; }
        .stats .value.gold  { color: #B8860B; }
        .stats .value.green { color: #1c7a4d; }
        .stats .value.red   { color: #C81E3A; }

        table.journal { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        table.journal thead th {
            background: #1f2d3a; color: #ffffff;
            font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px;
            padding: 6px 6px; text-align: left; font-weight: normal;
        }
        table.journal thead th.right { text-align: right; }
        table.journal thead th.center { text-align: center; }
        table.journal tbody td {
            padding: 5px 6px; border-bottom: 1px solid #ece4d6; vertical-align: top;
        }
        table.journal tbody tr:nth-child(even) { background: #fafaf7; }
        table.journal tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        table.journal tbody td.center { text-align: center; }
        table.journal tbody td .sub { font-size: 7.5px; color: #6f7e8c; display: block; margin-top: 1px; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7px; font-weight: bold; white-space: nowrap; }
        .badge-ok   { background: #e3f7ec; color: #1c7a4d; }
        .badge-low  { background: #fdf4e0; color: #B8860B; }
        .badge-none { background: #fdecea; color: #C81E3A; }

        .footer {
            margin-top: 16px; padding-top: 6px; border-top: 1px solid #ece4d6;
            font-size: 7.5px; color: #6f7e8c; text-align: center;
        }
        .empty { text-align: center; padding: 30px 0; color: #9aa7b2; font-style: italic; }
    </style>
</head>
<body>

{{-- En-tête --}}
<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <div class="header-brand">ÉCOLE INTERNATIONALE MARIAM</div>
                <div class="header-sub">Plateforme de gestion scolaire et budgétaire</div>
            </td>
            <td class="header-meta">
                <div><strong>Généré le</strong> {{ $genereLe->format('d/m/Y à H:i') }}</div>
                @if($generePar)
                    <div>par {{ $generePar->name }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- Titre --}}
<div class="doc-title">Catalogue de la bibliothèque</div>
<div class="doc-subtitle">{{ $totaux['nb_titres'] }} titre(s) — document de référence</div>

@if(!empty($filtreLabels))
    <div class="doc-filters">
        <strong>Filtres appliqués :</strong> {{ implode(' · ', $filtreLabels) }}
    </div>
@endif

{{-- Cartes stats --}}
<table class="stats">
    <tr>
        <td>
            <div class="label">Titres</div>
            <div class="value blue">{{ $totaux['nb_titres'] }}</div>
        </td>
        <td>
            <div class="label">Exemplaires</div>
            <div class="value gold">{{ $totaux['nb_exemplaires'] }}</div>
        </td>
        <td>
            <div class="label">Disponibles</div>
            <div class="value green">{{ $totaux['nb_disponibles'] }}</div>
        </td>
        <td>
            <div class="label">Indisponibles</div>
            <div class="value red">{{ $totaux['nb_indisponibles'] }}</div>
        </td>
    </tr>
</table>

{{-- Tableau --}}
@if($livres->isEmpty())
    <div class="empty">Aucun livre dans le catalogue sur la sélection.</div>
@else
    <table class="journal">
        <thead>
            <tr>
                <th style="width: 35%;">Titre</th>
                <th style="width: 20%;">Auteur</th>
                <th style="width: 15%;">Catégorie</th>
                <th style="width: 12%;">Éditeur</th>
                <th class="center" style="width: 8%;">Total</th>
                <th class="center" style="width: 10%;">Dispo.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($livres as $livre)
                @php
                    $dispo = $livre->nombre_disponibles;
                    $total = $livre->nombre_exemplaires;
                    $badge = $dispo === 0 ? 'none' : ($dispo === 1 ? 'low' : 'ok');
                @endphp
                <tr>
                    <td>
                        <strong>{{ $livre->titre }}</strong>
                        @if($livre->numero_isbn)
                            <span class="sub">ISBN : {{ $livre->numero_isbn }}</span>
                        @endif
                    </td>
                    <td>{{ $livre->auteur?->nom_prenom ?? '—' }}</td>
                    <td>{{ $livre->categorie?->libelle ?? '—' }}</td>
                    <td>
                        {{ $livre->maisonEdition?->libelle ?? '—' }}
                        @if($livre->anneeEdition)
                            <span class="sub">{{ $livre->anneeEdition->libelle }}</span>
                        @endif
                    </td>
                    <td class="center">{{ $total }}</td>
                    <td class="center">
                        <span class="badge badge-{{ $badge }}">{{ $dispo }}/{{ $total }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    École Internationale Mariam — Catalogue de la bibliothèque — Document de référence interne, généré automatiquement.
</div>

</body>
</html>