@component('mail::message')
# Bienvenue sur TargetDesk CRM

Bonjour **{{ $user->name }}**,

**C'est parti ! Votre compte TargetDesk est prêt.** 🚀
Nous sommes enchantés de vous compter parmi nous. Toute l'équipe se réjouit de vous aider à transformer vos relations clients en véritables succès.

## Vos informations de connexion

@component('mail::panel')
**Email :** {{ $user->email }}
**Mot de passe :** {{ $password }}
**Rôle :** {{ $userRole }}
@endcomponent

@component('mail::button', ['url' => $loginUrl, 'color' => 'primary'])
Commencer maintenant
@endcomponent

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