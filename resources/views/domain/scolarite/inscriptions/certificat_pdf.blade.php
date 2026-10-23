<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat de scolarité — {{ $eleve->matricule }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; margin: 40px 50px; }
        .entete { text-align: center; border-bottom: 2px solid #D4A94D; padding-bottom: 10px; margin-bottom: 40px; }
        .entete h1 { color: #8E1216; margin: 0 0 4px; font-size: 22px; }
        .entete p { margin: 0; color: #666; font-size: 11px; }
        h2 { text-align: center; letter-spacing: 2px; margin: 0 0 6px; font-size: 20px; }
        .annee { text-align: center; color: #666; margin-bottom: 40px; }
        .corps { line-height: 2; text-align: justify; }
        .corps strong { color: #000; }
        table { width: 100%; border-collapse: collapse; margin: 24px 0; }
        td { padding: 6px 8px; border: 1px solid #ddd; }
        td.label { width: 35%; background: #f5f5f5; color: #555; }
        .mention { margin-top: 24px; }
        .signature { margin-top: 60px; text-align: right; }
        .signature .lieu { margin-bottom: 70px; }
        .pied { position: fixed; bottom: 20px; left: 50px; right: 50px; text-align: center; font-size: 10px; color: #888; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    <div class="entete">
        <div style="text-align: center; margin-bottom: 4px;">@include('admin.layouts.partials._pdf-sceau', ['taille' => '20mm'])</div>
        <h1>École Internationale Mariam</h1>
        <p>Établissement d'enseignement</p>
    </div>

    <h2>CERTIFICAT DE SCOLARITÉ</h2>
    <p class="annee">Année scolaire {{ $annee?->libelle ?? '—' }}</p>

    <p class="corps">
        La Direction de l'École Internationale Mariam certifie que l'élève
        <strong>{{ $eleve->nom }} {{ $eleve->prenom }}</strong>,
        @if ($eleve->date_naissance)
            né{{ $eleve->sexe?->value === 2 ? 'e' : '' }} le <strong>{{ $eleve->date_naissance->format('d/m/Y') }}</strong>@if ($eleve->lieu_naissance) à <strong>{{ $eleve->lieu_naissance }}</strong>@endif,
        @endif
        est régulièrement inscrit{{ $eleve->sexe?->value === 2 ? 'e' : '' }} dans notre établissement
        pour l'année scolaire <strong>{{ $annee?->libelle ?? '—' }}</strong>.
    </p>

    <table>
        <tr><td class="label">Matricule</td><td>{{ $eleve->matricule ?? '—' }}</td></tr>
        <tr><td class="label">Cycle</td><td>{{ $inscription->cycle?->libelle ?? '—' }}</td></tr>
        <tr><td class="label">Niveau</td><td>{{ $inscription->niveau?->libelle ?? '—' }}</td></tr>
        <tr><td class="label">Classe</td><td>{{ $inscription->classe?->libelle ?? 'Non affecté' }}</td></tr>
        <tr><td class="label">Date d'inscription</td><td>{{ $inscription->date_inscription?->format('d/m/Y') ?? '—' }}</td></tr>
    </table>

    <p class="mention">
        Le présent certificat est délivré à l'intéressé{{ $eleve->sexe?->value === 2 ? 'e' : '' }}
        pour servir et valoir ce que de droit.
    </p>

    <div class="signature">
        <div class="lieu">Fait le {{ now()->format('d/m/Y') }}</div>
        <div>La Direction</div>
    </div>

    <div class="pied">
        Certificat n° {{ $inscription->id }}/{{ now()->format('Y') }} — édité le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
