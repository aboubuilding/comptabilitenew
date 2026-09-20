{{-- resources/views/domain/finances/banques/print.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal des chèques — {{ $banque->nom }}</title>
    <style>
        @page { margin: 15mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            color: #1f2d3a;
            margin: 0;
        }

        /* ===== En-tête ===== */
        .header {
            border-bottom: 3px solid #C81E3A;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }

        .header-brand {
            font-size: 16px;
            font-weight: bold;
            color: #C81E3A;
            letter-spacing: .5px;
        }
        .header-sub {
            font-size: 9px;
            color: #6f7e8c;
            margin-top: 2px;
        }
        .header-meta {
            text-align: right;
            font-size: 8.5px;
            color: #6f7e8c;
        }

        /* ===== Titre ===== */
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2d3a;
            margin: 0 0 4px;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #6f7e8c;
            margin-bottom: 12px;
        }

        /* ===== Cartes de synthèse ===== */
        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 12px;
        }
        .stats td {
            background: #faf6ee;
            border: 1px solid #ece4d6;
            border-radius: 6px;
            padding: 8px 10px;
            width: 25%;
        }
        .stats .label {
            font-size: 7.5px;
            text-transform: uppercase;
            color: #6f7e8c;
            letter-spacing: .5px;
        }
        .stats .value {
            font-size: 12px;
            font-weight: bold;
            color: #1f2d3a;
            margin-top: 2px;
        }
        .stats .value.green { color: #1c7a4d; }
        .stats .value.red   { color: #C81E3A; }
        .stats .value.blue  { color: #1d4ed8; }
        .stats .value.gold  { color: #B8860B; }

        /* ===== Tableau ===== */
        table.journal {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table.journal thead th {
            background: #1f2d3a;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 7px 8px;
            text-align: left;
            font-weight: normal;
        }
        table.journal thead th.right { text-align: right; }
        table.journal thead th.center { text-align: center; }

        table.journal tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #ece4d6;
            vertical-align: top;
        }
        table.journal tbody tr:nth-child(even) { background: #fafaf7; }
        table.journal tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        table.journal tbody td.center { text-align: center; }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-emis     { background: #dbeafe; color: #1d4ed8; }
        .badge-encaisse { background: #e3f7ec; color: #1c7a4d; }
        .badge-rejete   { background: #fdecea; color: #C81E3A; }

        .montant { font-weight: bold; }
        .texte-petit { font-size: 8px; color: #6f7e8c; }

        /* ===== Totaux ===== */
        .totaux {
            width: 100%;
            margin-top: 12px;
        }
        .totaux td { padding: 6px 8px; }
        .totaux .label { text-align: right; color: #6f7e8c; font-size: 9px; }
        .totaux .value {
            text-align: right;
            font-weight: bold;
            font-size: 11px;
            font-variant-numeric: tabular-nums;
            width: 130px;
        }
        .totaux tr.final td {
            border-top: 2px solid #C81E3A;
            padding-top: 10px;
            font-size: 12px;
            color: #7A0F22;
        }

        /* ===== Pied de page ===== */
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #ece4d6;
            font-size: 8px;
            color: #6f7e8c;
            text-align: center;
        }

        .empty {
            text-align: center;
            padding: 30px 0;
            color: #9aa7b2;
            font-style: italic;
        }
    </style>
</head>
<body>

{{-- ============ EN-TÊTE ============ --}}
<div class="header">
    <table class="header-table">
        <tr>
            <td>
                <div class="header-brand">ÉCOLE INTERNATIONALE MARIAM</div>
                <div class="header-sub">Plateforme de gestion scolaire et budgétaire</div>
            </td>
            <td class="header-meta">
                <div><strong>Document généré le</strong> {{ $genereLe->format('d/m/Y à H:i') }}</div>
                @if($generePar)
                    <div>par {{ $generePar->name }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ============ TITRE ============ --}}
<div class="doc-title">Journal des chèques — {{ $banque->nom }}</div>
<div class="doc-subtitle">
    Banque #{{ $banque->id }} &nbsp;·&nbsp;
    {{ $cheques->count() }} chèque(s) rattaché(s)
</div>

{{-- ============ CARTES DE SYNTHÈSE ============ --}}
<table class="stats">
    <tr>
        <td>
            <div class="label">En attente</div>
            <div class="value blue">{{ $compteurs['emis'] }}</div>
            <div class="texte-petit">{{ number_format($montants['emis'], 2, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Encaissés</div>
            <div class="value green">{{ $compteurs['encaisse'] }}</div>
            <div class="texte-petit">{{ number_format($montants['encaisse'], 2, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Rejetés</div>
            <div class="value red">{{ $compteurs['rejete'] }}</div>
            <div class="texte-petit">{{ number_format($montants['rejete'], 2, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Volume traité</div>
            <div class="value gold">
                {{ number_format($montants['emis'] + $montants['encaisse'], 2, ',', ' ') }}
            </div>
            <div class="texte-petit">hors rejets</div>
        </td>
    </tr>
</table>

{{-- ============ TABLEAU DU JOURNAL ============ --}}
@if($cheques->isEmpty())
    <div class="empty">Aucun chèque rattaché à cette banque.</div>
@else
    <table class="journal">
        <thead>
            <tr>
                <th style="width: 12%;">Numéro</th>
                <th style="width: 22%;">Émetteur</th>
                <th style="width: 12%;">Émission</th>
                <th style="width: 14%;" class="center">Statut</th>
                <th style="width: 15%;" class="right">Montant</th>
                <th style="width: 15%;">Rapprochement</th>
                <th style="width: 10%;">Motif rejet</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cheques as $cheque)
                @php $statut = $cheque->statut; @endphp
                <tr>
                    <td><strong>{{ $cheque->numero }}</strong></td>
                    <td>{{ $cheque->emetteur }}</td>
                    <td>{{ optional($cheque->date_emission)->format('d/m/Y') ?? '—' }}</td>
                    <td class="center">
                        <span class="badge badge-{{ $statut?->css() }}">
                            {{ $statut?->label() ?? '—' }}
                        </span>
                    </td>
                    <td class="right montant">
                        {{ number_format((float) $cheque->montant, 2, ',', ' ') }}
                    </td>
                    <td class="texte-petit">
                        @if($cheque->date_rapprochement)
                            {{ $cheque->date_rapprochement->format('d/m/Y') }}
                            @if($cheque->rapprocheur)
                                <br>par {{ $cheque->rapprocheur->name }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="texte-petit">
                        {{ $cheque->motif_rejet ? \Str::limit($cheque->motif_rejet, 30) : '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ============ TOTAUX ============ --}}
    <table class="totaux">
        <tr>
            <td class="label">Total encaissé</td>
            <td class="value" style="color: #1c7a4d;">
                {{ number_format($montants['encaisse'], 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td class="label">Total en attente</td>
            <td class="value" style="color: #1d4ed8;">
                {{ number_format($montants['emis'], 2, ',', ' ') }}
            </td>
        </tr>
        <tr>
            <td class="label">Total rejeté</td>
            <td class="value" style="color: #C81E3A;">
                {{ number_format($montants['rejete'], 2, ',', ' ') }}
            </td>
        </tr>
        <tr class="final">
            <td class="label">SOLDE NET (encaissé − rejeté)</td>
            <td class="value">
                {{ number_format($montants['encaisse'] - $montants['rejete'], 2, ',', ' ') }}
            </td>
        </tr>
    </table>
@endif

{{-- ============ PIED DE PAGE ============ --}}
<div class="footer">
    École Internationale Mariam — Journal des chèques de la banque « {{ $banque->nom }} » —
    Document de contrôle interne, généré automatiquement.
</div>

</body>
</html>