@component('mail::message')
# Bienvenue sur TargetDesk CRM

Bonjour **{{ $user->name }}**,

<div style="text-align: center;">
**C'est parti ! Votre compte TargetDesk est prêt.** 🚀
</div>

Nous sommes enchantés de vous compter parmi nous. Toute l'équipe se réjouit de vous aider à transformer vos relations clients en véritables succès.

## Vos informations de connexion

@component('mail::panel')
**Email :** {{ $user->email }}
**Mot de passe :** {{ $password }}
**Rôle :** {{ $userRole }}
@endcomponent

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $loginUrl }}" style="
        background-color: #7F51FF;
        color: white;
        text-decoration: none;
        padding: 14px 30px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 16px;
        display: inline-block;
        text-align: center;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(127, 81, 255, 0.3);
    ">Commencer maintenant</a>
</div>

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