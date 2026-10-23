{{-- resources/views/domain/logistique/ventes/ticket.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket — {{ $vente->reference }}</title>
    <style>
        @page { margin: 8mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #2B1E1E; margin: 0; }

        /* ============ EN-TÊTE ============ */
        .header {
            text-align: center;
            border-bottom: 2px solid #D4A94D;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 14px;
            color: #E31E24;
            margin: 0;
            letter-spacing: 1px;
        }
        .header .sub {
            font-size: 8px;
            color: #5A4A4A;
            margin-top: 2px;
        }

        /* ============ TITRE ============ */
        .doc-title {
            background: #2B1E1E;
            color: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 8px;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 11px;
            letter-spacing: 2px;
        }
        .doc-title .ref {
            font-family: monospace;
            font-size: 9px;
            color: #D4A94D;
            margin-top: 2px;
        }

        /* ============ INFOS ============ */
        .info-block {
            background: #FBF4E6;
            border: 1px solid #EADFCB;
            border-radius: 6px;
            padding: 7px 10px;
            margin-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 9px;
        }
        .info-row .label { color: #5A4A4A; }
        .info-row .value { font-weight: 600; color: #2B1E1E; text-align: right; }

        /* ============ LIGNES ============ */
        table.lignes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.lignes thead th {
            background: #2B1E1E;
            color: #fff;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 5px 6px;
            text-align: left;
            font-weight: normal;
        }
        table.lignes thead th.right { text-align: right; }
        table.lignes thead th.center { text-align: center; }
        table.lignes tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #EADFCB;
            font-size: 9px;
            vertical-align: top;
        }
        table.lignes tbody td.right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        table.lignes tbody td.center { text-align: center; }
        table.lignes tbody td .sub { font-size: 7.5px; color: #5A4A4A; display: block; margin-top: 1px; }

        /* ============ TOTAUX ============ */
        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .total-table td { padding: 3px 6px; font-size: 9.5px; }
        .total-table td.label { text-align: right; color: #5A4A4A; }
        .total-table td.value {
            text-align: right;
            font-weight: 700;
            width: 110px;
            font-variant-numeric: tabular-nums;
        }
        .total-table tr.final td {
            border-top: 2px solid #E31E24;
            padding-top: 6px;
            font-size: 13px;
            color: #B3171C;
        }

        /* ============ STATUT ============ */
        .statut {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
        }
        .statut.paye   { background: #e3f7ec; color: #1F7A4D; }
        .statut.impaye { background: #fdecea; color: #E31E24; }
        .statut.annule { background: #eceff2; color: #5A4A4A; }

        /* ============ PIED ============ */
        .footer {
            margin-top: 12px;
            padding-top: 6px;
            border-top: 1px dashed #EADFCB;
            text-align: center;
            font-size: 7.5px;
            color: #5A4A4A;
            line-height: 1.5;
        }
    </style>
</head>
<body>

{{-- EN-TÊTE --}}
<div class="header">
    <div style="text-align: center; margin-bottom: 4px;">@include('admin.layouts.partials._pdf-sceau', ['taille' => '14mm'])</div>
    <h1>ÉCOLE INTERNATIONALE MARIAM</h1>
    <div class="sub">Plateforme de gestion scolaire et budgétaire</div>
</div>

{{-- TITRE --}}
<div class="doc-title">
    <h2>TICKET DE VENTE</h2>
    <div class="ref">{{ $vente->reference }}</div>
</div>

{{-- INFOS --}}
<div class="info-block">
    <div class="info-row">
        <span class="label">Date</span>
        <span class="value">{{ optional($vente->date_vente)->format('d/m/Y') }}</span>
    </div>
    <div class="info-row">
        <span class="label">Acheteur</span>
        <span class="value">{{ $vente->acheteur_nom }}</span>
    </div>
    @if ($vente->client?->telephone)
        <div class="info-row">
            <span class="label">Téléphone</span>
            <span class="value">{{ $vente->client->telephone }}</span>
        </div>
    @endif
    @if ($vente->magasin)
        <div class="info-row">
            <span class="label">Magasin</span>
            <span class="value">{{ $vente->magasin->libelle }}</span>
        </div>
    @endif
    <div class="info-row">
        <span class="label">Statut</span>
        <span class="value">
            <span class="statut {{ $vente->statut_paiement?->css() }}">
                {{ $vente->statut_paiement?->label() }}
            </span>
        </span>
    </div>
    @if ($vente->utilisateur)
        <div class="info-row">
            <span class="label">Vendeur</span>
            <span class="value">{{ $vente->utilisateur->name }}</span>
        </div>
    @endif
</div>

{{-- LIGNES --}}
<table class="lignes">
    <thead>
        <tr>
            <th style="width: 45%;">Produit</th>
            <th class="center" style="width: 15%;">Qté</th>
            <th class="right" style="width: 20%;">P.U.</th>
            <th class="right" style="width: 20%;">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($vente->details as $ligne)
            <tr>
                <td>
                    {{ $ligne->produit?->libelle ?? 'Produit #' . $ligne->produit_id }}
                    @if ((float) $ligne->remise > 0)
                        <span class="sub">Remise : −{{ number_format((float) $ligne->remise, 1, ',', ' ') }} %</span>
                    @endif
                </td>
                <td class="center">{{ number_format((float) $ligne->quantite, 2, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $ligne->prix_unitaire, 2, ',', ' ') }}</td>
                <td class="right">{{ number_format((float) $ligne->montant_ht, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- TOTAUX --}}
<table class="total-table">
    <tr>
        <td class="label">Total HT</td>
        <td class="value">{{ number_format((float) $vente->total_ht, 2, ',', ' ') }}</td>
    </tr>
    <tr class="final">
        <td class="label">TOTAL TTC</td>
        <td class="value">{{ number_format((float) $vente->total_ttc, 2, ',', ' ') }} F</td>
    </tr>
</table>

{{-- PIED --}}
<div class="footer">
    Merci de votre achat à l'École Internationale Mariam.<br>
    Ce ticket est un justificatif officiel — à conserver.<br>
    Généré le {{ $genereLe->format('d/m/Y à H:i') }}.
</div>

</body>
</html>