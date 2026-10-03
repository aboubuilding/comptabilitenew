{{-- resources/views/domain/logistique/produits/print.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Catalogue produits</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2d3a; margin: 0; }

        /* En-tête */
        .header { border-bottom: 3px solid #C81E3A; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; }
        .header-brand { font-size: 15px; font-weight: bold; color: #C81E3A; letter-spacing: .5px; }
        .header-sub { font-size: 8.5px; color: #6f7e8c; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 8px; color: #6f7e8c; line-height: 1.5; }

        /* Titre */
        .doc-title { font-size: 13px; font-weight: bold; margin: 0 0 3px; }
        .doc-subtitle { font-size: 9px; color: #6f7e8c; margin-bottom: 10px; }
        .doc-filters {
            font-size: 8px; color: #6f7e8c; background: #faf6ee;
            border-left: 3px solid #D4A94D; padding: 5px 8px; margin-bottom: 10px;
        }

        /* Cartes stats */
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

        /* Tableau */
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

        /* Badges */
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7px; font-weight: bold; white-space: nowrap; }
        .badge-vendable    { background: #e0ecfc; color: #2563eb; }
        .badge-consommable { background: #fdf4e0; color: #B8860B; }
        .badge-mixte       { background: #e3f7ec; color: #1c7a4d; }

        .badge-stock { display: inline-block; padding: 1px 7px; border-radius: 8px; font-size: 7px; font-weight: bold; }
        .badge-stock.ok      { background: #e3f7ec; color: #1c7a4d; }
        .badge-stock.alerte  { background: #fdf4e0; color: #B8860B; }
        .badge-stock.rupture { background: #fdecea; color: #C81E3A; }

        .montant { font-weight: bold; }

        /* Totaux */
        .totaux { width: 100%; margin-top: 12px; }
        .totaux td { padding: 6px 8px; }
        .totaux .label { text-align: right; color: #6f7e8c; font-size: 9px; }
        .totaux .value {
            text-align: right; font-weight: bold; font-size: 11px;
            font-variant-numeric: tabular-nums; width: 130px;
        }
        .totaux tr.final td {
            border-top: 2px solid #C81E3A; padding-top: 10px;
            font-size: 12px; color: #7A0F22;
        }

        /* Pied */
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
<div class="doc-title">Catalogue des produits</div>
<div class="doc-subtitle">{{ $totaux['nb'] }} produit(s) — document de contrôle du stock</div>

@if(!empty($filtreLabels))
    <div class="doc-filters">
        <strong>Filtres appliqués :</strong> {{ implode(' · ', $filtreLabels) }}
    </div>
@endif

{{-- Stats --}}
<table class="stats">
    <tr>
        <td>
            <div class="label">Produits référencés</div>
            <div class="value blue">{{ $totaux['nb'] }}</div>
        </td>
        <td>
            <div class="label">Quantité totale en stock</div>
            <div class="value gold">{{ number_format($totaux['quantite_totale'], 0, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Valeur du stock</div>
            <div class="value green">{{ number_format($totaux['valeur_stock'], 0, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Alertes / Ruptures</div>
            <div class="value red">
                {{ $totaux['en_alerte'] }} / {{ $totaux['en_rupture'] }}
            </div>
        </td>
    </tr>
</table>

{{-- Tableau --}}
@if($produits->isEmpty())
    <div class="empty">Aucun produit sur la sélection.</div>
@else
    <table class="journal">
        <thead>
            <tr>
                <th style="width: 30%;">Produit</th>
                <th class="center" style="width: 12%;">Type</th>
                <th style="width: 10%;">Unité</th>
                <th class="right" style="width: 12%;">Prix unitaire</th>
                <th class="right" style="width: 10%;">Stock total</th>
                <th class="right" style="width: 13%;">Valeur stock</th>
                <th class="center" style="width: 13%;">État</th>
            </tr>
        </thead>
        <tbody>
            @foreach($produits as $p)
                @php
                    $stockTotal = $p->stock_total;
                    $enAlerte   = $p->est_en_alerte;
                    $enRupture  = $p->est_en_rupture;
                    $badgeClass = $enRupture ? 'rupture' : ($enAlerte ? 'alerte' : 'ok');
                    $badgeLabel = $enRupture ? 'Rupture' : ($enAlerte ? 'Stock bas' : 'Disponible');
                @endphp
                <tr>
                    <td>
                        <strong>{{ $p->libelle }}</strong>
                        @if($p->unite_achat && $p->equivalence)
                            <span class="sub">
                                1 {{ $p->unite_achat }} = {{ $p->equivalence }} {{ $p->unite_stock }}
                            </span>
                        @endif
                    </td>
                    <td class="center">
                        <span class="badge badge-{{ $p->type_produit?->css() }}">
                            {{ $p->type_produit?->label() ?? '—' }}
                        </span>
                    </td>
                    <td>{{ $p->unite_stock }}</td>
                    <td class="right montant">{{ number_format((float) $p->prix_unitaire, 2, ',', ' ') }}</td>
                    <td class="right montant">{{ number_format($stockTotal, 2, ',', ' ') }}</td>
                    <td class="right">{{ number_format($p->valeur_stock_total, 2, ',', ' ') }}</td>
                    <td class="center">
                        <span class="badge-stock {{ $badgeClass }}">{{ $badgeLabel }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totaux --}}
    <table class="totaux">
        <tr>
            <td class="label">Quantité totale en stock</td>
            <td class="value" style="color: #B8860B;">
                {{ number_format($totaux['quantite_totale'], 2, ',', ' ') }}
            </td>
        </tr>
        <tr class="final">
            <td class="label">VALEUR TOTALE DU STOCK</td>
            <td class="value">
                {{ number_format($totaux['valeur_stock'], 2, ',', ' ') }}
            </td>
        </tr>
    </table>
@endif

<div class="footer">
    École Internationale Mariam — Catalogue produits — Document de contrôle interne, généré automatiquement.
</div>

</body>
</html>