<x-mail::message>
# Réinitialisation de votre mot de passe

Bonjour {{ $user->name }},

Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.

<x-mail::button :url="$url">
Réinitialiser le mot de passe
</x-mail::button>

Ce lien de réinitialisation de mot de passe expirera dans 60 minutes.

Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune autre action n'est requise.

Cordialement,<br>
L'équipe {{ config('app.name') }}
</x-mail::message>
