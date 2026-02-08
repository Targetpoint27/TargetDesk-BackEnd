@component('mail::message')
# Suspension de votre compte TargetDesk

Bonjour **{{ $user->name }}**,

Nous vous informons que votre compte TargetDesk CRM a été temporairement désactivé par un administrateur.

## Informations sur la suspension

@component('mail::panel')
**Compte :** {{ $user->email }}
**Date de suspension :** {{ now()->format('d/m/Y à H:i') }}
**Statut :** Désactivé
**Raison :** {{ $reason ?? 'Non spécifiée' }}
@endcomponent

## Que se passe-t-il maintenant ?

- Votre accès à la plateforme TargetDesk est temporairement suspendu
- Vos données restent sécurisées et ne seront pas supprimées
- Toutes vos sessions actives ont été fermées automatiquement

## Pour réactiver votre compte

Veuillez contacter votre administrateur ou notre équipe support pour comprendre les raisons de cette suspension et les étapes nécessaires à la réactivation.

@component('mail::button', ['url' => 'mailto:support@targetdesk.fr?subject=Réactivation de compte - ' . $user->email, 'color' => 'primary'])
Contacter le support
@endcomponent

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Cette notification a été envoyée automatiquement suite à une action administrative. Si vous pensez qu'il s'agit d'une erreur, contactez immédiatement notre support.
@endcomponent
@endcomponent