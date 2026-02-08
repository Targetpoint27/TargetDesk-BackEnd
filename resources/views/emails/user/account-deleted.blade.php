@component('mail::message')
# Suppression de votre compte TargetDesk

Bonjour **{{ $user->name }}**,

Nous vous confirmons que votre compte TargetDesk CRM a été supprimé définitivement.

## Informations de suppression

@component('mail::panel')
**Compte supprimé :** {{ $user->email }}
**Date de suppression :** {{ now()->format('d/m/Y à H:i') }}
**Supprimé par :** {{ $deletedBy ?? 'Administrateur' }}
**Raison :** {{ $reason ?? 'Non spécifiée' }}
@endcomponent

## Ce qui a été supprimé

- Votre compte utilisateur et vos informations personnelles
- Tous vos accès à la plateforme TargetDesk
- Vos préférences et paramètres personnalisés

## Données conservées

Conformément à nos politiques de rétention :
- Les données métier (clients, contrats, etc.) restent propriété de l'entreprise
- Les logs d'audit sont conservés pour la sécurité et la conformité
- Les données anonymisées peuvent être conservées à des fins statistiques

## Nouveau compte

Si vous souhaitez créer un nouveau compte à l'avenir, veuillez contacter votre administrateur ou notre équipe support.

@component('mail::button', ['url' => 'mailto:support@targetdesk.fr?subject=Création nouveau compte', 'color' => 'primary'])
Contacter le support
@endcomponent

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Cette suppression est définitive. Si vous pensez qu'il s'agit d'une erreur, contactez immédiatement votre administrateur.
@endcomponent
@endcomponent