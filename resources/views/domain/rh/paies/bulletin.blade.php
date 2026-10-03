{{-- resources/views/domain/rh/paies/bulletin.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de paie — {{ $paie->reference ?? $paie->id }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1f2d3a; margin: 0; }

        /* ============ EN-TÊTE ============ */
        .header {
            border-bottom: 3px solid #C81E3A;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table { width: 100%; }
        .header-table td { vertical-align: top; }
        .header-table td.meta { text-align: right; }
        .header-brand { font-size: 15px; font-weight: bold; color: #C81E3A; letter-spacing: .5px; }
        .header-sub { font-size: 8.5px; color: #6f7e8c; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 8px; color: #6f7e8c; line-height: 1.5; }

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
        }
        .doc-title .ref {
            font-family: monospace;
            font-size: 10px;
            color: #D4A94D;
            margin-top: 3px;
            letter-spacing: 1px;
        }

        /* ============ BLOC IDENTITÉ ============ */
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

        /* ============ TABLEAU ÉLÉMENTS ============ */
        table.bulletin {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.bulletin thead th {
            background: #1f2d3a;
            color: #ffffff;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 6px 8px;
            text-align: left;
            font-weight: normal;
        }
        table.bulletin thead th.right { text-align: right; }
        table.bulletin tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #ece4d6;
            font-size: 9.5px;
            vertical-align: top;
        }
        table.bulletin tbody td.right {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }
        table.bulletin tbody tr.section-row td {
            background: #faf6ee;
            font-weight: bold;
            color: #7A0F22;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding-top: 8px;
            padding-bottom: 8px;
        }

        /* ============ TOTAUX ============ */
        .total-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .total-table td { padding: 4px 8px; font-size: 10px; }
        .total-table td.label { text-align: right; color: #6f7e8c; }
        .total-table td.value {
            text-align: right;
            font-weight: 700;
            width: 130px;
            font-variant-numeric: tabular-nums;
        }
        .total-table tr.final td {
            border-top: 2px solid #C81E3A;
            padding-top: 8px;
            font-size: 14px;
            color: #7A0F22;
        }

        /* ============ MODE PAIEMENT ============ */
        .mode-block {
            background: #f0f6ff;
            border-left: 3px solid #2563eb;
            border-radius: 4px;
            padding: 8px 10px;
            margin-top: 10px;
            font-size: 9px;
            color: #1e3a8a;
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
    <table class="header-table">
        <tr>
            <td>
                <div class="header-brand">ÉCOLE INTERNATIONALE MARIAM</div>
                <div class="header-sub">Plateforme de gestion scolaire et budgétaire</div>
            </td>
            <td class="meta">
                <div class="header-meta">
                    <div><strong>Généré le</strong> {{ $genereLe->format('d/m/Y à H:i') }}</div>
                    @if($generePar)
                        <div>par {{ $generePar->name }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- TITRE --}}
<div class="doc-title">
    <h2>BULLETIN DE PAIE</h2>
    <div class="ref">{{ $paie->reference ?? ('#' . $paie->id) }}</div>
</div>

{{-- IDENTITÉ --}}
<table class="info-grid">
    <tr>
        <td>
            <div class="info-block">
                <h3>Employé</h3>
                <div class="info-row">
                    <span class="label">Nom &amp; prénom</span>
                    <span class="value">{{ $paie->employe?->nom_prenom ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Profession</span>
                    <span class="value">{{ $paie->employe?->profession?->libelle ?? '—' }}</span>
                </div>
                @if($paie->employe?->numero_cnss)
                    <div class="info-row">
                        <span class="label">N° CNSS</span>
                        <span class="value" style="font-family:monospace;">{{ $paie->employe->numero_cnss }}</span>
                    </div>
                @endif
            </div>
        </td>
        <td>
            <div class="info-block">
                <h3>Période</h3>
                <div class="info-row">
                    <span class="label">Libellé</span>
                    <span class="value">{{ $paie->periode?->libelle ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Du</span>
                    <span class="value">{{ optional($paie->periode?->date_debut)->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Au</span>
                    <span class="value">{{ optional($paie->periode?->date_fin)->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Statut</span>
                    <span class="value">{{ $paie->statut?->label() ?? '—' }}</span>
                </div>
            </div>
        </td>
    </tr>
</table>

{{-- ÉLÉMENTS DE RÉMUNÉRATION --}}
<table class="bulletin">
    <thead>
        <tr>
            <th style="width: 65%;">Élément</th>
            <th class="right" style="width: 35%;">Montant</th>
        </tr>
    </thead>
    <tbody>
        <tr class="section-row">
            <td colspan="2">Rémunération</td>
        </tr>
        <tr>
            <td>Salaire de base</td>
            <td class="right">{{ number_format((float) $paie->salaire_base, 2, ',', ' ') }}</td>
        </tr>
        @if($paie->prime_anciennete)
            <tr>
                <td>Prime d'ancienneté</td>
                <td class="right">{{ number_format((float) $paie->prime_anciennete, 2, ',', ' ') }}</td>
            </tr>
        @endif
        @if($paie->sursalaire)
            <tr>
                <td>Sursalaire</td>
                <td class="right">{{ number_format((float) $paie->sursalaire, 2, ',', ' ') }}</td>
            </tr>
        @endif
        @if($paie->bonus)
            <tr>
                <td>Bonus</td>
                <td class="right">{{ number_format((float) $paie->bonus, 2, ',', ' ') }}</td>
            </tr>
        @endif
        @if($paie->logement)
            <tr>
                <td>Indemnité de logement</td>
                <td class="right">{{ number_format((float) $paie->logement, 2, ',', ' ') }}</td>
            </tr>
        @endif
        @if($paie->nourriture)
            <tr>
                <td>Indemnité de nourriture</td>
                <td class="right">{{ number_format((float) $paie->nourriture, 2, ',', ' ') }}</td>
            </tr>
        @endif
        @if($paie->deplacement)
            <tr>
                <td>Indemnité de déplacement</td>
                <td class="right">{{ number_format((float) $paie->deplacement, 2, ',', ' ') }}</td>
            </tr>
        @endif
        @if($paie->autre_avantage)
            <tr>
                <td>Autres avantages</td>
                <td class="right">{{ number_format((float) $paie->autre_avantage, 2, ',', ' ') }}</td>
            </tr>
        @endif

        @if($paie->total_deductions > 0)
            <tr class="section-row">
                <td colspan="2">Déductions</td>
            </tr>
            <tr>
                <td>CNSS, charges familiales, forfait professionnel</td>
                <td class="right" style="color: #C81E3A;">
                    − {{ number_format((float) $paie->total_deductions, 2, ',', ' ') }}
                </td>
            </tr>
        @endif

        @if($paie->total_retenues > 0)
            <tr class="section-row">
                <td colspan="2">Retenues</td>
            </tr>
            <tr>
                <td>
                    IRPP
                    @if($paie->employe?->irpp_mode === 'auto')
                        <span style="font-size:8px;color:#6f7e8c;"> (calculé par barème)</span>
                    @elseif($paie->employe?->irpp_mode === 'manuel')
                        <span style="font-size:8px;color:#6f7e8c;"> (montant fixe)</span>
                    @endif
                </td>
                <td class="right" style="color: #C81E3A;">
                    − {{ number_format((float) $paie->total_retenues, 2, ',', ' ') }}
                </td>
            </tr>
        @endif
    </tbody>
</table>

{{-- ⚠️ SUPPRIMÉ : le second bloc @if($paie->total_retenues > 0) orphelin
     qui contenait <tr> et <td> hors table (cassait le PDF Dompdf) --}}

{{-- TOTAUX --}}
<table class="total-table">
    <tr>
        <td class="label">Total brut</td>
        <td class="value">{{ number_format((float) $paie->total_brut, 2, ',', ' ') }}</td>
    </tr>
    @if($paie->total_deductions > 0)
        <tr>
            <td class="label">Total déductions</td>
            <td class="value" style="color: #C81E3A;">
                − {{ number_format((float) $paie->total_deductions, 2, ',', ' ') }}
            </td>
        </tr>
    @endif
    @if($paie->total_retenues > 0)
        <tr>
            <td class="label">Total retenues</td>
            <td class="value" style="color: #C81E3A;">
                − {{ number_format((float) $paie->total_retenues, 2, ',', ' ') }}
            </td>
        </tr>
    @endif
    <tr class="final">
        <td class="label">NET À PAYER</td>
        <td class="value">{{ number_format((float) $paie->net_a_payer, 2, ',', ' ') }} F</td>
    </tr>
</table>

{{-- MODE DE PAIEMENT --}}
@if($paie->mode_paiement)
    <div class="mode-block">
        <strong>Mode de paiement :</strong>
        {{ $paie->mode_paiement === 'banque' ? 'Virement bancaire' : 'Paiement à la billetterie (espèces)' }}
        @if($paie->mode_paiement === 'banque' && $paie->banque)
            — {{ $paie->banque }}
            @if($paie->iban) · IBAN : {{ $paie->iban }} @endif
        @endif
        @if($paie->statut?->value === 'paye')
            — Payé le {{ optional($paie->date_paiement)->format('d/m/Y') }}
            @if($paie->payeur) par {{ $paie->payeur->name }} @endif
        @endif
    </div>
@endif

{{-- SIGNATURES --}}
<table class="signatures">
    <tr>
        <td>
            <div class="line"></div>
            L'employé
        </td>
        <td>
            <div class="line"></div>
            Le responsable RH / Paie
        </td>
    </tr>
</table>

{{-- PIED --}}
<div class="footer">
    Ce bulletin de paie est un document confidentiel. À conserver sans limitation de durée.<br>
    École Internationale Mariam — Document généré automatiquement.
</div>

</body>
</html>