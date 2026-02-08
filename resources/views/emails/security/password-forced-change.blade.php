@component('mail::message')
# Changement de mot de passe requis

Bonjour **{{ $user->name }}**,

Pour des raisons de sécurité, vous devez changer votre mot de passe TargetDesk CRM immédiatement.

## Motif du changement obligatoire

@component('mail::panel')
**Compte :** {{ $user->email }}
**Raison :** {{ $reason ?? 'Mesure de sécurité préventive' }}
**Date :** {{ now()->format('d/m/Y à H:i') }}
**Délai limite :** {{ $deadline ?? '24 heures' }}
@endcomponent

## Action immédiate requise

Votre compte sera automatiquement suspendu si vous ne changez pas votre mot de passe avant la date limite.

@component('mail::button', ['url' => config('app.frontend_url') . '/change-password?token=' . ($resetToken ?? ''), 'color' => 'error'])
Changer mon mot de passe maintenant
@endcomponent

## Critères pour le nouveau mot de passe

Votre nouveau mot de passe doit :
- Contenir au minimum **8 caractères**
- Inclure des **majuscules** et des **minuscules**
- Contenir au moins **un chiffre**
- Inclure au moins **un caractère spécial**
- Être différent de vos **5 derniers mots de passe**

## Sécurité renforcée

Suite à ce changement :
- Toutes vos sessions actives seront fermées
- Vous devrez vous reconnecter sur tous vos appareils
- Nous surveillerons votre compte pour détecter toute activité suspecte

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Si vous ne pouvez pas changer votre mot de passe, contactez immédiatement notre support à support@targetdesk.fr avant la date limite.
@endcomponent
@endcomponent