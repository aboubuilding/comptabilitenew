<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche élève — {{ $eleve->matricule }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1, h2, h3 { margin: 0 0 8px; }
        .entete { text-align: center; border-bottom: 2px solid #8B0000; padding-bottom: 8px; margin-bottom: 16px; }
        .entete h1 { color: #8B0000; }
        .section { margin-bottom: 16px; }
        .section h3 { background: #f5f5f5; padding: 6px; border-left: 3px solid #8B0000; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 4px 6px; vertical-align: top; }
        .label { color: #666; width: 30%; }
        .photo { float: right; width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <div class="entete">
        <h1>École Internationale Mariam</h1>
        <p>Fiche élève — {{ $eleve->matricule }}</p>
    </div>

    @if($eleve->photo)
        <img src="{{ public_path('storage/' . $eleve->photo) }}" class="photo" alt="">
    @endif

    <div class="section">
        <h3>État civil</h3>
        <table>
            <tr><td class="label">Nom</td><td>{{ $eleve->nom }}</td></tr>
            <tr><td class="label">Prénom</td><td>{{ $eleve->prenom }}</td></tr>
            <tr><td class="label">Prénom usuel</td><td>{{ $eleve->prenom_usuel ?? '—' }}</td></tr>
            <tr><td class="label">Sexe</td><td>{{ $eleve->sexe?->libelle() ?? '—' }}</td></tr>
            <tr><td class="label">Date de naissance</td><td>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</td></tr>
            <tr><td class="label">Lieu de naissance</td><td>{{ $eleve->lieu_naissance ?? '—' }}</td></tr>
            <tr><td class="label">Nationalité</td><td>{{ $eleve->nationalite->libelle ?? '—' }}</td></tr>
            <tr><td class="label">École de provenance</td><td>{{ $eleve->ecole_provenance ?? '—' }}</td></tr>
        </table>
    </div>

    @if($eleve->inscriptionActive)
        <div class="section">
            <h3>Scolarité (année active)</h3>
            <table>
                <tr><td class="label">Classe</td><td>{{ $eleve->inscriptionActive->classe->libelle ?? 'Non affecté' }}</td></tr>
                <tr><td class="label">Niveau</td><td>{{ $eleve->inscriptionActive->niveau->libelle ?? '—' }}</td></tr>
                <tr><td class="label">Cycle</td><td>{{ $eleve->inscriptionActive->cycle->libelle ?? '—' }}</td></tr>
            </table>
        </div>
    @endif

    <div class="section">
        <h3>Personne à prévenir</h3>
        <table>
            <tr><td class="label">Nom</td><td>{{ $eleve->personne_prevenir ?? '—' }}</td></tr>
            <tr><td class="label">Téléphone</td><td>{{ $eleve->numero_personne_prevenir ?? '—' }}</td></tr>
            <tr><td class="label">Lien</td>
                <td>
                    {{ $eleve->lien_parente_personne
                        ? (\App\Domain\Scolarite\Types\LienParente::tryFrom($eleve->lien_parente_personne)?->libelle() ?? '—')
                        : '—' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Informations médicales</h3>
        <table>
            <tr><td class="label">Médecin</td><td>{{ $eleve->nom_medecin ?? '—' }} {{ $eleve->numero_medecin ? '('.$eleve->numero_medecin.')' : '' }}</td></tr>
            <tr><td class="label">Allergies</td><td>{{ $eleve->allergie ?? '—' }}</td></tr>
        </table>
    </div>
</body>
</html>