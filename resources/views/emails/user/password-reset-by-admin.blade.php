@component('mail::message')
# Réinitialisation de votre mot de passe

Bonjour **{{ $user->name }}**,

Votre mot de passe TargetDesk CRM a été réinitialisé par un administrateur.

## Nouveau mot de passe temporaire

@component('mail::panel')
**Email :** {{ $user->email }}
**Nouveau mot de passe :** {{ $temporaryPassword }}
**Date de réinitialisation :** {{ now()->format('d/m/Y à H:i') }}
**Réinitialisé par :** {{ $resetBy ?? 'Administrateur' }}
@endcomponent

@component('mail::button', ['url' => config('app.frontend_url') . '/login', 'color' => 'success'])
Se connecter maintenant
@endcomponent

## Action requise immédiatement

Pour votre sécurité, vous devez :

1. **Vous connecter** avec ce mot de passe temporaire
2. **Changer immédiatement** votre mot de passe
3. **Choisir un mot de passe fort** (minimum 8 caractères, majuscules, minuscules, chiffres)

## Sécurité

- Ce mot de passe temporaire expire dans **24 heures**
- Votre ancienne session a été fermée automatiquement
- Ne partagez jamais vos identifiants avec qui que ce soit

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Si vous n'avez pas demandé cette réinitialisation, contactez immédiatement notre service support à support@targetdesk.fr
@endcomponent
@endcomponent