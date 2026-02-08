@component('mail::message')
# Réactivation de votre compte TargetDesk

Bonjour **{{ $user->name }}**,

Excellente nouvelle ! Votre compte TargetDesk CRM a été réactivé avec succès.

## Informations de réactivation

@component('mail::panel')
**Compte :** {{ $user->email }}
**Date de réactivation :** {{ now()->format('d/m/Y à H:i') }}
**Statut :** Actif
**Rôle actuel :** {{ $user->roles->first()->name ?? 'Utilisateur' }}
@endcomponent

## Accès restauré

Vous pouvez maintenant :
- Vous connecter à votre compte TargetDesk
- Accéder à toutes vos données précédentes
- Reprendre votre travail là où vous l'aviez laissé

@component('mail::button', ['url' => config('app.frontend_url') . '/login', 'color' => 'success'])
Se connecter à TargetDesk
@endcomponent

## Recommandations de sécurité

Nous vous recommandons de :
- **Vérifier votre mot de passe** et le changer si nécessaire
- **Examiner vos paramètres de compte** pour vous assurer qu'ils sont corrects
- **Signaler toute activité suspecte** à notre équipe support

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Si vous n'avez pas demandé la réactivation de ce compte, veuillez contacter immédiatement notre service support à support@targetdesk.fr
@endcomponent
@endcomponent