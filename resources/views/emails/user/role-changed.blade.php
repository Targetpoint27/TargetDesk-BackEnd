@component('mail::message')
# Modification de vos permissions

Bonjour **{{ $user->name }}**,

Vos permissions sur TargetDesk CRM ont été mises à jour par un administrateur.

## Détails de la modification

@component('mail::panel')
**Compte :** {{ $user->email }}
**Ancien rôle :** {{ $oldRole ?? 'Non défini' }}
**Nouveau rôle :** {{ $newRole }}
**Date de modification :** {{ now()->format('d/m/Y à H:i') }}
**Modifié par :** {{ $modifiedBy ?? 'Administrateur' }}
@endcomponent

## Nouvelles permissions

Votre nouveau rôle vous donne accès aux fonctionnalités suivantes :
{!! $permissions ?? 'Consultez votre profil pour voir vos nouvelles permissions.' !!}

@component('mail::button', ['url' => config('app.frontend_url') . '/profile', 'color' => 'primary'])
Voir mon profil
@endcomponent

## Important

- Ces modifications sont effectives immédiatement
- Vous devrez peut-être vous reconnecter pour voir les changements
- Certaines fonctionnalités peuvent maintenant être disponibles ou restreintes

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Si vous avez des questions sur vos nouvelles permissions, contactez votre administrateur ou notre équipe support.
@endcomponent
@endcomponent