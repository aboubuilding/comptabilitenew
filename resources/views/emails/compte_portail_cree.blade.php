<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Compte portail parent</title>
</head>
<body style="font-family: Arial, sans-serif; color:#222; line-height:1.5;">
    <div style="max-width:600px; margin:0 auto; padding:20px;">
        <h2 style="color:#8B0000;">Bienvenue {{ $parentNom }}</h2>

        <p>Un compte d'accès au <strong>portail parent</strong> a été créé pour votre famille
           « <strong>{{ $nomFamille }}</strong> ».</p>

        <p>Voici vos identifiants de connexion :</p>

        <table style="width:100%; border-collapse:collapse; margin:16px 0;">
            <tr>
                <td style="padding:8px; background:#f5f5f5; border:1px solid #ddd;"><strong>Email</strong></td>
                <td style="padding:8px; border:1px solid #ddd;">{{ $email }}</td>
            </tr>
            <tr>
                <td style="padding:8px; background:#f5f5f5; border:1px solid #ddd;"><strong>Mot de passe</strong></td>
                <td style="padding:8px; border:1px solid #ddd;"><code>{{ $motDePasse }}</code></td>
            </tr>
        </table>

        <p><strong>Important</strong> : nous vous recommandons de changer votre mot de passe
           dès votre première connexion.</p>

        <hr style="margin:24px 0; border:none; border-top:1px solid #eee;">
        <p style="font-size:12px; color:#888;">
            {{ $etablissement }} — Ce message vous a été adressé automatiquement.
        </p>
    </div>
</body>
</html>