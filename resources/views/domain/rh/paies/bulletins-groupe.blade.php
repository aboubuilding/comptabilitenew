{{-- resources/views/domain/rh/paies/bulletins-groupe.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins de paie — {{ $periode->libelle }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2d3a; margin: 0; }

        /* ============ PAGE DE GARDE ============ */
        .cover { page-break-after: always; }
        .cover-header {
            border-bottom: 3px solid #C81E3A; padding-bottom: 8px; margin-bottom: 16px;
        }
        .cover-brand { font-size: 18px; font-weight: bold; color: #C81E3A; letter-spacing: .5px; }
        .cover-sub { font-size: 9px; color: #6f7e8c; margin-top: 3px; }
        .cover-title {
            font-size: 16px; font-weight: bold; color: #1f2d3a;
            margin: 24px 0 4px;
        }
        .cover-meta { font-size: 10px; color: #6f7e8c; margin-bottom: 20px; }

        /* Stats globales */
        .stats-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 16px;
        }
        .stats-grid td {
            background: #faf6ee; border: 1px solid #ece4d6; border-radius: 8px;
            padding: 12px 14px; width: 25%;
        }
        .stats-grid .label {
            font-size: 7.5px; text-transform: uppercase; color: #6f7e8c; letter-spacing: .5px;
        }
        .stats-grid .value {
            font-size: 13px; font-weight: bold; color: #1f2d3a; margin-top: 4px;
        }
        .stats-grid .value.green { color: #1c7a4d; }
        .stats-grid .value.gold  { color: #B8860B; }
        .stats-grid .value.red   { color: #C81E3A; }

        /* Ventilation modes */
        .modes-grid { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin-bottom: 16px; }
        .modes-grid td {
            border-radius: 8px; padding: 12px 14px; width: 50%; color: #fff;
        }
        .modes-grid .billette { background: #B8860B; }
        .modes-grid .banque   { background: #2563eb; }
        .modes-grid .label { font-size: 8px; opacity: .85; text-transform: uppercase; letter-spacing: .5px; }
        .modes-grid .value { font-size: 15px; font-weight: bold; margin-top: 4px; }
        .modes-grid .sub   { font-size: 8.5px; opacity: .9; margin-top: 4px; }

        /* Liste des employés (page de garde) */
        .cover-list-table {
            width: 100%; border-collapse: collapse; font-size: 8.5px;
        }
        .cover-list-table thead th {
            background: #1f2d3a; color: #fff; font-size: 7.5px;
            text-transform: uppercase; letter-spacing: .4px;
            padding: 6px 8px; text-align: left;
        }
        .cover-list-table thead th.right { text-align: right; }
        .cover-list-table tbody td {
            padding: 5px 8px; border-bottom: 1px solid #ece4d6;
        }
        .cover-list-table tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        .cover-list-table tbody tr:nth-child(even) { background: #fafaf7; }
        .cover-list-table tfoot td {
            padding: 10px 8px 0;
            font-weight: bold;
            border-top: 2px solid #C81E3A;
        }
        .cover-list-table tfoot td.right { text-align: right; font-variant-numeric: tabular-nums; }

        /* ============ BULLETINS (une page par employé) ============ */
        .bulletin-page { page-break-after: always; }
        .bulletin-page:last-child { page-break-after: auto; }

        .bulletin-header {
            border-bottom: 2px solid #C81E3A; padding-bottom: 6px; margin-bottom: 10px;
            width: 100%;
        }
        .bulletin-header-table { width: 100%; }
        .bulletin-header-table td { vertical-align: top; }
        .bulletin-header-table td.right { text-align: right; width: 40%; }
        .bulletin-brand { font-size: 11px; font-weight: bold; color: #C81E3A; }
        .bulletin-ref { font-size: 8px; color: #6f7e8c; margin-top: 2px; }

        .bulletin-title {
            text-align: center; font-size: 11px; font-weight: bold;
            color: #fff; background: #1f2d3a; padding: 6px 10px;
            border-radius: 4px; margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .bulletin-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .bulletin-info td {
            background: #faf6ee; border: 1px solid #ece4d6;
            padding: 6px 8px; font-size: 8.5px; vertical-align: top;
            width: 50%;
        }
        .bulletin-info .label { font-size: 7px; text-transform: uppercase; color: #6f7e8c; letter-spacing: .5px; display: block; }
        .bulletin-info .value { font-weight: 600; color: #1f2d3a; margin-top: 2px; display: block; }

        table.bulletin-detail {
            width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px;
        }
        table.bulletin-detail thead th {
            background: #1f2d3a; color: #fff; font-size: 7.5px;
            text-transform: uppercase; letter-spacing: .4px;
            padding: 5px 8px; text-align: left;
        }
        table.bulletin-detail thead th.right { text-align: right; }
        table.bulletin-detail tbody td {
            padding: 5px 8px; border-bottom: 1px solid #ece4d6;
        }
        table.bulletin-detail tbody td.right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        table.bulletin-detail tbody tr.section-row td {
            background: #faf6ee; font-weight: bold; color: #7A0F22;
            font-size: 8px; text-transform: uppercase; letter-spacing: .4px;
            padding: 6px 8px;
        }

        .total-table { width: 100%; margin-top: 8px; }
        .total-table td { padding: 4px 8px; font-size: 9px; }
        .total-table td.label { text-align: right; color: #6f7e8c; }
        .total-table td.value { text-align: right; font-weight: bold; width: 130px; font-variant-numeric: tabular-nums; }
        .total-table tr.final td {
            border-top: 2px solid #C81E3A; padding-top: 8px;
            font-size: 12px; color: #7A0F22;
        }

        .bulletin-mode {
            background: #f0f6ff; border-left: 3px solid #2563eb;
            border-radius: 4px; padding: 6px 10px; margin-top: 8px;
            font-size: 8px; color: #1e3a8a;
        }

        .bulletin-footer {
            margin-top: 20px; padding-top: 8px; border-top: 1px dashed #ece4d6;
            font-size: 7.5px; color: #6f7e8c; text-align: center;
        }

        /* Garde-fou : si aucun bulletin */
        .empty-cover {
            text-align: center; padding: 60px 20px;
            color: #9aa7b2; font-size: 11px;
        }
    </style>
</head>
<body>

{{-- ================================================================
     GUARD : aucune paie pour cette période
     ================================================================ --}}
@if($paies->isEmpty())
    <div class="empty-cover">
        <strong style="display:block;font-size:14px;color:#1f2d3a;margin-bottom:10px;">
            Aucun bulletin pour la période « {{ $periode->libelle }} »
        </strong>
        Générez les bulletins depuis l'écran Périodes avant de télécharger le récapitulatif.
    </div>
@else

{{-- ================================================================
     PAGE DE GARDE — Récapitulatif global
     ================================================================ --}}
<div class="cover">
    <div class="cover-header">
        <table style="width:100%">
            <tr>
                <td>
                    <div class="cover-brand">ÉCOLE INTERNATIONALE MARIAM</div>
                    <div class="cover-sub">Plateforme de gestion scolaire et budgétaire</div>
                </td>
                <td style="text-align:right;font-size:8px;color:#6f7e8c;line-height:1.6;">
                    <div><strong>Généré le</strong> {{ $genereLe->format('d/m/Y à H:i') }}</div>
                    @if($generePar) <div>par {{ $generePar->name }}</div> @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="cover-title">Récapitulatif de la paie — {{ $periode->libelle }}</div>
    <div class="cover-meta">
        Période du {{ optional($periode->date_debut)->format('d/m/Y') }}
        au {{ optional($periode->date_fin)->format('d/m/Y') }}
        · {{ $recap['nb'] }} bulletin(s)
    </div>

    {{-- Cartes stats — CORRIGÉES : total_deductions / total_retenues --}}
    <table class="stats-grid">
        <tr>
            <td>
                <div class="label">Masse brute</div>
                <div class="value gold">{{ number_format($recap['total_brut'], 0, ',', ' ') }}</div>
            </td>
            <td>
                <div class="label">Masse nette</div>
                <div class="value green">{{ number_format($recap['total_net'], 0, ',', ' ') }}</div>
            </td>
            <td>
                <div class="label">Déductions</div>
                <div class="value red">{{ number_format($recap['total_deductions'], 0, ',', ' ') }}</div>
            </td>
            <td>
                <div class="label">Retenues IRPP</div>
                <div class="value red">{{ number_format($recap['total_retenues'], 0, ',', ' ') }}</div>
            </td>
        </tr>
    </table>

    {{-- Ventilation modes — CORRIGÉE : `prevu` et non `montant` --}}
    <table class="modes-grid">
        <tr>
            <td class="billette">
                <div class="label">Billetterie (espèces)</div>
                <div class="value">{{ number_format($recap['billette']['prevu'], 0, ',', ' ') }} F</div>
                <div class="sub">
                    {{ $recap['billette']['nb'] }} employé(s) —
                    déjà payés : {{ number_format($recap['billette']['montant'], 0, ',', ' ') }} F
                </div>
            </td>
            <td class="banque">
                <div class="label">Banque (virement)</div>
                <div class="value">{{ number_format($recap['banque']['prevu'], 0, ',', ' ') }} F</div>
                <div class="sub">
                    {{ $recap['banque']['nb'] }} employé(s) —
                    déjà payés : {{ number_format($recap['banque']['montant'], 0, ',', ' ') }} F
                </div>
            </td>
        </tr>
    </table>

    {{-- Liste des employés --}}
    <table class="cover-list-table">
        <thead>
            <tr>
                <th style="width:35%">Employé</th>
                <th style="width:20%">Profession</th>
                <th class="right" style="width:15%">Brut</th>
                <th class="right" style="width:15%">Net à payer</th>
                <th style="width:15%">Mode</th>
            </tr>
        </thead>
        <tbody>
            @foreach($paies as $p)
                <tr>
                    <td><strong>{{ $p->employe?->nom_prenom ?? '—' }}</strong></td>
                    <td>{{ $p->employe?->profession?->libelle ?? '—' }}</td>
                    <td class="right">{{ number_format((float) $p->total_brut, 0, ',', ' ') }}</td>
                    <td class="right" style="font-weight:bold;">
                        {{ number_format((float) $p->net_a_payer, 0, ',', ' ') }}
                    </td>
                    <td>{{ $p->mode_paiement === 'banque' ? 'Banque' : 'Billetterie' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-transform:uppercase;">Totaux</td>
                <td class="right">{{ number_format($recap['total_brut'], 0, ',', ' ') }}</td>
                <td class="right" style="color:#7A0F22;">{{ number_format($recap['total_net'], 0, ',', ' ') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- ================================================================
     UN BULLETIN PAR PAGE
     ================================================================ --}}
@foreach($paies as $paie)
    <div class="bulletin-page">

        {{-- En-tête --}}
        <div class="bulletin-header">
            <table class="bulletin-header-table">
                <tr>
                    <td>
                        <div class="bulletin-brand">ÉCOLE INTERNATIONALE MARIAM</div>
                        <div style="font-size:8px;color:#6f7e8c;">Bulletin de paie</div>
                    </td>
                    <td class="right">
                        <div class="bulletin-ref">
                            <strong>{{ $paie->reference ?? ('#' . $paie->id) }}</strong><br>
                            {{ $periode->libelle }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="bulletin-title">BULLETIN DE PAIE</div>

        {{-- Identité --}}
        <table class="bulletin-info">
            <tr>
                <td>
                    <span class="label">Employé</span>
                    <span class="value">{{ $paie->employe?->nom_prenom ?? '—' }}</span>
                    <span style="font-size:8px;color:#6f7e8c;">
                        {{ $paie->employe?->profession?->libelle ?? '' }}
                    </span>
                </td>
                <td>
                    <span class="label">Période</span>
                    <span class="value">{{ $periode->libelle }}</span>
                    <span style="font-size:8px;color:#6f7e8c;">
                        du {{ optional($periode->date_debut)->format('d/m/Y') }}
                        au {{ optional($periode->date_fin)->format('d/m/Y') }}
                    </span>
                </td>
            </tr>
        </table>

        {{-- Détails --}}
        <table class="bulletin-detail">
            <thead>
                <tr>
                    <th style="width:65%">Élément</th>
                    <th class="right" style="width:35%">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr class="section-row"><td colspan="2">Rémunération</td></tr>
                <tr>
                    <td>Salaire de base</td>
                    <td class="right">{{ number_format((float) $paie->salaire_base, 2, ',', ' ') }}</td>
                </tr>
                @if($paie->prime_anciennete)
                    <tr><td>Prime d'ancienneté</td><td class="right">{{ number_format((float) $paie->prime_anciennete, 2, ',', ' ') }}</td></tr>
                @endif
                @if($paie->sursalaire)
                    <tr><td>Sursalaire</td><td class="right">{{ number_format((float) $paie->sursalaire, 2, ',', ' ') }}</td></tr>
                @endif
                @if($paie->bonus)
                    <tr><td>Bonus</td><td class="right">{{ number_format((float) $paie->bonus, 2, ',', ' ') }}</td></tr>
                @endif
                @if($paie->logement)
                    <tr><td>Indemnité de logement</td><td class="right">{{ number_format((float) $paie->logement, 2, ',', ' ') }}</td></tr>
                @endif
                @if($paie->nourriture)
                    <tr><td>Indemnité de nourriture</td><td class="right">{{ number_format((float) $paie->nourriture, 2, ',', ' ') }}</td></tr>
                @endif
                @if($paie->deplacement)
                    <tr><td>Indemnité de déplacement</td><td class="right">{{ number_format((float) $paie->deplacement, 2, ',', ' ') }}</td></tr>
                @endif
                @if($paie->autre_avantage)
                    <tr><td>Autres avantages</td><td class="right">{{ number_format((float) $paie->autre_avantage, 2, ',', ' ') }}</td></tr>
                @endif

                @if($paie->total_deductions > 0)
                    <tr class="section-row"><td colspan="2">Déductions</td></tr>
                    <tr>
                        <td>CNSS, charges familiales, forfait professionnel</td>
                        <td class="right" style="color:#C81E3A;">
                            − {{ number_format((float) $paie->total_deductions, 2, ',', ' ') }}
                        </td>
                    </tr>
                @endif

                @if($paie->total_retenues > 0)
                    <tr class="section-row"><td colspan="2">Retenues</td></tr>
                    <tr>
                        <td>
                            IRPP
                            @if($paie->employe?->irpp_mode === 'auto')
                                <span style="font-size:7.5px;color:#6f7e8c;"> (calculé par barème)</span>
                            @else
                                <span style="font-size:7.5px;color:#6f7e8c;"> (montant fixe)</span>
                            @endif
                        </td>
                        <td class="right" style="color:#C81E3A;">
                            − {{ number_format((float) $paie->total_retenues, 2, ',', ' ') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>

        {{-- Totaux --}}
        <table class="total-table">
            <tr>
                <td class="label">Total brut</td>
                <td class="value">{{ number_format((float) $paie->total_brut, 2, ',', ' ') }}</td>
            </tr>
            <tr>
                <td class="label">Déductions</td>
                <td class="value" style="color:#C81E3A;">
                    − {{ number_format((float) $paie->total_deductions, 2, ',', ' ') }}
                </td>
            </tr>
            <tr>
                <td class="label">Retenues</td>
                <td class="value" style="color:#C81E3A;">
                    − {{ number_format((float) $paie->total_retenues, 2, ',', ' ') }}
                </td>
            </tr>
            <tr class="final">
                <td class="label">NET À PAYER</td>
                <td class="value">{{ number_format((float) $paie->net_a_payer, 2, ',', ' ') }} F</td>
            </tr>
        </table>

        {{-- Mode paiement --}}
        <div class="bulletin-mode">
            <strong>Mode de paiement :</strong>
            {{ $paie->mode_paiement === 'banque' ? 'Virement bancaire' : 'Paiement à la billetterie (espèces)' }}
            @if($paie->mode_paiement === 'banque' && $paie->banque)
                — {{ $paie->banque }}
                @if($paie->iban) · IBAN : {{ $paie->iban }} @endif
            @endif
            @if($paie->statut?->value === 'paye')
                — Payé le {{ optional($paie->date_paiement)->format('d/m/Y') }}
            @endif
        </div>

        <div class="bulletin-footer">
            Bulletin confidentiel — École Internationale Mariam — Document généré automatiquement.
        </div>
    </div>
@endforeach

@endif {{-- fin du guard $paies->isEmpty() --}}

</body>
</html>