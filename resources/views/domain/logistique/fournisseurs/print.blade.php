{{-- resources/views/domain/logistique/fournisseurs/print.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fournisseurs — École Internationale Mariam</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #2B1E1E; margin: 0; }

        .header { border-bottom: 2px solid #D4A94D; padding-bottom: 8px; margin-bottom: 12px; }
        .header-table { width: 100%; }
        .header-brand { font-size: 15px; font-weight: bold; color: #E31E24; letter-spacing: .5px; }
        .header-sub { font-size: 8.5px; color: #5A4A4A; margin-top: 2px; }
        .header-meta { text-align: right; font-size: 8px; color: #5A4A4A; line-height: 1.5; }

        .doc-title { font-size: 13px; font-weight: bold; margin: 0 0 3px; }
        .doc-subtitle { font-size: 9px; color: #5A4A4A; margin-bottom: 10px; }

        table.journal { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        table.journal thead th {
            background: #2B1E1E; color: #ffffff;
            font-size: 7.5px; text-transform: uppercase; letter-spacing: .4px;
            padding: 6px; text-align: left; font-weight: normal;
        }
        table.journal thead th.right { text-align: right; }
        table.journal thead th.center { text-align: center; }
        table.journal tbody td {
            padding: 6px; border-bottom: 1px solid #EADFCB; vertical-align: top;
        }
        table.journal tbody tr:nth-child(even) { background: #fafaf7; }
        table.journal tbody td.right { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
        table.journal tbody td.center { text-align: center; }
        table.journal tbody td .sub { font-size: 7.5px; color: #5A4A4A; display: block; margin-top: 1px; }

        .footer {
            margin-top: 16px; padding-top: 6px; border-top: 1px solid #EADFCB;
            font-size: 7.5px; color: #5A4A4A; text-align: center;
        }
        .empty { text-align: center; padding: 30px 0; color: #7A6A6A; font-style: italic; }
    </style>
</head>
<body>

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 19mm; padding-right: 8px;">@include('admin.layouts.partials._pdf-sceau', ['taille' => '17mm'])</td>
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

<div class="doc-title">Catalogue des fournisseurs</div>
<div class="doc-subtitle">{{ $nbFournisseurs }} fournisseur(s) — document de référence</div>

@if($fournisseurs->isEmpty())
    <div class="empty">Aucun fournisseur enregistré.</div>
@else
    <table class="journal">
        <thead>
            <tr>
                <th style="width: 25%;">Raison sociale</th>
                <th style="width: 18%;">Contact</th>
                <th style="width: 18%;">Téléphone / Email</th>
                <th style="width: 20%;">Adresse</th>
                <th class="center" style="width: 8%;">Achats</th>
                <th class="right" style="width: 11%;">Montant total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fournisseurs as $f)
                <tr>
                    <td><strong>{{ $f->raison_social }}</strong></td>
                    <td>{{ $f->nom_contact ?? '—' }}</td>
                    <td>
                        @if($f->telephone_contact) {{ $f->telephone_contact }}<br> @endif
                        @if($f->email_contact) <span class="sub">{{ $f->email_contact }}</span> @endif
                    </td>
                    <td>{{ \Str::limit($f->adresse ?? '—', 60) }}</td>
                    <td class="center">{{ $f->nombre_achats }}</td>
                    <td class="right">{{ number_format($f->montant_total_achats, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="footer">
    École Internationale Mariam — Catalogue des fournisseurs — Document de référence interne.
</div>

</body>
</html>