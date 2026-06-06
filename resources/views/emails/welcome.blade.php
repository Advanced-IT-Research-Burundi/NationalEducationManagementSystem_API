<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bienvenue sur NEMS</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8fafc; padding: 20px; text-align: center; border-bottom: 2px solid #e2e8f0; }
        .content { padding: 20px 0; }
        .footer { text-align: center; font-size: 12px; color: #64748b; margin-top: 20px; }
        .button { display: inline-block; padding: 10px 20px; background-color: #3b82f6; color: #fff; text-decoration: none; border-radius: 5px; }
        .credentials { background-color: #f1f5f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Bienvenue sur NEMS</h2>
        </div>
        
        <div class="content">
            <p>Bonjour {{ $user->name }},</p>
            
            <p>Votre compte sur le Système  de Gestion de l'Éducation (NEMS) a été créé avec succès.</p>
            
            @if($password)
            <div class="credentials">
                <p><strong>Vos identifiants de connexion :</strong></p>
                <p>Email : {{ $user->email }}</p>
                <p>Mot de passe temporaire : <strong>{{ $password }}</strong></p>
            </div>
            
            <p><em>Remarque : Vous serez invité(e) à modifier ce mot de passe temporaire lors de votre première connexion.</em></p>
            @endif
            
            <p>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:5173') }}/login" class="button">Se connecter</a>
            </p>
            
            <p>Si vous n'êtes pas à l'origine de cette demande, veuillez ignorer cet email.</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} NEMS - Ministère de l'Éducation . Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
