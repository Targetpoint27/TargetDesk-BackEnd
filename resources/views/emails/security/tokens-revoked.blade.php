@component('mail::message')
# Sessions fermées pour sécurité

Bonjour **{{ $user->name }}**,

Toutes vos sessions actives sur TargetDesk CRM ont été fermées pour des raisons de sécurité.

## Détails de l'action

@component('mail::panel')
**Compte :** {{ $user->email }}
**Date :** {{ now()->format('d/m/Y à H:i') }}
**Sessions fermées :** {{ $sessionCount ?? 'Toutes' }}
**Motif :** {{ $reason ?? 'Mesure de sécurité préventive' }}
**Déclenchée par :** {{ $triggeredBy ?? 'Système automatique' }}
@endcomponent

## Ce qui s'est passé

- Toutes vos sessions actives ont été fermées immédiatement
- Vos tokens d'accès ont été révoqués
- Vous avez été déconnecté de tous vos appareils
- Votre compte reste actif et sécurisé

## Pour vous reconnecter

@component('mail::button', ['url' => config('app.frontend_url') . '/login', 'color' => 'primary'])
Se connecter à nouveau
@endcomponent

## Mesures de sécurité

Avant de vous reconnecter, nous recommandons de :
- **Vérifier que votre appareil est sécurisé**
- **Scanner votre système** contre les logiciels malveillants
- **Changer votre mot de passe** si vous suspectez une compromission
- **Examiner l'activité récente** de votre compte

## Si vous n'êtes pas à l'origine

Si vous n'avez pas demandé cette action, contactez immédiatement notre équipe support.

---

**L'équipe TargetDesk**
Email: support@targetdesk.fr
Site: [targetdesk.fr](https://targetdesk.fr)

@component('mail::subcopy')
Cette action a été prise pour protéger votre compte. En cas de questions, contactez notre support à support@targetdesk.fr
@endcomponent
@endcomponent