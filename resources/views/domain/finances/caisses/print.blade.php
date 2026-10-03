{{-- resources/views/domain/finances/caisses/print.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Journal de caisse — {{ $caisse->libelle }}</title>
    <style>
        @page { margin: 12mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9.5px;
            color: #1f2d3a;
            margin: 0;
        }

        /* ===== En-tête ===== */
        .header {
            border-bottom: 3px solid #C81E3A;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table { width: 100%; }
        .header-table td { vertical-align: middle; }

        .header-brand {
            font-size: 15px;
            font-weight: bold;
            color: #C81E3A;
            letter-spacing: .5px;
        }
        .header-sub {
            font-size: 8.5px;
            color: #6f7e8c;
            margin-top: 2px;
        }
        .header-meta {
            text-align: right;
            font-size: 8px;
            color: #6f7e8c;
            line-height: 1.5;
        }

        /* ===== Titre ===== */
        .doc-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f2d3a;
            margin: 0 0 3px;
        }
        .doc-subtitle {
            font-size: 9px;
            color: #6f7e8c;
            margin-bottom: 10px;
        }

        /* ===== Bandeau d'identité de la session ===== */
        .session-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .session-bar td {
            border: 1px solid #ece4d6;
            background: #faf6ee;
            padding: 6px 10px;
            font-size: 8.5px;
            vertical-align: top;
        }
        .session-bar .label {
            font-size: 7px;
            text-transform: uppercase;
            color: #6f7e8c;
            letter-spacing: .5px;
            display: block;
        }
        .session-bar .value {
            font-size: 10px;
            font-weight: bold;
            color: #1f2d3a;
            margin-top: 1px;
        }
        .session-bar .value.green { color: #1c7a4d; }
        .session-bar .value.red   { color: #C81E3A; }
        .session-bar .value.blue  { color: #1d4ed8; }

        /* ===== Badge statut de la caisse ===== */
        .badge-caisse {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-caisse.ouverte  { background: #e3f7ec; color: #1c7a4d; }
        .badge-caisse.cloturee { background: #eceff2; color: #5b6b7a; }

        /* ===== Cartes de synthèse ===== */
        .stats {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin-bottom: 10px;
        }
        .stats td {
            background: #ffffff;
            border: 1px solid #ece4d6;
            border-radius: 6px;
            padding: 7px 10px;
            width: 25%;
        }
        .stats .label {
            font-size: 7px;
            text-transform: uppercase;
            color: #6f7e8c;
            letter-spacing: .5px;
        }
        .stats .value {
            font-size: 11px;
            font-weight: bold;
            color: #1f2d3a;
            margin-top: 2px;
        }
        .stats .value.entrees   { color: #1c7a4d; }
        .stats .value.sorties   { color: #C81E3A; }
        .stats .value.theorique { color: #7A0F22; }

        /* ===== Bloc clôture (si la session est fermée) ===== */
        .cloture-block {
            background: #fafbfc;
            border: 1px solid #ece4d6;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .cloture-block .title {
            font-size: 9px;
            font-weight: bold;
            color: #1f2d3a;
            margin-bottom: 6px;
        }
        .cloture-block table { width: 100%; }
        .cloture-block td { padding: 2px 6px; font-size: 8.5px; vertical-align: top; }
        .cloture-block td .label { color: #6f7e8c; font-size: 7.5px; display: block; }
        .cloture-block td .value { font-weight: bold; color: #1f2d3a; }
        .cloture-block td .value.red   { color: #C81E3A; }
        .cloture-block td .value.green { color: #1c7a4d; }

        .motif-ecart {
            background: #fdf2f2;
            border-left: 3px solid #C81E3A;
            padding: 6px 9px;
            margin-top: 6px;
            font-size: 8.5px;
            color: #7A0F22;
        }

        /* ===== Tableau journal ===== */
        table.journal {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        table.journal thead th {
            background: #1f2d3a;
            color: #ffffff;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 6px 6px;
            text-align: left;
            font-weight: normal;
        }
        table.journal thead th.right  { text-align: right; }
        table.journal thead th.center { text-align: center; }

        table.journal tbody td {
            padding: 5px 6px;
            border-bottom: 1px solid #ece4d6;
            vertical-align: top;
        }
        table.journal tbody tr:nth-child(even) { background: #fafaf7; }
        table.journal tbody td.right  { text-align: right; font-variant-numeric: tabular-nums; }
        table.journal tbody td.center { text-align: center; }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
            white-space: nowrap;
        }
        .badge-entree { background: #e3f7ec; color: #1c7a4d; }
        .badge-sortie { background: #fdecea; color: #C81E3A; }

        .montant { font-weight: bold; }
        .montant.entree { color: #1c7a4d; }
        .montant.sortie { color: #C81E3A; }
        .texte-petit { font-size: 7.5px; color: #6f7e8c; }

        /* ===== Pied de tableau (totaux) ===== */
        table.journal tfoot td {
            padding: 6px 6px;
            font-size: 9px;
            font-weight: bold;
            background: #faf6ee;
            border-top: 2px solid #C81E3A;
        }
        table.journal tfoot td.right { text-align: right; font-variant-numeric: tabular-nums; }
        table.journal tfoot .final-label { text-align: right; color: #7A0F22; text-transform: uppercase; font-size: 8.5px; }
        table.journal tfoot .final-value { text-align: right; color: #7A0F22; font-size: 11px; }

        /* ===== Pied de page ===== */
        .footer {
            margin-top: 16px;
            padding-top: 6px;
            border-top: 1px solid #ece4d6;
            font-size: 7.5px;
            color: #6f7e8c;
            text-align: center;
        }

        .empty {
            text-align: center;
            padding: 30px 0;
            color: #9aa7b2;
            font-style: italic;
        }
        .section-titre { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #C81E3A; letter-spacing: .5px; margin: 12px 0 5px; }
        table.totaux-type { margin-bottom: 6px; }
    </style>
</head>
<body>

@php
    /** @var \App\Domain\Finances\Models\Caisse $caisse */
    $isOuverte = $caisse->isOuverte();
@endphp

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
<div class="doc-title">Journal de caisse — {{ $caisse->libelle }}</div>
<div class="doc-subtitle">
    Session #{{ $caisse->id }} &nbsp;·&nbsp;
    <span class="badge-caisse {{ $isOuverte ? 'ouverte' : 'cloturee' }}">
        {{ $caisse->statut?->label() }}
    </span>
    &nbsp;·&nbsp;
    {{ $journal['mouvements']->count() }} mouvement(s)
</div>

{{-- ============ BANDEAU D'IDENTITÉ ============ --}}
<table class="session-bar">
    <tr>
        <td style="width: 33%;">
            <span class="label">Caissier responsable</span>
            <span class="value">
                {{ $caisse->caissier?->name ?? '—' }}
            </span>
        </td>
        <td style="width: 34%;">
            <span class="label">Ouverture</span>
            <span class="value">
                {{ optional($caisse->date_ouverture)->format('d/m/Y à H:i') ?? '—' }}
            </span>
        </td>
        <td style="width: 33%;">
            <span class="label">Clôture</span>
            <span class="value">
                {{ optional($caisse->date_cloture)->format('d/m/Y à H:i') ?? '— en cours —' }}
            </span>
        </td>
    </tr>
</table>

{{-- ============ CARTES DE SYNTHÈSE ============ --}}
<table class="stats">
    <tr>
        <td>
            <div class="label">Solde initial</div>
            <div class="value">{{ number_format((float) $caisse->solde_initial, 2, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Total entrées</div>
            <div class="value entrees">+{{ number_format($journal['totalEntrees'], 2, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Total sorties</div>
            <div class="value sorties">−{{ number_format($journal['totalSorties'], 2, ',', ' ') }}</div>
        </td>
        <td>
            <div class="label">Solde théorique</div>
            <div class="value theorique">{{ number_format($journal['soldeTheorique'], 2, ',', ' ') }}</div>
        </td>
    </tr>
</table>

{{-- ============ TOTAUX ENCAISSÉS PAR TYPE DE PAIEMENT ============ --}}
<div class="section-titre">Totaux des paiements encaissés par type de paiement</div>
@if($totauxParType->isEmpty() && $nbManuelles === 0)
    <div class="empty" style="margin-bottom: 10px;">Aucun paiement encaissé dans cette caisse.</div>
@else
    <table class="journal totaux-type">
        <thead>
            <tr>
                <th style="width: 55%;">Type de paiement</th>
                <th style="width: 20%;" class="center">Lignes</th>
                <th style="width: 25%;" class="right">Montant encaissé</th>
            </tr>
        </thead>
        <tbody>
            @foreach($totauxParType as $total)
                <tr>
                    <td><strong>{{ $total['libelle'] }}</strong></td>
                    <td class="center">{{ $total['nb'] }}</td>
                    <td class="right montant entree">{{ number_format($total['montant'], 2, ',', ' ') }}</td>
                </tr>
            @endforeach
            @if($nbManuelles > 0)
                <tr>
                    <td><strong>Entrées manuelles</strong></td>
                    <td class="center">{{ $nbManuelles }}</td>
                    <td class="right montant entree">{{ number_format($totalManuel, 2, ',', ' ') }}</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="final-label">TOTAL ENCAISSÉ</td>
                <td class="final-value">{{ number_format($totalEncaisse + $totalManuel, 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
@endif

{{-- ============ BLOC CLÔTURE (si session fermée) ============ --}}
@if(!$isOuverte)
    <div class="cloture-block">
        <div class="title">Informations de clôture</div>
        <table>
            <tr>
                <td style="width: 22%;">
                    <span class="label">Solde compté</span>
                    <span class="value">{{ number_format((float) $caisse->solde_compte, 2, ',', ' ') }}</span>
                </td>
                <td style="width: 22%;">
                    <span class="label">Écart</span>
                    <span class="value {{ abs((float) $caisse->ecart) > 0.01 ? 'red' : 'green' }}">
                        {{ number_format((float) $caisse->ecart, 2, ',', ' ') }}
                    </span>
                </td>
                <td style="width: 28%;">
                    <span class="label">Clôturée le</span>
                    <span class="value">
                        {{ optional($caisse->date_cloture)->format('d/m/Y à H:i') }}
                    </span>
                </td>
                <td style="width: 28%;">
                    <span class="label">Validée par</span>
                    <span class="value">{{ $caisse->valideur?->name ?? '— en attente —' }}</span>
                </td>
            </tr>
        </table>

        @if($caisse->motif_ecart)
            <div class="motif-ecart">
                <strong>Motif de l'écart :</strong> {{ $caisse->motif_ecart }}
            </div>
        @endif
    </div>
@endif

{{-- ============ TABLEAU DU JOURNAL ============ --}}
<div class="section-titre">Journal chronologique des mouvements</div>
@if($journal['mouvements']->isEmpty())
    <div class="empty">Aucun mouvement enregistré dans cette session.</div>
@else
    <table class="journal">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 30%;">Libellé</th>
                <th style="width: 13%;" class="center">Type</th>
                <th style="width: 15%;">Bénéficiaire</th>
                <th style="width: 10%;" class="right">Entrée</th>
                <th style="width: 10%;" class="right">Sortie</th>
                <th style="width: 12%;">Utilisateur</th>
            </tr>
        </thead>
        <tbody>
            @foreach($journal['mouvements'] as $m)
                @php $type = $m->type_mouvement; @endphp
                <tr>
                    <td>{{ optional($m->date_mouvement)->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $m->libelle }}</strong>
                        @if($m->motif)
                            <div class="texte-petit">{{ \Str::limit($m->motif, 70) }}</div>
                        @endif
                    </td>
                    <td class="center">
                        <span class="badge badge-{{ $type?->isEntree() ? 'entree' : 'sortie' }}">
                            {{ $type?->label() ?? '—' }}
                        </span>
                    </td>
                    <td class="texte-petit">{{ $m->beneficiaire ?? '—' }}</td>
                    <td class="right montant entree">
                        {{ $type?->isEntree() ? number_format((float) $m->montant, 2, ',', ' ') : '' }}
                    </td>
                    <td class="right montant sortie">
                        {{ $type && ! $type->isEntree() ? number_format((float) $m->montant, 2, ',', ' ') : '' }}
                    </td>
                    <td class="texte-petit">
                        {{ $m->utilisateur?->name ?? '—' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="final-label">Totaux</td>
                <td class="right" style="color: #1c7a4d;">
                    +{{ number_format($journal['totalEntrees'], 2, ',', ' ') }}
                </td>
                <td class="right" style="color: #C81E3A;">
                    −{{ number_format($journal['totalSorties'], 2, ',', ' ') }}
                </td>
                <td></td>
            </tr>
            <tr>
                <td colspan="6" class="final-label">SOLDE THÉORIQUE</td>
                <td class="final-value">{{ number_format($journal['soldeTheorique'], 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
@endif

{{-- ============ PIED DE PAGE ============ --}}
<div class="footer">
    École Internationale Mariam — Journal de caisse « {{ $caisse->libelle }} » —
    Document de contrôle interne, généré automatiquement.
</div>

</body>
</html>