<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $sujet }}</title>
</head>
<body style="font-family: Arial, sans-serif; color:#222; line-height:1.5;">
    <div style="max-width:600px; margin:0 auto; padding:20px;">
        <h2 style="color:#8E1216;">{{ $sujet }}</h2>
        <div>{!! nl2br(e($corps)) !!}</div>
        <hr style="margin:24px 0; border:none; border-top:1px solid #eee;">
        <p style="font-size:12px; color:#888;">
            {{ config('app.name', 'École Internationale Mariam') }} —
            Ce message vous a été adressé automatiquement, merci de ne pas y répondre directement.
        </p>
    </div>
</body>
</html>