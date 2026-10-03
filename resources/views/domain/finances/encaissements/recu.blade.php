{{-- resources/views/domain/finances/encaissements/recu.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu — {{ $paiement->reference }}</title>
    <style>
        @page { margin: 8mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2d3a;
            margin: 0;
        }

        /* ============ EN-TÊTE ============ */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #C81E3A;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-left h1 {
            font-size: 14px;
            color: #C81E3A;
            margin: 0;
            letter-spacing: .5px;
        }
        .header-left small {
            display: block;
            font-size: 8.5px;
            color: #6f7e8c;
            margin-top: 2px;
        }
        .header-right {
            text-align: right;
            font-size: 8.5px;
            color: #6f7e8c;
            line-height: 1.5;
        }

        /* ============ TITRE ============ */
        .doc-title {
            background: #1f2d3a;
            color: #fff;
            padding: 8px 12px;
            border-radius: 4px;
            text-align: center;
            margin-bottom: 12px;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 12px;
            letter-spacing: 2px;
            font-weight: bold;
        }
        .doc-title .reference {
            font-family: monospace;
            font-size: 10px;
            color: #D4A94D;
            margin-top: 3px;
            letter-spacing: 1px;
        }

        /* ============ BLOCS INFO ============ */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 6px;
        }
        .info-block {
            background: #faf6ee;
            border: 1px solid #ece4d6;
            border-radius: 6px;
            padding: 8px 10px;
        }
        .info-block h3 {
            font-size: 8px;
            text-transform: uppercase;
            color: #6f7e8c;
            margin: 0 0 5px;
            letter-spacing: .6px;
            font-weight: 600;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 9px;
        }
        .info-row .label { color: #6f7e8c; }
        .info-row .value { font-weight: 600; color: #1f2d3a; text-align: right; }

        /* ============ TABLEAU DÉTAILS ============ */
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.details thead th {
            background: #1f2d3a;
            color: #fff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 6px 8px;
            text-align: left;
            font-weight: normal;
        }
        table.details thead th.right { text-align: right; }
        table.details thead th.center { text-align: center; }
        table.details tbody td {
            padding: 7px 8px;
            border-bottom: 1px solid #ece4d6;
            font-size: 9.5px;
            vertical-align: top;
        }
        table.details tbody td.right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        table.details tbody td.center { text-align: center; }
        table.details tbody td .sub { font-size: 8px; color: #6f7e8c; display: block; margin-top: 1px; }

        /* ============ TOTAL ============ */
        .total-wrapper {
            margin-top: 6px;
            margin-bottom: 12px;
        }
        .total-table {
            width: 100%;
            border-collapse: collapse;
        }
        .total-table td { padding: 4px 8px; font-size: 10px; }
        .total-table td.label { text-align: right; color: #6f7e8c; }
        .total-table td.value {
            text-align: right;
            font-weight: 700;
            width: 120px;
            font-variant-numeric: tabular-nums;
        }
        .total-table tr.final td {
            border-top: 2px solid #C81E3A;
            padding-top: 8px;
            font-size: 14px;
            color: #7A0F22;
        }

        /* ============ SIGNATURES ============ */
        .signatures {
            width: 100%;
            margin-top: 20px;
            border-top: 1px dashed #ece4d6;
            padding-top: 10px;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            font-size: 8.5px;
            color: #6f7e8c;
            padding-top: 30px;
            vertical-align: bottom;
        }
        .signatures td .line {
            border-top: 1px solid #1f2d3a;
            width: 70%;
            margin: 0 auto 4px;
            padding-top: 2px;
        }

        /* ============ PIED ============ */
        .footer {
            margin-top: 14px;
            padding-top: 6px;
            border-top: 1px solid #ece4d6;
            text-align: center;
            font-size: 7.5px;
            color: #6f7e8c;
            line-height: 1.5;
        }
    </style>
</head>
<body>

{{-- EN-TÊTE --}}
<div class="header">
    <div class="header-left">
        <h1>ÉCOLE INTERNATIONALE MARIAM</h1>
        <small>Plateforme de gestion scolaire et budgétaire</small>
    </div>
    <div class="header-right">
        <div><strong>Généré le</strong> {{ $genereLe->format('d/m/Y à H:i') }}</div>
        @if($generePar)
            <div>par {{ $generePar->name }}</div>
        @endif
    </div>
</div>

{{-- TITRE --}}
<div class="doc-title">
    <h2>REÇU DE PAIEMENT</h2>
    <div class="reference">{{ $paiement->reference ?? '—' }}</div>
</div>

{{-- INFO PAYEUR / ÉLÈVE --}}
<table class="info-grid">
    <tr>
        <td>
            <div class="info-block">
                <h3>Payeur</h3>
                @if(! empty($eleve))
                    <div class="info-row">
                        <span class="label">Élève</span>
                        <span class="value">{{ $eleve->nom }} {{ $eleve->prenom }} ({{ $eleve->matricule }})</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="label">Nom</span>
                    <span class="value">{{ $paiement->payeur ?? '—' }}</span>
                </div>
                @if($paiement->telephone_payeur)
                    <div class="info-row">
                        <span class="label">Téléphone</span>
                        <span class="value">{{ $paiement->telephone_payeur }}</span>
                    </div>
                @endif
            </div>
        </td>
        <td>
            <div class="info-block">
                <h3>Encaissement</h3>
                <div class="info-row">
                    <span class="label">Date</span>
                    <span class="value">
                        {{ optional($detail->date_encaissement)->format('d/m/Y') ?? '—' }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="label">Caisse</span>
                    <span class="value">{{ $caisse?->libelle ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Mode</span>
                    <span class="value">{{ $paiement->mode_paiement?->label() ?? '—' }}</span>
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- DÉTAILS ENCAISSÉS --}}
@php
    // On affiche tous les détails encaissés du paiement parent (le reçu
    // couvre l'ensemble du paiement, pas seulement le détail courant).
    $detailsAffiches = $paiement->details
        ->where('etat', 1)
        ->where('statut_paiement', \App\Domain\Finances\Types\PaiementStatut::ENCAISSE);
    $total = $detailsAffiches->sum('montant');
@endphp

<table class="details">
    <thead>
        <tr>
            <th style="width: 55%;">Désignation</th>
            <th class="center" style="width: 15%;">Nature</th>
            <th class="right" style="width: 30%;">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($detailsAffiches as $d)
            <tr>
                <td>
                    {{ $d->libelle }}
                    @if($d->quantite && $d->prix_unitaire)
                        <span class="sub">
                            {{ number_format((float) $d->quantite, 2, ',', ' ') }} ×
                            {{ number_format((float) $d->prix_unitaire, 2, ',', ' ') }}
                        </span>
                    @endif
                </td>
                <td class="center">{{ $d->type_paiement?->label() ?? '—' }}</td>
                <td class="right">{{ number_format((float) $d->montant, 2, ',', ' ') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- TOTAL --}}
<div class="total-wrapper">
    <table class="total-table">
        <tr>
            <td class="label">Sous-total</td>
            <td class="value">{{ number_format((float) $total, 2, ',', ' ') }}</td>
        </tr>
        <tr class="final">
            <td class="label">TOTAL ENCAISSÉ</td>
            <td class="value">{{ number_format((float) $total, 2, ',', ' ') }} F</td>
        </tr>
        @if($paiement->montant_recu !== null)
            <tr>
                <td class="label">Montant reçu</td>
                <td class="value">{{ number_format((float) $paiement->montant_recu, 2, ',', ' ') }}</td>
            </tr>
            @if($paiement->montant_recu > $total)
                <tr>
                    <td class="label">Monnaie rendue</td>
                    <td class="value">{{ number_format((float) $paiement->montant_recu - $total, 2, ',', ' ') }}</td>
                </tr>
            @endif
        @endif
    </table>
</div>

{{-- SIGNATURES --}}
<table class="signatures">
    <tr>
        <td>
            <div class="line"></div>
            Le payeur
        </td>
        <td>
            <div class="line"></div>
            Le caissier
            @if($detail->caissier)
                <br><span style="font-size: 8px;">{{ $detail->caissier->name }}</span>
            @endif
        </td>
    </tr>
</table>

{{-- PIED --}}
<div class="footer">
    Ce reçu est un document officiel de l'École Internationale Mariam. À conserver précieusement.<br>
    Toute reproduction ou falsification est strictement interdite.
</div>

</body>
</html>