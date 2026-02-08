@component('mail::message')
# Bienvenue sur TargetDesk CRM

Bonjour **{{ $user->name }}**,

Votre compte TargetDesk CRM a été créé avec succès ! Nous sommes ravis de vous accueillir dans notre plateforme de gestion de la relation client.

## Vos informations de connexion

@component('mail::panel')
**Email :** {{ $user->email }}
**Mot de passe temporaire :** {{ $temporaryPassword }}
**Rôle :** {{ $user->roles->first()->name ?? 'Utilisateur' }}
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url') . '/login', 'color' => 'success'])
Se connecter à TargetDesk
@endcomponent

## Sécurité importante

Pour votre sécurité, veuillez :
- **Changer votre mot de passe** lors de votre première connexion
- **Activer l'authentification à deux facteurs** si disponible
- **Ne jamais partager vos identifiants** avec qui que ce soit

## Besoin d'aide ?

Notre équipe support est à votre disposition pour vous accompagner dans la prise en main de la plateforme.

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Si vous n'avez pas demandé la création de ce compte, veuillez contacter immédiatement notre service support à support@targetdesk.fr
@endcomponent
@endcomponent